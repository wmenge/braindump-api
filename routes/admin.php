<?php

use Braindump\Api\Middleware\Authentication;
        
$app->group('/admin', function () {

    $this->get('[/]', '\Braindump\Api\Controller\Admin\AdminController:getRoot');
    
    $this->get('/export', '\Braindump\Api\Controller\Admin\AdminDataController:getExport');
    $this->post('/import', '\Braindump\Api\Controller\Admin\AdminDataController:postImport');
    $this->post('/setup', '\Braindump\Api\Controller\Admin\AdminDataController:postSetup');
    $this->map([ 'POST', 'PUT' ], '/migrate', '\Braindump\Api\Controller\Admin\AdminDataController:migrate');
    
    $this->get('/users[/]', '\Braindump\Api\Controller\Admin\AdminUserController:getUsers');
    $this->get('/users/createForm', '\Braindump\Api\Controller\Admin\AdminUserController:getCreateForm');
    $this->get('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:getUser');
    $this->post('/users[/]', '\Braindump\Api\Controller\Admin\AdminUserController:postUser');
    $this->put('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:putUser');
    $this->post('/users/{id}/throttle/{action}[/]', '\Braindump\Api\Controller\Admin\AdminUserController:postThrottle');
    $this->delete('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:deleteUser');

    $this->get('/info', function($req, $res) { phpinfo(); });
        
})->add('Braindump\Api\Middleware\Authentication:adminAuthorize')->add('Braindump\Api\Middleware\Authentication:adminAuthenticate');;