<?php

namespace App\Security;

/**
 * Jedyne źródło prawdy o rolach w aplikacji.
 * Nowa rola: dodaj stałą, wpisz ją do ALL (od najniższej do najwyższej),
 * dopisz do hierarchii w config/packages/security.yaml oraz tekst `role.<ROLA>` w DefaultTexts.
 */
final class Roles
{
    public const USER = 'ROLE_USER';
    public const ADMIN = 'ROLE_ADMIN';
    public const SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /** Od najniższej do najwyższej. */
    public const ALL = [self::USER, self::ADMIN, self::SUPER_ADMIN];

    public static function isValid(mixed $role): bool
    {
        return is_string($role) && in_array($role, self::ALL, true);
    }

    /** Klucz tekstu z nazwą roli. */
    public static function textKey(string $role): string
    {
        return 'role.'.$role;
    }

    /**
     * Konto ma jedną rolę nadaną ręcznie; zwraca najwyższą z podanych.
     *
     * @param list<string> $roles
     */
    public static function highest(array $roles): string
    {
        foreach (array_reverse(self::ALL) as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return self::USER;
    }
}
