<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use App\Task\TaskPriority;
use App\Task\TaskStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Zadanie użytkownika. Tagi i podzadania są małymi listami trzymanymi w JSON razem z zadaniem.
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Index(name: 'IDX_TASK_OWNER_STATUS', fields: ['owner', 'status'])]
class Task
{
    public const TITLE_MAX_LENGTH = 160;
    public const DESCRIPTION_MAX_LENGTH = 2000;
    public const TAG_MAX_LENGTH = 24;
    public const TAGS_MAX = 8;
    public const SUBTASK_MAX_LENGTH = 120;
    public const SUBTASKS_MAX = 20;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: self::TITLE_MAX_LENGTH)]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 10, enumType: TaskStatus::class)]
    private TaskStatus $status = TaskStatus::Todo;

    #[ORM\Column(length: 10, enumType: TaskPriority::class)]
    private TaskPriority $priority = TaskPriority::Medium;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $tags = [];

    /** @var list<array{title: string, done: bool}> */
    #[ORM\Column(type: Types::JSON)]
    private array $subtasks = [];

    /** Kolejność w kolumnie (ustawiana przeciąganiem). */
    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $description = $description === null ? '' : trim(str_replace("\r\n", "\n", $description));
        $this->description = $description === '' ? null : $description;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    /** @return list<string> */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @param list<string> $tags */
    public function setTags(array $tags): void
    {
        $this->tags = array_values($tags);
    }

    /** @return list<array{title: string, done: bool}> */
    public function getSubtasks(): array
    {
        return $this->subtasks;
    }

    /** @param list<array{title: string, done: bool}> $subtasks */
    public function setSubtasks(array $subtasks): void
    {
        $this->subtasks = array_values($subtasks);
    }

    /** Przełącza podzadanie o danym indeksie; zwraca false, gdy takiego nie ma. */
    public function toggleSubtask(int $index): bool
    {
        if (!isset($this->subtasks[$index])) {
            return false;
        }
        $this->subtasks[$index]['done'] = !$this->subtasks[$index]['done'];

        return true;
    }

    public function subtasksDone(): int
    {
        return count(array_filter($this->subtasks, static fn (array $s): bool => $s['done']));
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isOverdue(): bool
    {
        return $this->dueDate !== null
            && $this->status !== TaskStatus::Done
            && $this->dueDate < new \DateTimeImmutable('today');
    }
}
