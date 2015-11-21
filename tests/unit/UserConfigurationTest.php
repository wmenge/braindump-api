<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/UserConfiguration.php';

use Braindump\Api\Model\UserConfiguration as UserConfiguration;

class UserConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    protected function setUp()
    {
        $this->config = UserConfiguration::create();

        $notebookFacadeStub = $this->getMock('\Braindump\Api\Model\NotebookFacade', ['getNotebookForId']);
        
        // Create a map of arguments to return values.
        $map = [
          [41, null],                                                // non-existing notebook
          [42, (object)['id' => 42, 'title' => 'existing notebook']] // existing notebook
        ];

        // Configure the stub.
        $notebookFacadeStub->method('getNotebookForId')->will($this->returnValueMap($map));

        UserConfiguration::setNotebookFacade($notebookFacadeStub);
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, UserConfiguration::isValid($model));
    }

    public function isValidProvider()
    {
        return [
            [null, false],
            [42, false],
            ['Invalid string', false],
            [['field' => 'an array with a string'], false],
            [(object)['some_property' => 42], false],
            [(object)['some_property' => 'A string'], false],
            [(object)['email_to_notebook' => 'A string'], false],
            [(object)['email_to_notebook' => 41], false], // non-existing notebook
            [(object)['email_to_notebook' => 42], true]   // existing notebook
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $this->config->map($input);
        $this->assertEquals($output, $this->config->as_array());
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
            // Valid object
            [(object)['email_to_notebook' => 42],
            ['email_to_notebook' => 42]]
        ];
    }
}
