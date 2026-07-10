<?php

namespace App\EventListener\Infra;

use App\Exception\Domain\BusinessException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiRestExceptionListener extends AbstractExceptionListener
{
    protected function retrieveResponseSetup(Response $response): Response
    {
        $status = $response->getStatusCode();
        $throwable = $this->event->getThrowable();
        if ($throwable instanceof BusinessException) {
            $code = $throwable->getBusinessCode();
            $message = $throwable->getMessage();
            $details = $throwable->getDetails();
            if (!empty($throwable->getHttpStatus())) {
                $status = $throwable->getHttpStatus();
            }
        } else {
            $code = $response->getStatusCode();
            $message = Response::$statusTexts[$code] ?? 'Internal Server Error';
            $details = [];
        }

        return new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status, $response->headers->all());
    }
}
