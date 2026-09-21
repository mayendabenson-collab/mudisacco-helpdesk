<?php

declare(strict_types=1);

namespace App\Service\Storage;

use App\Entity\Ticket;
use App\Entity\TicketAttachment;
use App\Entity\TicketMessage;
use App\Entity\User;
use App\Service\Audit\AuditLogger;
use App\Service\Notification\NotificationRouter;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;

class TicketAttachmentService
{
    private const MAX_SIZE_BYTES = 10_485_760;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecureFileNameGenerator $fileNameGenerator,
        private readonly AuditLogger $auditLogger,
        private readonly NotificationRouter $notificationRouter,
        private readonly KernelInterface $kernel,
    ) {
    }

    /** @return list<string> */
    public function validate(?UploadedFile $file): array
    {
        if (!$file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        $errors = [];

        if (!$file->isValid()) {
            $errors[] = 'The attachment could not be uploaded. Try again with a different file.';
        }

        if ($file->getSize() !== null && $file->getSize() > self::MAX_SIZE_BYTES) {
            $errors[] = 'Attachments must be 10 MB or smaller.';
        }

        try {
            $this->fileNameGenerator->generate($file->getClientOriginalName());
        } catch (\InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }

        $mimeType = $this->detectMimeType($file);
        if ($mimeType !== null && !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = 'Unsupported attachment content type.';
        }

        return array_values(array_unique($errors));
    }

    public function store(Ticket $ticket, ?TicketMessage $message, User $uploadedBy, UploadedFile $file, ?string $ipAddress = null): TicketAttachment
    {
        $errors = $this->validate($file);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $storedName = $this->fileNameGenerator->generate($file->getClientOriginalName());
        $ticketDirectory = $ticket->getId()->toRfc4122();
        $relativePath = $ticketDirectory . DIRECTORY_SEPARATOR . $storedName;
        $targetDirectory = $this->uploadRoot() . DIRECTORY_SEPARATOR . $ticketDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Could not prepare secure attachment storage.');
        }

        $mimeType = $this->detectMimeType($file) ?? 'application/octet-stream';
        $fileSize = (int) ($file->getSize() ?? 0);
        $file->move($targetDirectory, $storedName);

        $attachment = (new TicketAttachment())
            ->setTicket($ticket)
            ->setMessage($message)
            ->setUploadedBy($uploadedBy)
            ->setOriginalFilename($file->getClientOriginalName())
            ->setStoredPath($relativePath)
            ->setMimeType($mimeType)
            ->setSizeBytes($fileSize);

        $ticket->addAttachment($attachment);
        $this->entityManager->persist($attachment);

        $this->auditLogger->record($uploadedBy, 'ticket.attachment_added', 'ticket', $ticket->getId(), [
            'reference' => $ticket->getReference(),
            'attachment_id' => $attachment->getId()->toRfc4122(),
            'filename' => $attachment->getOriginalFilename(),
            'size_bytes' => $attachment->getSizeBytes(),
        ], $ipAddress);

        $this->notifyOtherSide($ticket, $uploadedBy);
        $this->entityManager->flush();

        return $attachment;
    }

    public function absolutePath(TicketAttachment $attachment): string
    {
        $root = realpath($this->uploadRoot());
        $path = realpath($this->uploadRoot() . DIRECTORY_SEPARATOR . $attachment->getStoredPath());

        if ($root === false || $path === false || !str_starts_with($path, $root)) {
            throw new RuntimeException('Attachment file is not available.');
        }

        return $path;
    }

    public function uploadRoot(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tickets';
    }

    private function detectMimeType(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (!is_string($path) || $path === '') {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        return is_string($mimeType) ? $mimeType : null;
    }

    private function notifyOtherSide(Ticket $ticket, User $uploadedBy): void
    {
        $payload = [
            'event' => 'ATTACHMENT_ADDED',
            'ticket_id' => $ticket->getId()->toRfc4122(),
            'reference' => $ticket->getReference(),
        ];

        $memberUser = $ticket->getMember()->getUser();
        if ($memberUser instanceof User && !$memberUser->getId()->equals($uploadedBy->getId())) {
            $this->notificationRouter->notify($memberUser, 'Attachment added: ' . $ticket->getReference(), 'A new file was attached to your ticket.', $payload);
        }

        if ($ticket->getAssignedTo() instanceof User && !$ticket->getAssignedTo()->getId()->equals($uploadedBy->getId())) {
            $this->notificationRouter->notify($ticket->getAssignedTo(), 'Attachment added: ' . $ticket->getReference(), 'A new file was attached to this ticket.', $payload);
        }
    }
}