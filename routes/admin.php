<?php
namespace Braindump\Api\Admin;

require_once(__DIR__ . '/../lib/DatabaseHelper.php');
require_once(__DIR__ . '/../model/NotebookHelper.php');
require_once(__DIR__ . '/../model/NoteHelper.php');
require_once(__DIR__ . '/../model/UserHelper.php');
$dbHelper = new \Braindump\Api\Lib\DatabaseHelper($app, \ORM::get_db());
$notebookHelper = new \Braindump\Api\Model\NotebookHelper($dbHelper);
$noteHelper = new \Braindump\Api\Model\NoteHelper($dbHelper);
$userHelper = new \Braindump\Api\Model\UserHelper($dbHelper);


$app->group('/admin', function () use ($app, $dbHelper, $notebookHelper, $noteHelper, $userHelper) {

    $app->get('/login', function () use ($app, $dbHelper, $userHelper) {
        $app->render('login.php');
    });

    $app->post('/login', function () use ($app) {

        print_r($app->request->post());

        try {
            \Sentry::authenticate($app->request->post(), true);
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
            $app->render('login.php');
            return;
        }
        
        $app->redirect('/admin');

    });

    $app->get('/logout', function () use ($app) {
        try {
            \Sentry::logout();
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->redirect('/admin');
    });
});

$app->group('/admin', 'Braindump\Api\Admin\Middleware\adminAuthenticate', function () use ($app, $dbHelper, $notebookHelper, $noteHelper, $userHelper) {

    $app->get('(/)', function () use ($app, $dbHelper, $userHelper) {

        $data = [
              'currentVersion'  => $dbHelper->getCurrentVersion(),
              'highestVersion'  => $dbHelper->getHighestVersion(),
              'migrationNeeded' => $dbHelper->isMigrationNeeded() ];

        try {
            $menuData = [
              'notebookCount'   => \ORM::for_table('notebook')->count(),
              'noteCount'       => \ORM::for_table('note')->count(),
              'userCount'       => \ORM::for_table('users')->count() ];
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->render('admin-template.php', [
            'menu'    => $app->view->fetch('admin-menu.php', $menuData),
            'content' => $app->view->fetch('admin-page.php', $data)
        ]);
    });

    $app->get('/export', function () use ($app) {

        $notebooks = \ORM::for_table('notebook')->find_array();

        foreach ($notebooks as &$notebook) {
            $notebook['notes'] = \ORM::for_table('note')
            ->select_many('id', 'title', 'created', 'updated', 'url', 'type', 'content', 'user_id')
            ->where_equal('notebook_id', $notebook['id'])->find_array();
        }
        
        $app->response->headers->set('Content-Disposition', 'attachment; filename=export.json');
        outputJson($notebooks, $app);
    });

    $app->post('/import', function () use ($notebookHelper, $noteHelper, $dbHelper, $app) {

        $notebooks = 0;
        $notes = 0;

        //Check size and type of input

        // First check if JSON is posted as request body
        $input = $app->request->getBody();

        // Then check if a file upload has been made
        if (strlen($input) == 0) {
            if ($_FILES['importFile']['error'] == UPLOAD_ERR_OK               //checks for errors
                && is_uploaded_file($_FILES['importFile']['tmp_name'])) { //checks that file is uploaded
                $input = file_get_contents($_FILES['importFile']['tmp_name']);
            }
        }

        $notebookRecords = json_decode($input);

        if (!is_array($notebookRecords)) {
            $app->flash('error', 'No (valid) data found');
            $app->redirect($app->refererringRoute);
            return;
        }
        
        try {
            \ORM::get_db()->beginTransaction();

            \ORM::for_table('note')->delete_many();
            \ORM::for_table('notebook')->delete_many();
            
            foreach ($notebookRecords as $notebookRecord) {

                if (!$notebookHelper->isValid($notebookRecord)) {
                    \ORM::get_db()->rollback();

                    $app->flash('error', 'Invalid data');
                    $app->redirect($app->refererringRoute);
                    return;
                }

                $notebook = \ORM::for_table('notebook')->create();
                $notebookHelper->map($notebook, $notebookRecord, true);

                // Todo: Check errors after db operations
                $notebook->save();
                $notebooks++;

                foreach ($notebookRecord->notes as $noteRecord) {
                    if (!$noteHelper->isValid($noteRecord)) {
                        \ORM::get_db()->rollback();
                        $app->flash('error', 'Invalid data');
                        $app->redirect($app->refererringRoute);
                        return;
                    }

                    $note = \ORM::for_table('note')->create();
                    $noteHelper->map($note, $notebook, $noteRecord, true);
                    $note->save();
                    $notes++;
                }
            }
            
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('%d notebook(s) and %d note(s) have been imported', $notebooks, $notes));
            $app->redirect('/admin');

        } catch (\Exception $e) {
            //\ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }
    });

    $app->post('/setup', function () use ($dbHelper, $app) {

        // Only perform setup if user has confirmed
        if ($app->request->params('confirm') != 'YES') {
            $app->flash('warning', 'Please confirm setup');
            $app->redirect($app->refererringRoute);
            return;
        }

        try {
            \ORM::get_db()->beginTransaction();
            $dbHelper->createDatabase();
            \ORM::get_db()->commit();
            $app->flash('success', 'Setup is executed');
            $app->redirect($app->refererringRoute);
            return;
        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }
        
    });

    $app->map('/migrate', function () use ($dbHelper, $app) {

        try {
            \ORM::get_db()->beginTransaction();
            $dbHelper->migrateDatabase();
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('Migrated database schema to %s', $dbHelper->getCurrentVersion()));
            // get referring route does not seem to work from GET Request
            // $app->redirect($app->refererringRoute);
            $app->redirect('/admin');
            return;
        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }

    })->via('GET', 'POST');

});
