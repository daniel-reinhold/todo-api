<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $response = new JsonResponse(
                data: [
                    'status' => $exception->getStatusCode(),
                    'message' => $exception->getMessage()
                ],
                status: $exception->getStatusCode()
            );

            $event->setResponse($response);
        }
    }
}