<?php namespace Braindump\Api\Controller\Admin;

use Braindump\Api\Model\Notebook as Notebook;

// Mocking standard date function to be able to compare file name
function date($format)
{
    return 0;
}

namespace Braindump\Api\Test\Integration;

/**
 * WARNING: DO NOT RUN UNDER NORMAL UNIT TESTING
 *
 * Creating large JSON exports with json_encode will not work
 * as it needs the serialized data in memory and constructs a json
 * string in memory.
 * Using bcncommerce/json-stream allows to construct a json document as
 * a sstream.
 * This test is not a proper unit test but a test to check that the stream
 * approach works on large documents
 * This method should be run as part of a normal unit test run
 */
class LargeExport extends Slim_Framework_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);

        // During import, a mock hasher is needed
        $hasher = $this->createMock('Cartalyst\Sentry\Hashing\HasherInterface', array('hash', 'checkhash'));
        $hasher->method('hash')
               ->with($this->equalTo('test'))->willReturn('test');
        \Braindump\Api\Model\Sentry\Paris\User::setHasher($hasher);
    }

    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    public function testLargeExport()
    {
        $this->markTestSkipped('test large export skipped');

        // Investigate: memory dump stil occurs with 10 books, 1000 notes, not with 100 books, 100 notes...
        // Create a big number of notes
        for ($i = 0; $i < 100; $i++) {

            $notebook = \Braindump\Api\Model\Notebook::create();
            $notebook->title = $i;
            $notebook->user_id = 1;
            
            $notebook->save();

            for ($j = 0; $j < 100; $j++) {

                $note = \Braindump\Api\Model\Note::create();
                $note->title = $j;
                $note->type = \Braindump\Api\Model\Note::TYPE_TEXT;
                $note->user_id = 1;
                $note->notebook_id = $notebook->id;
                $note->content = str_repeat("Lorem Ipsum ", 10000);

                $note->save();
            }
        }

        $response = $this->controller->getExport($this->getRequestMock(), new \Slim\Psr7\Response());

        // assert response object is about 1 Gb
        $this->assertEquals(1203071373, $response->getBody()->getSize());
    }

    public function testLargeImport()
    {
        $this->markTestSkipped('test large import skipped');

        // First, create a big export...
        for ($i = 0; $i < 100; $i++) {

            $notebook = \Braindump\Api\Model\Notebook::create();
            $notebook->title = $i;
            $notebook->user_id = 1;
            
            $notebook->save();

            for ($j = 0; $j < 100; $j++) {

                $note = \Braindump\Api\Model\Note::create();
                $note->title = $j;
                $note->type = \Braindump\Api\Model\Note::TYPE_TEXT;
                $note->user_id = 1;
                $note->notebook_id = $notebook->id;
                $note->content = str_repeat("Lorem Ipsum ", 10000);

                $note->save();
            }
        }

        $testResponse = $this->controller->getExport($this->getRequestMock(), new \Slim\Psr7\Response());
        
        $this->assertEquals(1203071373, $testResponse->getBody()->getSize());

        // Fetch the JSON stream resource from the testResponse....
        $jsonStreamResource = $testResponse->getBody()->detach();
        rewind($jsonStreamResource);

        // ... feed it into a new request
        $newRequest = $this->getRequestMock();

        $reflectionClass = new \ReflectionClass('Slim\Http\Stream');
        $reflectionProperty = $reflectionClass->getProperty('stream');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($newRequest->getBody(), $jsonStreamResource);

        // Execute the request
        $response = $this->controller->postImport($newRequest, new \Slim\Psr7\Response());

        // New export...
        $responseAfterImport = $this->controller->getExport($this->getRequestMock(), new \Slim\Psr7\Response());
        
        // assert response object is about 1 Gb
        $this->assertEquals(1203071373, $responseAfterImport->getBody()->getSize());
    }
}

class ExportTest extends Slim_Framework_TestCase
{
    protected function setUp(): void
    {   parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    /**
     * Files element should be optional
     */
    public function testGetExportWithoutFiles()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');

        $response = $this->controller->getExport($this->getRequestMock(), new \Slim\Psr7\Response());

        $this->assertEquals([ 'application/json;charset=utf-8' ], $response->getHeader('Content-Type'));
        $this->assertEquals([ 'attachment; filename=export-localhost-0.json' ], $response->getHeader('Content-Disposition'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

}

class ExportWithFilesTest extends Slim_Framework_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-with-files-seed.xml');
    }

    public function testGetExport()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/export-expected-2.json');
        
        $response = $this->controller->getExport($this->getRequestMock(), new \Slim\Psr7\Response());

        // Mock HTTP Host (empty during unit test)
        $this->assertEquals([ 'application/json;charset=utf-8' ], $response->getHeader('Content-Type'));
        $this->assertEquals([ 'attachment; filename=export-localhost-0.json' ], $response->getHeader('Content-Disposition'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }
}

class ImportTest extends Slim_Framework_TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);

        // During import, a mock hasher is needed
        $hasher = $this->createMock('Cartalyst\Sentry\Hashing\HasherInterface', array('hash', 'checkhash'));
        $hasher->method('hash')
               ->with($this->equalTo('test'))->willReturn('test');
        \Braindump\Api\Model\Sentry\Paris\User::setHasher($hasher);
    }

    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/empty-dataset-seed.xml');
    }

    public function testPostImportWithoutFiles()
    {
        $importData = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');

        $request = $this->getRequestMock();
        $request->getBody()->write($importData);
        $request->getBody()->rewind();

        $response = $this->controller->postImport($request, new \Slim\Psr7\Response());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');

        $expectedUserContent = $dataset->getTable("users");

/*        $expectedGroupContent = $dataset->getTable("groups");
        $groupTable = $this->getConnection()->createQueryTable('groups', 'SELECT * FROM groups');
        $this->assertTablesEqual($expectedGroupContent, $groupTable);
*/
        $userTable = $this->getConnection()->createQueryTable('users', 'SELECT id, login, password, permissions, activated, activated_at, name, created_at, updated_at FROM users');
        $this->assertTablesEqual($expectedUserContent, $userTable);
        
        $expectedNotebookContent = $dataset->getTable("notebook");
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
        
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
        

        // Todo: Assert calls to flash!s
    }


    public function testPostImportWithFiles()
    {
        $importData = file_get_contents(dirname(__FILE__).'/files/export-expected-2.json');

        $request = $this->getRequestMock();
        $request->getBody()->write($importData);
        $request->getBody()->rewind();

        $response = $this->controller->postImport($request, new \Slim\Psr7\Response());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-with-files-seed.xml');

        $expectedUserContent = $dataset->getTable("users");

/*        $expectedGroupContent = $dataset->getTable("groups");
        $groupTable = $this->getConnection()->createQueryTable('groups', 'SELECT * FROM groups');
        $this->assertTablesEqual($expectedGroupContent, $groupTable);
*/
        $userTable = $this->getConnection()->createQueryTable('users', 'SELECT id, login, password, permissions, activated, activated_at, name, created_at, updated_at FROM users');
        $this->assertTablesEqual($expectedUserContent, $userTable);
        
        $expectedNotebookContent = $dataset->getTable("notebook");
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
        
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
        
        $expectedFileContent = $dataset->getTable("file");
        $fileTable = $this->getConnection()->createQueryTable('file', 'SELECT * FROM file');
        $this->assertTablesEqual($expectedFileContent, $fileTable);
        
        

        // Todo: Assert calls to flash!s
    }


    /*public function testPostImportWithInvalidData()
    {

    }

    public function testPostImportWithEmptyData()
    {

    }*/

}
