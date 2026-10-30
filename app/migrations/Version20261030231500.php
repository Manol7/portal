<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030231500 extends AbstractMigration
{
    private const EN = [
        'nav.admin' => 'Admin panel',
        'home.admin' => 'Admin panel',
        'admin.title' => 'Admin panel — Portal',
        'admin.label' => 'Admin panel',
        'admin.heading' => 'Panel.',
        'admin.actions' => 'Actions',
        'admin.users.title' => 'Users',
        'admin.users.text' => 'Accounts and roles',
        'admin.translations.title' => 'Translations',
        'admin.translations.text' => 'Texts in every language',
        'admin.messages.title' => 'Messages',
        'admin.messages.text' => 'Messages from the contact form',
        'admin.settings' => 'Settings',
        'admin.settings.contact_recipient' => 'Contact email address',
        'admin.settings.contact_recipient_hint' => 'Notifications about new contact form messages are sent to this address.',
        'admin.settings.save' => 'Save settings',
        'admin.settings.saved' => 'Settings saved.',
    ];

    public function getDescription(): string
    {
        return 'Tabela ustawień (adres kontaktowy) oraz angielskie teksty panelu administratora.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting (name VARCHAR(64) NOT NULL, value LONGTEXT NOT NULL, PRIMARY KEY (name)) DEFAULT CHARACTER SET utf8mb4');
        foreach (self::EN as $key => $value) {
            $this->addSql(
                'INSERT IGNORE INTO translation (locale, message_key, value) VALUES (?, ?, ?)',
                ['en', $key, $value],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting');
        foreach (array_keys(self::EN) as $key) {
            $this->addSql("DELETE FROM translation WHERE locale = 'en' AND message_key = ?", [$key]);
        }
    }
}
