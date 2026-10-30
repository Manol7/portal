<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030220000 extends AbstractMigration
{
    private const EN = [
        'nav.contact' => 'Contact',
        'nav.messages' => 'Messages',
        'home.messages' => 'Messages',
        'home.contact' => 'Contact',

        'contact.title' => 'Contact — Portal',
        'contact.label' => 'Write to us',
        'contact.heading' => 'Contact.',
        'contact.text' => 'We will reply as soon as possible.',
        'contact.name' => 'Full name',
        'contact.email' => 'Email address',
        'contact.subject' => 'Subject',
        'contact.message' => 'Message',
        'contact.honeypot' => 'Leave this field empty',
        'contact.submit' => 'Send message',
        'contact.sent' => 'Thank you! Your message has been sent.',
        'contact.rate_limited' => 'Too many messages sent. Please try again later.',
        'contact.name_blank' => 'Enter your name.',
        'contact.name_long' => 'The name is too long.',
        'contact.subject_blank' => 'Enter a subject.',
        'contact.subject_long' => 'The subject is too long.',
        'contact.message_blank' => 'Write a message.',
        'contact.message_short' => 'The message must have at least {{ limit }} characters.',
        'contact.message_long' => 'The message can have at most {{ limit }} characters.',

        'messages.title' => 'Messages — Portal',
        'messages.label' => 'Admin panel',
        'messages.heading' => 'Messages.',
        'messages.table_title' => 'Contact form messages',
        'messages.count' => 'Number of messages: {count}',
        'messages.col_date' => 'Date',
        'messages.col_from' => 'From',
        'messages.col_subject' => 'Subject',
        'messages.open' => 'Open',
        'messages.unread' => 'Unread',
        'messages.mark_read' => 'Mark as read',
        'messages.mark_unread' => 'Mark as unread',
        'messages.none' => 'No messages.',
        'messages.deleted' => 'Message deleted.',
    ];

    public function getDescription(): string
    {
        return 'Angielskie teksty formularza kontaktowego i panelu wiadomości (maile dla właściciela pozostają po polsku).';
    }

    public function up(Schema $schema): void
    {
        foreach (self::EN as $key => $value) {
            $this->addSql(
                'INSERT IGNORE INTO translation (locale, message_key, value) VALUES (?, ?, ?)',
                ['en', $key, $value],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::EN) as $key) {
            $this->addSql("DELETE FROM translation WHERE locale = 'en' AND message_key = ?", [$key]);
        }
    }
}
