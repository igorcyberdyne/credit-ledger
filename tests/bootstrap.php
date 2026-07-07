<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// sécurité
if ('test' != $_ENV['APP_ENV']) {
    throw new RuntimeException('Bootstrap de test uniquement en env=test');
}

$commands = [
    ['php', 'bin/console', 'doctrine:database:drop', '--force', '--if-exists', '--env=test'],
    ['php', 'bin/console', 'doctrine:database:create', '--env=test'],
    ['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction', '--env=test'],
    ['php', 'bin/console', 'doctrine:fixtures:load', '--no-interaction', '--env=test'],
];

foreach ($commands as $cmd) {
    $process = new Process($cmd);
    $process->setTimeout(300);
    $process->mustRun();
}

echo "\n[OK] Test database ready\n\n";
