<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\StatusTrait;
use App\Entity\Traits\TimeStampTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * UsersProfiles
 *
 * @ORM\Table(name="users_profiles", indexes={@ORM\Index(name="fk_users_profiles_users_idx", columns={"users_id"}), @ORM\Index(name="fk_users_profiles_media1", columns={"media_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UsersProfiles
{
    use TimeStampTrait, IdTrait, StatusTrait;

    /**
     * @var string|null
     *
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     */
    private $fullName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone_number", type="string", length=15, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var \Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="users_id", referencedColumnName="id")
     * })
     */
    private $users;

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): self
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->dateCreated = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->dateUpdated = new \DateTime();
    }
}
