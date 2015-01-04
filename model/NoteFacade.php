<?php

namespace Braindump\Api\Model;

// TODO: rename to NoteProvider?
class NoteFacade
{
    const TYPE_TEXT = 'Text';
    const TYPE_HTML = 'HTML';

    private $dbFacade;

    public function __construct($dbFacade)
    {
        $this->dbFacade = $dbFacade;
    }

    public function getNoteList($sortString = null, $queryString = null)
    {
        // TODO: Add paging to all lists
        $queryObj = \ORM::for_table('note')->select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url');

        if (!empty($queryString)) {
            $queryObj = $queryObj->where_raw(
                '(`title` LIKE ? OR `content` LIKE ?)',
                array(sprintf('%%%s%%', $queryString), sprintf('%%%s%%', $queryString))
            );
        }

        if (!empty($sortString)) {
            $queryObj = $this->dbFacade->addSortExpression($queryObj, $sortString);
        }

        return $queryObj->find_array();
    }

    public function getNoteListForNoteBook($notebook, $sortString = null, $queryString = null)
    {
        $queryObj = \ORM::for_table('note')
            ->select_many('id', 'notebook_id', 'title', 'created', 'updated', 'url')
            ->where_equal('notebook_id', $notebook->id);

        if (!empty($queryString)) {
            $queryObj = $queryObj->where_raw(
                '(`title` LIKE ? OR `content` LIKE ?)',
                array(sprintf('%%%s%%', $queryString), sprintf('%%%s%%', $queryString))
            );
        }

        if (!empty($sortString)) {
            $queryObj = $this->dbFacade->addSortExpression($queryObj, $sortString);
        }

        return $queryObj->find_array();
    }

    // TODO: rename to findById
    public function getNoteForId($id)
    {
        return \ORM::for_table('note')
            ->select('*')
            ->where_equal('id', $id)
            ->find_one();
    }

    public function isValid($data)
    {
        // Check minimum required fields
        return
            is_object($data) &&
            property_exists($data, 'title') &&
            is_string($data->title) &&
            !empty($data->title) &&
            property_exists($data, 'type') &&
            in_array($data->type, [NoteFacade::TYPE_TEXT, NoteFacade::TYPE_HTML]);

        // if url is supplied, check content
    //      ($inpuxtData->content_url == null || !(filter_var($inputData->content_url, FILTER_VALIDATE_URL) === false);
    }

    public function map($note, $notebook, $data, $import = false)
    {
        // Explicitly map parameters, be paranoid of your input
        // check https://phpbestpractices.org
        // and http://stackoverflow.com/questions/129677
        if ($this->isValid($data)) {

            if (!empty($notebook)) {
                $note->notebook_id = $notebook->id;
            }
            if (property_exists($data, 'title')) {
                $note->title = htmlentities($data->title, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'url')) {
                $note->url = htmlentities($data->url, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'type')) {
                $note->type = htmlentities($data->type, ENT_QUOTES, 'UTF-8');
            }

            if (property_exists($data, 'content')) {
                if ($note->type == NoteFacade::TYPE_HTML) {
                    // check http://dev.evernote.com/doc/articles/enml.php for evenrote html format
                    // TODO Check which tags to allow/disallow
                    // TODO Allow images with base64 content
                    $purifier = new \HTMLPurifier(\HTMLPurifier_Config::createDefault());
                    $note->content = $purifier->purify($data->content);
                } elseif ($note->type == NoteFacade::TYPE_TEXT) {
                    $note->content = htmlentities($data->content, ENT_QUOTES, 'UTF-8');
                } else {
                    // Shouldn't happen
                }
            }

            // In import scenario, try to get create and update times from data object
            if ($import && property_exists($data, 'created') && is_numeric($data->created)) {
                $note->created = filter_var($data->created, FILTER_SANITIZE_NUMBER_INT);
            } elseif (!property_exists($note, 'created') || $note->created == null) {
                $note->created = time();
            }

            if ($import && property_exists($data, 'updated') && is_numeric($data->updated)) {
                $note->updated = filter_var($data->updated, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $note->updated = time();
            }

            if (!property_exists($notebook, 'user_id') || $notebook->user_id == null) {
                $note->user_id = \Sentry::getUser()->id;
            }
        }
    }
}
