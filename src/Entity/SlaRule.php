<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketPriority;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'sla_rules')]
#[ORM\Index(columns: ['priority'], name: 'idx_sla_rules_priority')]
class SlaRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column(length: 32, enumType: TicketPriority::class)]
    private TicketPriority $priority = TicketPriority::MEDIUM;

    #[ORM\Column]
    private int $responseMinutes = 240;

    #[ORM\Column]
    private int $resolutionMinutes = 1440;

    #[ORM\Column]
    private int $escalationMinutes = 1200;

    #[ORM\Column]
    private bool $active = true;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getResponseMinutes(): int
    {
        return $this->responseMinutes;
    }

    public function getResolutionMinutes(): int
    {
        return $this->resolutionMinutes;
    }

    public function getEscalationMinutes(): int
    {
        return $this->escalationMinutes;
    }

    public function setTargets(int $responseMinutes, int $resolutionMinutes, int $escalationMinutes): self
    {
        $this->responseMinutes = $responseMinutes;
        $this->resolutionMinutes = $resolutionMinutes;
        $this->escalationMinutes = $escalationMinutes;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
