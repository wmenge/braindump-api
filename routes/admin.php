<?php

use Slim\Routing\RouteCollectorProxy;

$app->group('/admin', function (RouteCollectorProxy $group) {

    $group->get('', '\Braindump\Api\Controller\Admin\AdminController:getRoot');
    
    $group->get('/export', '\Braindump\Api\Controller\Admin\AdminDataController:getExport');
    $group->post('/import', '\Braindump\Api\Controller\Admin\AdminDataController:postImport');
    $group->post('/setup', '\Braindump\Api\Controller\Admin\AdminDataController:postSetup');
    $group->map([ 'POST', 'PUT' ], '/migrate', '\Braindump\Api\Controller\Admin\AdminDataController:migrate');
    
    $group->get('/users', '\Braindump\Api\Controller\Admin\AdminUserController:getUsers');
    $group->get('/users/createForm', '\Braindump\Api\Controller\Admin\AdminUserController:getCreateForm');
    $group->get('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:getUser');
    $group->post('/users', '\Braindump\Api\Controller\Admin\AdminUserController:postUser');
    $group->put('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:putUser');
    $group->post('/users/{id}/throttle/{action}[/]', '\Braindump\Api\Controller\Admin\AdminUserController:postThrottle');
    $group->delete('/users/{id}', '\Braindump\Api\Controller\Admin\AdminUserController:deleteUser');

    $group->get('/info', function(Slim\Psr7\Request $request, Slim\Psr7\Response $response) { phpinfo(); return $response; });
    $group->get('/xdebug_info', function(Slim\Psr7\Request $request, Slim\Psr7\Response $response) { xdebug_info(); return $response; });
        
})->add('Braindump\Api\Middleware\Authentication:adminAuthorize')->add('Braindump\Api\Middleware\Authentication:adminAuthenticate');;