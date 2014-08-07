<?php

namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/AbstractDbTest.php';
require_once __DIR__ . '/../../model/NoteHelper.php';

class NoteHelperDbTest extends AbstractDbTest
{
    protected $helper;

    protected function setUp()
    {
        parent::setUp();
        $this->helper = new \Braindump\Api\Model\NoteHelper(new \Braindump\Api\Lib\DatabaseHelper());
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    public function testGetNoteList()
    {
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '3', 'title' => 'note 3', 'notebook_id' => '2', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteList());
    }

    public function testSortedGetNoteList()
    {
        $expected = [
            [ 'id' => '3', 'title' => 'note 3', 'notebook_id' => '2', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteList('-title'));
    }

    public function testFilteredGetNoteList()
    {
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteList('', 'note 1'));
    }

    public function testGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteListForNotebook($notebook));
    }

    public function testSortedGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteListForNotebook($notebook, '-title'));
    }

    public function testFilteredGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->helper->getNoteListForNotebook($notebook, '', 'note 1'));
    }

    public function testGetNoteForId()
    {
        $expected = [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null,
                      'type' => 'Text', 'content' => 'Note content' ];
        $this->assertEquals($expected, $this->helper->getNoteForId(1)->as_array());
    }

    public function testGetNonExistingNoteForId()
    {
        $this->assertEquals(null, $this->helper->getNoteForId(4));
    }
}
