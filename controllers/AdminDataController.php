<?php namespace Braindump\Api\Controller\Admin;

require_once(__DIR__ . '/AdminBaseController.php');

require_once(__DIR__ . '/../lib/DatabaseFacade.php');
require_once(__DIR__ . '/../model/NotebookFacade.php');
require_once(__DIR__ . '/../model/NoteFacade.php');
require_once(__DIR__ . '/../model/UserFacade.php');
require_once(__DIR__ . '/../model/FileFacade.php');

use Cartalyst\Sentry\Users\Paris\User as User;

use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Model\Note as Note;
use Braindump\Api\Model\File as File;

use Bcn\Component\Json\Writer as Writer;
use Bcn\Component\Json\Reader as Reader;
use Bcn\Component\Json\Reader\Tokenizer as Tokenizer;

// Temporary solution: allow writer to obey JSON_PRETTY_PRINTER and JSON_NUMERIC_CHECK
class BraindumpWriter extends Writer {

    protected $options = 0;
    protected $streamEmpty = true;

    public function __construct($stream, $options = 0)
    {
        parent::__construct($stream, $options);
        $this->options = $options;
    }

    protected function streamWrite($value)
    {
        parent::streamWrite($value);
        $this->streamEmpty = false;
    }

    protected function key($key)
    {
        parent::key($key);
        if (($this->options & JSON_PRETTY_PRINT)) $this->streamWrite(' ');
    }


    public function scalar($value)
    {
        $this->streamWrite(json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | $this->options));
        return $this;
    }

    public function leave()
    {
        if (($this->options & JSON_PRETTY_PRINT) && !$this->streamEmpty && count($this->parents) > 0) {
            $this->streamWrite(PHP_EOL);
            $this->streamWrite(str_repeat("    ", count($this->parents) - 1));
        }

        return parent::leave();
    }

    protected function prefix($key)
    {
        switch ($this->context) {
            case self::CONTEXT_OBJECT_START:
                $this->streamWrite("{");

                if (($this->options & JSON_PRETTY_PRINT)) {
                    $this->streamWrite(PHP_EOL);
                    $this->streamWrite(str_repeat("    ", count($this->parents)));
                }

                $this->key($key);
                $this->context = self::CONTEXT_OBJECT;
                break;
            case self::CONTEXT_ARRAY_START:
                $this->streamWrite("[");

                if (($this->options & JSON_PRETTY_PRINT)) {
                    $this->streamWrite(PHP_EOL);
                    $this->streamWrite(str_repeat("    ", count($this->parents)));
                }

                $this->context = self::CONTEXT_ARRAY;
                break;
            case self::CONTEXT_OBJECT:
                $this->streamWrite(',');
                
                if (($this->options & JSON_PRETTY_PRINT)) {
                    $this->streamWrite(PHP_EOL);
                    $this->streamWrite(str_repeat("    ", count($this->parents)));
                }

                $this->key($key);
                break;
            case self::CONTEXT_ARRAY:
                $this->streamWrite(',');
                if (($this->options & JSON_PRETTY_PRINT)) {
                    $this->streamWrite(PHP_EOL);
                    $this->streamWrite(str_repeat("    ", count($this->parents)));
                }

                break;
        }  

              
    }
}

class BraindumpReader extends Reader {

    public function readToken($scalarOnly = true) {
        if ($scalarOnly && $this->token['token'] != Tokenizer::TOKEN_SCALAR) return false;
            
        $token = $this->token;
        $this->next();
        return $token;
    }

}

class AdminDataController extends \Braindump\Api\Controller\AdminBaseController {

    public function __construct(\Interop\Container\ContainerInterface $ci) {
        $this->fileFacade = new \Braindump\Api\Model\FileFacade();
        File::$config = $ci->get('settings')['braindump']['file_upload_config'];
        parent::__construct($ci);
    }

