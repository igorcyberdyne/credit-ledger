<?php

namespace App\Tools;

interface TaskExecutorInterface
{
    public function configure(
        \Closure $task,
        string $taskName,
        ?array $context = null,
        ?string $category = null,
    ): TaskExecutor;

    public function setEnableTaskExecutor(
        bool $enableTaskExecutor,
    ): TaskExecutor;

    public function execute(): mixed;
}
