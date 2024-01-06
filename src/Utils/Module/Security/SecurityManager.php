<?php

namespace App\Utils\Module\Security;

use App\Entity\Roles;
use App\Entity\RolesHasPermissions;
use App\Utils\Constants\Users\UsersRoles;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityManager
{

    public ?UserInterface $requestUser;

    private $denyAccess;
    private RequestStack $requestStack;
    private EntityManager $em;

    public function __construct(EntityManager $em, $denyAccess, RequestStack $requestStack, Security $security)
    {
        $this->em = $em;
        $this->denyAccess = $denyAccess;
        $this->requestStack = $requestStack;
        $this->requestUser = $security->getUser();
    }

    public function isAuthorized(): object
    {

        // default return
        $return = (object)[
            'hasAccess' => false,
            'method' => false,
            'roles' => [],
            'permissions' => []
        ];

        if (!$this->requestUser) {
            return $return;
        }

        $return->roles = $this->requestUser->getRoles();
        if (!$return->roles) {
            return $return;
        }
        if (in_array(UsersRoles::SUPER_ADMIN, $return->roles, true) || in_array(UsersRoles::ADMIN, $return->roles, true)) {
            // is SUPER_ADMIN
            $return->hasAccess = true;
            return $return;
        }

        return $return;
    }

    public function isAdmin(): bool
    {
        if (in_array(UsersRoles::ADMIN, $this->requestUser->getRoles())) {
            return true;
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        if (in_array(UsersRoles::SUPER_ADMIN, $this->requestUser->getRoles())) {
            return true;
        }

        return false;
    }

    public function isAuthor(): bool
    {
        if (in_array(UsersRoles::AUTHOR, $this->requestUser->getRoles())) {
            return true;
        }

        return false;
    }

    public function isPublicAuthorized(): bool
    {
        if ($this->requestUser?->getRoles()) {
            return true;
        }
        return false;

    }

}
