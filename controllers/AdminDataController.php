<?php namespace Braindump\Api\Controller\Admin;

require_once(__DIR__ . '/BaseController.php');

require_once(__DIR__ . '/../lib/DatabaseFacade.php');
require_once(__DIR__ . '/../model/NotebookFacade.php');
require_once(__DIR__ . '/../model/NoteFacade.php');
require_once(__DIR__ . '/../model/UserFacade.php');
require_once(__DIR__ . '/../model/FileFacade.php');

use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Model\Note as Note;
use Cartalyst\Sentry\Users\Paris\User as User;
use Braindump\Api\Model\File as File;

class AdminDataController extends \Braindump\Api\Controller\BaseController {

    public function getExport($request, $response) {

        $groups = \ORM::for_table('groups')
            ->select_many('name', 'permissions', 'created_at', 'updated_at')
            ->find_array();

        $users = \ORM::for_table('users')->find_array();
        
        foreach ($users as &$user) {
            // Add groups to user
            $sentryUser = \Sentry::findUserById($user['id']);

            // Get the user groups
            $sentryGroups = $sentryUser->getGroups();

            foreach ($sentryGroups as $group) {
                $user['groups'][] = $group->name;
            }

            // Add notebooks to user
            $user['notebooks'] = Notebook::select_many('id', 'title', 'created', 'updated')
                ->where_equal('user_id', $user['id'])
                ->find_array();
            
            foreach ($user['notebooks'] as &$notebook) {
                // Add notes to notebook
                $notebook['notes'] = Note::select_many('title', 'created', 'updated', 'url', 'type', 'content')
                    ->where_equal('notebook_id', $notebook['id'])
                    ->find_array();

                unset($notebook['id']);
            }

            // Add files to user
            $files = File::select_many('logical_filename', 'physical_filename', 'original_filename')
                ->where_equal('user_id', $user['id'])
                ->find_array();

            if (is_array($files) && count($files) > 0) {
                $user['files'] = $files;
            
                // Add content of files to entries (todo: memory issues on larger sets)
                foreach ($user['files'] as &$fileEntry) {
                    //$file = new File();
                    $file = File::create();
                    $file->physical_filename = $fileEntry['physical_filename'];
                    unset($fileEntry['physical_filename']);
                    $fileEntry['content'] = base64_encode($file->getContents());
                }
            }
            
            unset($user['id']);

        }

        $response = outputJson(['groups' => $groups, 'users' => $users], $response);

        return $response->withHeader('Content-Disposition', 'attachment; filename=export-' . $_SERVER["HTTP_HOST"] . '-' . date('Y-m-d His') . '.json');
    }

