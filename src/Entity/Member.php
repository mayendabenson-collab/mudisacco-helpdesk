<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MemberStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'members')]
#[ORM\Index(columns: ['status'], name: 'idx_members_status')]
#[UniqueEntity(fields: ['memberNumber'])]
class Member
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 40, unique: true)]
    private string $memberNumber = '';

    #[ORM\Column(length: 140)]
    private string $displayName = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $primaryPhone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 32, enumType: MemberStatus::class)]
    private MemberStatus $status = MemberStatus::ACTIVE;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Branch::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Branch $branch = null;

    #[ORM\OneToMany(mappedBy: 'member', targetEntity: Ticket::class)]
    private Collection $tickets;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->tickets = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMemberNumber(): string
    {
        return $this->memberNumber;
    }

    public function setMemberNumber(string $memberNumber): self
    {
        $this->memberNumber = strtoupper(trim($memberNumber));
        $this->touch();

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = trim($displayName);
        $this->touch();

        return $this;
    }

    public function getPrimaryPhone(): ?string
    {
        return $this->primaryPhone;
    }

    public function setPrimaryPhone(?string $primaryPhone): self
    {
        $this->primaryPhone = $primaryPhone !== null && trim($primaryPhone) !== '' ? trim($primaryPhone) : null;
        $this->touch();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email !== null && trim($email) !== '' ? strtolower(trim($email)) : null;
        $this->touch();

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        $this->touch();

        return $this;
    }

    public function getStatus(): MemberStatus
    {
        return $this->status;
    }

    public function setStatus(MemberStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    /** @return Collection<int, Ticket> */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    public function setBranch(?Branch $branch): self
    {
        $this->branch = $branch;
        $this->touch();

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
