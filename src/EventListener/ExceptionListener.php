<?php

namespace App\EventListener;

use App\Exception\BillingUnavailableException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = $exception->getMessage();

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        if($exception instanceof BillingUnavailableException) {
            $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            $message = "Сервис временно недоступен";
        }

        $data = [
            'code' => $statusCode,
            'message' => $message,
        ];

        $event->setResponse(new JsonResponse($data, $statusCode));
    }
}
