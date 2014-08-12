<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/NoteHelper.php');

$noteHelper = new \Braindump\Api\Model\NoteHelper(new \Braindump\Api\Lib\DatabaseHelper($app));

$app->get('/(notebooks/:id/)notes(/)', function ($id = null) use ($app, $noteHelper) {

    $req = $app->request();

    if (empty($id)) {
        outputJson($noteHelper->getNoteList($req->get('sort'), $req->get('q')), $app);
    } else {
        // Check if notebook exists, return 404 if it doesn't
        $notebook = \ORM::for_table('notebook')->find_one($id);
        if ($notebook == null) {
            return $app->notFound();
        }
        outputJson($noteHelper->getNoteListForNoteBook($notebook, $req->get('sort'), $req->get('q')), $app);
    }
});

$app->get('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteHelper) {

    $notebook = null;

    //Check if notebook exists, return 404 if it doesn't
    if ($notebook_id != null) {
        $notebook = \ORM::for_table('notebook')->find_one($notebook_id);
        if ($notebook == null) {
            return $app->notFound();
        }
    }

    $note = $noteHelper->getNoteForId($note_id);

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

$app->post('/notebooks/:id/notes(/)', function ($id) use ($app, $noteHelper) {

    //Check if notebook exists, return 404 if it doesn't
    $notebook = \ORM::for_table('notebook')->find_one($id);

    if ($notebook == null) {
        return $app->notFound();
    }

    $input = json_decode($app->request->getBody());

    if (!$noteHelper->isValid($input)) {
        $app->response->setStatus(400);
        echo 'Invalid input';
        return;
    }

    $note = \ORM::for_table('note')->create();
    $noteHelper->map($note, $notebook, $input);
    $note->save();

    $note = $noteHelper->getNoteForId($note->id());

    outputJson($note->as_array(), $app);
});

$app->put('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteHelper) {

    // Check if notebook exists (if supplied)
    if ($notebook_id != null) {

        $notebook = \ORM::for_table('notebook')->find_one($notebook_id);

        if ($notebook == null) {
            return $app->notFound();
        }
    }

    $input = json_decode($app->request->getBody());

    if (!$noteHelper->isValid($input)) {
        $app->response->setStatus(400);
        echo 'Invalid input';

        return;
    }

    // Get note
    $note = \ORM::for_table('note')->find_one($note_id, $noteHelper);

    if ($note == null) {
        // For a create scenario, a valid notebook id should
        // have been supplied (return 400 instead of 404 to
        // indicate error situation)
        if (!isset($notebook)) {
            $app->response->setStatus(400);
            echo 'Invalid input';
            return;
        }
        $note = \ORM::for_table('note')->create();
    } else {
        // If notebook is supplied, it should match the
        // notebook id in the note
        /*if ($notebook != null && $notebook->id != $note->notebook_id) {
    		$app->response->setStatus(400);
			echo '';
			return;
    	}*/
    }

    $noteHelper->map($note, $notebook, $input);
    $note->save();

    $note = $noteHelper->getNoteForId($note->id());

    outputJson($note->as_array(), $app);

});

$app->delete('/(notebooks/:notebook_id/)notes/:note_id(/)', function ($notebook_id, $note_id) use ($app, $noteHelper) {

    if (!empty($notebook_id)) {
        $notebook = \ORM::for_table('notebook')->find_one($notebook_id);
        if ($notebook == null) {
            return $app->notFound();
        }
    }

    $note = \ORM::for_table('note')->find_one($note_id);

    if ($note == null) {
        return $app->notFound();
    }

    $note->delete();

});
