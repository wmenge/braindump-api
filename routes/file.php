<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../controllers/FileController.php');

$app->group('/api', function () {

    $this->get('/files/{name}', '\Braindump\Api\Controller\File\FileController:getFile');
    $this->any('/files[/]', '\Braindump\Api\Controller\File\FileController:postFile');
    
})->add('Braindump\Api\Admin\Middleware\apiAuthorize')->add('Braindump\Api\Admin\Middleware\apiAuthenticate');