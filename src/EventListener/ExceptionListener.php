<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
            $event->setResponse(
                new JsonResponse("Iternal server error", $code)
            );
        }

        if ($event->getThrowable() instanceof BadRequestHttpException) {
            $event->setResponse(
                new JsonResponse(
                    [
                        "message" => $message,
                    ],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        // if ($code == 500) {
        //     $event->setResponse(
        //         new JsonResponse("Iternal server error", $code)
        //     );
        // } else {
        //     $responseData = [
        //         "error" => [
        //             "code" => $code,
        //             "message" => $event->getThrowable()->getMessage(),
        //         ],
        //     ];

        //     $event->setResponse(new JsonResponse($responseData, $code));
        // }
    }
}
