<?php
namespace Braindump\Api\Test\Unit;

use Braindump\Api\Model\Note as Note;

class NoteTest extends \PHPUnit\Framework\TestCase
{
    protected $note;

    protected function setup(): void
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

        $this->markTestSkipped('testInlineImage skipped');

        $string = '<img src="data:image/png;base64,
iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAABGdBTUEAALGP
C/xhBQAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9YGARc5KB0XV+IA
AAAddEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIFRoZSBHSU1Q72QlbgAAAF1J
REFUGNO9zL0NglAAxPEfdLTs4BZM4DIO4C7OwQg2JoQ9LE1exdlYvBBeZ7jq
ch9//q1uH4TLzw4d6+ErXMMcXuHWxId3KOETnnXXV6MJpcq2MLaI97CER3N0
vr4MkhoXe0rZigAAAABJRU5ErkJggg==" alt="test" />';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
    }

    public function testUrlImage() {

        $string = '<img src="test" alt="test">';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
    }

    public function testHTML5FigureElement() {

        $string = '<figure><img src="test" alt="test"><figcaption>caption</figcaption></figure>';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());

    }

    // public function testTrixFigureElement() {

    //     $string = '<figure data-trix-attachment="test" data-trix-content-type="image"><img src="test" width="541" height="167"><figcaption class="caption"></figcaption></figure>';

    //     $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
    //     $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

    //     $notebook = (object)['id' => 42];
    //     $this->note->map($notebook, $input);
    //     $this->assertEquals($output, $this->note->as_array());
    // }

    /***
      In pre-HTML5 only inline are considered valid in anchors.
      In HTML5, anchors can contain block level elements also
      Valid in HTML5: 

      <a href="/test"><div>content</div></a>

      HTMLPurifier will be default validate according to pre-HTML5 
      and convert:

      <a href="/test"><div>content</div></a>

      to:

      <a href="/test"></a><div><a href="/test">content</a></div><a href="/test"></a>

      https://www.w3.org/TR/html-markup/a.html#a-changes
      Note: * pre-HTML5 terms: inline vs block
            * HTML5 terms: phrasing vs flowing

     */
    public function testAnchorWithBlockLevelElement() {
    
        $string = '<a href="/test"><div>content</div></a>';

        $input = (object)[ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string ];
        $output = [ 'title' => 'Note title', 'type' => 'HTML', 'content' => $string, 'notebook_id' => 42 ];

        $notebook = (object)['id' => 42];
        $this->note->map($notebook, $input);
        $this->assertEquals($output, $this->note->as_array());
        

    }
}
