<?php

use BudgetPlanner\Lib\DatabaseFacade;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;

return [
    'settings' => (require __DIR__ . '/braindump-config.php'),
    'migrations' => (require  __DIR__ . '/../migrations/migration-config.php'),
    'renderer' => new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/', []),
    'flash' => new \Slim\Flash\Messages(),
];