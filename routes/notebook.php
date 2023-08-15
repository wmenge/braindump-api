<?php
$app->group('/api', function () {

    $this->get('/notebooks[/]', '\Braindump\Api\Controller\Notebooks\NotebookController:getNotebooks');
    $this->get('/notebooks/{id}[/]', '\Braindump\Api\Controller\Notebooks\NotebookController:getNotebook');
    $this->post('/notebooks[/]', '\Braindump\Api\Controller\Notebooks\NotebookController:postNotebook');
    $this->put('/notebooks/{id}[/]', '\Braindump\Api\Controller\Notebooks\NotebookController:putNotebook');
    $this->delete('/notebooks/{id}[/]', '\Braindump\Api\Controller\Notebooks\NotebookController:deleteNotebook');

})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');