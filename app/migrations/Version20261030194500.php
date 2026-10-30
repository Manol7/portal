<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030194500 extends AbstractMigration
{
    private const EN = [
        'error.404.title' => 'Page not found.',
        'error.404.text' => 'The address is wrong or the page has been moved.',
        'error.403.title' => 'Access denied.',
        'error.403.text' => 'You do not have permission to view this page.',
        'error.500.title' => 'Something went wrong.',
        'error.500.text' => 'Please try again in a moment.',
        'error.home' => 'Back to the home page',
    ];

    public function getDescription(): string
    {
        return 'Angielskie teksty stron błędów.';
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
