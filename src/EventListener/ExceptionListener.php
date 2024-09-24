<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $code = $event instanceof HttpException ? 400 : 500;
        $message = $event->getThrowable()->getMessage();

        if ($code == 500) {
            $message = "Iternal server error";
        }

        $responseData = [
            "error" => [
                "code" => $code,
                "message" => $event->getThrowable()->getMessage(),
            ],
        ];

        $event->setResponse(new JsonResponse($responseData, $code));
    }
}
