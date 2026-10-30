<?php

namespace App\Account;

use App\Entity\User;
use App\Translation\Translations;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reguły pól konta współdzielone przez formularz tworzenia, edycję i szablony (atrybuty HTML).
 */
final class AccountRules
{
    public const EMAIL_MAX_LENGTH = User::EMAIL_MAX_LENGTH;
    public const PASSWORD_MIN_LENGTH = 4;
    public const PASSWORD_MAX_LENGTH = 4096;

    /**
     * @return list<Assert\Constraint>
     */
    public static function email(Translations $t): array
    {
        return [
            new Assert\NotBlank(message: $t->get('flash.email_blank')),
            new Assert\Email(message: $t->get('flash.email_invalid')),
            new Assert\Length(max: self::EMAIL_MAX_LENGTH, maxMessage: $t->get('flash.email_long')),
        ];
    }

    /**
     * @return list<Assert\Constraint>
     */
    public static function password(Translations $t): array
    {
        return [
            new Assert\Length(
                min: self::PASSWORD_MIN_LENGTH,
                max: self::PASSWORD_MAX_LENGTH,
                minMessage: $t->get('form.password_min'),
            ),
        ];
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
