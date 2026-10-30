<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Reguły "kto może zarządzać kim" dla panelu użytkowników.
 * Zmiana zasad (np. nowa rola o ograniczonych uprawnieniach) odbywa się tylko tutaj.
 */
final class UserPermissions
{
    public function __construct(private readonly Security $security)
    {
    }

    /** Administrator nie może zarządzać kontami superadministratorów. */
    public function canManage(User $target): bool
    {
        return Roles::highest($target->getRoles()) !== Roles::SUPER_ADMIN
            || $this->security->isGranted(Roles::SUPER_ADMIN);
    }

    /** Rolę superadministratora może nadać tylko superadministrator. */
    public function canGrant(string $role): bool
    {
        return Roles::isValid($role)
            && ($role !== Roles::SUPER_ADMIN || $this->security->isGranted(Roles::SUPER_ADMIN));
    }

    /**
     * @return list<string>
     */
    public function grantableRoles(): array
    {
        return array_values(array_filter(Roles::ALL, $this->canGrant(...)));
    }
}
