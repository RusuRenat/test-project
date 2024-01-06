<?php

namespace App\EventListener;

use App\Utils\Constants\Utils;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;

class AuthenticationErrorListener
{

    public function onInvalidToken(JWTInvalidEvent $event): void
    {
        $event->getResponse()->setContent(
            json_encode(self::$errorMessage)
        );
    }

    public function onExpiredToken(JWTExpiredEvent $event): void
    {
        $event->getResponse()->setContent(
            json_encode(self::$errorMessage)
        );
    }

    public function onNotFoundToken(JWTNotFoundEvent $event): void
    {
        $event->getResponse()->setContent(
            json_encode(self::$errorMessage)
        );
    }

    private static array $errorMessage = ["error" => Utils::NOT_AUTHORIZED];
}
