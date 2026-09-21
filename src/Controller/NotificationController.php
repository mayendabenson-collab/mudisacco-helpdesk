<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'notification_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        $notifications = $entityManager->getRepository(Notification::class)->findBy(['recipient' => $user], ['createdAt' => 'DESC'], 100);

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->requireUser();
        if (!$notification->getRecipient()->getId()->equals($user->getId())) {
            throw $this->createAccessDeniedException('You can only update your own notifications.');
        }

        if (!$this->isCsrfTokenValid('notification_read_' . $notification->getId()->toRfc4122(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid notification token.');
        }

        $notification->markRead();
        $entityManager->flush();

        return $this->redirectToRoute('notification_index');
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must sign in to continue.');
        }

        return $user;
    }
}