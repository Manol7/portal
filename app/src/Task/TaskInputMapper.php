<?php

namespace App\Task;

use App\Entity\Task;

/**
 * Wczytuje i waliduje dane zadania z formularza. Zwraca klucz tekstu z błędem albo null, gdy dane zapisano w encji.
 */
final class TaskInputMapper
{
    /** @param array<string, mixed> $input */
    public function apply(Task $task, array $input): ?string
    {
        $title = $this->string($input, 'title');
        if ($title === '') {
            return 'tasks.error.title_blank';
        }
        if (mb_strlen($title) > Task::TITLE_MAX_LENGTH) {
            return 'tasks.error.title_long';
        }

        $description = $this->string($input, 'description');
        if (mb_strlen($description) > Task::DESCRIPTION_MAX_LENGTH) {
            return 'tasks.error.description_long';
        }

        $status = TaskStatus::tryFrom($this->string($input, 'status'));
        $priority = TaskPriority::tryFrom($this->string($input, 'priority'));
        if ($status === null || $priority === null) {
            return 'tasks.error.invalid';
        }

        $dueRaw = $this->string($input, 'due');
        $due = null;
        if ($dueRaw !== '') {
            $due = \DateTimeImmutable::createFromFormat('!Y-m-d', $dueRaw);
            if ($due === false || $due->format('Y-m-d') !== $dueRaw) {
                return 'tasks.error.invalid';
            }
        }

        $tags = $this->tags($this->string($input, 'tags'));
        if ($tags === null) {
            return 'tasks.error.tags';
        }

        $subtasks = $this->subtasks($this->string($input, 'subtasks'), $task->getSubtasks());
        if ($subtasks === null) {
            return 'tasks.error.subtasks';
        }

        $task->setTitle($title);
        $task->setDescription($description);
        $task->setStatus($status);
        $task->setPriority($priority);
        $task->setDueDate($due ?: null);
        $task->setTags($tags);
        $task->setSubtasks($subtasks);

        return null;
    }

    /** @return list<string>|null null, gdy tagów jest za dużo lub któryś jest za długi */
    private function tags(string $raw): ?array
    {
        $tags = [];
        foreach (preg_split('/[,;\n]+/', $raw) ?: [] as $tag) {
            $tag = mb_strtolower(trim($tag));
            if ($tag === '') {
                continue;
            }
            if (mb_strlen($tag) > Task::TAG_MAX_LENGTH) {
                return null;
            }
            $tags[$tag] = $tag;
        }

        return count($tags) > Task::TAGS_MAX ? null : array_values($tags);
    }

    /**
     * Jedno podzadanie na wiersz; stan "zrobione" zostaje zachowany dla podzadań o tej samej treści.
     *
     * @param list<array{title: string, done: bool}> $existing
     * @return list<array{title: string, done: bool}>|null
     */
    private function subtasks(string $raw, array $existing): ?array
    {
        $doneByTitle = [];
        foreach ($existing as $subtask) {
            $doneByTitle[$subtask['title']] = $subtask['done'];
        }

        $subtasks = [];
        foreach (preg_split('/\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (mb_strlen($line) > Task::SUBTASK_MAX_LENGTH) {
                return null;
            }
            $subtasks[] = ['title' => $line, 'done' => $doneByTitle[$line] ?? false];
        }

        return count($subtasks) > Task::SUBTASKS_MAX ? null : $subtasks;
    }

    /** @param array<string, mixed> $input */
    private function string(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? trim(str_replace("\r\n", "\n", $input[$key])) : '';
    }
}
