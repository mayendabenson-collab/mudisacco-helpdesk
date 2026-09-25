<?php

declare(strict_types=1);

namespace App\Security\PasswordReset;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Service\Audit\AuditLogger;
use App\Service\Notification\NotificationRouter;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly NotificationRouter $notificationRouter,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /** @return array{token:string, entity:PasswordResetToken} */
    public function createToken(User $user, string $purpose, ?User $actor = null, ?string $ipAddress = null): array
    {
        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = (new PasswordResetToken())
            ->setUser($user)
            ->setPurpose($purpose)
            ->setTokenHash($this->hashToken($rawToken))
            ->setExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

        $this->entityManager->persist($token);

        $this->auditLogger->record($actor, $purpose === PasswordResetToken::PURPOSE_SETUP ? 'user.setup_token_created' : 'user.password_reset_token_created', 'user', $user->getId(), [
            'email' => $user->getEmail(),
            'expires_at' => $token->getExpiresAt()->format(DATE_ATOM),
        ], $ipAddress);

        $this->notificationRouter->notify(
            $user,
            $purpose === PasswordResetToken::PURPOSE_SETUP ? 'Set up your Mudi SACCO support account' : 'Reset your Mudi SACCO support password',
            'A secure one-time password link has been generated for your account.',
            [
                'event' => $purpose === PasswordResetToken::PURPOSE_SETUP ? 'ACCOUNT_SETUP_TOKEN_CREATED' : 'PASSWORD_RESET_TOKEN_CREATED',
                'token_id' => $token->getId()->toRfc4122(),
                'expires_at' => $token->getExpiresAt()->format(DATE_ATOM),
            ]
        );

        return ['token' => $rawToken, 'entity' => $token];
    }

    public function findUsableToken(string $rawToken): ?PasswordResetToken
    {
        if ($rawToken === '') {
            return null;
        }

        $token = $this->entityManager->getRepository(PasswordResetToken::class)->findOneBy(['tokenHash' => $this->hashToken($rawToken)]);

        return $token instanceof PasswordResetToken && $token->isUsable() ? $token : null;
    }

    public function resetPassword(PasswordResetToken $token, string $plainPassword, ?string $ipAddress = null): void
    {
        if (!$token->isUsable()) {
            throw new RuntimeException('This password link has expired or already been used.');
        }

        if (strlen($plainPassword) < 12) {
            throw new RuntimeException('Password must be at least 12 characters.');
        }

        $user = $token->getUser();
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setPasswordChangeRequired(false);
        $user->setActive(true);
        $user->clearLoginFailures();
        $token->markConsumed();

        $this->auditLogger->record($user, 'user.password_changed', 'user', $user->getId(), [
            'purpose' => $token->getPurpose(),
        ], $ipAddress);

        $this->entityManager->flush();
    }

    private function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}