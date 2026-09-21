<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'password_reset_tokens')]
#[ORM\Index(columns: ['token_hash'], name: 'idx_password_reset_token_hash')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_password_reset_expires_at')]
class PasswordResetToken
{
    public const PURPOSE_SETUP = 'SETUP';
    public const PURPOSE_RESET = 'RESET';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash = '';

    #[ORM\Column(length: 16)]
    private string $purpose = self::PURPOSE_RESET;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify('+1 hour');
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): self
    {
        if (!in_array($purpose, [self::PURPOSE_SETUP, self::PURPOSE_RESET], true)) {
            throw new \InvalidArgumentException('Unsupported password token purpose.');
        }

        $this->purpose = $purpose;

        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function markConsumed(?\DateTimeImmutable $consumedAt = null): self
    {
        $this->consumedAt = $consumedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function isUsable(?\DateTimeImmutable $now = null): bool
    {
        return $this->consumedAt === null && $this->expiresAt > ($now ?? new \DateTimeImmutable());
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}