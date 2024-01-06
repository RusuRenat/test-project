<?php

namespace App\Entity;

use App\Entity\Traits\TimeStampTrait;
use App\Entity\Traits\DisplayNameTrait;
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\NameTrait;
use App\Entity\Traits\StatusTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Roles
 *
 * @ORM\Table(name="roles", uniqueConstraints={@ORM\UniqueConstraint(name="display_name_UNIQUE", columns={"display_name"}), @ORM\UniqueConstraint(name="name_UNIQUE", columns={"name"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Roles
{
    use IdTrait, StatusTrait, NameTrait, DisplayNameTrait, TimeStampTrait;
}