    public function getExport($request, $response) {

        // ooh boy this smells! slim uses PSR 7 Stream objects, while the JSON writer expects a stream resource
        // The body object has a stream propertye, but it is protected
        $reflectionClass = new \ReflectionClass('Slim\Http\Stream');
        $reflectionProperty = $reflectionClass->getProperty('stream');
        $reflectionProperty->setAccessible(true);
        $resource = $reflectionProperty->getValue($response->getBody());

        $writer = new BraindumpWriter($resource, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

        //$stream = $response->getBody();
        //$writer = new BraindumpWriter(fopen($response->getBody(), "w"), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);

        $writer->enter(Writer::TYPE_OBJECT);                // enter root object

        $groups = \ORM::for_table('groups')
                    ->select_many('name', 'permissions', 'created_at', 'updated_at')
                    ->find_array();

        $writer->write("groups", $groups);    // enter items array

        $users = \ORM::for_table('users')->find_result_set();
        
        $writer->enter("users", Writer::TYPE_ARRAY);

        foreach ($users as &$user) {

            $writer->enter(null, Writer::TYPE_OBJECT);

            $userValues = $user->as_array();

            // add user properties
            foreach($userValues as $key => $value) {
                if ($key != "id") $writer->write($key, $value);
            }
        
            // Add groups to user
            $sentryUser = \Sentry::findUserById($user['id']);

            // Get the user groups
            $sentryGroups = $sentryUser->getGroups();

            $writer->enter('groups', Writer::TYPE_ARRAY);

            foreach ($sentryGroups as $group) {
                $writer->write(null, $group->name);
            }

            $writer->leave(); // groups

            // Add notebooks to user
            $notebooks = Notebook::select_many('id', 'title', 'created', 'updated')
                ->where_equal('user_id', $user['id'])
                ->find_result_set();

            $writer->enter('notebooks', Writer::TYPE_ARRAY);

            foreach ($notebooks as &$notebook) {

                $writer->enter(null, Writer::TYPE_OBJECT);

                $notebookValues = $notebook->as_array();

                foreach($notebookValues as $key => $value) {
                   if ($key != "id") $writer->write($key, $value);
                }

                // Add notes to notebook
                $notes = Note::select_many('title', 'created', 'updated', 'url', 'type', 'content')
                    ->where_equal('notebook_id', $notebook['id'])
                    ->find_result_set();

                $writer->enter('notes', Writer::TYPE_ARRAY);

                foreach ($notes as &$note) {

                    $writer->enter(null, Writer::TYPE_OBJECT);

                    $noteValues = $note->as_array();

                    foreach($noteValues as $key => $value) {
                       if ($key != "id") $writer->write($key, $value);
                    }

                    $writer->leave(); // note
                }

                $writer->leave(); // notes
                
                $writer->leave(); // notebook
            }

            $writer->leave(); // notebooks

            // Add files to user
            $files = $this->fileFacade->getFilesForUserId($user['id']);

            if (is_array($files) && count($files) > 0) {

                $writer->enter('files', Writer::TYPE_ARRAY);

                foreach ($files as &$fileEntry)
                {
                    $writer->enter(null, Writer::TYPE_OBJECT);

                    $writer->write("logical_filename", $fileEntry['logical_filename']);
                    $writer->write("original_filename", $fileEntry['original_filename']);
                    
                    $file = File::create();
                    $file->physical_filename = $fileEntry['physical_filename'];

                    $writer->write("content", base64_encode($file->getContents()));

                    $writer->leave(); // file                
                }

                $writer->leave(); // files
            }

            $writer->leave(); // user
        }

        $writer->leave(); // users

        $writer->leave(); // global object

        return $response->withHeader('Content-Type', 'application/json;charset=utf-8')
                        ->withHeader('Content-Disposition', 'attachment; filename=export-' . $_SERVER["HTTP_HOST"] . '-' . date('Y-m-d His') . '.json');
    }

    public function postImport($request, $response) {

        $groups = 0;
        $users = 0;
        $notebooks = 0;
        $notes = 0;
        $files = 0;

        if ($request->getBody()->getSize() > 0) {

           $resource = $request->getBody()->detach();
        
        } elseif ($_FILES['importFile']['error'] == UPLOAD_ERR_OK               //checks for errors
                && is_uploaded_file($_FILES['importFile']['tmp_name'])) {
              
            $resource = fopen($_FILES['importFile']['tmp_name'], 'r');
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

            $reader = new BraindumpReader($resource);

            $reader->enter(null, Reader::TYPE_OBJECT); 

            $groups = $reader->read("groups");

            foreach ($groups as $group) {
                \Sentry::createGroup((array)$group);
            }

            $reader->enter("users", READER::TYPE_ARRAY);

            while($reader->enter(null, Reader::TYPE_OBJECT)) {

                $sentryUser = \Cartalyst\Sentry\Users\Paris\User::create();

                while($token = $reader->readToken()) {
                   $sentryUser->set($token['key'], $token['content']);
                }

                $sentryUser->save();
               
                // Bad hack: Password and activation code are already hashed
                //           Sentry will rehash them, revert this
                $userArray = $sentryUser->as_array();
                $userArray['id'] = $sentryUser->id;
                unset($userArray['permissions']);
                
                $sentryUser->hydratePlain($userArray);
                $sentryUser->save();

                $groupassignments = $reader->read("groups");

                foreach ($groupassignments as $groupName) {
                    $sentryUser->addGroup(\Sentry::findGroupByName($groupName));
                }

                $reader->enter("notebooks", Reader::TYPE_ARRAY);

                while($reader->enter(null, Reader::TYPE_OBJECT)) {

                    $notebookArray = [];

                    while($token = $reader->readToken()) {
                        $notebookArray[$token['key']] = $token['content'];
                    }

                    if (!Notebook::isValid((object)$notebookArray)) {
                        \ORM::get_db()->rollback();

                        $this->flash->addMessage('error', 'Invalid data');
                        return $response->withStatus(302)->withHeader('Location', '/admin');
                    }

                    $notebook = Notebook::create();
                    $notebook->map((object)$notebookArray, true);

                    $notebook->user_id = $sentryUser->id;
                    $notebook->save();
                    $notebooks++;

                    $reader->enter("notes", Reader::TYPE_ARRAY);

                    while($reader->enter(null, Reader::TYPE_OBJECT)) {

                        $noteArray = [];

                        while($token = $reader->readToken()) {
                            $noteArray[$token['key']] = $token['content'];
                        }

                        if (!Note::isValid((object)$noteArray)) {
                            \ORM::get_db()->rollback();
                            $this->flash->addMessage('error', 'Invalid data');
                            return $response->withStatus(302)->withHeader('Location', '/admin');
                        }

                        $note = Note::create();
                        $note->map($notebook, (object)$noteArray, true);
                        $note->user_id = $sentryUser->id;
                        $note->save(false);
                        $notes++;

                        $reader->leave(); // note

                    }

                    $reader->leave(); // notes

                    $reader->leave(); // notebook

                }

                $reader->leave(); // notebooks

                if ($reader->enter("files", Reader::TYPE_ARRAY)) {

                    while($reader->enter(null, Reader::TYPE_OBJECT)) {

                        $fileArray = [];

                        while($token = $reader->readToken()) {
                            $fileArray[$token['key']] = $token['content'];
                        }

                        // Store file (assume trusted)
                        $filename = uniqid();
                        $path = File::$config['upload_directory'] . $filename;
                        
                        if (!file_put_contents($path, base64_decode($fileArray['content']))) {
                            \ORM::get_db()->rollback();
                            $this->flash->addMessage('error', 'Invalid data');
                            return $response->withStatus(302)->withHeader('Location', '/admin');
                        }

                        unset($fileArray['content']);

                        $fileArray['physical_filename'] = $filename;
                        $fileArray['mime_type'] = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
                        $fileArray['hash'] = md5_file($path);
                        $fileArray['size'] = filesize($path);
                        
                        $fileObj = (object)$fileArray;

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

                        $reader->leave(); // file

                    }

                    $reader->leave(); // files

                }

                $reader->leave(); // user
            }

            /*\ORM::get_db()->beginTransaction();

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
            $groups = $reader->read("groups");
            print_r($groups);

            foreach ($groups as $group) {
                \Sentry::createGroup((array)$group);
            }

            $reader->enter("users", Reader::TYPE_ARRAY);

            // ...create users....
            while ($reader->enter()) {

                //print_r($reader);

                $sentryUser = \Cartalyst\Sentry\Users\Paris\User::create();
        print_r('///BEFORE USER///');


                // Read user attributes (Expected that flat properties are provided before nested arrays/object)
                while($token = $reader->readToken()) {
                    print_r($token);
                    $sentryUser->set($token['key'], $token['content']);
                    $reader->next();
                }
print_r('///AFTER USER///');



                //print_r('hallo');

                //print_r($user->as_array());

                $sentryUser->save();
               
                // Bad hack: Password and activation code are already hashed
                //           Sentry will rehash them, revert this
                //$userArray = $sentryUser->as_array();
                //$userArray['id'] = $sentryUser->id;
                //$sentryUser->hydratePlain($userArray);
                //$sentryUser->save();
               
                // ... assign groups to suers
                //if (property_exists($user, 'groups')) {
                $groups = $reader->read("groups");

                foreach ($groups as $groupName) {
                    $sentryUser->addGroup(\Sentry::findGroupByName($groupName));
                }

                $reader->enter("notebooks");

                // ...recreate notebooks and notes for each user
                while ($reader->enter()) {

                    $notebookArray = []; //new \stdClass(); //Notebook::create();

                    print_r('///BEFORE NOTEBOOK///');

                    while($token = $reader->readToken()) {
                        print_r($token);
                        $notebookArray[$token['key']] = $token['content'];
                        $reader->next();
                    }

                    print_r('///AFTER NOTEBOOK///');

        
                    if (!Notebook::isValid((object)$notebookArray)) {
                        \ORM::get_db()->rollback();

                        $this->flash->addMessage('error', 'Invalid data');
                        return $response->withStatus(302)->withHeader('Location', '/admin');
                    }

                    $notebook = Notebook::create();
                    $notebook->map((object)$notebookArray, true);

                    $notebook->user_id = $sentryUser->id;
                    $notebook->save();
                    $notebooks++;

                    $reader->enter("notes");

                    //foreach ($notebookRecord->notes as $noteRecord) {
                    while ($reader->enter()) {

                        print_r('note!!!');

                        $noteArray = [];//new \stdClass(); //Notebook::create();

                        while($token = $reader->readToken()) {
                            $noteArray[$token['key']] = $token['content'];
                            $reader->next();
                        }

                        print_r($noteArray);

                        if (!Note::isValid((object)$noteArray)) {
                            \ORM::get_db()->rollback();
                            $this->flash->addMessage('error', 'Invalid data');
                            return $response->withStatus(302)->withHeader('Location', '/admin');
                        }

                        $note = Note::create();
                        $note->map($notebook, (object)$noteArray, true);
                        $note->user_id = $sentryUser->id;
                        $note->save(false);
                        $notes++;

                        //$reader->leave(); // note
                    }

                    //$reader->leave(); // notes

                }

                $reader->leave(); // notebooks

            }

            $reader->leave(); // users


            // recreate files
            /*if (property_exists($user, 'files')) {
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
            }*/

            $reader->leave(); // object

            \ORM::get_db()->commit();
            
            $this->flash->addMessage('success', sprintf('%d notebook(s), %d note(s) and %d file(s) have been imported', $notebooks, $notes, $files));
            return $response->withStatus(302)->withHeader('Location', '/admin');

        } catch (\Exception $e) {
            print_r($e->getMessage());
            \ORM::get_db()->rollback();

            $this->flash->addMessage('error', $e->getMessage());
            print_r($e->getTraceAsString());
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
                'email'      => $ci->get('settings')['braindump']['initial_admin_user'],
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