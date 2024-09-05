<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ResourceNotFoundException;
use App\Response\ErrorGeneralResponse;
use App\Response\ErrorNotFoundResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiCustomResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(public ParameterBagInterface $parameterBag)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'generateCustomError',
        ];
    }

    public function generateCustomError(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException || $exception instanceof ResourceNotFoundException) {
            $this->generateNotFoundError($event);

            return;
        }

        $event->setResponse(
            new ErrorGeneralResponse(
                message: 'error_general',
                details: ['description' => $exception->getMessage()],
            )
        );
    }

    private function generateNotFoundError(ExceptionEvent $event): void
    {
        $details = [];

        $exception = $event->getThrowable();

        if ($exception instanceof ResourceNotFoundException) {
            $details = ['description' => $exception->getMessage()];

            $event->setResponse(new ErrorNotFoundResponse('not_found', Response::HTTP_NOT_FOUND, $details));
        }

        $event->setResponse(
            new ErrorNotFoundResponse(
                'not_found',
                Response::HTTP_NOT_FOUND,
                $details
            )
        );
    }
}
