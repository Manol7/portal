<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031010000 extends AbstractMigration
{
    private const EN = [
        'nav.tasks' => 'Tasks',
        'home.tasks' => 'Tasks',
        'tasks.title' => 'Tasks — Portal',
        'tasks.label' => 'Your tasks',
        'tasks.heading' => 'Tasks.',
        'tasks.new' => 'New task',
        'tasks.edit' => 'Edit',
        'tasks.delete' => 'Delete',
        'tasks.save' => 'Save',
        'tasks.reset' => 'Clear',
        'tasks.stats' => 'Progress',
        'tasks.stats.total' => 'Total',
        'tasks.stats.done' => 'Done',
        'tasks.stats.overdue' => 'Overdue',
        'tasks.search' => 'Search tasks',
        'tasks.any' => 'any',
        'tasks.priority' => 'Priority',
        'tasks.priority.low' => 'Low',
        'tasks.priority.medium' => 'Medium',
        'tasks.priority.high' => 'High',
        'tasks.tags' => 'Tags',
        'tasks.tags_hint' => 'e.g. work, home',
        'tasks.due' => 'Due date',
        'tasks.due.overdue' => 'Overdue',
        'tasks.due.today' => 'Today',
        'tasks.due.week' => 'Next 7 days',
        'tasks.due.none' => 'No due date',
        'tasks.overdue' => 'overdue',
        'tasks.sort' => 'Sort',
        'tasks.sort.manual' => 'manual',
        'tasks.sort.due' => 'due date',
        'tasks.sort.priority' => 'priority',
        'tasks.sort.newest' => 'newest',
        'tasks.scope_all' => 'Everyone\'s tasks',
        'tasks.shown' => 'Showing {shown} of {total}',
        'tasks.empty' => 'Empty.',
        'tasks.status.todo' => 'To do',
        'tasks.status.doing' => 'In progress',
        'tasks.status.done' => 'Done',
        'tasks.field.title' => 'Title',
        'tasks.field.description' => 'Description',
        'tasks.field.status' => 'Status',
        'tasks.subtasks' => 'Subtasks',
        'tasks.subtasks_hint' => 'One per line',
        'tasks.confirm_delete' => 'Delete this task?',
        'tasks.created' => 'Task added.',
        'tasks.saved' => 'Task saved.',
        'tasks.deleted' => 'Task deleted.',
        'tasks.error.title_blank' => 'Enter a task title.',
        'tasks.error.title_long' => 'The title is too long.',
        'tasks.error.description_long' => 'The description is too long.',
        'tasks.error.tags' => 'Up to 8 tags, each up to 24 characters.',
        'tasks.error.subtasks' => 'Up to 20 subtasks, each up to 120 characters.',
        'tasks.error.invalid' => 'Invalid task data.',
    ];

    public function getDescription(): string
    {
        return 'Tabela zadań oraz angielskie teksty listy zadań.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(10) NOT NULL, priority VARCHAR(10) NOT NULL, due_date DATE DEFAULT NULL, tags JSON NOT NULL, subtasks JSON NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_TASK_OWNER_STATUS (owner_id, status), INDEX IDX_527EDB257E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        foreach (self::EN as $key => $value) {
            $this->addSql('INSERT IGNORE INTO translation (locale, message_key, value) VALUES (?, ?, ?)', ['en', $key, $value]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
        foreach (array_keys(self::EN) as $key) {
            $this->addSql("DELETE FROM translation WHERE locale = 'en' AND message_key = ?", [$key]);
        }
    }
}
