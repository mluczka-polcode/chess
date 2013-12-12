<?php

namespace Acme\ChessBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Acme\ChessBundle\Exception\ChessException;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ChessException)
        {
            $response = new Response();
            $response->setContent($exception->getMessage());
            $response->setStatusCode($exception->getCode() == 1 ? 400 : 500);
            $event->setResponse($response);
        }
        else
        {
            throw new \Exception($exception->getMessage(), $exception->getStatusCode());
        }
    }
}
