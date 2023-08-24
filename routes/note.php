<?php

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->get('/notes', '\Braindump\Api\Controller\Notes\NoteController:getNotes');
    $group->get('/notebooks/{id}/notes', '\Braindump\Api\Controller\Notes\NoteController:getNotes');
    
    $group->get('/notebooks/{notebook_id}/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:getNote');
    $group->get('/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:getNote');
    
    $group->post('/notebooks/{id}/notes', '\Braindump\Api\Controller\Notes\NoteController:postNote');
    
    $group->put('/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:putNote');
    $group->put('/notebooks/{notebook_id}/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:putNote');
    
    $group->delete('/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:deleteNote');
    $group->delete('/notebooks/{notebook_id}/notes/{note_id}', '\Braindump\Api\Controller\Notes\NoteController:deleteNote');
    
})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');
