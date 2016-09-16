<?php namespace Braindump\Api\Admin;

require_once __DIR__ . '/../../model/NotebookFacade.php';

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
 * Using cncommerce/json-stream allows to construct a json document as
 * a sstream.
 * This test is not a proper unit test but a test to check that the stream
 * approach works on large documents
 * This method should be run as part of a normal unit test run
 */
/*class LargeExport extends Slim_Framework_TestCase
{
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    public function testLargeExport()
    {
        for ($i = 0; $i < 100000; $i++) {
            $notebook = \Braindump\Api\Model\Notebook::create();
            $notebook->title = $i;
            $notebook->save();
        }
    }
}*/

class ImportExportTest extends Slim_Framework_TestCase
{
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
        $this->get('/admin/export');

        // Mock HTTP Host (empty during unit test)
        $this->assertEquals('application/json', $this->response->headers['Content-Type']);
        $this->assertEquals('attachment; filename=export-localhost-0.json', $this->response->headers['Content-Disposition']);
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

}

class ImportExportWithFilesTest extends Slim_Framework_TestCase
{
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
        $this->get('/admin/export');

        // Mock HTTP Host (empty during unit test)
        $this->assertEquals('application/json', $this->response->headers['Content-Type']);
        $this->assertEquals('attachment; filename=export-localhost-0.json', $this->response->headers['Content-Disposition']);
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testPostImport()
    {
        // Additional fixture, start with empty dataset
        /*$dbFacade = new \Braindump\Api\Lib\DatabaseFacade($this->app, \ORM::get_db());
        $dbFacade->createDatabase();
        $importData = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');

        $this->post('/admin/import', $importData);
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');

        $expectedNotebookContent = $dataset->getTable("notebook");
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        
        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        //$this->assertTablesEqual($expectedNoteContent, $noteTable);*/
    }

    public function testPostImportWithInvalidData()
    {

    }

    public function testPostImportWithEmptyData()
    {

    }

}
