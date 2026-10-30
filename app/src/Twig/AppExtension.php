<?php

namespace App\Twig;

use App\Account\AccountRules;
use App\Entity\User;
use App\Security\Roles;
use App\Security\UserPermissions;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Funkcje Twig niezwiązane z tłumaczeniami: zasoby publiczne oraz role i uprawnienia.
 */
final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserPermissions $permissions,
        private readonly string $publicDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('public_asset', $this->publicAsset(...)),
            new TwigFunction('primary_role', static fn (User $user): string => Roles::highest($user->getRoles())),
            new TwigFunction('can_manage', $this->permissions->canManage(...)),
            new TwigFunction('can_grant', $this->permissions->canGrant(...)),
            new TwigFunction('account_rules', static fn (): array => [
                'email_max' => AccountRules::EMAIL_MAX_LENGTH,
                'password_min' => AccountRules::PASSWORD_MIN_LENGTH,
                'password_max' => AccountRules::PASSWORD_MAX_LENGTH,
            ]),
        ];
    }

    /** Dopisuje czas modyfikacji pliku, żeby przeglądarka pobierała nową wersję po zmianie (bez ręcznego ?v=). */
    public function publicAsset(string $path): string
    {
        $file = $this->publicDir.'/'.ltrim($path, '/');
        $version = is_file($file) ? filemtime($file) : null;

        return '/'.ltrim($path, '/').($version ? '?v='.$version : '');
    }
}
