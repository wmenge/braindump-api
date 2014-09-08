<?php

namespace Braindump\Api\Test\Integration;

class ImportExportRoutesTest extends Slim_Framework_TestCase
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
        $this->get('/export');
        $this->assertEquals(200, $this->response->status());
        $this->assertSame($expected, $this->response->body());
    }

    public function testPostImport()
    {
        // Additional fixture, start with empty dataset
        $dbHelper = new \Braindump\Api\Lib\DatabaseHelper($this->app, \ORM::get_db());
        $dbHelper->createDatabase(); //\ORM::get_db(), [ '0.1' => __DIR__ . '/../../migrations/braindump-0.1-sqlite.sql']);
        
        $importData = file_get_contents(dirname(__FILE__).'/files/export-expected-1.json');

        $this->post('/import', $importData);
        //$this->assertEquals(200, $this->response->status());
        
        // Assert db content
        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notes-seed.xml');

        $expectedNotebookContent = $dataset->getTable("notebook");
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);

        $expectedNoteContent = $dataset->getTable("note");
        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $this->assertTablesEqual($expectedNoteContent, $noteTable);
    }

    public function testPostImportWithInvalidData()
    {

    }

    public function testPostImportWithEmptyData()
    {

    }

}
