<?php

Class NotebookHelper {

	const MAGIC_BOOK_ALL_NOTES = 'MAGIC_BOOK_ALL_NOTES';

	private static function getMagicNotebookAllNotes() {
		// A 'magic' notebook that represents a list of all notebooks
		$magicNotebook = new stdClass;
		$magicNotebook->magic = true;
		$magicNotebook->id = MAGIC_BOOK_ALL_NOTES;
		$magicNotebook->title = 'All notes';
		$magicNotebook->noteCount = ORM::for_table('note')->count();
		return $magicNotebook;
	}
	
	public static function getNoteBookList() {

		$list = ORM::for_table('notebook')
			->select('*')
			->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
			->find_array();

		if (!empty($list)) {
			array_unshift($list, NotebookHelper::getMagicNotebookAllNotes());
		}

		return $list;
	}

	public static function getNotebookForId($id) {
		return ORM::for_table('notebook')
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
		// https://phpbestpractices.org 
		$notebook->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');
	}

	public static function createSampleData() {

		// Start a transaction
		ORM::get_db()->beginTransaction();

		$notebook = ORM::for_table('notebook')->create();
		$notebook->title = 'Your first notebook';
		$notebook->save();

		$note = ORM::for_table('note')->create();
		$note->notebook_id = $notebook->id();
		$note->title = 'This is a Note';
		$note->url = 'https://github.com/wmenge/braindump-api';
		$note->type = NoteHelper::TYPE_HTML;
		$note->content = '<div>Your very first note</div>';
		if ($note->created == null) $note->created = time();
		$note->updated = time();
		$note->save();

		// Commit a transaction
		ORM::get_db()->commit();
	}
}

$app->get('/(notebooks)(/)', function() {
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

	// Check if notebook exists
	$notebook = ORM::for_table('notebook')->find_one($id);
    	
    if ($notebook == null) return $app->notFound();

	// Start a transaction
	ORM::get_db()->beginTransaction();

    // First, delete all notes in notebook
	ORM::for_table('note')
		->where_equal('notebook_id', $notebook->id)
		->delete_many();

	// Finally, delete notebook
    $notebook->delete();

    // Commit a transaction
	ORM::get_db()->commit();

});