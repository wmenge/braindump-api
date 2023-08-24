<?php

namespace Braindump\Api\Test\Integration;

class NotebookRoutesTest extends Slim_Framework_TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Notebooks\NotebookController($this->container);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notebooks-seed.xml');
    }

    public function testGetNotebooks()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-1.json');

        $response = $this->controller->getNotebooks($this->getRequestMock(), new \Slim\Psr7\Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

/*    public function testGetNotebookSampledata()
    {
        // Additional fixture, start with empty dataset, with one user
        $dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
            \ORM::get_db(),
            (require( __DIR__ . '/../../migrations/migration-config.php')));
        
        $dbFacade->createDatabase();
        $user = \ORM::for_table('users')->create();
        $user->email = 'administrator@braindump-local';
        $user->password = 'password';
        $user->save();
        
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-3.json');
        $this->get('/api/notebooks');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, $response->getBody());
    }*/

    public function testGetNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-2.json');
        
        $response = $this->controller->getNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 1 ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

    public function testGetUnkownNotebook()
    {
        $response = $this->controller->getNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 99 ]);

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
    }

    public function testGetNotebookOfDifferentUser()
    {
        $response = $this->controller->getNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 3 ]);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPostNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-4.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Notebook" }');

        $response = $this->controller->postNotebook($request, new \Slim\Psr7\Response());
    
        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPostInvalidNotebook()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ }');

        $response = $this->controller->postNotebook($request, new \Slim\Psr7\Response());
    
        // Assert response
        $this->assertEquals(400, $response->getStatusCode());
        // TODO: assert message
    
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-5.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "Updated title" }');

        $response = $this->controller->putNotebook($request, new \Slim\Psr7\Response(), [ 'id' => 1 ]);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/put-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNewNotebook()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-4.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Notebook" }');

        $response = $this->controller->putNotebook($request, new \Slim\Psr7\Response(), [ 'id' => 4 ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutInvalidNotebook()
    {
        $request = $this->getRequestMock();
        $request->getBody()->write('{ }');

        $response = $this->controller->putNotebook($request, new \Slim\Psr7\Response(), [ 'id' => 1 ]);

        $this->assertEquals(400, $response->getStatusCode());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testPutNotebookOfDifferentUser()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-notebooks-expected-4.json');
        
        $request = $this->getRequestMock();
        $request->getBody()->write('{ "title": "New Notebook" }');

        $response = $this->controller->putNotebook($request, new \Slim\Psr7\Response(), [ 'id' => 3 ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNotebook()
    {
        $response = $this->controller->deleteNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 1 ]);

        $this->assertEquals(200, $response->getStatusCode());

        // test also with non-empty notebook

        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/delete-notebooks-expected-1.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteUnknownNotebook()
    {
        $response = $this->controller->deleteNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 99 ]);

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }

    public function testDeleteNotebookOfDifferentUser()
    {
        $response = $this->controller->deleteNotebook($this->getRequestMock(), new \Slim\Psr7\Response(), [ 'id' => 3 ]);

        $this->assertEquals(404, $response->getStatusCode());
        // TODO: assert message
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/post-notebooks-expected-2.xml');
        $expectedNotebookContent = $dataset->getTable("notebook");
        
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
    }
}
