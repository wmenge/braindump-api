<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../controllers/NoteController.php');

$app->group('/api', function () {

    $this->get('/notes[/]', '\Braindump\Api\Controller\Notes\NoteController:getNotes');
    $this->get('/notebooks/{id}/notes[/]', '\Braindump\Api\Controller\Notes\NoteController:getNotes');
    
    $this->get('/notebooks/{notebook_id}/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:getNote');
    $this->get('/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:getNote');
    
    $this->post('/notebooks/{id}/notes[/]', '\Braindump\Api\Controller\Notes\NoteController:postNote');
    
    $this->put('/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:putNote');
    $this->put('/notebooks/{notebook_id}/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:putNote');
    
    $this->delete('/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:deleteNote');
    $this->delete('/notebooks/{notebook_id}/notes/{note_id}[/]', '\Braindump\Api\Controller\Notes\NoteController:deleteNote');
    
})->add('Braindump\Api\Admin\Middleware\apiAuthorize')->add('Braindump\Api\Admin\Middleware\apiAuthenticate');
