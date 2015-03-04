<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/Note.php');

class NoteFacade
{
    private $dbFacade;

    public function __construct($dbFacade)
    {
        $this->dbFacade = $dbFacade;
    }

    public function getNoteList($sortString = null, $queryString = null)
    {
        // TODO: Add paging to all lists
        $queryObj = Note::select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url')
            ->filter('currentUser');

        if (!empty($queryString)) {
            $queryObj = $queryObj->filter('content', $queryString);
        }

        if (!empty($sortString)) {
            $queryObj = $this->dbFacade->addSortExpression($queryObj, $sortString);
        }

        return $queryObj->find_array();
    }

    public function getNoteListForNoteBook($notebook, $sortString = null, $queryString = null)
    {
        $queryObj = Note::select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url')
            ->where_equal('notebook_id', $notebook->id)
            ->filter('currentUser');

        if (!empty($queryString)) {
            $queryObj = $queryObj->filter('content', $queryString);
        }

        if (!empty($sortString)) {
            $queryObj = $this->dbFacade->addSortExpression($queryObj, $sortString);
        }

        return $queryObj->find_array();
    }

    // TODO: rename to findById
    public function getNoteForId($id)
    {
        return Note::filter('currentUser')->find_one($id);
    }
}
