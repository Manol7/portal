<?php

namespace App\Mail;

use App\Settings\Settings;
use App\Translation\Translations;
use Symfony\Component\Mailer\Exception\ExceptionInterface;
use Symfony\Component\Mime\Email;

/**
 * Wysyła próbny mail na zapisane ustawienia, żeby administrator mógł sprawdzić konfigurację.
 */
final class MailTester
{
    private const LOCALE = 'pl';

    public function __construct(
        private readonly MailerFactory $mailers,
        private readonly Sender $sender,
        private readonly Settings $settings,
        private readonly Translations $translations,
    ) {
    }

    /** Zwraca null przy sukcesie albo komunikat błędu transportu. */
    public function send(): ?string
    {
        try {
            $email = (new Email())
                ->from($this->sender->address(self::LOCALE))
                ->to(...$this->settings->contactRecipients())
                ->subject($this->translations->get('email.test.subject', [], self::LOCALE))
                ->text($this->translations->get('email.test.body', [], self::LOCALE));

            $this->mailers->create()->send($email);
        } catch (ExceptionInterface|\LogicException $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
