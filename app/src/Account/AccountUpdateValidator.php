<?php

namespace App\Account;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Translation\Translations;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Walidacja danych z formularza edycji konta. Zwraca pierwszy błąd (już przetłumaczony) albo null.
 */
final class AccountUpdateValidator
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ValidatorInterface $validator,
        private readonly Translations $translations,
    ) {
    }

    public function validate(User $user, mixed $email, mixed $password, mixed $confirmation): ?string
    {
        if (!is_string($email) || !is_string($password) || !is_string($confirmation)) {
            return $this->translations->get('flash.bad_data');
        }

        $violations = $this->validator->validate(AccountRules::normalizeEmail($email), AccountRules::email($this->translations));
        if (count($violations) > 0) {
            return $violations[0]->getMessage();
        }

        $existing = $this->users->findOneBy(['email' => AccountRules::normalizeEmail($email)]);
        if ($existing instanceof User && $existing->getId() !== $user->getId()) {
            return $this->translations->get('flash.email_used');
        }

        if ($password !== $confirmation) {
            return $this->translations->get('flash.password_mismatch');
        }

        // Puste hasło oznacza "bez zmiany".
        if ($password !== '') {
            $violations = $this->validator->validate($password, AccountRules::password($this->translations));
            if (count($violations) > 0) {
                return $violations[0]->getMessage();
            }
        }

        return null;
    }
}
