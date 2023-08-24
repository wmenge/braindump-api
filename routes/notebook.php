<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->get('/notebooks', '\Braindump\Api\Controller\Notebooks\NotebookController:getNotebooks');
    $group->get('/notebooks/{id}', '\Braindump\Api\Controller\Notebooks\NotebookController:getNotebook');
    $group->post('/notebooks', '\Braindump\Api\Controller\Notebooks\NotebookController:postNotebook');
    $group->put('/notebooks/{id}', '\Braindump\Api\Controller\Notebooks\NotebookController:putNotebook');
    $group->delete('/notebooks/{id}', '\Braindump\Api\Controller\Notebooks\NotebookController:deleteNotebook');

})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');