<?php namespace Braindump\Api\Test\Integration;

require_once(__DIR__ . '/../../controllers/UserConfigurationController.php');

class UserConfigurationRoutesTest extends Slim_Framework_TestCase
{
    public function setup()
    {
        parent::setUp();
        $this->controller = new \Braindump\Api\Controller\User\UserConfigurationController($this->container);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/configuration-seed.xml');
    }

    public function testGetConfiguration()
    {
        $expected = file_get_contents(dirname(__FILE__).'/files/get-configuration-expected-1.json');

        $response = $this->controller->getConfiguration($this->getRequestMock(), new \Slim\Http\Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($expected, (string)$response->getBody());
    }

}
