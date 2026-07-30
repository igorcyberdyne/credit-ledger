<?php

namespace App\Tests\Tools;

use Symfony\Component\Process\Process;

class Database
{
    public static function resetDatabase(): void
    {
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
    }
}
