<?php

namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/DatabaseHelper.php';

abstract class AbstractDbTest extends \PHPUnit_Extensions_Database_TestCase
{
    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private static $conn = null;

    protected function setUp()
    {
        $dbHelper = new \Braindump\Api\Lib\DatabaseHelper();
        $dbHelper->createDatabase(\ORM::get_db(), [ '0.1' => __DIR__ . '/../../data/braindump.create.sqlite.sql']);
        
        parent::setUp();
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if (self::$conn === null) {
            \ORM::configure([ 'connection_string' => 'sqlite::memory:' ]);
            $dbHelper = new \Braindump\Api\Lib\DatabaseHelper();
            self::$conn = $this->createDefaultDBConnection(\ORM::get_db(), ':memory:');
        }
        
        return self::$conn;
    }
}
