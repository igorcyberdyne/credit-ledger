<?php

namespace App\EventListener\Infra;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

abstract class AbstractExceptionListener
{
    public function __construct(
        protected readonly ExceptionEvent $event,
        protected readonly LoggerInterface $logger,
    ) {
    }

    abstract protected function retrieveResponseSetup(Response $response): Response;

    public function getResponse(): Response
    {
        $response = new Response();
        $throwable = $this->event->getThrowable();
        if ($throwable instanceof HttpExceptionInterface) {
            $response->setStatusCode($throwable->getStatusCode());
            $response->headers->replace($throwable->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->retrieveResponseSetup($response);
    }
}
