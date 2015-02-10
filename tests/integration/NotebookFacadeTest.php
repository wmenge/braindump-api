<?php
namespace Braindump\Api\Model;

// Mocking standard time function to be able to compare DB content
function time()
{
    return 0;
}

namespace Braindump\Api\Test\Integration;

require_once __DIR__ . '/../../model/NotebookFacade.php';

class NotebookFacadeTest extends AbstractDbTest
{
    protected $Facade;

    protected function setUp()
    {
        parent::setUp();

        $mockApp = new \stdClass();
        $mockApp->braindumpConfig = (require( __DIR__ . '/../../config/braindump-config.php'));
        $dbFacade = new \Braindump\Api\Lib\DatabaseFacade($mockApp, \ORM::get_db());
        
        $this->Facade = new \Braindump\Api\Model\NotebookFacade($dbFacade);

        \Sentry::$id = 1;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notebooks-seed.xml');
    }

    // TODO: add test to retrieve notebooks of different user
    public function testGetNotebookList()
    {
        $expected = [
            [ 'id' => 1, 'title' => 'title 1', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 1 ],
            [ 'id' => 2, 'title' => 'title 2', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 1 ],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteBookList());
    }

    public function testGetNotebookListForDifferentUser()
    {
        // Switch mock to different user
        \Sentry::$id = 2;

        $expected = [
            [ 'id' => 3, 'title' => 'title 3', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 2 ]
        ];

        $this->assertEquals($expected, $this->Facade->getNoteBookList());
    }

    public function testSortedGetNotebookList()
    {
        $expected = [
            [ 'id' => 2, 'title' => 'title 2', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 1 ],
            [ 'id' => 1, 'title' => 'title 1', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 1 ],
        ];
        
        $this->assertEquals($expected, $this->Facade->getNoteBookList('-title'));
    }

    public function testGetNotebookForId()
    {
        $expected = [ 'id' => 1, 'title' => 'title 1', 'created' => 0, 'updated' => 0, 'noteCount' => 0, 'user_id' => 1 ];
        
        $this->assertEquals($expected, $this->Facade->getNotebookForId(1)->as_array());
    }

    public function testGetNonExistingNotebookForId()
    {
        $this->assertEquals(null, $this->Facade->getNotebookForId(9));
    }

    public function testGetNotebookForDifferentUser()
    {
        $this->assertEquals(null, $this->Facade->getNotebookForId(3));
    }
    
    public function testCreateSampleData()
    {
        $this->Facade->createSampleData();

        $dataset = $this->createFlatXmlDataSet(dirname(__FILE__).'/files/notebooks-expected-create-sample-data.xml');

        // Compare actual db content against expectation in file
        $notebookTable = $this->getConnection()->createQueryTable('notebook', 'SELECT * FROM notebook');
        $expectedNotebookContent = $dataset->getTable("notebook");
        $this->assertTablesEqual($expectedNotebookContent, $notebookTable);

        $noteTable = $this->getConnection()->createQueryTable('note', 'SELECT * FROM note');
        $expectedNoteContent = $dataset->getTable("note");
        $this->assertTablesEqual($expectedNoteContent, $noteTable);

    }
}
