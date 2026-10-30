<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031000000 extends AbstractMigration
{
    private const EN = [
        'nav.mailer' => 'Mailer settings',
        'admin.mailer.title' => 'Mailer settings',
        'admin.mailer.text' => 'Transport, sender and recipients',
        'admin.mailer.test_sent' => 'Test message sent.',
        'admin.mailer.test_failed' => 'Sending failed: {error}',
        'admin.mailer.dsn_invalid' => 'Invalid mailer DSN.',
        'admin.mailer.from_invalid' => 'Enter a valid sender email address.',
        'admin.mailer.from_name_long' => 'The sender name is too long.',
        'admin.mailer.recipients_count' => 'Enter 1 to {max} recipient addresses.',
        'admin.mailer.recipient_invalid' => 'Invalid recipient address: {email}',
        'mailer.title' => 'Mailer settings — Portal',
        'mailer.label' => 'Admin panel',
        'mailer.heading' => 'Mailer.',
        'mailer.settings' => 'Settings',
        'mailer.dsn' => 'Transport DSN',
        'mailer.dsn_hint_env' => 'The server configuration DSN is in use. Enter a new one to override it.',
        'mailer.dsn_hint_db' => 'The saved (encrypted) DSN is in use. Leave empty to keep it.',
        'mailer.dsn_reset' => 'Restore default ({dsn})',
        'mailer.from' => 'Sender address',
        'mailer.from_name' => 'Sender name',
        'mailer.recipients' => 'Recipients',
        'mailer.recipients_hint' => 'One address per line or comma-separated.',
        'mailer.test' => 'Test',
        'mailer.test_hint' => 'Send a test message to the recipients.',
        'mailer.test_send' => 'Send test email',
        'email.test.subject' => 'Test message',
        'email.test.body' => 'The mailer configuration works.',
    ];

    private const OBSOLETE = ['admin.settings', 'admin.settings.contact_recipient', 'admin.settings.contact_recipient_hint'];

    public function getDescription(): string
    {
        return 'Angielskie teksty ustawień mailera.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::EN as $key => $value) {
            $this->addSql('INSERT IGNORE INTO translation (locale, message_key, value) VALUES (?, ?, ?)', ['en', $key, $value]);
        }
        foreach (self::OBSOLETE as $key) {
            $this->addSql('DELETE FROM translation WHERE message_key = ?', [$key]);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::EN) as $key) {
            $this->addSql("DELETE FROM translation WHERE locale = 'en' AND message_key = ?", [$key]);
        }
    }
}
