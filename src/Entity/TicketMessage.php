<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketMessageVisibility;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_messages')]
#[ORM\Index(columns: ['ticket_id'], name: 'idx_ticket_messages_ticket')]
class TicketMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column(length: 32, enumType: TicketMessageVisibility::class)]
    private TicketMessageVisibility $visibility = TicketMessageVisibility::PUBLIC;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = trim($body);

        return $this;
    }

    public function getVisibility(): TicketMessageVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(TicketMessageVisibility $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
