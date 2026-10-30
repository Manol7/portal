<?php

namespace App\Mail;

use App\Entity\ContactMessage;
use App\Settings\Settings;
use App\Translation\Translations;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\ExceptionInterface;
use Symfony\Component\Mime\Address;

/**
 * Wysyła powiadomienie o nowej wiadomości z formularza kontaktowego do skrzynek z ustawień.
 * Treść maila dla właściciela jest zawsze po polsku, niezależnie od języka odwiedzającego.
 */
final class ContactNotifier
{
    private const OWNER_LOCALE = 'pl';

    public function __construct(
        private readonly MailerFactory $mailers,
        private readonly Sender $sender,
        private readonly Settings $settings,
        private readonly Translations $translations,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** Zwraca false, gdy wysyłka się nie powiodła (wiadomość i tak jest już w bazie). */
    public function notify(ContactMessage $message): bool
    {
        $subject = $this->translations->get('email.contact.subject', ['subject' => $message->getSubject()], self::OWNER_LOCALE);

        try {
            $email = (new TemplatedEmail())
                ->from($this->sender->address(self::OWNER_LOCALE))
                ->to(...$this->settings->contactRecipients())
                ->replyTo(new Address($message->getEmail()))
                ->subject($this->singleLine($subject))
                ->htmlTemplate('email/contact.html.twig')
                ->textTemplate('email/contact.txt.twig')
                ->context(['message' => $message, 'locale' => self::OWNER_LOCALE]);

            $this->mailers->create()->send($email);
        } catch (ExceptionInterface|\LogicException $exception) {
            $this->logger->error('Nie udało się wysłać wiadomości kontaktowej.', ['id' => $message->getId(), 'exception' => $exception]);

            return false;
        }

        return true;
    }

    /** Usuwa znaki nowej linii, żeby dane od użytkownika nie trafiły do nagłówków maila jako nowe nagłówki. */
    private function singleLine(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
