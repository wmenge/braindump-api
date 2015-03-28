<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/UserConfiguration.php';

use Braindump\Api\Model\UserConfiguration as UserConfiguration;

class UserConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = UserConfiguration::create();
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
            [(object)['field' => 'an obect with an incorrect property'], false],
            [(object)['email_to_notebook' => 'correct field with incorrect type'], false],
            [(object)['email_to_notebook' => 42], true],
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $this->configuration->map($input);
        $this->assertEquals($output, $this->configuration->as_array());
    }

    public function mapProvider()
    {
        return [
            [null, []],
            [42, []],
            ['Invalid string', []],
            [['field' => 'an array with a string'], []],
            [(object)['field' => 'an obect with an incorrect property'], []],
            [(object)['email_to_notebook' => 'correct field with incorrect type'], []],
            [(object)['email_to_notebook' => 42], ['email_to_notebook' => 42]],
        ];
    }
}
