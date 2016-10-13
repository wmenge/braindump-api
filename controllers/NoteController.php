<?php namespace Braindump\Api\Controller\Notes;

require_once __DIR__ . '/BaseController.php';

require_once(__DIR__ . '/../model/NoteFacade.php');

use Braindump\Api\Model\Note as Note;

class NoteController extends \Braindump\Api\Controller\BaseController {

    private $noteFacade;// = new \Braindump\Api\Model\NoteFacade();
    private $notebookFacade;

    public function __construct(\Interop\Container\ContainerInterface $ci) {
        $this->noteFacade = new \Braindump\Api\Model\NoteFacade();
        $this->notebookFacade = new \Braindump\Api\Model\NotebookFacade();
        parent::__construct($ci);
    }
   
    public function getNotes($request, $response, $args) {

        if (empty($args['id'])) {
            outputJson($this->noteFacade->getNoteList($request->getQueryParam('sort'), $request->getQueryParam('q')), $response);
        } else {
            // Check if notebook exists, return 404 if it doesn't
            $notebook = $this->notebookFacade->getNotebookForId($args['id']);

            if ($notebook == null) {
                return $response->withStatus(404);
            }

            outputJson($this->noteFacade->getNoteListForNoteBook($notebook, $request->getQueryParam('sort'), $request->getQueryParam('q')), $response);

        }
        return $response;
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

        outputJson($note->as_array(), $response);
        return $response;
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
            //$app->halt(400, 'Invalid input');
        }

        $note = Note::create();

        $note->map($notebook, $input);
        $note->save();

        $note = $this->noteFacade->getNoteForId($note->id());

        outputJson($note->as_array(), $response);
        return $response;
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

        outputJson($note->as_array(), $response);
        return $response;
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