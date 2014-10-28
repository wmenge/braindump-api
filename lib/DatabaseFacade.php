<?php

namespace Braindump\Api\Lib;

class DatabaseFacade
{
    private $db = null;
    private $app = null;

    public function __construct($app, $db)
    {
        $this->db = $db;
        $this->app = $app;
    }

    public function createDatabase()
    {
        $this->deleteDatabase();
        $this->createMigrationTable();

        $scripts = $this->app->braindumpConfig['databases_setup_scripts'];

        // For initial setup, just run all scripts
        foreach ($scripts as $version => $script) {
            $this->runSqlScript($script);
            
            $migration = \ORM::for_table('migration')->create();
            $migration->version = $version;
            $migration->executed = time();
            $migration->save();
        }
    }

    public function migrateDatabase()
    {
        $currentVersion = $this->getCurrentVersion();
        $scripts = $this->app->braindumpConfig['databases_setup_scripts'];

        $this->createMigrationTable();

        // TODO: Integration tests for each consecutive migration scenario
        foreach ($scripts as $version => $script) {

            if ($version <= $currentVersion) {
                continue;
            }

            $this->runSqlScript($script);
            
            $migration = \ORM::for_table('migration')->create();
            $migration->version = $version;
            $migration->executed = time();
            $migration->save();
        }
    }

    public function isMigrationNeeded()
    {
        return $this->getCurrentVersion() < $this->getHighestVersion();
    }

    public function getCurrentVersion()
    {
        try {
            return \ORM::for_table('migration')->max('version');
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getHighestVersion()
    {
        $versions = array_keys($this->app->braindumpConfig['databases_setup_scripts']);
        return max($versions);
    }

    private function deleteDatabase()
    {
        $this->runSqlScript($this->app->braindumpConfig['drop_tables_script']);
    }

    private function createMigrationTable()
    {
        $this->runSqlScript($this->app->braindumpConfig['migration_table_script']);
    }

    private function runSqlScript($script)
    {
        $sql = @file_get_contents($script);
        if ($sql === false) {
            throw new \Exception("File not found", 1);
        }
        $this->db->exec($sql);
    }

    /***
	 * Facade function to parse a sort querystring expression.
	 *
	 * Converts: "-title,type"
	 *
	 * to:
	 * [
	 *     { field: "title", order: SORT_DESC },
	 *     { field: "type", order: SORT_ASC }
	 * ]
	 *
	 * Does not check if fieldnames actually exist
	 */
    private function parseSortExpression($sortString)
    {
        $expressions = array();
        $tokens = mb_split(',', $sortString);

        foreach ($tokens as $token) {

            $trimmedToken = trim($token);
            $firstChar = mb_substr($trimmedToken, 0, 1);

            $expression = new \stdClass();

            if ($firstChar == '-') {
                $expression->field = trim(mb_substr($trimmedToken, 1));
                $expression->order = SORT_DESC;
            } else {
                $expression->field = $trimmedToken;
                $expression->order = SORT_ASC;
            }

            if (!empty($expression->field)) {
                $expressions[] = $expression;
            }
        }

        return $expressions;
    }

    /***
     *
     * Adds sort expression to given $query object
     *
     */
    public function addSortExpression($query, $sortString)
    {
        $sortList = $this->parseSortExpression($sortString);
        if (empty($sortList)) {
            return $query;
        }

        foreach ($sortList as $expression) {
            switch ($expression->order) {
                case SORT_ASC:
                $query = $query->order_by_asc($expression->field);
                break;
                default:
                $query = $query->order_by_desc($expression->field);
                break;
            }
        }

        return $query;
    }
}
