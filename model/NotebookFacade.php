<?php

namespace Braindump\Api\Model;

class NotebookFacade
{
    private $dbFacade;

    public function __construct($dbFacade)
    {
        $this->dbFacade = $dbFacade;
    }

    public function getNoteBookList($sortString = null)
    {
        $query = \ORM::for_table('notebook')
            ->select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->where_equal('user_id', \Sentry::getUser()->id);

        $query = $this->dbFacade->addSortExpression($query, $sortString);

        return $query->find_array();
    }

    public function getNotebookForId($id)
    {
        return \ORM::for_table('notebook')
            ->select('*')
            ->select_expr('(SELECT COUNT(*) FROM note WHERE notebook_id = notebook.id)', 'noteCount')
            ->where_equal('id', $id)
            ->where_equal('user_id', \Sentry::getUser()->id)
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

    public function map($notebook, $data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // https://phpbestpractices.org
        if ($this->isValid($data)) {
            $notebook->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');

            // In import scenario, try to get create and update times from data object
            if ($import && property_exists($data, 'created') && is_numeric($data->created)) {
                $notebook->created = filter_var($data->created, FILTER_SANITIZE_NUMBER_INT);
            } elseif (!property_exists($notebook, 'created') || $notebook->created == null) {
                $notebook->created = time();
            }

            if ($import && property_exists($data, 'updated') && is_numeric($data->updated)) {
                $notebook->updated = filter_var($data->updated, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $notebook->updated = time();
            }

            if (!property_exists($notebook, 'user_id') || $notebook->user_id == null) {
                $notebook->user_id = \Sentry::getUser()->id;
            }
        }
    }

    public function createSampleData()
    {
        // Start a transaction
        \ORM::get_db()->beginTransaction();

        $notebook = \ORM::for_table('notebook')->create();
        $notebook->title = 'Your first notebook';
        $notebook->created = time();
        $notebook->updated = time();
        $notebook->user_id = \Sentry::getUser()->id;
        
        $notebook->save();

        $note = \ORM::for_table('note')->create();
        $note->notebook_id = $notebook->id();
        $note->title = 'This is a Note';
        $note->url = 'https://github.com/wmenge/braindump-api';
        $note->type = NoteFacade::TYPE_HTML;
        $note->content = '<div>Your very first note</div>';
        $note->created = time();
        $note->updated = time();
        $note->user_id = \Sentry::getUser()->id;
        $note->save();

        // Commit a transaction
        \ORM::get_db()->commit();
    }
}
