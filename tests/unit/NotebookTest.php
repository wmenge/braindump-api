<?php
namespace Braindump\Api\Test\Unit;

use Braindump\Api\Model\Notebook as Notebook;

class NotebookTest extends \PHPUnit\Framework\TestCase
{
    protected $notebook;

    protected function setup(): void
    {
        $this->notebook = Notebook::create();
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, Notebook::isValid($model));
    }

    public function isValidProvider()
    {
        return [
            [null, false],
            [42, false],
            ['Invalid string', false],
            [['field' => 'an array with a string'], false],
            [(object)['field' => 'an obect with an incorrect property'], false],
            [(object)['title' => 42], true],
            [(object)['title' => 'a notebook with a title'], true],
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $this->notebook->map($input);
        $this->assertEquals($output, $this->notebook->as_array());
    }

    public function mapProvider()
    {
        return [
            [null, []],
            [42, []],
            ['Invalid string', []],
            [['field' => 'an array with a string'], []],
            [(object)['field' => 'an obect with an incorrect property'], []],
            [(object)['title' => 42], ['title' => '42']],
            [(object)['title' => 'Notebook title'], ['title' => 'Notebook title']],
        ];
    }
}
