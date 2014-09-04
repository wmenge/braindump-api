<?php
namespace Braindump\Api\Admin;

require_once(__DIR__ . '/../lib/DatabaseHelper.php');
require_once(__DIR__ . '/../model/NotebookHelper.php');
require_once(__DIR__ . '/../model/NoteHelper.php');

$notebookHelper = new \Braindump\Api\Model\NotebookHelper(new \Braindump\Api\Lib\DatabaseHelper());
$noteHelper = new \Braindump\Api\Model\NoteHelper(new \Braindump\Api\Lib\DatabaseHelper($app));

$app->get('/admin', function () use ($app) {

    $app->render('admin.php', 
        [ 'notebookCount' => \ORM::for_table('notebook')->count(), 
          'noteCount' => \ORM::for_table('note')->count() ]);
//    $app->response->headers->set('Content-Disposition', 'attachment; filename=export.json');
});

$app->get('/export', function () use ($app) {

    $notebooks = \ORM::for_table('notebook')->find_array();

    foreach ($notebooks as &$notebook) {
        $notebook['notes'] = \ORM::for_table('note')
        ->select_many('id', 'title', 'created', 'updated', 'url', 'type', 'content')
        ->where_equal('notebook_id', $notebook['id'])->find_array();
    }
    
    $app->response->headers->set('Content-Disposition', 'attachment; filename=export.json');
    outputJson($notebooks, $app);
});

$app->post('/import', function () use ($notebookHelper, $noteHelper, $app) {

    $notebooks = 0;
    $notes = 0;
    //wrap in transaction!
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
            $app->redirect('/admin');

        //var_dump($_FILES);
        //echo "no data";
        return;
    }

    // Start a transaction
    
    $dbHelper = new \Braindump\Api\Lib\DatabaseHelper();
    $dbHelper->createDatabase(\ORM::get_db(), $app->braindumpConfig['databases_setup_scripts']);

    \ORM::get_db()->beginTransaction();

    foreach ($notebookRecords as $notebookRecord) {

        if (!$notebookHelper->isValid($notebookRecord)) {
            \ORM::get_db()->rollback();

            $app->flash('error', 'Invalid data');
            $app->redirect('/admin');
            //$app->response->setStatus(400);
            //echo 'Invalid input:' . $app->request->getBody();
            return;
        }

        $notebook = \ORM::for_table('notebook')->create();
        $notebookHelper->map($notebook, $notebookRecord);
        // Todo: Check errors after db operations
        $notebook->save();
        $notebooks++;

        foreach ($notebookRecord->notes as $noteRecord) {
            if (!$noteHelper->isValid($noteRecord)) {
                \ORM::get_db()->rollback();
                $app->flash('error', 'Invalid data');
                $app->redirect('/admin');
                //$app->response->setStatus(400);
                //echo 'Invalid input';
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


});

$app->post('/setup', function () use ($app) {

    // Only perform setup if user has confirmed
    if ($app->request->params('confirm') != 'YES') {
        //echo 'confirm needed!';
        $app->flash('warning', 'Please confirm setup');
        $app->redirect('/admin');
        return;
    }

    $dbHelper = new \Braindump\Api\Lib\DatabaseHelper();
    $dbHelper->createDatabase(\ORM::get_db(), $app->braindumpConfig['databases_setup_scripts']);
    $app->flash('success', 'Setup is executed');
    $app->redirect('/admin');
});
