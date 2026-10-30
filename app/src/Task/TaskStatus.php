<?php

namespace App\Task;

/**
 * Kolumny tablicy zadań (w kolejności wyświetlania). Nowy status: dopisz case i teksty `tasks.status.<wartość>`.
 */
enum TaskStatus: string
{
    case Todo = 'todo';
    case Doing = 'doing';
    case Done = 'done';

    public function textKey(): string
    {
        return 'tasks.status.'.$this->value;
    }
}
