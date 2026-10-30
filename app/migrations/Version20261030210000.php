<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030210000 extends AbstractMigration
{
    private const EN = [
        'flash.login_success' => 'Logged in successfully.',
        'form.password_blank' => 'Enter a password.',
        'nav.error' => 'Error',
        'lang.name_pl' => 'Polski',
        'lang.name_en' => 'English',
    ];

    public function getDescription(): string
    {
        return 'Angielskie teksty dodane przy refaktoryzacji (logowanie, formularz, okruszki, nazwy języków).';
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
