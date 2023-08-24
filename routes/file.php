<?php

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->get('/files/{name}', '\Braindump\Api\Controller\File\FileController:getFile');
    $group->any('/files', '\Braindump\Api\Controller\File\FileController:postFile');
    
})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');