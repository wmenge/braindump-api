<?php

namespace Braindump\Api\Test\Integration;

class NotebookRoutesTest extends Slim_Framework_TestCase
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notebooks-seed.xml');
    }

    public function testGetRoot()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-1.json');
        $this->get('/');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetNotebooks()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-1.json');
        $this->get('/notebooks');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetNotebookSampledata()
    {
        // Additional fixture, start with empty dataset
        $dbHelper = new \Braindump\Api\Lib\DatabaseHelper();
        $dbHelper->createDatabase(\ORM::get_db(), [ '0.1' => __DIR__ . '/../../migrations/braindump-0.1-sqlite.sql']);
        
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-3.json');
        $this->get('/notebooks');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-2.json');
        $this->get('/notebooks/1');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testGetUnkownNotebook()
    {
        $this->get('/notebooks/3');
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
        //$this->assertSame($expected, $this->response->body());
    }

    public function testPostNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-4.json');
        
        $requestBody = '{ "title": "New Notebook" }';

        $this->post('/notebooks', $requestBody);
        
        // Assert response
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPostInvalidNotebook()
    {
        $requestBody = '{ }';

        // Assert response
        $this->post('/notebooks', $requestBody);
        $this->assertEquals(400, $this->response->status());
        // todo: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-5.json');
        
        $requestBody = '{ "title": "Updated title" }';

        $this->put('/notebooks/1', $requestBody);

        // Assert response
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/put-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-4.json');
        
        $requestBody = '{ "title": "New Notebook" }';

        $this->put('/notebooks/3', $requestBody);
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutInvalidNotebook()
    {
        $requestBody = '{ }';

        $this->put('/notebooks/1', $requestBody);
        $this->assertEquals(400, $this->response->status());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNotebook()
    {
        $this->delete('/notebooks/1');
        $this->assertEquals(200, $this->response->status());

        // test also with non-empty notebook

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/delete-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteUnknownNotebook()
    {
        $this->delete('/notebooks/3');
        $this->assertEquals(404, $this->response->status());
        // todo: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }
}
