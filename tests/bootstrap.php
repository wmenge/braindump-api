<?php namespace Braindump\Api;

// replace with slims default helper (except for export)
function outputJson($data, $response)
{
    // JSON_NUMERIC_CHECK is needed as PDO will return strings
    // as default (even if DB schema defines numeric types).
    // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
    // TODO: replace with proper rendering engine?
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
    return $response;
}

namespace Braindump\Api\Controller\Admin;

function base64_encode($content)
{
    return $content;
}

function filesize($filename)
{
    return 1;
}

function md5_file($filename) {
    return '1';
}

function finfo_file($resource, $path) {
    return 'application/pdf';
}

$unique_id = 0;

function uniqid() {
    global $unique_id;
    $unique_id++;
    return 'unique_name' . $unique_id;
    
}

namespace Braindump\Api\Model;

function file_get_contents($filename)
{
    return 'content';
}

// Mock Sentry class 
class SentryFacadeMock
{
    public static $id = 1;

    public static function getUser()
    {
        return SentryFacadeMock::findUserById(SentryFacadeMock::$id);
    }

    public static function findUserById($id)
    {
        //$mockUser = new \stdClass();
        $mockUser = \Mockery::mock();
        $mockUser->shouldReceive('getGroups')->andReturn([((object)['name' => 'Administrators'])]);

        $mockConfigurationInstance = \Mockery::mock();
        $mockConfigurationInstance->shouldReceive('as_array')->andReturn(['email_to_notebook' => '1']);

        $mockConfiguration = \Mockery::mock();
        $mockConfiguration->shouldReceive('find_one')->andReturn($mockConfigurationInstance);

        $mockUser->shouldReceive('configuration')->andReturn($mockConfiguration);
    
        $mockUser->id = $id;
        return $mockUser;
    }

    public static function check()
    {
        return true;
    }

    public static function createGroup($groupArrray) {
        
        $group = \Cartalyst\Sentry\Groups\Paris\Group::create();
        $group->hydrate($groupArrray);
        $group->save();

        return $group;
    }

    public static function createUser($userArray) {

        $user = \Cartalyst\Sentry\Users\Paris\User::create();
        $user->hydrate($userArray);
        $user->save();

        return $user;
    }

    public static function findGroupByName($name) {
        return \Cartalyst\Sentry\Groups\Paris\Group::where_equal('name', $name)->find_one();
    }
}

namespace Braindump\Api\Test\Integration;

// Todo: Convert to mock that can be asserted to be called with error or success mesagges
class FlashMessagesMock
{
    public static function addMessage($arg1, $arg2) {

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

// TODO: REPLACE with https://github.com/there4/slim-test-helpers
error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/DatabaseFacade.php';

use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;

abstract class AbstractDbTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private static $conn = null;
    protected $dbFacade = null;

    protected function setUp()
    {
        $this->dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
            \ORM::get_db(),
            (require( __DIR__ . '/../migrations/migration-config.php')));
        $this->dbFacade->createDatabase();
        
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


//https://akrabat.com/testing-slim-framework-actions/
abstract class Slim_Framework_TestCase extends AbstractDbTest
{
    // Run for each unit test to setup our slim app environment
    public function setup()
    {
        // Initialize our own copy of the slim application
        $app = new \Slim\App([
            'version'        => '0.0.0',
            'debug'          => false,
            'mode'           => 'testing',
        ]);
        
        $this->container = $app->getContainer();
        $this->container['renderer'] = new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/');
        $this->container['flash'] = function () { return new FlashMessagesMock(); };
        
        parent::setUp();
    }

    protected function getRequestMock($headers = []) {
        $environment = \Slim\Http\Environment::mock($headers);
        $body = new RequestBody();
        return Request::createFromEnvironment($environment)->withBody($body);
    }
}
