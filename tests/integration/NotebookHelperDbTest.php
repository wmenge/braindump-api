<?php
namespace Braindump\Api\Model;

// Mocking standard time function to be able to compare DB content
function time()
{
    return 0;
}

namespace Braindump\Api\Test\Integration;

require_once __DIR__ . '/../../model/NotebookHelper.php';

class NotebookHelperDbTest extends AbstractDbTest
{
    protected $helper;

    protected function setUp()
    {
        parent::setUp();
        $this->helper = new \Braindump\Api\Model\NotebookHelper(new \Braindump\Api\Lib\DatabaseHelper());
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notebooks-seed.xml');
    }

    public function testGetNotebookList()
    {
        $expected = [
            [ 'id' => 1, 'title' => 'title 1', 'noteCount' => 0 ],
            [ 'id' => 2, 'title' => 'title 2', 'noteCount' => 0 ],
        ];

        $this->assertEquals($expected, $this->helper->getNoteBookList());
    }

    public function testSortedGetNotebookList()
    {
        $expected = [
            [ 'id' => 2, 'title' => 'title 2', 'noteCount' => 0 ],
            [ 'id' => 1, 'title' => 'title 1', 'noteCount' => 0 ],
        ];
        
        $this->assertEquals($expected, $this->helper->getNoteBookList('-title'));
    }

    public function testGetNotebookForId()
    {
        $expected = [ 'id' => 1, 'title' => 'title 1', 'noteCount' => 0 ];
        
        $this->assertEquals($expected, $this->helper->getNotebookForId(1)->as_array());
    }

    public function testGetNonExistingNotebookForId()
    {
        $this->assertEquals(null, $this->helper->getNotebookForId(3));
    }

    public function testCreateSampleData()
    {
        $this->helper->createSampleData();

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
