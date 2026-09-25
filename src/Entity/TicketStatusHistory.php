<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_status_history')]
#[ORM\Index(columns: ['ticket_id'], name: 'idx_ticket_status_history_ticket')]
class TicketStatusHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\Column(length: 32, enumType: TicketStatus::class, nullable: true)]
    private ?TicketStatus $fromStatus = null;

    #[ORM\Column(length: 32, enumType: TicketStatus::class)]
    private TicketStatus $toStatus;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $changedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(TicketStatus $toStatus = TicketStatus::OPEN, ?\DateTimeImmutable $createdAt = null)
    {
        $this->id = Uuid::v7();
        $this->toStatus = $toStatus;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getFromStatus(): ?TicketStatus
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?TicketStatus $fromStatus): self
    {
        $this->fromStatus = $fromStatus;

        return $this;
    }

    public function getToStatus(): TicketStatus
    {
        return $this->toStatus;
    }

    public function setToStatus(TicketStatus $toStatus): self
    {
        $this->toStatus = $toStatus;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): self
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
