<?php

Class NoteHelper {

	const TYPE_TEXT = 'Text';
	const TYPE_HTML = 'HTML';

	public static function getNoteList() {
		// @TODO: Add paging to all lists
		return ORM::for_table('note')
			->select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url')
			->find_array();
	}


	public static function getNoteListForNoteBook($notebook) {
		return ORM::for_table('note')
			->select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url')
			->where_equal('notebook_id', $notebook->id)
			->find_array();
	}

	public static function getNoteForId($id) {
		return ORM::for_table('note')
			->select('*')
			->where_equal('id', $id)
			->find_one();
	}

	public static function isValid($data) {

		// Check minimum required fields
		return
			is_object($data) && 
			!empty($data->title) && 
			in_array($data->type, array(NoteHelper::TYPE_TEXT, NoteHelper::TYPE_HTML));
	//		($inpuxtData->content_url == null || !(filter_var($inputData->content_url, FILTER_VALIDATE_URL) === false);
	}

	public static function map($note, $notebook, $data) {
		// Explicitly map parameters, be paranoid of your input
		// check https://phpbestpractices.org 
		// and http://stackoverflow.com/questions/129677
		if (!empty($notebook)) {
			$note->notebook_id = $notebook->id;
		}
		$note->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');
		$note->url = htmlentities($data->url, ENT_QUOTES, 'UTF-8');
		$note->type = htmlentities($data->type, ENT_QUOTES, 'UTF-8');
		if ($note->type == NoteHelper::TYPE_HTML) {
			// check http://dev.evernote.com/doc/articles/enml.php for evenrote html format
			// @TODO Check which tags to allow/disallow
			// @TODO Allow images with base64 content
			$purifier = new HTMLPurifier(HTMLPurifier_Config::createDefault());
 			$note->content = $purifier->purify($data->content);
		} elseif ($note->type == NoteHelper::TYPE_TEXT) {
			$note->content = htmlentities($data->content, ENT_QUOTES, 'UTF-8');
		}
		if ($note->created == null) $note->created = time();
		$note->updated = time();
	}

}

$app->get('/notes(/)', function() {
	outputJson(NoteHelper::getNoteList());
});

$app->get('/notebooks/:id/notes(/)', function($id) use ($app) {

	// Check if notebook exists, return 404 if it doesn't
	$notebook = ORM::for_table('notebook')->find_one($id);
    if ($notebook == null) return $app->notFound();

	outputJson(NoteHelper::getNoteListForNoteBook($notebook));
});

$app->get('/(notebooks/:notebook_id/)notes/:note_id(/)', function($notebook_id, $note_id) use ($app) {
	
	//Check if notebook exists, return 404 if it doesn't
	if ($notebook_id != null) {
		$notebook = ORM::for_table('notebook')->find_one($notebook_id);
    	if ($notebook == null) return $app->notFound();
	}
	
	$note = NoteHelper::getNoteForId($note_id);

	// Return 404 for non-existent note	
    if ($note == null) return $app->notFound();

	// Redirect to correct notebook if note resides in different workbook
    if ($notebook != null && $notebook->id != $note->notebook_id) {
    	$app->redirect(sprintf('/notebooks/%s/notes/%s', $note->notebook_id, $note->id));
    }
	
    outputJson($note->as_array());
});

$app->post('/notebooks/:id/notes(/)', function($id) use ($app) {

	//Check if notebook exists, return 400 if it doesn't
	$notebook = ORM::for_table('notebook')->find_one($id);
    
    if ($notebook == null) {
    	$app->response->setStatus(400);
		echo 'Invalid input';
		return;
    }

    $input = json_decode($app->request->getBody());

    if (!NoteHelper::isValid($input)) {
		$app->response->setStatus(400);
		echo 'Invalid input';
		return;
	}

	$note = ORM::for_table('note')->create();
	NoteHelper::map($note, $notebook, $input);
	$note->save();

	$note = NoteHelper::getNoteForId($note->id());
	
	if ($note == null) return $app->notFound();

	outputJson($note->as_array());
});

$app->put('/(notebooks/:notebook_id/)notes/:note_id(/)', function($notebook_id, $note_id) use ($app) {

	// Check if notebook exists (if supplied)
	if ($notebook_id != null) {

		$notebook = ORM::for_table('notebook')->find_one($notebook_id);
	    
	    if ($notebook == null) {
	   		$app->response->setStatus(400);
			echo 'Invalid notebook';
			return;
	    }
	}

	$input = json_decode($app->request->getBody());

    if (!NoteHelper::isValid($input)) {
		$app->response->setStatus(400);
		echo 'Invalid input';
		return;
	}

	// Get note
	$note = ORM::for_table('note')->find_one($note_id);

	if ($note == null) {
		// For a create scenario, a valid notebook id should 
		// have been supplied
		if ($notebook == null) {
	   		$app->response->setStatus(400);
			echo 'Invalid notebook';
			return;
	    }
    	$note = ORM::for_table('note')->create();
    } else {   
    	// If notebook is supplied, it should match the
    	// notebook id in the note
    	/*if ($notebook != null && $notebook->id != $note->notebook_id) {
    		$app->response->setStatus(400);
			echo '';
			return;
    	}*/
	}

	NoteHelper::map($note, $notebook, $input);
	$note->save();

	$note = NoteHelper::getNoteForId($note->id());
	
	if ($note == null) return $app->notFound();

	outputJson($note->as_array());
   
});

$app->delete('(/notebooks/:notebook_id/)notes/:note_id(/)', function($notebook_id, $note_id) use ($app) {
	
	$note = ORM::for_table('note')->find_one($note_id);
	    
    if ($note == null) {
   		$app->response->setStatus(400);
		echo 'Invalid note';
		return;
    }

    $note->delete();

});