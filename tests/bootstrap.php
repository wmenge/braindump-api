<?php
namespace Braindump\Api;

function outputJson($data, $app)
{
    // JSON_NUMERIC_CHECK is needed as PDO will return strings
    // as default (even if DB schema defines numeric types).
    // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
    // todo: replace with proper rendering engine?
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response()->body(json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
}

namespace Braindump\Api\Admin;

function outputJson($data, $app)
{
    \Braindump\Api\outputJson($data, $app);
}

namespace Braindump\Api\Model;

// Mock Sentry class
class SentryFacadeMock {

    static function getUser()
    {
        return SentryFacadeMock::findUserById(1);
    }

    static function findUserById($id)
    {
        $mockUser = new \stdClass();
        $mockUser->id = $id;
        return $mockUser;
    }
}

// Create the Sentry alias
class_alias('Braindump\Api\Model\SentryFacadeMock', 'Sentry');

namespace Braindump\Api\Test\Integration;

//
// Unit Test Bootstrap and Slim PHP Testing Framework
// =============================================================================
//
// SlimpPHP is a little hard to test - but with this harness we can load our
// routes into our own `$app` container for unit testing, and then `run()` and
// hand a reference to the `$app` to our tests so that they have access to the
// dependency injection container and such.
//
// * Author: [Craig Davis](craig@there4development.com)
// * Since: 10/2/2013
//
// -----------------------------------------------------------------------------


error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/DatabaseHelper.php';

abstract class AbstractDbTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private static $conn = null;

    protected function setUp()
    {
        $mockApp = new \stdClass();
        $mockApp->braindumpConfig = (require( __DIR__ . '/../config/braindump-config.php'));
        $dbHelper = new \Braindump\Api\Lib\DatabaseHelper($mockApp, \ORM::get_db());
        $dbHelper->createDatabase();
        
        parent::setUp();
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (self::$conn === null) {
            \ORM::configure([ 'connection_string' => 'sqlite::memory:' ]);
            self::$conn = $this->createDefaultDBConnection(\ORM::get_db(), ':memory:');
        }
        
        return self::$conn;
    }
}

abstract class Slim_Framework_TestCase extends AbstractDbTest
{
    // We support these methods for testing. These are available via
    // `this->get()` and `$this->post()`. This is accomplished with the
    // `__call()` magic method below.
    private $testingMethods = array('get', 'post', 'patch', 'put', 'delete', 'head');

    // Run for each unit test to setup our slim app environment
    public function setup()
    {
        // Initialize our own copy of the slim application
        $app = new \Slim\Slim(array(
            'version'        => '0.0.0',
            'debug'          => false,
            'mode'           => 'testing'
        ));

        $app->braindumpConfig = (require( __DIR__ . '/../config/braindump-config.php'));

        // Include our core application file
        // require __DIR__ . '/../public/index.php'
        require __DIR__ . '/../routes/admin.php';
        require __DIR__ . '/../routes/note.php';
        require __DIR__ . '/../routes/notebook.php';

        // Establish a local reference to the Slim app object
        $this->app = $app;

        parent::setUp();

    }

    // Abstract way to make a request to SlimPHP, this allows us to mock the
    // slim environment
    private function request($method, $path, $formVars, $optionalHeaders = array())
    {
        // Capture STDOUT
        ob_start();

        if (is_array($formVars)) {
            $input = http_build_query($formVars);
        } elseif (is_string($formVars)) {
            $input = $formVars;
        }

        // separate querystring from route
        $querystring = '';
        if (strpos($path, '?') !== false) {
            list($path, $querystring) = explode("?", $path);
        }
        // Prepare a mock environment
        \Slim\Environment::mock(array_merge(array(
            'REQUEST_METHOD' => strtoupper($method),
            'PATH_INFO'      => $path,
            'SERVER_NAME'    => 'local.dev',
            //'slim.input'     => http_build_query($formVars)
            'slim.input'     => $input,
            'QUERY_STRING'   => $querystring
        ), $optionalHeaders));

        // Establish some useful references to the slim app properties
        $this->request  = $this->app->request();
        $this->response = $this->app->response();

        // Execute our app
        $this->app->run();

        // Return the application output. Also available in `response->body()`
        return ob_get_clean();
    }

    // Implement our `get`, `post`, and other http operations
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->testingMethods)) {
            list($path, $formVars, $headers) = array_pad($arguments, 3, array());
            return $this->request($method, $path, $formVars, $headers);
        }
        throw new \BadMethodCallException(strtoupper($method) . ' is not supported');
    }
}

/* End of file bootstrap.php */