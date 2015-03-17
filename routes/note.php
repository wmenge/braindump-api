<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/NoteFacade.php');

use Braindump\Api\Model\Note as Note;

$noteFacade = new \Braindump\Api\Model\NoteFacade();

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app, $notebookFacade, $noteFacade) {

    $app->get('/(notebooks/:id/)notes(/)', function ($id = null) use ($app, $noteFacade, $notebookFacade) {

        $req = $app->request();

        if (empty($id)) {
            outputJson($noteFacade->getNoteList($req->get('sort'), $req->get('q')), $app);
        } else {
            // Check if notebook exists, return 404 if it doesn't
            $notebook = $notebookFacade->getNotebookForId($id);

            if ($notebook == null) {
                return $app->notFound();
            }

            outputJson($noteFacade->getNoteListForNoteBook($notebook, $req->get('sort'), $req->get('q')), $app);
        }
    });

    $app->get('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteFacade, $notebookFacade) {

        $notebook = null;

        //Check if notebook exists, return 404 if it doesn't
        if ($notebook_id != null) {
            $notebook = $notebookFacade->getNotebookForId($notebook_id);

            if ($notebook == null) {
                return $app->notFound();
            }
        }

        $note = $noteFacade->getNoteForId($note_id);

        // Return 404 for non-existent note
        if ($note == null) {
            return $app->notFound();
        }

        // Redirect to correct notebook if note resides in different workbook
        if ($notebook != null && $notebook->id != $note->notebook_id) {
            $app->redirect(sprintf('/notebooks/%s/notes/%s', $note->notebook_id, $note->id));
        }

        outputJson($note->as_array(), $app);
    });

    $app->post('/notebooks/:id/notes(/)', function ($id) use ($app, $noteFacade, $notebookFacade) {

        //Check if notebook exists, return 404 if it doesn't
        $notebook = $notebookFacade->getNotebookForId($id);

        if ($notebook == null) {
            return $app->notFound();
        }

        $input = json_decode($app->request->getBody());

        if (!Note::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        $note = Note::create();

        $note->map($notebook, $input);
        $note->save();

        $note = $noteFacade->getNoteForId($note->id());

        outputJson($note->as_array(), $app);
    });

    $app->put('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteFacade, $notebookFacade) {

        $notebook = null;

        // Check if notebook exists (if supplied)
        if ($notebook_id != null) {

            $notebook = $notebookFacade->getNotebookForId($notebook_id);

            if ($notebook == null) {
                return $app->notFound();
            }
        }

        $input = json_decode($app->request->getBody());

        if (!Note::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        // Get note
        $note = $noteFacade->getNoteForId($note_id);

        if ($note == null) {
            // For a create scenario, a valid notebook id should
            // have been supplied (return 400 instead of 404 to
            // indicate error situation)
            if (!isset($notebook)) {
                $app->halt(400, 'Invalid input');
            }
            $note = Note::create();
        } else {
            // If notebook is supplied, it should match the
            // notebook id in the note
            /*if ($notebook != null && $notebook->id != $note->notebook_id) {
        		$app->response->setStatus(400);
    			echo '';
    			return;
        	}*/
        }

        $note->map($notebook, $input);
        $note->save();

        $note = $noteFacade->getNoteForId($note->id());

        outputJson($note->as_array(), $app);

    });

    $app->delete('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteFacade, $notebookFacade) {

        if (!empty($notebook_id)) {
            $notebook = $notebookFacade->getNotebookForId($notebook_id);
            
            if ($notebook == null) {
                return $app->notFound();
            }
        }

        $note = $noteFacade->getNoteForId($note_id);

        if ($note == null) {
            return $app->notFound();
        }

        $note->delete();

    });

});
