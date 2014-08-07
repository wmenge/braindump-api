<?php

namespace Braindump\Api\Model;

class NotebookHelper
{
    private $dbHelper;

    public function __construct($dbHelper)
    {
        $this->dbHelper = $dbHelper;
    }

    public function getNoteBookList($sortString = null)
    {
        $query = \ORM::for_table('notebook')
            ->select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount');

        $query = $this->dbHelper->addSortExpression($query, $sortString);

        return $query->find_array();
    }

    public function getNotebookForId($id)
    {
        return \ORM::for_table('notebook')
            ->select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->where_equal('id', $id)
            ->find_one();
    }

    public function isValid($data)
    {
        return
            is_object($data) &&
            property_exists($data, 'title') &&
            is_string($data->title) &&
            !empty($data->title);
    }

    public function map($notebook, $data)
    {
        // Explicitly map parameters, be paranoid of your input
        // https://phpbestpractices.org
        if ($this->isValid($data)) {
            $notebook->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');
        }
    }

    public function createSampleData()
    {
        // Start a transaction
        \ORM::get_db()->beginTransaction();

        $notebook = \ORM::for_table('notebook')->create();
        $notebook->title = 'Your first notebook';
        $notebook->save();

        $note = \ORM::for_table('note')->create();
        $note->notebook_id = $notebook->id();
        $note->title = 'This is a Note';
        $note->url = 'https://github.com/wmenge/braindump-api';
        $note->type = NoteHelper::TYPE_HTML;
        $note->content = '<div>Your very first note</div>';
        if ($note->created == null) {
            $note->created = time();
        }
        $note->updated = time();
        $note->save();

        // Commit a transaction
        \ORM::get_db()->commit();
    }
}
