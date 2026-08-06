<?php

namespace App\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Stopwatch\Stopwatch;

class TaskExecutor implements TaskExecutorInterface
{
    private string $taskName;
    private \Closure $task;
    private array $context = [];
    private string $category = 'app';
    private bool $enableTaskExecutor;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Stopwatch $stopwatch,
        #[Autowire('%app.enable_task_executor%')]
        public readonly int $defaultEnableTaskExecutor,
    ) {
        $this->enableTaskExecutor = 1 === $defaultEnableTaskExecutor;
    }

    /**
     * Configure un callable en journalisant son début, sa fin et ses éventuelles erreurs.
     *
     * @template T
     *
     * @param array<string, mixed> $context
     * @param string|null          $category catégorie affichée dans la timeline du profiler Symfony
     */
    public function configure(
        \Closure $task,
        string $taskName,
        ?array $context = null,
        ?string $category = null,
    ): TaskExecutor {
        $taskName = basename(str_replace('\\', '/', $taskName));

        $this->taskName = $taskName;
        $this->task = $task;
        $this->context = $context ?? $this->context;
        $this->category = $category ?? $this->category;

        return $this;
    }

    public function setEnableTaskExecutor(bool $enableTaskExecutor): TaskExecutor
    {
        $this->enableTaskExecutor = $enableTaskExecutor;

        return $this;
    }

    /**
     * Exécute un callable.
     *
     * Le Stopwatch alimente la timeline du profiler Symfony (dev),
     * tandis que microtime() garantit une mesure fiable de la durée
     * même en prod si le profiler est désactivé (Stopwatch no-op).
     *
     * @template T
     *
     * @throws \Throwable relance systématiquement l'exception d'origine après journalisation
     */
    public function execute(): mixed
    {
        if (!$this->enableTaskExecutor) {
            return null;
        }

        $this->logger->info(sprintf('[%s] Démarrage', $this->taskName), $this->context);

        $startedAt = microtime(true);
        $startMemory = memory_get_usage(true);

        $event = $this->stopwatch->start($this->taskName, $this->category);

        try {
            $result = call_user_func($this->task);

            $event->stop();

            $this->logger->info(
                sprintf('[%s] Terminé avec succès (%s)', $this->taskName, $this->formatMetrics($startedAt, $startMemory)),
                $this->context
            );

            return $result;
        } catch (\Throwable $exception) {
            if ($event->isStarted()) {
                $event->stop();
            }

            $this->logger->error(
                sprintf(
                    '[%s] Échec après %s : %s',
                    $this->taskName,
                    $this->formatMetrics($startedAt, $startMemory),
                    $exception->getMessage()
                ),
                [
                    ...$this->context,
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
