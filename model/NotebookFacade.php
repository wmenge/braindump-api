<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/Notebook.php');

class NotebookFacade
{
    private $dbFacade;

    public function __construct($dbFacade)
    {
        $this->dbFacade = $dbFacade;
    }

    public function getNoteBookList($sortString = null)
    {
        $query = Notebook::select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->filter('currentUserFilter')
            ->filter('sortFilter', $sortString);

        // Todo: Move to filter
        //$query = $this->dbFacade->addSortExpression($query, $sortString);

        return $query->find_array();
    }

    public function getNotebookForId($id)
    {
        return Notebook::select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->filter('currentUserFilter')
            ->find_one($id);
    }

    public function createSampleData()
    {
        // Start a transaction
        \ORM::get_db()->beginTransaction();

        $notebook = Notebook::create();
        $notebook->title = 'Your first notebook';
        $notebook->save();

        $note = Note::create();
        $note->notebook_id = $notebook->id();
        $note->title = 'This is a Note';
        $note->url = 'https://github.com/wmenge/braindump-api';
        $note->type = Note::TYPE_HTML;
        $note->content = '<div>Your very first note</div>';
        $note->save();

        // Commit a transaction
        \ORM::get_db()->commit();
    }
}
