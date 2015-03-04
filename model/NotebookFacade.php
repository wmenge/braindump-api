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
            ->filter('currentUser');

        // Todo: Move to filter
        $query = $this->dbFacade->addSortExpression($query, $sortString);

        return $query->find_array();
    }

    public function getNotebookForId($id)
    {
        return Notebook::select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->filter('currentUser')
            ->find_one($id);
    }

    public function createSampleData()
    {
        // Start a transaction
        \ORM::get_db()->beginTransaction();

        $notebook = Notebook::create();
        $notebook->title = 'Your first notebook';
        $notebook->created = time();
        $notebook->updated = time();
        $notebook->user_id = \Sentry::getUser()->id;
        
        $notebook->save();

        $note = \ORM::for_table('note')->create();
        $note->notebook_id = $notebook->id();
        $note->title = 'This is a Note';
        $note->url = 'https://github.com/wmenge/braindump-api';
        $note->type = Note::TYPE_HTML;
        $note->content = '<div>Your very first note</div>';
        $note->created = time();
        $note->updated = time();
        $note->user_id = \Sentry::getUser()->id;
        $note->save();

        // Commit a transaction
        \ORM::get_db()->commit();
    }
}
