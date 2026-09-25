<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Security\PasswordReset\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordController extends AbstractController
{
    #[Route('/password/request', name: 'password_request', methods: ['GET', 'POST'])]
    public function requestReset(Request $request, EntityManagerInterface $entityManager, PasswordResetService $passwordResetService): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('password_request', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid password request token.');
            }

            $email = strtolower(trim((string) $request->request->get('email')));
            $user = $email !== '' ? $entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;

            if ($user instanceof User && $user->isActive()) {
                $tokenData = $passwordResetService->createToken($user, PasswordResetToken::PURPOSE_RESET, null, $request->getClientIp());
                $entityManager->flush();

                if ($this->getParameter('kernel.environment') !== 'prod') {
                    $resetUrl = $this->generateUrl('password_reset', ['token' => $tokenData['token']], UrlGeneratorInterface::ABSOLUTE_URL);
                    $this->addFlash('success', 'Development reset link: ' . $resetUrl);
                }
            }

            $this->addFlash('success', 'If the account can receive password resets, instructions have been generated.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_request.html.twig');
    }

    #[Route('/password/reset/{token}', name: 'password_reset', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, PasswordResetService $passwordResetService): Response
    {
        $resetToken = $passwordResetService->findUsableToken($token);
        if (!$resetToken instanceof PasswordResetToken) {
            $this->addFlash('error', 'This password link is invalid or expired.');

            return $this->redirectToRoute('password_request');
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('password_reset_' . $token, (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $password = (string) $request->request->get('password');
            $confirmPassword = (string) $request->request->get('confirm_password');

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if ($errors === []) {
                try {
                    $passwordResetService->resetPassword($resetToken, $password, $request->getClientIp());
                    $this->addFlash('success', 'Password updated. You can now sign in.');

                    return $this->redirectToRoute('app_login');
                } catch (RuntimeException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('security/password_reset.html.twig', [
            'token' => $token,
            'resetToken' => $resetToken,
            'errors' => $errors,
        ]);
    }

    /**
     * Authenticated mandatory password change for users whose passwordChangeRequired flag is set.
     * Requires the current password to verify ownership before accepting the new one.
     */
    #[Route('/password/change', name: 'password_change', methods: ['GET', 'POST'])]
    public function change(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('password_change', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $current  = (string) $request->request->get('current_password');
            $new      = (string) $request->request->get('password');
            $confirm  = (string) $request->request->get('confirm_password');

            if (!$passwordHasher->isPasswordValid($user, $current)) {
                $errors[] = 'Current password is incorrect.';
            }

            if (strlen($new) < 12) {
                $errors[] = 'New password must be at least 12 characters.';
            }

            if ($new !== $confirm) {
                $errors[] = 'New passwords do not match.';
            }

            if ($errors === []) {
                $user->setPasswordHash($passwordHasher->hashPassword($user, $new));
                $user->setPasswordChangeRequired(false);
                $entityManager->flush();

                $this->addFlash('success', 'Password updated successfully.');

                return $this->redirectToRoute('app_dashboard');
            }
        }

        return $this->render('security/password_change.html.twig', [
            'errors' => $errors,
        ]);
    }
}