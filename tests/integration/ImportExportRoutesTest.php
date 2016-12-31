<?php namespace Braindump\Api\Controller\Admin;

require_once __DIR__ . '/../../model/NotebookFacade.php';

use Braindump\Api\Model\Notebook as Notebook;

require_once(__DIR__ . '/../../controllers/AdminDataController.php');

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
/*class LargeExport extends Slim_Framework_TestCase
{
    public function setup()
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);
    }

    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    public function testLargeExport()
    {
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

        $response = $this->controller->getExport($this->getRequest(), new \Slim\Http\Response());
        
        // assert response object is about 1 Gb
        $this->assertEquals(1203253165, $response->getBody()->getSize());
        
        //print_r(mb_strlen($response->getBody(), '8bit'));
    }
}*/

class ExportTest extends Slim_Framework_TestCase
{
    public function setup()
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

        $response = $this->controller->getExport($this->getRequest(), new \Slim\Http\Response());

        $this->assertEquals([ 'application/json;charset=utf-8' ], $response->getHeader('Content-Type'));
        $this->assertEquals([ 'attachment; filename=export-localhost-0.json' ], $response->getHeader('Content-Disposition'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

}

class ExportWithFilesTest extends Slim_Framework_TestCase
{
    public function setup()
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
        
        $response = $this->controller->getExport($this->getRequest(), new \Slim\Http\Response());

        // Mock HTTP Host (empty during unit test)
        $this->assertEquals([ 'application/json;charset=utf-8' ], $response->getHeader('Content-Type'));
        $this->assertEquals([ 'attachment; filename=export-localhost-0.json' ], $response->getHeader('Content-Disposition'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }
}
/*
class ImportWithFilesTest extends Slim_Framework_TestCase
{

    public function setup()
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\Admin\AdminDataController($this->container);
    }

    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-with-files-seed.xml');
        //return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/empty-dataset-seed.xml');
    }

    /*public function testPostImport()
    {
        // Additional fixture, start with empty dataset
        $this->dbFacade->createDatabase();
        
        $importData = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');

        $request = $this->getRequest();
        $request->getBody()->write($importData);

        $response = $this->controller->postImport($request, new \Slim\Http\Response());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');

        $expectedNotebookContent = $dataset->getTable("notebook");
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);
        
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }*/

    /*public function testPostImportWithInvalidData()
    {

    }

    public function testPostImportWithEmptyData()
    {

    }*/

//}
