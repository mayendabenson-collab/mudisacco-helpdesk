<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return Permission::tryFrom($attribute) instanceof Permission;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if (in_array(SystemRole::ADMIN->value, $user->getRoles(), true)) {
            return true;
        }

        foreach ($user->getRoleEntities() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if ($permission->getCode() === $attribute) {
                    return true;
                }
            }
        }

        return false;
    }
}
