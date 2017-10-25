<?php
require '../vendor/autoload.php';

require '../lib/SentryFacade.php';
class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');

// Setup DB connection
$braindumpConfig = (require __DIR__ . '/../config/braindump-config.php');

ORM::configure($braindumpConfig['database_config']);

$user = \Sentry::findUserByLogin('administrator@braindump-api.local');
$user->password = 'welcome';
$user->save();

$throttle = \Sentry::findThrottlerByUserId($user->id);
$throttle->unsuspend();
$throttle->unban();