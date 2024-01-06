<?php

namespace App\Entity;

use App\Entity\Traits\EntityEmail;
use App\Entity\Traits\IdTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuthenticationAttempts
 *
 * @ORM\Table(name="authentication_attempts")
 * @ORM\Entity
 */
class AuthenticationAttempts
{
    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var int|null
     *
     * @ORM\Column(name="attempts", type="integer", nullable=true)
     */
    private $attempts;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_attempt_date", type="datetime", nullable=false)
     */
    private $lastAttemptDate;

    public function getAttempts(): ?int
    {
        return $this->attempts;
    }

    public function setAttempts(?int $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLastAttemptDate(): ?\DateTimeInterface
    {
        return $this->lastAttemptDate;
    }

    public function setLastAttemptDate(\DateTimeInterface $lastAttemptDate): self
    {
        $this->lastAttemptDate = $lastAttemptDate;

        return $this;
    }


}
