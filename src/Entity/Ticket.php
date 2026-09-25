<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TicketChannel;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tickets')]
#[ORM\Index(columns: ['status'], name: 'idx_tickets_status')]
#[ORM\Index(columns: ['priority'], name: 'idx_tickets_priority')]
#[ORM\Index(columns: ['member_id'], name: 'idx_tickets_member')]
#[ORM\Index(columns: ['assigned_to_id'], name: 'idx_tickets_assigned_to')]
#[ORM\Index(columns: ['department_id'], name: 'idx_tickets_department')]
#[ORM\Index(columns: ['branch_id'], name: 'idx_tickets_branch')]
#[ORM\Index(columns: ['channel'], name: 'idx_tickets_channel')]
#[ORM\Index(columns: ['sla_due_at'], name: 'idx_tickets_sla_due_at')]
#[ORM\Index(columns: ['escalated_at'], name: 'idx_tickets_escalated_at')]
#[UniqueEntity(fields: ['reference'])]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 40, unique: true)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Member::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Member $member;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Department $department;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Team $assignedTeam = null;

    #[ORM\ManyToOne(targetEntity: Branch::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Branch $branch = null;

    #[ORM\Column(length: 32, enumType: TicketChannel::class, nullable: true)]
    private ?TicketChannel $channel = null;

    #[ORM\Column(length: 180)]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(length: 32, enumType: TicketPriority::class)]
    private TicketPriority $priority = TicketPriority::MEDIUM;

    #[ORM\Column(length: 32, enumType: TicketStatus::class)]
    private TicketStatus $status = TicketStatus::OPEN;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $slaDueAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $acceptedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $firstRespondedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $slaStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $slaPausedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $totalPausedSeconds = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $escalatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $escalatedBy = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketMessage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketAttachment::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $attachments;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketStatusHistory::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $statusHistory;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->reference = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));
        $this->messages = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = strtoupper(trim($reference));
        $this->touch();

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setMember(Member $member): self
    {
        $this->member = $member;
        $this->touch();

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        $this->touch();

        return $this;
    }

    public function getDepartment(): Department
    {
        return $this->department;
    }

    public function setDepartment(Department $department): self
    {
        $this->department = $department;
        $this->touch();

        return $this;
    }

    public function getCategory(): ?Category
    {
        return isset($this->category) ? $this->category : null;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        $this->touch();

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        $this->touch();

        return $this;
    }

    public function getAssignedTeam(): ?Team
    {
        return $this->assignedTeam;
    }

    public function setAssignedTeam(?Team $assignedTeam): self
    {
        $this->assignedTeam = $assignedTeam;
        $this->touch();

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = trim($subject);
        $this->touch();

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        $this->touch();

        return $this;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function setPriority(TicketPriority $priority): self
    {
        $this->priority = $priority;
        $this->touch();

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getAcceptedBy(): ?User
    {
        return $this->acceptedBy;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function markAccepted(User $staffUser, ?\DateTimeImmutable $acceptedAt = null): self
    {
        $this->acceptedBy = $staffUser;
        $this->acceptedAt = $acceptedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getFirstRespondedAt(): ?\DateTimeImmutable
    {
        return $this->firstRespondedAt;
    }

    public function markFirstResponse(?\DateTimeImmutable $respondedAt = null): self
    {
        if ($this->firstRespondedAt === null) {
            $this->firstRespondedAt = $respondedAt ?? new \DateTimeImmutable();
            $this->touch();
        }

        return $this;
    }

    public function getSlaStartedAt(): ?\DateTimeImmutable
    {
        return $this->slaStartedAt;
    }

    public function setSlaStartedAt(?\DateTimeImmutable $slaStartedAt): self
    {
        $this->slaStartedAt = $slaStartedAt;
        $this->touch();

        return $this;
    }

    public function startSla(\DateTimeImmutable $startedAt, ?\DateTimeImmutable $dueAt): self
    {
        $this->slaStartedAt = $startedAt;
        $this->slaDueAt = $dueAt;
        $this->slaPausedAt = null;
        $this->totalPausedSeconds = 0;
        $this->touch();

        return $this;
    }

    public function getSlaPausedAt(): ?\DateTimeImmutable
    {
        return $this->slaPausedAt;
    }

    public function pauseSla(?\DateTimeImmutable $pausedAt = null): self
    {
        if ($this->slaPausedAt === null) {
            $this->slaPausedAt = $pausedAt ?? new \DateTimeImmutable();
            $this->touch();
        }

        return $this;
    }

    public function resumeSla(?\DateTimeImmutable $resumedAt = null): self
    {
        if ($this->slaPausedAt === null) {
            return $this;
        }

        $resumedAt ??= new \DateTimeImmutable();
        $pausedSeconds = max(0, $resumedAt->getTimestamp() - $this->slaPausedAt->getTimestamp());
        $this->totalPausedSeconds += $pausedSeconds;

        if ($this->slaDueAt !== null && $pausedSeconds > 0) {
            $this->slaDueAt = $this->slaDueAt->modify('+' . $pausedSeconds . ' seconds');
        }

        $this->slaPausedAt = null;
        $this->touch();

        return $this;
    }

    public function isSlaPaused(): bool
    {
        return $this->slaPausedAt !== null;
    }

    public function getTotalPausedSeconds(): int
    {
        return $this->totalPausedSeconds;
    }

    public function setTotalPausedSeconds(int $totalPausedSeconds): self
    {
        $this->totalPausedSeconds = max(0, $totalPausedSeconds);
        $this->touch();

        return $this;
    }

    public function getEscalatedAt(): ?\DateTimeImmutable
    {
        return $this->escalatedAt;
    }

    public function getEscalatedBy(): ?User
    {
        return $this->escalatedBy;
    }

    public function markEscalated(?User $actor = null, ?\DateTimeImmutable $escalatedAt = null): self
    {
        if ($this->escalatedAt === null) {
            $this->escalatedAt = $escalatedAt ?? new \DateTimeImmutable();
            $this->escalatedBy = $actor;
            $this->touch();
        }

        return $this;
    }

    public function isActiveForSla(): bool
    {
        return in_array($this->status, [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, TicketStatus::WAITING_FOR_MEMBER, TicketStatus::REOPENED], true);
    }

    public function isSlaBreached(?\DateTimeImmutable $now = null): bool
    {
        if ($this->slaDueAt === null) {
            return false;
        }

        if ($this->firstRespondedAt !== null) {
            return $this->firstRespondedAt > $this->slaDueAt;
        }

        if (!$this->isActiveForSla()) {
            return false;
        }

        $comparisonTime = $this->slaPausedAt ?? $now ?? new \DateTimeImmutable();

        return $comparisonTime > $this->slaDueAt;
    }

    public function getRemainingSlaSeconds(?\DateTimeImmutable $now = null): ?int
    {
        if ($this->slaDueAt === null || $this->firstRespondedAt !== null || !$this->isActiveForSla()) {
            return null;
        }

        $comparisonTime = $this->slaPausedAt ?? $now ?? new \DateTimeImmutable();

        return $this->slaDueAt->getTimestamp() - $comparisonTime->getTimestamp();
    }

    public function getSlaStatus(?\DateTimeImmutable $now = null): string
    {
        if ($this->slaDueAt === null) {
            return 'NOT_SET';
        }

        if ($this->escalatedAt !== null) {
            return 'ESCALATED';
        }

        if ($this->firstRespondedAt !== null) {
            return $this->firstRespondedAt <= $this->slaDueAt ? 'MET' : 'BREACHED';
        }

        if ($this->slaPausedAt !== null) {
            return $this->isSlaBreached($now) ? 'BREACHED' : 'PAUSED';
        }

        if ($this->isSlaBreached($now)) {
            return 'BREACHED';
        }

        $remaining = $this->getRemainingSlaSeconds($now);

        return $remaining !== null && $remaining <= 3600 ? 'DUE_SOON' : 'ON_TRACK';
    }

    public function getSlaDueAt(): ?\DateTimeImmutable
    {
        return $this->slaDueAt;
    }

    public function setSlaDueAt(?\DateTimeImmutable $slaDueAt): self
    {
        $this->slaDueAt = $slaDueAt;
        $this->touch();

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        $this->touch();

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;
        $this->touch();

        return $this;
    }

    /** @return Collection<int, TicketMessage> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(TicketMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setTicket($this);
            $this->touch();
        }

        return $this;
    }

    /** @return Collection<int, TicketAttachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(TicketAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setTicket($this);
            $this->touch();
        }

        return $this;
    }

    /** @return Collection<int, TicketStatusHistory> */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(TicketStatusHistory $history): self
    {
        if (!$this->statusHistory->contains($history)) {
            $this->statusHistory->add($history);
            $history->setTicket($this);
            $this->touch();
        }

        return $this;
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

    public function getChannel(): ?TicketChannel
    {
        return $this->channel;
    }

    public function setChannel(?TicketChannel $channel): self
    {
        $this->channel = $channel;
        $this->touch();

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
