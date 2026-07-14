<?php

namespace App\EventListener\Infra;

use App\Exception\Domain\BusinessException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApiRestExceptionListener extends AbstractExceptionListener
{
    protected function retrieveResponseSetup(Response $response): Response
    {
        $details = [];
        $code = $status = $response->getStatusCode();
        $throwable = $this->event->getThrowable();
        $message = $throwable->getMessage();

        if ($throwable instanceof BusinessException) {
            $code = $throwable->getBusinessCode();
            $message = $throwable->getMessage();
            $details = $throwable->getDetails();
            if (!empty($throwable->getHttpStatus())) {
                $status = $throwable->getHttpStatus();
            }
        } elseif ($throwable instanceof AccessDeniedHttpException) {
            $message = "Vous n'avez pas les droits d'accès";
        } elseif ($code >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $message = Response::$statusTexts[$code] ?? 'Internal Server Error';
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
