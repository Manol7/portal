<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030200500 extends AbstractMigration
{
    private const EN = [
        'gear.aria' => 'Settings',
        'gear.live' => 'Live translation',
        'live.edit' => 'Fix translation',
        'live.title' => 'Fix translation',
        'live.save' => 'Save',
        'live.error' => 'Could not save the translation.',
    ];

    public function getDescription(): string
    {
        return 'Angielskie teksty menu ustawień i dynamicznego tłumaczenia.';
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
