<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// sécurité
if ('test' != $_ENV['APP_ENV']) {
    throw new RuntimeException('Bootstrap de test uniquement en env=test');
}

App\Tests\Tools\Database::resetDatabase();

date_default_timezone_set('UTC');

echo "\n[OK] Test database ready\n\n";
