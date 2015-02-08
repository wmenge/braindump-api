<?php namespace Braindump\Api\Test\Integration;

class AuthenticationTest extends Slim_Framework_TestCase
{

    public function setup()
    {
        // replace mock implementation of authentication middleware with real implementation
        //$this->authenticationImplementation = Slim_Framework_TestCase::REAL_AUTHENTICATION;

        //class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');

        // replacde mock implementation of Senty with real implementation
        //$sentryImplementation = Slim_Framework_TestCase::REAL_SENTRY;
        //class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');
    
        parent::setUp();
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/authentication-seed.xml');
    }

    public function testUnauthenticatedApiRequest()
    {
        $this->get('/api');
        $this->assertEquals(403, $this->response->status());
    }
}
