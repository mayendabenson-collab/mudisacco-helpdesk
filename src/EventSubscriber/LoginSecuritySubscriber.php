<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $email = strtolower(trim((string) $event->getRequest()->request->get('_username')));
        if ($email === '') {
            return;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            return;
        }

        $user->recordFailedLogin();
        $this->auditLogger->record(null, 'auth.login_failed', 'user', $user->getId(), [
            'email' => $user->getEmail(),
            'failed_login_count' => $user->getFailedLoginCount(),
            'locked_until' => $user->getLockedUntil()?->format(DATE_ATOM),
        ], $event->getRequest()->getClientIp());
        $this->entityManager->flush();
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $user->clearLoginFailures();
        $this->auditLogger->record($user, 'auth.login_success', 'user', $user->getId(), [
            'email' => $user->getEmail(),
        ], $event->getRequest()->getClientIp());
        $this->entityManager->flush();
    }
}