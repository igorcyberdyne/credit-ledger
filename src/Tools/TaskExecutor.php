<?php

namespace App\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final readonly class TaskExecutor
{
    public function __construct(
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
    ) {
    }

    /**
     * Exécute un callable en journalisant son début, sa fin et ses éventuelles erreurs.
     *
     * Le Stopwatch alimente la timeline du profiler Symfony (dev),
     * tandis que microtime() garantit une mesure fiable de la durée
     * même en prod si le profiler est désactivé (Stopwatch no-op).
     *
     * @template T
     *
     * @param callable(): T        $task
     * @param array<string, mixed> $context
     * @param string               $category catégorie affichée dans la timeline du profiler Symfony
     *
     * @throws \Throwable relance systématiquement l'exception d'origine après journalisation
     */
    public function run(string $taskName, callable $task, array $context = [], string $category = 'app'): mixed
    {
        $this->logger->info(sprintf('[%s] Démarrage', $taskName), $context);

        $startedAt = microtime(true);
        $startMemory = memory_get_usage(true);

        $event = $this->stopwatch->start($taskName, $category);

        try {
            $result = $task();

            $event->stop();

            $this->logger->info(
                sprintf('[%s] Terminé avec succès (%s)', $taskName, $this->formatMetrics($startedAt, $startMemory)),
                $context
            );

            return $result;
        } catch (\Throwable $exception) {
            if ($event->isStarted()) {
                $event->stop();
            }

            $this->logger->error(
                sprintf(
                    '[%s] Échec après %s : %s',
                    $taskName,
                    $this->formatMetrics($startedAt, $startMemory),
                    $exception->getMessage()
                ),
                [
                    ...$context,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            throw $exception;
        }
    }

    private function formatMetrics(float $startedAt, int $startMemory): string
    {
        $durationMs = (microtime(true) - $startedAt) * 1000;
        $memoryMio = (memory_get_usage(true) - $startMemory) / 1024 / 1024;

        return sprintf(
            '%s ms / %s Mio',
            number_format($durationMs, 2),
            number_format($memoryMio, 2)
        );
    }
}
