<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Entity\Branch;
use App\Entity\Category;
use App\Entity\Member;
use App\Entity\SatisfactionRating;
use App\Entity\Ticket;
use App\Entity\TicketMessage;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\TicketChannel;
use App\Enum\TicketMessageVisibility;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Security\SystemRole;
use App\Service\Audit\AuditLogger;
use App\Service\Notification\NotificationRouter;
use App\Sla\SlaService;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use LogicException;

class TicketService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TicketNumberGenerator $numberGenerator,
        private readonly TicketAssignmentService $assignmentService,
        private readonly TicketLifecycleService $lifecycleService,
        private readonly SlaService $slaService,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationRouter $notificationRouter,
    ) {
    }

    public function createForMember(User $memberUser, Category $category, string $subject, string $description, ?string $ipAddress = null): Ticket
    {
        $member = $this->memberForUser($memberUser);
        $now = new \DateTimeImmutable();

        $ticket = (new Ticket())
            ->setReference($this->numberGenerator->nextReference($now))
            ->setMember($member)
            ->setCreatedBy($memberUser)
            ->setCategory($category)
            ->setDepartment($category->getDepartment())
            ->setPriority($category->getDefaultPriority())
            ->setSubject($subject)
            ->setDescription($description);

        $this->slaService->applyInitialSla($ticket, $now);

        $ticket->addStatusHistory(
            (new \App\Entity\TicketStatusHistory(TicketStatus::OPEN, $now))
                ->setFromStatus(null)
                ->setChangedBy($memberUser)
                ->setReason('Ticket created')
        );

        $this->entityManager->persist($ticket);
        $assignedStaff = $this->assignmentService->assignInitialStaff($ticket);

        if ($assignedStaff instanceof User) {
            $this->auditLogger->record($memberUser, 'ticket.assigned', 'ticket', $ticket->getId(), [
                'reference' => $ticket->getReference(),
                'assigned_to' => $assignedStaff->getUserIdentifier(),
                'department' => $ticket->getDepartment()->getCode(),
                'automatic' => true,
            ], $ipAddress);
        }

        $this->auditLogger->record($memberUser, 'ticket.created', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'category' => $category->getCode(),
            'department' => $category->getDepartment()->getCode(),
            'priority' => $ticket->getPriority()->value,
            'assigned_to' => $assignedStaff?->getUserIdentifier(),
            'sla_due_at' => $ticket->getSlaDueAt()?->format(DATE_ATOM),
        ], $ipAddress);

        $this->notificationRouter->notify(
            $memberUser,
            'Ticket created: ' . $ticket->getReference(),
            'Your ticket has been received by Mudi SACCO support.',
            $this->ticketPayload($ticket, 'TICKET_CREATED')
        );

        if ($assignedStaff instanceof User) {
            $this->notificationRouter->notify(
                $assignedStaff,
                'New assigned ticket: ' . $ticket->getReference(),
                $ticket->getSubject(),
                $this->ticketPayload($ticket, 'TICKET_ASSIGNED')
            );
        }

        $this->entityManager->flush();

        return $ticket;
    }

    public function createByStaffForMember(
        User $staffUser,
        Member $member,
        Category $category,
        string $subject,
        string $description,
        TicketPriority $priority,
        ?TicketChannel $channel = null,
        ?Branch $branch = null,
        ?string $ipAddress = null
    ): Ticket {
        $now = new \DateTimeImmutable();

        $ticket = (new Ticket())
            ->setReference($this->numberGenerator->nextReference($now))
            ->setMember($member)
            ->setCreatedBy($staffUser)
            ->setCategory($category)
            ->setDepartment($category->getDepartment())
            ->setPriority($priority)
            ->setSubject($subject)
            ->setDescription($description);

        if ($channel instanceof TicketChannel) {
            $ticket->setChannel($channel);
        }

        if ($branch instanceof Branch) {
            $ticket->setBranch($branch);
        }

        $this->slaService->applyInitialSla($ticket, $now);

        $ticket->addStatusHistory(
            (new \App\Entity\TicketStatusHistory(TicketStatus::OPEN, $now))
                ->setFromStatus(null)
                ->setChangedBy($staffUser)
                ->setReason(sprintf('Ticket created by staff (%s) on behalf of customer', $staffUser->getFullName()))
        );

        $this->entityManager->persist($ticket);
        $assignedStaff = $this->assignmentService->assignInitialStaff($ticket);

        if ($assignedStaff instanceof User) {
            $this->auditLogger->record($staffUser, 'ticket.assigned', 'ticket', $ticket->getId(), [
                'reference' => $ticket->getReference(),
                'assigned_to' => $assignedStaff->getUserIdentifier(),
                'department' => $ticket->getDepartment()->getCode(),
                'automatic' => true,
            ], $ipAddress);
        }

        $this->auditLogger->record($staffUser, 'ticket.created_by_staff', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'member_id' => $member->getId()->toRfc4122(),
            'member_number' => $member->getMemberNumber(),
            'category' => $category->getCode(),
            'department' => $category->getDepartment()->getCode(),
            'priority' => $ticket->getPriority()->value,
            'channel' => $channel?->value,
            'branch' => $branch?->getCode(),
            'assigned_to' => $assignedStaff?->getUserIdentifier(),
            'sla_due_at' => $ticket->getSlaDueAt()?->format(DATE_ATOM),
        ], $ipAddress);

        if ($assignedStaff instanceof User && !$assignedStaff->getId()->equals($staffUser->getId())) {
            $this->notificationRouter->notify(
                $assignedStaff,
                'New assigned ticket: ' . $ticket->getReference(),
                $ticket->getSubject(),
                $this->ticketPayload($ticket, 'TICKET_ASSIGNED')
            );
        }

        $memberUser = $member->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket created: ' . $ticket->getReference(),
                'A support ticket has been recorded on your behalf by Mudi SACCO customer service.',
                $this->ticketPayload($ticket, 'TICKET_CREATED')
            );
        }

        $this->entityManager->flush();

        return $ticket;
    }

    public function accept(Ticket $ticket, User $staffUser, ?string $ipAddress = null): void
    {
        $this->assertCanAccept($ticket, $staffUser);
        $now = new \DateTimeImmutable();

        $this->assignmentService->assignTo($ticket, $staffUser, $staffUser, 'Accepted by staff member.');
        $ticket->markAccepted($staffUser, $now);
        $this->lifecycleService->transition($ticket, TicketStatus::IN_PROGRESS, $staffUser, 'Ticket accepted for handling.', $now);

        $this->auditLogger->record($staffUser, 'ticket.accepted', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'assigned_to' => $staffUser->getUserIdentifier(),
            'accepted_at' => $ticket->getAcceptedAt()?->format(DATE_ATOM),
        ], $ipAddress);

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket in progress: ' . $ticket->getReference(),
                'Your ticket is now being handled.',
                $this->ticketPayload($ticket, 'TICKET_ACCEPTED')
            );
        }

        $this->entityManager->flush();
    }

    public function reassign(Ticket $ticket, User $assignedTo, User $actor, ?string $ipAddress = null, ?Team $assignedTeam = null): void
    {
        if (in_array($ticket->getStatus(), [TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) {
            throw new DomainException('Resolved or closed tickets cannot be reassigned.');
        }

        if (!$assignedTo->isActive() || !in_array(SystemRole::STAFF->value, $assignedTo->getRoles(), true)) {
            throw new DomainException('Tickets can only be assigned to an active staff member.');
        }

        if ($assignedTo->getDepartment() === null || !$assignedTo->getDepartment()->getId()->equals($ticket->getDepartment()->getId())) {
            throw new DomainException('Assigned staff must belong to the ticket department.');
        }

        $this->assignmentService->assignTo($ticket, $assignedTo, $actor, 'Manual reassignment.', $assignedTeam);

        $this->auditLogger->record($actor, 'ticket.reassigned', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'assigned_to' => $assignedTo->getUserIdentifier(),
            'department' => $ticket->getDepartment()->getCode(),
        ], $ipAddress);

        $this->notificationRouter->notify(
            $assignedTo,
            'Ticket assigned: ' . $ticket->getReference(),
            $ticket->getSubject(),
            $this->ticketPayload($ticket, 'TICKET_ASSIGNED')
        );

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket assignment updated: ' . $ticket->getReference(),
                'Your ticket has been routed to a support officer.',
                $this->ticketPayload($ticket, 'TICKET_ASSIGNED')
            );
        }

        $this->entityManager->flush();
    }

    public function addReply(
        Ticket $ticket,
        User $author,
        string $messageBody,
        TicketMessageVisibility $visibility = TicketMessageVisibility::PUBLIC,
        ?string $ipAddress = null
    ): TicketMessage {
        $this->assertTicketCanReceiveReply($ticket, $author);
        $message = $this->addReplyWithoutFlush($ticket, $author, $messageBody, $visibility);

        if ($ticket->getStatus() === TicketStatus::WAITING_FOR_MEMBER && $this->isMemberAuthor($ticket, $author)) {
            // Resume SLA timer before transitioning — slaDueAt gets extended by the pause duration.
            $this->slaService->resumeIfMemberResponded($ticket);
            $this->lifecycleService->transition($ticket, TicketStatus::IN_PROGRESS, $author, 'Member provided requested information.');
        }

        $this->auditLogger->record($author, 'ticket.message_added', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'message_visibility' => $message->getVisibility()->value,
        ], $ipAddress);

        // Only notify member if public message (internal notes are strictly confidential to staff!)
        if ($visibility === TicketMessageVisibility::PUBLIC) {
            $this->notifyOtherSide($ticket, $author, 'New response on ' . $ticket->getReference(), $message->getBody(), 'STAFF_RESPONDED');
        }

        $this->entityManager->flush();

        return $message;
    }

    public function requestInformation(Ticket $ticket, User $staffUser, string $question, ?string $ipAddress = null): void
    {
        $this->assertAssignedStaffCanWork($ticket, $staffUser);
        $this->addReplyWithoutFlush($ticket, $staffUser, $question);

        // Pause SLA before transitioning — pause must happen before status changes so
        // pauseIfWaitingForMember doesn't need to re-check the (not-yet-set) status.
        $this->slaService->pauseIfWaitingForMember($ticket);
        $this->lifecycleService->transition($ticket, TicketStatus::WAITING_FOR_MEMBER, $staffUser, 'Staff requested more information.');

        $this->auditLogger->record($staffUser, 'ticket.information_requested', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'sla_paused_at' => $ticket->getSlaPausedAt()?->format(DATE_ATOM),
        ], $ipAddress);

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Information needed: ' . $ticket->getReference(),
                $question,
                $this->ticketPayload($ticket, 'INFORMATION_REQUESTED')
            );
        }

        $this->entityManager->flush();
    }

    public function resolve(Ticket $ticket, User $staffUser, string $resolution, ?string $ipAddress = null): void
    {
        $this->assertAssignedStaffCanWork($ticket, $staffUser);
        $this->addReplyWithoutFlush($ticket, $staffUser, 'Resolution: ' . $resolution);
        $this->lifecycleService->transition($ticket, TicketStatus::RESOLVED, $staffUser, 'Ticket resolved.');

        $this->auditLogger->record($staffUser, 'ticket.resolved', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
        ], $ipAddress);

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket resolved: ' . $ticket->getReference(),
                'Please review the resolution and confirm whether your issue is resolved.',
                $this->ticketPayload($ticket, 'TICKET_RESOLVED')
            );
        }

        $this->entityManager->flush();
    }

    public function closeByMember(Ticket $ticket, User $memberUser, ?int $ratingScore = null, ?string $ratingComment = null, ?string $ipAddress = null): void
    {
        $this->lifecycleService->transition($ticket, TicketStatus::CLOSED, $memberUser, 'Member confirmed resolution.');

        if ($ratingScore !== null) {
            $rating = $this->entityManager->getRepository(SatisfactionRating::class)->findOneBy(['ticket' => $ticket]) ?? new SatisfactionRating();
            $rating->setTicket($ticket)
                ->setMember($ticket->getMember())
                ->setScore($ratingScore)
                ->setComment($ratingComment);
            $this->entityManager->persist($rating);
        }

        $this->auditLogger->record($memberUser, 'ticket.closed', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'rating_score' => $ratingScore,
        ], $ipAddress);

        if ($ticket->getAssignedTo() instanceof User) {
            $this->notificationRouter->notify(
                $ticket->getAssignedTo(),
                'Ticket closed: ' . $ticket->getReference(),
                'The member confirmed the resolution.',
                $this->ticketPayload($ticket, 'TICKET_CLOSED')
            );
        }

        $this->entityManager->flush();
    }

    public function reopenByMember(Ticket $ticket, User $memberUser, string $reason, ?string $ipAddress = null): void
    {
        $this->addReplyWithoutFlush($ticket, $memberUser, 'Reopen reason: ' . $reason);
        $this->lifecycleService->transition($ticket, TicketStatus::REOPENED, $memberUser, 'Member rejected resolution.');

        $this->auditLogger->record($memberUser, 'ticket.reopened', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
        ], $ipAddress);

        if ($ticket->getAssignedTo() instanceof User) {
            $this->notificationRouter->notify(
                $ticket->getAssignedTo(),
                'Ticket reopened: ' . $ticket->getReference(),
                $reason,
                $this->ticketPayload($ticket, 'TICKET_REOPENED')
            );
        }

        $this->entityManager->flush();
    }

    public function closeByStaff(Ticket $ticket, User $staffUser, ?string $reason = null, ?string $ipAddress = null): void
    {
        $closeReason = $reason ? 'Closed by staff: ' . $reason : 'Ticket closed by staff.';
        $this->lifecycleService->transition($ticket, TicketStatus::CLOSED, $staffUser, $closeReason);

        $this->auditLogger->record($staffUser, 'ticket.closed', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'closed_by' => $staffUser->getUserIdentifier(),
            'reason' => $reason,
        ], $ipAddress);

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket closed: ' . $ticket->getReference(),
                'Your ticket has been closed by staff.',
                $this->ticketPayload($ticket, 'TICKET_CLOSED')
            );
        }

        $this->entityManager->flush();
    }

    public function reopenByStaff(Ticket $ticket, User $staffUser, string $reason, ?string $ipAddress = null): void
    {
        $this->addReplyWithoutFlush($ticket, $staffUser, 'Reopened by staff: ' . $reason, TicketMessageVisibility::INTERNAL);
        $this->lifecycleService->transition($ticket, TicketStatus::REOPENED, $staffUser, 'Staff reopened ticket: ' . $reason);

        $this->auditLogger->record($staffUser, 'ticket.reopened', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'reopened_by' => $staffUser->getUserIdentifier(),
            'reason' => $reason,
        ], $ipAddress);

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User) {
            $this->notificationRouter->notify(
                $memberUser,
                'Ticket reopened: ' . $ticket->getReference(),
                'Your ticket has been reopened for further investigation.',
                $this->ticketPayload($ticket, 'TICKET_REOPENED')
            );
        }

        $this->entityManager->flush();
    }

    public function escalate(Ticket $ticket, ?User $actor = null, ?string $reason = null, ?string $ipAddress = null): bool
    {
        if ($ticket->getEscalatedAt() !== null) {
            return false;
        }

        if (!$ticket->isActiveForSla()) {
            return false;
        }

        $ticket->markEscalated($actor);
        $this->auditLogger->record($actor, 'ticket.escalated', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'department' => $ticket->getDepartment()->getCode(),
            'reason' => $reason,
            'sla_due_at' => $ticket->getSlaDueAt()?->format(DATE_ATOM),
        ], $ipAddress);

        foreach ($this->supervisorsForTicket($ticket) as $recipient) {
            $this->notificationRouter->notify(
                $recipient,
                'Escalated ticket: ' . $ticket->getReference(),
                $reason ?: 'This ticket has breached its SLA and needs supervisory attention.',
                $this->ticketPayload($ticket, 'TICKET_ESCALATED')
            );
        }

        if ($ticket->getAssignedTo() instanceof User) {
            $this->notificationRouter->notify(
                $ticket->getAssignedTo(),
                'Ticket escalated: ' . $ticket->getReference(),
                $reason ?: 'This ticket has breached its SLA.',
                $this->ticketPayload($ticket, 'TICKET_ESCALATED')
            );
        }

        $this->entityManager->flush();

        return true;
    }

    private function addReplyWithoutFlush(
        Ticket $ticket,
        User $author,
        string $messageBody,
        TicketMessageVisibility $visibility = TicketMessageVisibility::PUBLIC
    ): TicketMessage {
        $message = (new TicketMessage())
            ->setTicket($ticket)
            ->setAuthor($author)
            ->setBody($messageBody)
            ->setVisibility($visibility);

        if (!$this->isMemberAuthor($ticket, $author) && $visibility === TicketMessageVisibility::PUBLIC) {
            $ticket->markFirstResponse($message->getCreatedAt());
        }

        $ticket->addMessage($message);
        $this->entityManager->persist($message);

        return $message;
    }

    private function notifyOtherSide(Ticket $ticket, User $author, string $subject, string $body, string $event): void
    {
        $memberUser = $ticket->getMember()->getUser();

        if ($memberUser instanceof User && !$memberUser->getId()->equals($author->getId())) {
            $this->notificationRouter->notify($memberUser, $subject, $body, $this->ticketPayload($ticket, $event));
        }

        $assignedTo = $ticket->getAssignedTo();
        if ($assignedTo instanceof User && !$assignedTo->getId()->equals($author->getId())) {
            $this->notificationRouter->notify($assignedTo, $subject, $body, $this->ticketPayload($ticket, $event));
        }
    }

    private function assertCanAccept(Ticket $ticket, User $staffUser): void
    {
        if (!in_array($ticket->getStatus(), [TicketStatus::OPEN, TicketStatus::REOPENED], true)) {
            throw new DomainException('Only open or reopened tickets can be accepted.');
        }

        if (!$this->isStaffLike($staffUser)) {
            throw new DomainException('Only support staff can accept tickets.');
        }

        if ($ticket->getAssignedTo() instanceof User && !$ticket->getAssignedTo()->getId()->equals($staffUser->getId()) && !$this->hasSupervisorOverride($staffUser)) {
            throw new DomainException('This ticket is assigned to another staff member. Ask a supervisor to reassign it.');
        }

        if (!$this->sameDepartment($ticket, $staffUser) && !$this->hasSupervisorOverride($staffUser)) {
            throw new DomainException('You can only accept tickets in your department.');
        }
    }

    private function assertTicketCanReceiveReply(Ticket $ticket, User $author): void
    {
        if ($ticket->getStatus() === TicketStatus::CLOSED) {
            throw new DomainException('Closed tickets cannot receive new replies.');
        }

        if ($this->isMemberAuthor($ticket, $author)) {
            if ($ticket->getStatus() === TicketStatus::RESOLVED) {
                throw new DomainException('Use confirm resolved or reopen instead of replying to a resolved ticket.');
            }

            return;
        }

        $this->assertAssignedStaffCanWork($ticket, $author);
    }

    private function assertAssignedStaffCanWork(Ticket $ticket, User $staffUser): void
    {
        if (!$this->isStaffLike($staffUser)) {
            throw new DomainException('Only support staff can handle this ticket.');
        }

        if ($ticket->getStatus() !== TicketStatus::IN_PROGRESS) {
            throw new DomainException('Accept the ticket before responding, requesting information, or resolving it.');
        }

        if ($ticket->getAssignedTo() instanceof User && $ticket->getAssignedTo()->getId()->equals($staffUser->getId())) {
            return;
        }

        if ($this->hasSupervisorOverride($staffUser) && $this->sameDepartment($ticket, $staffUser)) {
            return;
        }

        throw new DomainException('Only the assigned staff member or a supervisor override can work on this ticket.');
    }

    private function isMemberAuthor(Ticket $ticket, User $user): bool
    {
        $memberUser = $ticket->getMember()->getUser();

        return $memberUser instanceof User && $memberUser->getId()->equals($user->getId());
    }

    private function isStaffLike(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array(SystemRole::STAFF->value, $roles, true)
            || in_array(SystemRole::SUPERVISOR->value, $roles, true)
            || in_array(SystemRole::ADMIN->value, $roles, true);
    }

    private function hasSupervisorOverride(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array(SystemRole::SUPERVISOR->value, $roles, true) || in_array(SystemRole::ADMIN->value, $roles, true);
    }

    private function sameDepartment(Ticket $ticket, User $user): bool
    {
        return $user->getDepartment() !== null && $ticket->getDepartment()->getId()->equals($user->getDepartment()->getId());
    }

    private function memberForUser(User $memberUser): Member
    {
        $member = $this->entityManager->getRepository(Member::class)->findOneBy(['user' => $memberUser]);

        if (!$member instanceof Member) {
            throw new LogicException('The authenticated user is not linked to a SACCO member record.');
        }

        return $member;
    }

    /** @return list<User> */
    private function supervisorsForTicket(Ticket $ticket): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u', 'r')
            ->from(User::class, 'u')
            ->join('u.roles', 'r')
            ->where('u.active = true')
            ->andWhere('r.code IN (:roles)')
            ->andWhere('(r.code = :adminRole OR u.department = :department)')
            ->setParameter('roles', [SystemRole::SUPERVISOR->value, SystemRole::ADMIN->value])
            ->setParameter('adminRole', SystemRole::ADMIN->value)
            ->setParameter('department', $ticket->getDepartment());

        return $qb->getQuery()->getResult();
    }

    private function ticketPayload(Ticket $ticket, string $event): array
    {
        return [
            'event' => $event,
            'ticket_id' => $ticket->getId()->toRfc4122(),
            'reference' => $ticket->getReference(),
            'status' => $ticket->getStatus()->value,
            'sla_status' => $ticket->getSlaStatus(),
        ];
    }
}