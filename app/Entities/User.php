<?php

namespace App\Entities;

use App\Repositories\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $username;

    #[ORM\Column(name: 'full_name', type: 'string', length: 100)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'birth_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'is_admin', type: 'boolean')]
    private bool $isAdmin = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'verification_token', type: 'string', length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(name: 'email_verified_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(name: 'reset_token', type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_expires_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetExpiresAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function getFullName(): string { return $this->fullName; }
    public function setFullName(string $fullName): self { $this->fullName = $fullName; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function getBirthDate(): ?\DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(?\DateTimeInterface $birthDate): self { $this->birthDate = $birthDate; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $password): self { 
        if ($password !== null && !empty($password)) {
            $this->password = password_hash($password, PASSWORD_BCRYPT);
        } else {
            $this->password = $password;
        }
        return $this; 
    }
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }
    public function isAdmin(): bool { return $this->isAdmin; }
    public function setIsAdmin(bool $isAdmin): self { $this->isAdmin = $isAdmin; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $verificationToken): self { $this->verificationToken = $verificationToken; return $this; }
    public function getEmailVerifiedAt(): ?\DateTimeInterface { return $this->emailVerifiedAt; }
    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): self { $this->emailVerifiedAt = $emailVerifiedAt; return $this; }
    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): self { $this->resetToken = $resetToken; return $this; }
    public function getResetExpiresAt(): ?\DateTimeInterface { return $this->resetExpiresAt; }
    public function setResetExpiresAt(?\DateTimeInterface $resetExpiresAt): self { $this->resetExpiresAt = $resetExpiresAt; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
    }
}
