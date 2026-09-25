<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationChannel;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(columns: ['recipient_id', 'read_at'], name: 'idx_notifications_recipient_read')]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(length: 32, enumType: NotificationChannel::class)]
    private NotificationChannel $channel = NotificationChannel::IN_APP;

    #[ORM\Column(length: 180)]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column(type: 'json')]
    private array $payload = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

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

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function setChannel(NotificationChannel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = trim($subject);

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

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function markRead(?\DateTimeImmutable $readAt = null): self
    {
        $this->readAt = $readAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function markSent(?\DateTimeImmutable $sentAt = null): self
    {
        $this->sentAt = $sentAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
