<?php

namespace App\Task;

/** Filtry i sortowanie listy zadań wczytane z adresu URL; nieznane wartości są ignorowane. */
final class TaskFilter
{
    public const SORTS = ['manual', 'due', 'priority', 'newest'];
    public const DUE_OPTIONS = ['overdue', 'today', 'week', 'none'];

    public function __construct(
        public readonly string $query = '',
        public readonly ?TaskPriority $priority = null,
        public readonly string $tag = '',
        public readonly ?string $due = null,
        public readonly string $sort = 'manual',
        public readonly bool $allUsers = false,
    ) {
    }

    /** @param array<string, mixed> $params */
    public static function fromQuery(array $params, bool $canSeeAll): self
    {
        $string = static fn (string $key): string => is_string($params[$key] ?? null) ? trim($params[$key]) : '';
        $due = $string('due');
        $sort = $string('sort');

        return new self(
            mb_substr($string('q'), 0, 100),
            TaskPriority::tryFrom($string('priority')),
            mb_substr($string('tag'), 0, 24),
            in_array($due, self::DUE_OPTIONS, true) ? $due : null,
            in_array($sort, self::SORTS, true) ? $sort : 'manual',
            $canSeeAll && $string('scope') === 'all',
        );
    }

    public function isActive(): bool
    {
        return $this->query !== '' || $this->priority !== null || $this->tag !== '' || $this->due !== null;
    }

    /** Parametry adresu zachowujące bieżący widok (bez wartości domyślnych). */
    public function toQuery(): array
    {
        return array_filter([
            'q' => $this->query,
            'priority' => $this->priority?->value,
            'tag' => $this->tag,
            'due' => $this->due,
            'sort' => $this->sort === 'manual' ? null : $this->sort,
            'scope' => $this->allUsers ? 'all' : null,
        ], static fn (?string $v): bool => $v !== null && $v !== '');
    }
}
