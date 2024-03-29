<?php namespace Braindump\Api\Controller\Notes;

use Braindump\Api\Model\Note as Note;

class NoteController extends \Braindump\Api\Controller\BaseController {

    private $noteFacade;// = new \Braindump\Api\Model\NoteFacade();
    private $notebookFacade;

    public function __construct(\Psr\Container\ContainerInterface $ci) {
        $this->noteFacade = new \Braindump\Api\Model\NoteFacade();
        $this->notebookFacade = new \Braindump\Api\Model\NotebookFacade();
        parent::__construct($ci);
    }
   
    public function getNotes($request, $response, $args) {

        $queryParams = $request->getQueryParams();
        $sort = isset($queryParams['sort']) ? $queryParams['sort'] : null;
        $query = isset($queryParams['q']) ? $queryParams['q'] : null;

        if (empty($args['id'])) {
            return $this->outputJson($this->noteFacade->getNoteList($sort, $query), $response);
        } else {
            // Check if notebook exists, return 404 if it doesn't
            $notebook = $this->notebookFacade->getNotebookForId($args['id']);

            if ($notebook == null) {
                return $response->withStatus(404);
            }

            return $this->outputJson($this->noteFacade->getNoteListForNoteBook($notebook, $sort, $query), $response);
        }
    }

    public function getNote($request, $response, $args) {

        $notebook = null;

        //Check if notebook exists, return 404 if it doesn't
        if (isset($args['notebook_id'])) {
            $notebook = $this->notebookFacade->getNotebookForId($args['notebook_id']);

            if ($notebook == null) {
                return $response->withStatus(404);
            }
        }

        $note = $this->noteFacade->getNoteForId($args['note_id']);

        // Return 404 for non-existent note
        if ($note == null) {
            return $response->withStatus(404);
        }

        // Redirect to correct notebook if note resides in different workbook
        if ($notebook != null && $notebook->id != $note->notebook_id) {
            return $response->withStatus(302)->withHeader('Location', sprintf('/notebooks/%s/notes/%s', $note->notebook_id, $note->id));
        }

        return $this->outputJson($note->as_array(), $response);
    }

    public function postNote($request, $response, $args) {

        //Check if notebook exists, return 404 if it doesn't
        $notebook = $this->notebookFacade->getNotebookForId($args['id']);

        if ($notebook == null) {
            return $response->withStatus(404);
        }

        $input = json_decode($request->getBody());

        if (!Note::isValid($input)) {
            return $response->withStatus(400);
        }

        $note = Note::create();

        $note->map($notebook, $input);
        $note->save();

        $note = $this->noteFacade->getNoteForId($note->id());

        return $this->outputJson($note->as_array(), $response);
    }

    public function putNote($request, $response, $args) {

        $notebook = null;

        // Check if notebook exists (if supplied)
        if (isset($args['notebook_id'])) {
        
            $notebook = $this->notebookFacade->getNotebookForId($args['notebook_id']);

            if ($notebook == null) {
                return $response->withStatus(404);
            }
        }

        $input = json_decode($request->getBody());

        if (!Note::isValid($input)) {
            return $response->withStatus(400);
        }

        // Get note
        $note = $this->noteFacade->getNoteForId($args['note_id']);

        if ($note == null) {
            // For a create scenario, a valid notebook id should
            // have been supplied (return 400 instead of 404 to
            // indicate error situation)
            if (!isset($notebook)) {
                return $response->withStatus(400);
            }
            $note = Note::create();
        } else {
            // If notebook is supplied, it should match the
            // notebook id in the note
            /*if ($notebook != null && $notebook->id != $note->notebook_id) {
                $app->response->setStatus(400);
                echo '';
                return;
            }*/
        }

        $note->map($notebook, $input);
        $note->save();

        $note = $this->noteFacade->getNoteForId($note->id());

        return $this->outputJson($note->as_array(), $response);
    }

    public function deleteNote($request, $response, $args) {

        /*if (!empty($args['notebook_id'])) {
            $notebook = $notebookFacade->getNotebookForId($notebook_id);
            
            if ($notebook == null) {
                return $app->notFound();
            }
        }*/

        $note = $this->noteFacade->getNoteForId($args['note_id']);

        if ($note == null) {
            return $response->withStatus(404);
        }

        $note->delete();
        return $response;

    }

}