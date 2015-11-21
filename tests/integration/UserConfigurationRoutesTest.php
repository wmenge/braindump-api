<?php

namespace Braindump\Api\Test\Integration;

class UserConfigurationRoutesTest extends Slim_Framework_TestCase
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/configuration-seed.xml');
    }

    public function testGetConfiguration()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-configuration-expected-1.json');
        $this->get('/api/configuration');
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
