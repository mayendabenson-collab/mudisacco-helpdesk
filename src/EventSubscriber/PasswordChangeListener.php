<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Forces users with passwordChangeRequired=true to change their password
 * before they can access any other page in the application.
 */
class PasswordChangeListener implements EventSubscriberInterface
{
    /** Routes that must always be accessible even when a password change is required. */
    private const ALLOWED_ROUTES = [
        'password_change',
        'app_logout',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        // Skip if on an allowed route (change page, logout) or non-HTML requests (API, assets).
        if (in_array($currentRoute, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isPasswordChangeRequired()) {
            return;
        }

        // Authenticated user with forced password change: redirect to change page.
        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('password_change')
        ));
    }
}
