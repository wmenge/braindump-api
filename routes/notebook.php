<?php

Class NotebookHelper {

	public static function getNoteBookList() {
		return ORM::for_table('notebook')
			//->select_expr('"/notebooks/" || notebook.id', 'url')
			->select_expr('"/notebooks/" || notebook.id || "/notes"', 'notes_url')
			->select('*')
			->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
			->find_array();
	}

	public static function getNotebookForId($id) {
		return ORM::for_table('notebook')
			//q->select_expr('"/notebooks/" || notebook.id', 'url')
			->select_expr('"/notebooks/" || notebook.id || "/notes"', 'notes_url')
			->select('*')
			->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
			->where_equal('id', $id)
	    	->find_one();
	}

	public static function isValid($data) {
		return is_object($data) && !empty($data->title);
	}

	public static function map($notebook, $data) {
		// Explicitly map parameters, be paranoid of your input
		// check https://phpbestpractices.org 
		// and http://stackoverflow.com/questions/129677
		$notebook->title = htmlentities($data->title);
	}

	public static function createSampleData() {

		$notebook = ORM::for_table('notebook')->create();
		$notebook->title = 'Your first notebook';
		$notebook->save();

		$note = ORM::for_table('note')->create();
		$note->notebook_id = $notebook->id();
		$note->title = 'This is a Note';
		$note->url = 'http://braindump-client.local';
		$note->content = 'Your very first note';
		if ($note->created == null) $note->created = time();
		$note->updated = time();
		$note->save();
	}
}



$app->get('/(notebooks)(/)', function() {
	//@TODO: If Notebook list is empty, create sample data
	$list = NotebookHelper::getNoteBookList();

	if (empty($list)) {
		NotebookHelper::createSampleData();
		$list = NotebookHelper::getNoteBookList();
	}

	outputJson($list);
});

$app->get('/notebooks/:id(/)', function($id) use ($app) {
	$notebook = NotebookHelper::getNotebookForId($id);
    	
    if ($notebook == null) return $app->notFound();

    outputJson($notebook->as_array());
});

$app->post('/notebooks(/)', function() use ($app) {

	header("Access-Control-Allow-Origin: *");
	

	// @TODO Notebook Title should be unique (for user)
	// @TODO After creation, set url in header, 
	// check http://stackoverflow.com/questions/11159449
	
	$input = json_decode($app->request->getBody());

	if (!NotebookHelper::isValid($input)) {
		$app->response->setStatus(400);
		echo 'Invalid input:' . $app->request->getBody();
		return;
	}

	$notebook = ORM::for_table('notebook')->create();
	NotebookHelper::map($notebook, $input);
	$notebook->save();

	$notebook = NotebookHelper::getNotebookForId($notebook->id());
    	
	if ($notebook == null) return $app->notFound();

    outputJson($notebook->as_array());
});

$app->put('/notebooks/:id(/)', function($id) use ($app) {

	// Todo: Notebook Title should be unique (for user)
	// Todo: After creation, set url in header, 
	// check http://stackoverflow.com/questions/11159449
	$input = json_decode($app->request->getBody());

	if (!NotebookHelper::isValid($input)) {
		$app->response->setStatus(400);
		echo 'Invalid input';
		return;
	}

	$notebook = ORM::for_table('notebook')->find_one($id);
    	
    if ($notebook == null) {
    	$notebook = ORM::for_table('notebook')->create();
    }

	NotebookHelper::map($notebook, $input);
	$notebook->save();

	$notebook = NotebookHelper::getNotebookForId($notebook->id());
    	
	if ($notebook == null) return $app->notFound();

    outputJson($notebook->as_array());
});

$app->delete('/notebooks/:id(/)', function($id) use ($app) {
	//@TODO: Put in some sort of middleware
	header("Access-Control-Allow-Origin: *");
	$notebook = ORM::for_table('notebook')->find_one($id);
    	
    if ($notebook == null) return $app->notFound();

    $notebook->delete();
});