<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/NotebookFacade.php';

class NotebookFacadeTest extends \PHPUnit_Framework_TestCase
{
    protected $facade;

    protected function setUp()
    {
        $dbFacade = $this->getMockBuilder('\Braindump\Api\Lib\DatabaseFacade')
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->facade = new \Braindump\Api\Model\NotebookFacade($dbFacade);
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, $this->facade->isValid($model));
    }

    public function isValidProvider()
    {
        return [
            [null, false],
            [42, false],
            ['Invalid string', false],
            [['field' => 'an array with a string'], false],
            [(object)['field' => 'an obect with an incorrect property'], false],
            [(object)['title' => 42], false],
            [(object)['title' => 'a notebook with a title'], true],
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $mockNotebook = (object)[];
        $this->facade->map($mockNotebook, $input);
        $this->assertEquals($output, $mockNotebook);
    }

    public function mapProvider()
    {
        return [
            [null, (object)[]],
            [42, (object)[]],
            ['Invalid string', (object)[]],
            [['field' => 'an array with a string'], (object)[]],
            [(object)['field' => 'an obect with an incorrect property'], (object)[]],
            [(object)['title' => 42], (object)[]],
            [(object)['title' => 'Notebook title'], (object)['title' => 'Notebook title', 'created' => 0, 'updated' => 0, 'user_id' => 1]],
        ];
    }
}
