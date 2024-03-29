<?php namespace Braindump\Api\Test\Integration;

use Braindump\Api\Model\Notebook as Notebook;

class NoteFacadeTest extends AbstractDbTest
{
    protected $Facade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Facade = new \Braindump\Api\Model\NoteFacade();

        \Sentry::$id = 1;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/notes-seed.xml');
    }

    // TODO: add test to retrieve notes of different user
    public function testGetNoteList()
    {
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '3', 'title' => 'note 3', 'notebook_id' => '2', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        $this->assertEquals($expected, $this->Facade->getNoteList());
    }

    public function testGetNoteListForDifferentUser()
    {
        // Switch mock to different user
        \Sentry::$id = 2;

         $expected = [
            [ 'id' => '4', 'title' => 'note 4', 'notebook_id' => '3', 'created' => '0', 'updated' => '0', 'url' => ''],
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
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        // Mock notebook as parameter for method getNoteListForNotebook()
        $notebook = Notebook::create();
        $notebook->id = 1;

        $this->assertEquals($expected, $this->Facade->getNoteListForNotebook($notebook));
    }

    public function testSortedGetNoteListForNotebook()
    {
        $expected = [
            [ 'id' => '2', 'title' => 'note 2', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => '' ],
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => '' ],
        ];

        // Mock notebook as parameter for method getNoteListForNotebook()
        $notebook = Notebook::create();
        $notebook->id = 1;

        $this->assertEquals($expected, $this->Facade->getNoteListForNotebook($notebook, '-title'));
    }

    public function testFilteredGetNoteListForNotebook()
    {
        $expected = [
            [ 'id' => '1', 'title' => 'note 1', 'notebook_id' => '1', 'created' => '0', 'updated' => '0', 'url' => null],
        ];

        // Mock notebook as parameter for method getNoteListForNotebook()
        $notebook = Notebook::create();
        $notebook->id = 1;

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
        $this->assertEquals(null, $this->Facade->getNoteForId(99));
    }

    public function testGetNoteForDifferentUser()
    {
        $this->assertEquals(null, $this->Facade->getNoteForId(4));
    }
}
