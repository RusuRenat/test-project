<?php

namespace App\Entity;

use App\Entity\Traits\TimeStampTrait;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * RefreshTokens
 *
 * @ORM\Table(name="refresh_tokens")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class RefreshToken extends BaseRefreshToken
{
    use TimeStampTrait;
}
