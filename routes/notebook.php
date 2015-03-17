<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/NotebookFacade.php');

use Braindump\Api\Model\Notebook as Notebook;

$notebookFacade = new \Braindump\Api\Model\NotebookFacade();

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app, $notebookFacade, $noteFacade) {

    $app->get('(/)(notebooks)(/)', function () use ($notebookFacade, $app) {
       
        $list = $notebookFacade->getNoteBookList($app->request()->get('sort'));
        if (empty($list)) {
            $notebookFacade->createSampleData();
            $list = $notebookFacade->getNoteBookList($app->request()->get('sort'));
        }

        outputJson($list, $app);
    });

    $app->get('/notebooks/:id(/)', function ($id) use ($app, $notebookFacade) {

        $notebook = $notebookFacade->getNotebookForId($id);

        if ($notebook == null) {
            return $app->notFound();
        }

        outputJson($notebook->as_array(), $app);
    });

    $app->post('/notebooks(/)', function () use ($app, $notebookFacade) {
        // TODO: Notebook Title should be unique (for user)
        // TODO: After creation, set url in header,
        // check http://stackoverflow.com/questions/11159449

        $input = json_decode($app->request->getBody());

        if (!Notebook::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        $notebook = Notebook::create();
        $notebook->map($input);
        // TODO: Check errors after db operations
        $notebook->save();

        $notebook = $notebookFacade->getNotebookForId($notebook->id);

        outputJson($notebook->as_array(), $app);
    });

    $app->put('/notebooks/:id(/)', function ($id) use ($app, $notebookFacade) {
        // TODO: Notebook Title should be unique (for user)
        // TODO: After creation, set url in header,
        // TOOD: In create scenario, redirect to new id
        // check http://stackoverflow.com/questions/11159449
        $input = json_decode($app->request->getBody());

        if (!Notebook::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        $notebook = $notebookFacade->getNotebookForId($id);

        if ($notebook == null) {
            $notebook = Notebook::create();
        }

        $notebook->map($input);
        $notebook->save();

        $notebook = $notebookFacade->getNotebookForId($notebook->id);

        outputJson($notebook->as_array(), $app);
    });

    $app->delete('/notebooks/:id(/)', function ($id) use ($app, $notebookFacade) {

        // Check if notebook exists
        $notebook = $notebookFacade->getNotebookForId($id);

        if ($notebook == null) {
            return $app->notFound();
        }

        // Start a transaction
        \ORM::get_db()->beginTransaction();

        // First, delete all notes in notebook
        // TODO use paris relations
        \ORM::for_table('note')
            ->where_equal('notebook_id', $notebook->id)
            ->delete_many();

        // Finally, delete notebook
        $notebook->delete();

        // Commit a transaction
        \ORM::get_db()->commit();

    });

});
