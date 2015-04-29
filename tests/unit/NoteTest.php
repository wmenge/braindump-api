<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/Note.php';

use Braindump\Api\Model\Note as Note;

class NoteTest extends \PHPUnit_Framework_TestCase
{
    protected $note;

    protected function setUp()
    {
        $this->note = Note::create();
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, Note::isValid($model));
    }

    public function isValidProvider()
    {
        return [
            [null, false],
            [42, false],
            ['Invalid string', false],
            [['field' => 'an array with a string'], false],
            [(object)['title' => 42], false],
            [(object)['title' => 'A title'], false],
            [(object)['title' => 'A title', 'type' => 'non existing type'], false],
            [(object)['property' => 'A property', 'type' => 'HTML'], false],
            [(object)['title' => 'A title', 'type' => 'HTML'], true],
            [(object)['title' => 'A title', 'type' => 'Text'], true],
            //[(object)['title' => 'A title', 'type' => 'Text', 'url' => 'invalid url'], false],
            [(object)['title' => 'A title', 'type' => 'Text', 'url' => 'http://braindump.com'], true],
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
    }

    public function mapProvider()
    {
        return [
            [null, []],
            [42, []],
            ['Invalid string', []],
            [['field' => 'an array with a string'], []],
            [(object)['field' => 'an obect with an incorrect property'], []],
            [(object)['title' => 42], []],
            // Valid object with text content
            [(object)['title' => 'Note title', 'type' => 'Text', 'url' => 'http://t.com', 'content' => 'Sample content'],
             ['title' => 'Note title', 'type' => 'Text', 'notebook_id' => 42,
                      'url' => 'http://t.com', 'content' => 'Sample content']],
            // Valid object with HTML content
            [(object)['title' => 'Note title', 'type' => 'HTML', 'url' => 'http://t.com', 'content' => '<div>Sample content</div>'],
             ['title' => 'Note title', 'type' => 'HTML', 'notebook_id' => 42,
                      'url' => 'http://t.com', 'content' => '<div>Sample content</div>']]
        ];
    }

    public function testInlineImage() {

        $string = '<img src="data:image/png;base64,
iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABGdBTUEAALGP
C/xhBQAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9YGARc5KB0XV+IA
AAAddEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIFRoZSBHSU1Q72QlbgAAAF1J
REFUGNO9zL0NglAAxPEfdLTs4BZM4DIO4C7OwQg2JoQ9LE1exdlYvBBeZ7jq
ch9//q1uH4TLzw4d6+ErXMMcXuHWxId3KOETnnXXV6MJpcq2MLaI97CER3N0
vr4MkhoXe0rZigAAAABJRU5ErkJggg==" alt="Red dot" />';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => str_replace(PHP_EOL, '', $string), 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
    }

    public function testUrlImage() {

        $string = '<img src="http://ic.tweakimg.net/ext/i/imagenormal/2000606770.jpeg" alt="Red dot" />';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
    }
}
