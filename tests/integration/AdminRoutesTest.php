<?php

namespace Braindump\Api\Admin;

// Mocking standard date function to be able to compare file name
function date($format)
{
    return 0;
}

namespace Braindump\Api\Test\Integration;

class AdminRoutesTest extends Slim_Framework_TestCase
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    public function testGetExport()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');
        $this->get('/admin/export');
        //print_r($this->response);
//[Content-Disposition] => 

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
