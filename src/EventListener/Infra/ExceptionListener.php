<?php

namespace App\EventListener\Infra;

use App\Exception\Domain\BusinessException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener]
readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        protected RequestStack $requestStack,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $event->setResponse($this->getResponse($event));
    }

    private function getResponse(ExceptionEvent $event): Response
    {
        $throwable = $event->getThrowable();

        $this->logger->critical(sprintf(
            'Current error: %s with code: %s',
            $throwable->getMessage(),
            $throwable->getCode()
        ), $throwable->getTrace());

        if ($throwable instanceof BusinessException && !empty($throwable->getPrevious())) {
            $original = $throwable->getPrevious();
            $this->logger->critical(sprintf(
                'Original error: %s with code: %s',
                $original->getMessage(),
                $original->getCode()
            ), $original->getTrace());
        }

        return new ApiRestExceptionListener(
            $event,
            $this->logger,
        )->getResponse();
    }
}
