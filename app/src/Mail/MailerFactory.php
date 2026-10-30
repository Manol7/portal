<?php

namespace App\Mail;

use App\Settings\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Buduje mailer z DSN zapisanego w panelu (albo z MAILER_DSN). Dzięki temu zmiana ustawień działa bez restartu.
 * Globalny dispatcher jest potrzebny, żeby szablony TemplatedEmail były renderowane.
 */
final class MailerFactory
{
    public function __construct(
        private readonly Settings $settings,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(?string $dsn = null): MailerInterface
    {
        $transport = Transport::fromDsn($dsn ?? $this->settings->mailerDsn(), $this->dispatcher, null, $this->logger);

        return new Mailer($transport, null, $this->dispatcher);
    }
}
