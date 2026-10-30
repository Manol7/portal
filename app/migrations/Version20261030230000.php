<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030230000 extends AbstractMigration
{
    private const REMOVED_KEYS = ['contact.name', 'contact.name_blank', 'contact.name_long'];

    public function getDescription(): string
    {
        return 'Formularz kontaktowy bez imienia i nazwiska: usunięcie kolumny i nieużywanych tłumaczeń.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact_message DROP name');
        foreach (self::REMOVED_KEYS as $key) {
            $this->addSql('DELETE FROM translation WHERE message_key = ?', [$key]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE contact_message ADD name VARCHAR(100) NOT NULL DEFAULT ''");
    }
}
