<?php namespace Braindump\Api\Test\Integration;

require_once(__DIR__ . '/../../controllers/NoteController.php');

class NoteRoutesTest extends Slim_Framework_TestCase
{
    public function setup()
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Notes\NoteController($this->container);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    /**
     * Test some permutations of note GET operations
     *
     * @dataProvider getNotesProvider
     */
    public function testGetNotes($args, $headers, $file)
    {
        $expected = file_get_contents(dirname(__FILE__) . $file);
        
        $response = $this->controller->getNotes($this->getRequestMock($headers), new \Slim\Http\Response(), $args);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

    public function getNotesProvider()
    {
        return [
            [ []           , [], '/files/get-notes-expected-1.json'],
            [ [ 'id' => 1 ], [], '/files/get-notes-expected-2.json'],
            [ [ 'id' => 1 ], [ 'QUERY_STRING' => 'sort=-title' ], '/files/get-notes-expected-3.json'],
            [ [ 'id' => 1 ], [ 'QUERY_STRING' => 'q=note 1' ], '/files/get-notes-expected-4.json'],
        ];
    }

    public function testGetNotesForUnknownNotebook()
    {
        $response = $this->controller->getNotes($this->getRequestMock(), new \Slim\Http\Response(), [ 'id' => 99 ]);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetNotesForNotebookBelongingToDifferentUser()
    {
        $response = $this->controller->getNotes($this->getRequestMock(), new \Slim\Http\Response(), [ 'id' => 3 ]);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 1 ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

    public function testGetUnkownNote()
    {
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 99 ]);
        
        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        //$this->assertSame($expected, $this->response->getBody());
    }

    public function testGetNoteInNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 1, 'note_id' => 1 ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

    public function testGetNoteInWrongNotebook()
    {
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 2, 'note_id' => 1 ]);
        
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testGetNoteInUnknownNotebook()
    {
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 99, 'note_id' => 1 ]);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetNoteFromDifferentUsersNotebook()
    {
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 3, 'note_id' => 4 ]);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetNoteFromDifferentUser()
    {
        $response = $this->controller->getNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 4 ]);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPostNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text", "content": "Note content" }');

        $response = $this->controller->postNote($request, new \Slim\Http\Response(), [ 'id' => 1 ]);
        
        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notes-expected-1.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostInvalidNote()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ }');

        $response = $this->controller->postNote($request, new \Slim\Http\Response(), [ 'id' => 1 ]);        

        $this->assertEquals(400, $response->getStatusCode());
        // todo: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostNoteToInvalidNotebook()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text" }');

        $response = $this->controller->postNote($request, new \Slim\Http\Response(), [ 'id' => 99 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostNoteToNotebookOfDifferentUser()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text" }');

        $response = $this->controller->postNote($request, new \Slim\Http\Response(), [ 'id' => 3 ]);        

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPutNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/put-notes-expected-1.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "Updated note 1", "type": "Text", "content": "Updated Note content" }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'notebook_id' => 1, 'note_id' => 1 ]);        

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/put-notes-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text", "content": "Note content" }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'notebook_id' => 1, 'note_id' => 5 ]);        

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notes-expected-1.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPutInvalidNote()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{  }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'notebook_id' => 1, 'note_id' => 1 ]);        

        $this->assertEquals(400, $response->getStatusCode());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNoteOfDifferentUser()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text", "content": "Note content" }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'notebook_id' => 3, 'note_id' => 4 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNoteToInvalidNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/put-notes-expected-1.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "Updated note 1", "type": "Text", "content": "Updated Note content" }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'notebook_id' => 99, 'note_id' => 1 ]);        

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNoteToInvalidNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Note", "type": "Text", "content": "Note content" }');

        $response = $this->controller->putNote($request, new \Slim\Http\Response(), [ 'note_id' => 5 ]);        


        // Assert response (400, not 404 as you cannot put a new note without specifying
        // the notebook)
        $this->assertEquals(400, $response->getStatusCode());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNote()
    {
        $response = $this->controller->deleteNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 1 ]);        

        $this->assertEquals(200, $response->getStatusCode());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/delete-notes-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteUnknownNote()
    {
        $response = $this->controller->deleteNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 99 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNoteOfDifferentUser()
    {
        $response = $this->controller->deleteNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'note_id' => 4 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNoteInUnknownNotebook()
    {
        $response = $this->controller->deleteNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 99, 'note_id' => 4 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNoteInNotebookOfDifferentUser()
    {
        $response = $this->controller->deleteNote($this->getRequestMock(), new \Slim\Http\Response(), [ 'notebook_id' => 3, 'note_id' => 4 ]);        

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }
    
}
