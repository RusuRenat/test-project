<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Entity\Traits\StatusTrait;
use App\Entity\Traits\TimeStampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="username_UNIQUE", columns={"username"}), @ORM\UniqueConstraint(name="email_UNIQUE", columns={"email"})})
 * @ORM\Entity(repositoryClass="App\Repository\UsersRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait, TimeStampTrait, StatusTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=255, nullable=false)
     */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    private $passwordRequestedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(name="access_token", type="string", length=255, nullable=true)
     */
    private $accessToken;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="authentication_date", type="datetime", nullable=true)
     */
    private $authenticationDate;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="remember_me", type="boolean", nullable=true)
     */
    private $rememberMe;

    /**
     * @var string|null
     *
     * @ORM\Column(name="reset_password_token", type="string", length=255, nullable=true)
     */
    private $resetPasswordToken;

    /**
     * @var array|null
     *
     * @ORM\Column(name="roles", type="json", nullable=true)
     */
    private $roles;

    /**
     * @ORM\OneToOne(targetEntity="UsersProfiles", mappedBy="users", cascade={"persist", "remove"})
     */
    private $profile;

    /**
     * @return UsersProfiles|null
     */
    public function getProfile(): ?UsersProfiles
    {
        return $this->profile;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

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

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $passwordRequestedAt): self
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAuthenticationDate(): ?\DateTimeInterface
    {
        return $this->authenticationDate;
    }

    public function setAuthenticationDate(?\DateTimeInterface $authenticationDate): self
    {
        $this->authenticationDate = $authenticationDate;

        return $this;
    }

    public function isRememberMe(): ?bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(?bool $rememberMe): self
    {
        $this->rememberMe = $rememberMe;

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): self
    {
        $this->resetPasswordToken = $resetPasswordToken;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return '';
    }
}
