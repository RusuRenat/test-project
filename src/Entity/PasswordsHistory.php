<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeStampTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PasswordsHistory
 *
 * @ORM\Table(name="passwords_history", indexes={@ORM\Index(name="fk_passwords_history_users1_idx", columns={"users_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PasswordsHistory
{
    use IdTrait, TimeStampTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="password_salt", type="string", length=255, nullable=false)
     */
    private $passwordSalt;

    /**
     * @var string
     *
     * @ORM\Column(name="password_hash", type="text", length=65535, nullable=false)
     */
    private $passwordHash;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_date", type="datetime", nullable=false)
     */
    private $passwordDate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="expire_notification_date", type="datetime", nullable=true)
     */
    private $expireNotificationDate;

    /**
     * @var \Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="users_id", referencedColumnName="id")
     * })
     */
    private $users;

    public function getPasswordSalt(): ?string
    {
        return $this->passwordSalt;
    }

    public function setPasswordSalt(string $passwordSalt): self
    {
        $this->passwordSalt = $passwordSalt;

        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getPasswordDate(): ?\DateTimeInterface
    {
        return $this->passwordDate;
    }

    public function setPasswordDate(\DateTimeInterface $passwordDate): self
    {
        $this->passwordDate = $passwordDate;

        return $this;
    }

    public function getExpireNotificationDate(): ?\DateTimeInterface
    {
        return $this->expireNotificationDate;
    }

    public function setExpireNotificationDate(?\DateTimeInterface $expireNotificationDate): self
    {
        $this->expireNotificationDate = $expireNotificationDate;

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

}
