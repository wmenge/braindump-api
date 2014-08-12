<?php

namespace Braindump\Api\Test\Integration;

class NoteRoutesTest extends Slim_Framework_TestCase
{
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
    public function testGetNotes($route, $file)
    {
        $expected = file_get_contents(dirname(__FILE__) . $file);
        $this->get($route);
        
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function getNotesProvider()
    {
        return [
            ['/notes'                        , '/files/get-notes-expected-1.json'],
            ['/notebooks/1/notes'            , '/files/get-notes-expected-2.json'],
            ['/notebooks/1/notes?sort=-title', '/files/get-notes-expected-3.json'],
            ['/notebooks/1/notes?q=note 1'   , '/files/get-notes-expected-4.json']
        ];
    }

    public function testGetNotesForUnknownNotebook()
    {
        $this->get('/notebooks/3/notes');
        
        $this->assertEquals(404, $this->response->status());
        //$this->assertSame($expected, $this->response->body());
    }

    public function testGetNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        $this->get('/notes/1');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetUnkownNotebook()
    {
        $this->get('/notes/4');
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
        //$this->assertSame($expected, $this->response->body());
    }

    public function testGetNoteInNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        $this->get('/notebooks/1/notes/1');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetNoteInWrongNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        $this->get('/notebooks/2/notes/1');
        $this->assertEquals(302, $this->response->status());
    }

    public function testGetNoteInUnknownNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notes-expected-5.json');
        $this->get('/notebooks/3/notes/1');
        $this->assertEquals(404, $this->response->status());
    }

    public function testPostNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $requestBody = '{ "title": "New Note", "type": "Text", "content": "Note content" }';

        $this->post('/notebooks/1/notes', $requestBody);
        // Assert response
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notes-expected-1.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostInvalidNote()
    {
        $requestBody = '{ }';

        // Assert response
        $this->post('/notebooks/1/notes', $requestBody);
        $this->assertEquals(400, $this->response->status());
        // todo: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostNoteToInvalidNotebook()
    {
        $requestBody = '{ "title": "New Note", "type": "Text" }';

        // Assert response
        $this->post('/notebooks/3/notes', $requestBody);
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPutNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/put-notes-expected-1.json');
        
        $requestBody = '{ "title": "Updated note 1", "type": "Text", "content": "Updated Note content" }';

        $this->put('/notebooks/1/notes/1', $requestBody);

       // Assert response
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/put-notes-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNote()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $requestBody = '{ "title": "New Note", "type": "Text", "content": "Note content" }';

        $this->put('/notebooks/1/notes/4', $requestBody);

        // Assert response
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notes-expected-1.xml');
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPutInvalidNotebook()
    {
        $requestBody = '{ }';

        $this->put('/notebooks/1/notes/1', $requestBody);
        $this->assertEquals(400, $this->response->status());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNoteToInvalidNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/put-notes-expected-1.json');
        
        $requestBody = '{ "title": "Updated note 1", "type": "Text", "content": "Updated Note content" }';

        $this->put('/notebooks/3/notes/1', $requestBody);

       // Assert response
        $this->assertEquals(404, $this->response->status());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNoteToInvalidNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/post-notes-expected-1.json');
        
        $requestBody = '{ "title": "New Note", "type": "Text", "content": "Note content" }';

        $this->put('/notes/4', $requestBody);

        // Assert response (400, not 404 as you cannot put a new note without specifying
        // the notebook)
        $this->assertEquals(400, $this->response->status());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNote()
    {
        $this->delete('/notes/1');
        $this->assertEquals(200, $this->response->status());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/delete-notes-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteUnknownNote()
    {
        $this->delete('/notes/4');
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNoteInUnknownNotebook()
    {
        $this->delete('/notebooks/3/notes/1');
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');
        $expectedNotebookContent = $dataset->getTable("note");
        
        $notebookTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
   }
}
