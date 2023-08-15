<?php namespace Braindump\Api\Controller\Notebooks;

use Braindump\Api\Model\Notebook as Notebook;

class NotebookController extends \Braindump\Api\Controller\BaseController {

    private $notebookFacade;

    public function __construct(\Psr\Container\ContainerInterface $ci) {
        $this->notebookFacade = new \Braindump\Api\Model\NotebookFacade();
        parent::__construct($ci);
    }
   
    public function getNotebooks($request, $response) {

        $list = $this->notebookFacade->getNoteBookList($request->getQueryParam('sort'));
        if (empty($list)) {
            $this->notebookFacade->createSampleData();
            $list = $this->notebookFacade->getNoteBookList($request->getQueryParam('sort'));
        }

        return $this->outputJson($list, $response);
    }

    public function getNotebook($request, $response, $args) {

        $notebook = $this->notebookFacade->getNotebookForId($args['id']);

        if ($notebook == null) {
            return $response->withStatus(404);
        }

        return $this->outputJson($notebook->as_array(), $response);
    }

    public function postNotebook($request, $response) {

        // TODO: Notebook Title should be unique (for user)
        // TODO: After creation, set url in header,
        // check http://stackoverflow.com/questions/11159449

        $input = json_decode($request->getBody());

        if (!Notebook::isValid($input)) {
            return $response->withStatus(400, 'Invalid input');
        }

        $notebook = Notebook::create();
        $notebook->map($input);
        // TODO: Check errors after db operations
        $notebook->save();

        $notebook = $this->notebookFacade->getNotebookForId($notebook->id);

        return $this->outputJson($notebook->as_array(), $response);
    }

    public function putNotebook($request, $response, $args) {

        // TODO: Notebook Title should be unique (for user)
        // TODO: After creation, set url in header,
        // TOOD: In create scenario, redirect to new id
        // check http://stackoverflow.com/questions/11159449
        $input = json_decode($request->getBody());

        if (!Notebook::isValid($input)) {
            return $response->withStatus(400, 'Invalid input');
        }

        $notebook = $this->notebookFacade->getNotebookForId($args['id']);

        if ($notebook == null) {
            $notebook = Notebook::create();
        }

        $notebook->map($input);
        $notebook->save();

        $notebook = $this->notebookFacade->getNotebookForId($notebook->id);

        return $this->outputJson($notebook->as_array(), $response);
    }

    public function deleteNotebook($request, $response, $args) {

        // Check if notebook exists
        $notebook = $this->notebookFacade->getNotebookForId($args['id']);

        if ($notebook == null) {
            return $response->withStatus(404);
        }

        // Start a transaction
        \ORM::get_db()->beginTransaction();

        // First, delete all notes in notebook
        // TODO use paris relations
        \ORM::for_table('note')
            ->where_equal('notebook_id', $notebook->id)
            ->delete_many();

        // Finally, delete notebook
        $notebook->delete();

        // Commit a transaction
        \ORM::get_db()->commit();

        return $response;
    }

}