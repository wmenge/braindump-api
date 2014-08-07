<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/NoteHelper.php';

class NoteHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    protected function setUp()
    {
        $dbHelper = $this->getMock('\Braindump\Api\Lib\DatabaseHelper');
        $this->helper = new \Braindump\Api\Model\NoteHelper($dbHelper);
    }

    /**
     * @dataProvider testIsValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, $this->helper->isValid($model));
    }

    public function testIsValidProvider()
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
     * @dataProvider testMapProvider
     */
    public function testMap($input, $output)
    {
        $mockNote = (object)[];
        $mockNotebook = (object)['id' => 42];
        $this->helper->map($mockNote, $mockNotebook, $input);
        $this->assertEquals($output, $mockNote);
    }

    public function testMapProvider()
    {
        return [
            [null, (object)[]],
            [42, (object)[]],
            ['Invalid string', (object)[]],
            [['field' => 'an array with a string'], (object)[]],
            [(object)['field' => 'an obect with an incorrect property'], (object)[]],
            [(object)['title' => 42], (object)[]],
            // Valid object with text content
            [(object)['title' => 'Note title', 'type' => 'Text', 'url' => 'http://t.com', 'content' => 'Sample content'],
             (object)['title' => 'Note title', 'type' => 'Text', 'notebook_id' => 42,
                      'url' => 'http://t.com', 'content' => 'Sample content', 'created' => 0, 'updated' => 0, ]],
            // Valid object with HTML content
            [(object)['title' => 'Note title', 'type' => 'HTML', 'url' => 'http://t.com', 'content' => '<div>Sample content</div>'],
             (object)['title' => 'Note title', 'type' => 'HTML', 'notebook_id' => 42,
                      'url' => 'http://t.com', 'content' => '<div>Sample content</div>', 'created' => 0, 'updated' => 0, ]]
        ];
    }
}
