<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/NotebookHelper.php');

$notebookHelper = new \Braindump\Api\Model\NotebookHelper(new \Braindump\Api\Lib\DatabaseHelper());

$app->get('/(notebooks)(/)', function () use ($notebookHelper, $app) {
   
    $list = $notebookHelper->getNoteBookList($app->request()->get('sort'));
    if (empty($list)) {
        $notebookHelper->createSampleData();
        $list = $notebookHelper->getNoteBookList($app->request()->get('sort'));
    }

    outputJson($list, $app);
});

$app->get('/notebooks/:id(/)', function ($id) use ($app, $notebookHelper) {

    $notebook = $notebookHelper->getNotebookForId($id);

    if ($notebook == null) {
        return $app->notFound();
    }

    outputJson($notebook->as_array(), $app);
});

$app->post('/notebooks(/)', function () use ($app, $notebookHelper) {
    // @TODO Notebook Title should be unique (for user)
    // @TODO After creation, set url in header,
    // check http://stackoverflow.com/questions/11159449

    $input = json_decode($app->request->getBody());

    if (!$notebookHelper->isValid($input)) {
        $app->response->setStatus(400);
        echo 'Invalid input:' . $app->request->getBody();

        return;
    }

    $notebook = \ORM::for_table('notebook')->create();
    $notebookHelper->map($notebook, $input);
    // Todo: Check errors after db operations
    $notebook->save();

    $notebook = $notebookHelper->getNotebookForId($notebook->id());

    outputJson($notebook->as_array(), $app);
});

$app->put('/notebooks/:id(/)', function ($id) use ($app, $notebookHelper) {
    // Todo: Notebook Title should be unique (for user)
    // Todo: After creation, set url in header,
    // check http://stackoverflow.com/questions/11159449
    $input = json_decode($app->request->getBody());

    if (!$notebookHelper->isValid($input)) {
        $app->response->setStatus(400);
        echo 'Invalid input';

        return;
    }

    $notebook = \ORM::for_table('notebook')->find_one($id);

    if ($notebook == null) {
        $notebook = \ORM::for_table('notebook')->create();
    }

    $notebookHelper->map($notebook, $input);
    $notebook->save();

    $notebook = $notebookHelper->getNotebookForId($notebook->id());

    outputJson($notebook->as_array(), $app);
});

$app->delete('/notebooks/:id(/)', function ($id) use ($app) {

    // Check if notebook exists
    $notebook = \ORM::for_table('notebook')->find_one($id);

    if ($notebook == null) {
        return $app->notFound();
    }

    // Start a transaction
    \ORM::get_db()->beginTransaction();

    // First, delete all notes in notebook
    \ORM::for_table('note')
        ->where_equal('notebook_id', $notebook->id)
        ->delete_many();

    // Finally, delete notebook
    $notebook->delete();

    // Commit a transaction
    \ORM::get_db()->commit();

});
