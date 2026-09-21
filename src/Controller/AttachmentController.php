<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TicketAttachment;
use App\Security\TicketAccessVoter;
use App\Service\Storage\TicketAttachmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class AttachmentController extends AbstractController
{
    #[Route('/attachments/{id}/download', name: 'attachment_download', methods: ['GET'])]
    public function download(TicketAttachment $attachment, TicketAttachmentService $attachmentService): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(TicketAccessVoter::VIEW, $attachment->getTicket());

        return $this->file(
            $attachmentService->absolutePath($attachment),
            $attachment->getOriginalFilename(),
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }
}