    public function postImport($request, $response) {
        $groups = 0;
        $users = 0;
        $notebooks = 0;
        $notes = 0;
        $files = 0;

        //Check size and type of input

        // First check if JSON is posted as request body
        $input = $request->getBody();

        // Then check if a file upload has been made
        if (strlen($input) == 0) {
            if ($_FILES['importFile']['error'] == UPLOAD_ERR_OK               //checks for errors
                && is_uploaded_file($_FILES['importFile']['tmp_name'])) {
                //checks that file is uploaded
                $input = file_get_contents($_FILES['importFile']['tmp_name']);
            }
        }

        $data = json_decode($input);

        if (!is_object($data) || !is_array($data->groups) || !is_array($data->users)) {
            $this->flash->addMessage('error', 'No (valid) data found');
            return $response->withStatus(302)->withHeader('Location', '/admin');
        }

        // Process input...
        try {

            \ORM::get_db()->beginTransaction();

            // TODO: What about currently logged in users?
            // ...delete existing data...
            \ORM::for_table('note')->delete_many();
            \ORM::for_table('notebook')->delete_many();
            \ORM::for_table('throttle')->delete_many();
            \ORM::for_table('users_groups')->delete_many();
            \ORM::for_table('users')->delete_many();
            \ORM::for_table('groups')->delete_many();
            \ORM::for_table('file')->delete_many();

            // ...create groups...
            foreach ($data->groups as $group) {
                \Sentry::createGroup((array)$group);
            }

            // ...create users....
            foreach ($data->users as $user) {
                $userArray = (array)$user;

                unset($userArray['groups']);
                unset($userArray['notebooks']);
                unset($userArray['files']);

                $sentryUser = \Sentry::createUser($userArray);

                // Bad hack: Password and activation code are already hashed
                //           Sentry will rehash them, revert this
                $userArray['id'] = $sentryUser->id;
                $sentryUser->hydratePlain($userArray);
                $sentryUser->save();
               
                // ... assign groups to suers
                if (property_exists($user, 'groups')) {
                    foreach ($user->groups as $groupName) {
                        print_r($groupName);

                        $sentryUser->addGroup(\Sentry::findGroupByName($groupName));
                    }
                }

                // ...recreate notebooks and notes for each user
                foreach ($user->notebooks as $notebookRecord) {
                    if (!Notebook::isValid($notebookRecord)) {
                        \ORM::get_db()->rollback();

                        $this->flash->addMessage('error', 'Invalid data');
                        return $response->withStatus(302)->withHeader('Location', '/admin');
                    }

                    $notebook = Notebook::create();
                    $notebook->map($notebookRecord, true);

                    $notebook->user_id = $sentryUser->id;
                    $notebook->save();
                    $notebooks++;

                    foreach ($notebookRecord->notes as $noteRecord) {
                        if (!Note::isValid($noteRecord)) {
                            \ORM::get_db()->rollback();
                            $this->flash->addMessage('error', 'Invalid data');
                            return $response->withStatus(302)->withHeader('Location', '/admin');
                        }

                        $note = Note::create();
                        $note->map($notebook, $noteRecord, true);
                        $note->user_id = $sentryUser->id;
                        $note->save(false);
                        $notes++;
                    }

                }
            }

            // recreate files
            if (property_exists($user, 'files')) {
                foreach ($user->files as $fileRecord) {

                    // Store file (assume trusted)
                    $filename = uniqid();
                    $path = File::$config['upload_directory'] . $filename;
                    
                    if (!file_put_contents($path, base64_decode($fileRecord->content))) {
                        \ORM::get_db()->rollback();
                        $this->flash->addMessage('error', 'Invalid data');
                        return $response->withStatus(302)->withHeader('Location', '/admin');
                    }
                    
                    $fileObj = $input = (object)[
                        'logical_filename'  => $fileRecord->logical_filename,
                        'physical_filename' => $filename,
                        'original_filename'  => $fileRecord->original_filename,
                        'mime_type'         => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path),
                        'hash'              => md5_file($path),
                        'size'              => filesize($path),
                    ];

                    if (!File::isValid($fileObj)) {
                        \ORM::get_db()->rollback();
                        $this->flash->addMessage('error', 'Invalid data');
                        return $response->withStatus(302)->withHeader('Location', '/admin');
                    }

                    $file = File::create();
                    $file->map($fileObj);
                    $file->user_id = $sentryUser->id;
                    $file->save();
                    $files++;
                }
            }
            
            \ORM::get_db()->commit();
            
            $this->flash->addMessage('success', sprintf('%d notebook(s), %d note(s) and %d file(s) have been imported', $notebooks, $notes, $files));
            return $response->withStatus(302)->withHeader('Location', '/admin');

        } catch (\Exception $e) {
            \ORM::get_db()->rollback();

            $this->flash->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader('Location', '/admin');
        }
    }

    public function postSetup($request, $response, $args) {

        // Only perform setup if user has confirmed
        if ($request->getParsedBody()['confirm'] != 'YES') {
            $this->flash->addMessage('warning', 'Please confirm setup');
            return $response->withStatus(302)->withHeader('Location', '/admin');
        }

        try {
            \ORM::get_db()->beginTransaction();
            $this->dbFacade->createDatabase();

            // Create a default user
            $user = \Sentry::createUser([
                'email'      => 'administrator@braindump-api.local',
                'first_name' => 'Braindump',
                'last_name'  => 'Administrator',
                'password'   => 'welcome',
                'activated'  => true,
            ]);

            $user->addGroup(\Sentry::findGroupByName('Administrators'));

            \ORM::get_db()->commit();
            $this->flash->addMessage('success', 'Setup is executed');
            return $response->withStatus(302)->withHeader('Location', '/admin');
            return;
        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader('Location', '/admin');
        }
        
    }

    public function migrate($request, $response, $args) {

        try {
            \ORM::get_db()->beginTransaction();
            $this->dbFacade->migrateDatabase();
            \ORM::get_db()->commit();
        
            $this->flash->addMessage('success', sprintf('Migrated database schema to %s', $this->dbFacade->getCurrentVersion()));
        
        } catch (\Exception $e) {
        
            \ORM::get_db()->rollback();
            $this->flash->addMessage('error', $e->getMessage());
        
        }

        return $response->withStatus(302)->withHeader('Location', '/admin');
    }

}