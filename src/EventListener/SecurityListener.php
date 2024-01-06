<?php

namespace App\EventListener;

use App\Controller\ApiController;
use App\Utils\Constants\Utils;
use App\Utils\Module\Security\SecurityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class SecurityListener
{

    /**
     * @var SecurityManager
     */
    private SecurityManager $securityManager;

    public function __construct(SecurityManager $securityManager)
    {
        $this->securityManager = $securityManager;
    }

    public function onKernelController(ControllerEvent $event)
    {

        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof ApiController) {

            if (!str_contains($event->getRequest()->getPathInfo(), 'secured')) {
                return;
            }

            $isAuthorized = $this->securityManager->isAuthorized();

            $response = $controller[0]->setResponse(Utils::NOT_AUTHORIZED, Response::HTTP_UNAUTHORIZED);

            if (!$isAuthorized->hasAccess) {
                $event->setController(function () use ($response) {
                    return $response;
                });
            } else {
                $event->getRequest()->attributes->set('authParams', $isAuthorized);
            }
        }

    }

}
