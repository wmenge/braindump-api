<?php

require '../lib/SentryFacade.php';

// TODO: Replace Capsule with own Idiorm/Paris based implementation

// Import the necessary classes
use Illuminate\Database\Capsule\Manager as Capsule;

// Create the Sentry alias
class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');

// Create a new Database connection
$capsule = new Capsule;

// move to sentry config file
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => '../data/braindump.sqlite3'
]);

$capsule->bootEloquent();

