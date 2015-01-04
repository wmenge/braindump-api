<?php

namespace Braindump\Api\Test\Integration;

require_once __DIR__ . '/../../model/NoteFacade.php';

class NoteFacadeDbTest extends AbstractDbTest
{
    protected $Facade;

    protected function setUp()
    {
        parent::setUp();

        $mockApp = new \stdClass();
        $mockApp->braindumpConfig = (require( __DIR__ . '/../../config/braindump-config.php'));
        $dbFacade = new \Braindump\Api\Lib\DatabaseFacade($mockApp, \ORM::get_db());
        
        $this->Facade = new \Braindump\Api\Model\NoteFacade($dbFacade);
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

        $this->assertEquals($expected, $this->Facade->getNoteList());
    }

    public function testSortedGetNoteList()
    {
        $expected = [
            [ 'id' => '3', 'title' => 'note 3', 'notebook_id' => '2', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteList('-title'));
    }

    public function testFilteredGetNoteList()
    {
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteList('', 'note 1'));
    }

    public function testGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteListForNotebook($notebook));
    }

    public function testSortedGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => '' ],
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => '' ],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteListForNotebook($notebook, '-title'));
    }

    public function testFilteredGetNoteListForNotebook()
    {
        $notebook = (object)[ 'id' => 1 ];

        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteListForNotebook($notebook, '', 'note 1'));
    }

    public function testGetNoteForId()
    {
        $expected = [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null,
                      'type' => 'Text', 'content' => 'Note content', 'user_id' => '1' ];
        $this->assertEquals($expected, $this->Facade->getNoteForId(1)->as_array());
    }

    public function testGetNonExistingNoteForId()
    {
        $this->assertEquals(null, $this->Facade->getNoteForId(4));
    }
}
