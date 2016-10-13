<?php

namespace Braindump\Api\Lib;

// Refactor out DB config, as this is reallly not instance config, but application config
class DatabaseFacade
{
    private $db = null;
    public $config = null;

    public function __construct($db, $config)
    {
        //print_r($config);
        $this->db = $db;
        $this->config = $config;
    }

    public function createDatabase()
    {
        $this->deleteDatabase();
        $this->createMigrationTable();

        $scripts = $this->config['databases_setup_scripts'];

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
        $scripts = $this->config['databases_setup_scripts'];

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
        $versions = array_keys($this->config['databases_setup_scripts']);
        return max($versions);
    }

    private function deleteDatabase()
    {
        $this->runSqlScript($this->config['drop_tables_script']);
    }

    private function createMigrationTable()
    {
        $this->runSqlScript($this->config['migration_table_script']);
    }

    private function runSqlScript($script)
    {
        $sql = @file_get_contents($script);
        if ($sql === false) {
            throw new \Exception("File not found", 1);
        }
        $this->db->exec($sql);
    }
}
