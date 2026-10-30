<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Task\TaskFilter;
use App\Task\TaskPriority;
use App\Task\TaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Wszystkie zadania w zakresie (jednego użytkownika albo wszystkich, gdy $owner = null), bez filtrów.
     *
     * @return list<Task>
     */
    public function forScope(?User $owner): array
    {
        $qb = $this->createQueryBuilder('t')->addOrderBy('t.position')->addOrderBy('t.id');
        if ($owner !== null) {
            $qb->andWhere('t.owner = :owner')->setParameter('owner', $owner);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Stosuje filtry i sortowanie do już wczytanych zadań. Zakres jest mały (zadania jednej osoby),
     * więc filtrowanie w pamięci jest prostsze niż zapytania po polach JSON.
     *
     * @param list<Task> $tasks
     * @return list<Task>
     */
    public function filter(array $tasks, TaskFilter $filter): array
    {
        $today = new \DateTimeImmutable('today');
        $weekEnd = $today->modify('+7 days');
        $query = mb_strtolower($filter->query);

        $result = array_values(array_filter($tasks, static function (Task $t) use ($filter, $query, $today, $weekEnd): bool {
            if ($query !== '' && !str_contains(mb_strtolower($t->getTitle().' '.($t->getDescription() ?? '')), $query)) {
                return false;
            }
            if ($filter->priority !== null && $t->getPriority() !== $filter->priority) {
                return false;
            }
            if ($filter->tag !== '' && !in_array(mb_strtolower($filter->tag), $t->getTags(), true)) {
                return false;
            }
            $due = $t->getDueDate();

            return match ($filter->due) {
                'overdue' => $t->isOverdue(),
                'today' => $due == $today,
                'week' => $due !== null && $due >= $today && $due <= $weekEnd,
                'none' => $due === null,
                default => true,
            };
        }));

        $sorter = match ($filter->sort) {
            'due' => static fn (Task $a, Task $b): int => ($a->getDueDate()?->getTimestamp() ?? PHP_INT_MAX) <=> ($b->getDueDate()?->getTimestamp() ?? PHP_INT_MAX),
            'priority' => static fn (Task $a, Task $b): int => $b->getPriority()->weight() <=> $a->getPriority()->weight(),
            'newest' => static fn (Task $a, Task $b): int => $b->getId() <=> $a->getId(),
            default => null,
        };
        if ($sorter !== null) {
            // usort jest stabilne od PHP 8.0, więc remisy zachowują ręczną kolejność.
            usort($result, $sorter);
        }

        return $result;
    }

    /** Następna wolna pozycja na końcu kolumny. */
    public function nextPosition(User $owner, TaskStatus $status): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->andWhere('t.owner = :owner')->andWhere('t.status = :status')
            ->setParameter('owner', $owner)->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();

        return $max === null ? 0 : (int) $max + 1;
    }

    /**
     * Podsumowanie postępu dla podanych zadań.
     *
     * @param list<Task> $tasks
     * @return array{total: int, done: int, percent: int, overdue: int, tags: list<string>}
     */
    public function stats(array $tasks): array
    {
        $done = 0;
        $overdue = 0;
        $tags = [];
        foreach ($tasks as $task) {
            $done += $task->getStatus() === TaskStatus::Done ? 1 : 0;
            $overdue += $task->isOverdue() ? 1 : 0;
            foreach ($task->getTags() as $tag) {
                $tags[$tag] = true;
            }
        }
        $total = count($tasks);
        $tagList = array_keys($tags);
        sort($tagList);

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'overdue' => $overdue,
            'tags' => array_map('strval', $tagList),
        ];
    }
}
