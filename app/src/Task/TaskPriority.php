<?php

namespace App\Task;

/** Priorytet zadania; `weight()` służy do sortowania (większy = ważniejszy). */
enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
    }

    public function textKey(): string
    {
        return 'tasks.priority.'.$this->value;
    }
}
