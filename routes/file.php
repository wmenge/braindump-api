<?php

$app->group('/api', function () {

    $this->get('/files/{name}', '\Braindump\Api\Controller\File\FileController:getFile');
    $this->any('/files[/]', '\Braindump\Api\Controller\File\FileController:postFile');
    
})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');