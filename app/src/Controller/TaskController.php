<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Security\Roles;
use App\Task\TaskFilter;
use App\Task\TaskInputMapper;
use App\Task\TaskPriority;
use App\Task\TaskStatus;
use App\Translation\Translations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Lista zadań w widoku tablicy. Zadania są prywatne; administrator może przełączyć widok na zadania wszystkich.
 */
#[IsGranted(Roles::USER)]
final class TaskController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'tasks';

    public function __construct(
        private readonly Translations $translations,
        private readonly TaskRepository $tasks,
        private readonly TaskInputMapper $mapper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/tasks', name: 'app_tasks', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = TaskFilter::fromQuery($request->query->all(), $this->isGranted(Roles::ADMIN));
        $scope = $this->tasks->forScope($filter->allUsers ? null : $this->currentUser());
        $visible = $this->tasks->filter($scope, $filter);

        $columns = [];
        foreach (TaskStatus::cases() as $status) {
            $columns[$status->value] = [];
        }
        foreach ($visible as $task) {
            $columns[$task->getStatus()->value][] = $task;
        }

        $context = [
            'filter' => $filter,
            'columns' => $columns,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'stats' => $this->tasks->stats($scope),
            'shown' => count($visible),
            'sorts' => TaskFilter::SORTS,
            'due_options' => TaskFilter::DUE_OPTIONS,
            'limits' => [
                'title' => Task::TITLE_MAX_LENGTH,
                'description' => Task::DESCRIPTION_MAX_LENGTH,
                'tag' => Task::TAG_MAX_LENGTH,
                'subtask' => Task::SUBTASK_MAX_LENGTH,
            ],
        ];

        // Odświeżanie w tle pobiera tylko części dynamiczne; wynik nie może trafić do cache przeglądarki.
        if ($request->query->getBoolean('partial')) {
            $response = $this->render('tasks/_live.html.twig', $context);
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        }

        return $this->render('tasks/index.html.twig', $context);
    }

    /** Tworzy zadanie albo zapisuje zmiany, gdy podano `id`. */
    #[Route('/tasks/save', name: 'app_tasks_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->denyUnlessValidToken($request);
        $input = $request->request->all();

        $id = (int) ($input['id'] ?? 0);
        $task = $id > 0 ? $this->findAccessible($id) : new Task($this->currentUser());
        $previousStatus = $id > 0 ? $task->getStatus() : null;

        $error = $this->mapper->apply($task, $input);
        if ($error !== null) {
            $this->addFlash('error', $this->translations->get($error));
        } else {
            if ($task->getStatus() !== $previousStatus) {
                $task->setPosition($this->tasks->nextPosition($task->getOwner(), $task->getStatus()));
            }
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            $this->addFlash('success', $this->translations->get($id > 0 ? 'tasks.saved' : 'tasks.created'));
        }

        return $this->backToBoard($input);
    }

    #[Route('/tasks/{id}/delete', name: 'app_tasks_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $this->denyUnlessValidToken($request);
        $task = $this->findAccessible($id);

        $this->entityManager->remove($task);
        $this->entityManager->flush();
        $this->addFlash('success', $this->translations->get('tasks.deleted'));

        return $this->backToBoard($request->request->all());
    }

    /** Przeciąganie kart: zapisuje nowy status i kolejność jednej kolumny (JSON: {status, ids}). */
    #[Route('/tasks/reorder', name: 'app_tasks_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $this->denyUnlessValidToken($request);
        $payload = json_decode($request->getContent(), true);
        $status = TaskStatus::tryFrom(is_array($payload) ? (string) ($payload['status'] ?? '') : '');
        $ids = is_array($payload) && is_array($payload['ids'] ?? null) ? array_slice(array_map('intval', $payload['ids']), 0, 500) : null;
        if ($status === null || $ids === null) {
            return new JsonResponse(['ok' => false], Response::HTTP_BAD_REQUEST);
        }

        foreach ($ids as $position => $id) {
            $task = $this->findAccessible($id);
            $task->setStatus($status);
            $task->setPosition($position);
        }
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true]);
    }

    /** Przełącza podzadanie bez przeładowania strony; zwraca nowy postęp karty. */
    #[Route('/tasks/{id}/subtasks/{index}', name: 'app_tasks_subtask', requirements: ['id' => '\d+', 'index' => '\d+'], methods: ['POST'])]
    public function toggleSubtask(int $id, int $index, Request $request): JsonResponse
    {
        $this->denyUnlessValidToken($request);
        $task = $this->findAccessible($id);
        if (!$task->toggleSubtask($index)) {
            return new JsonResponse(['ok' => false], Response::HTTP_NOT_FOUND);
        }
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true, 'done' => $task->subtasksDone(), 'total' => count($task->getSubtasks())]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /** Zadanie własne albo dowolne dla administratora; w przeciwnym razie 404 (nie zdradzamy istnienia cudzych zadań). */
    private function findAccessible(int $id): Task
    {
        $task = $this->tasks->find($id);
        if ($task === null || (!$this->isGranted(Roles::ADMIN) && $task->getOwner()->getId() !== $this->currentUser()->getId())) {
            throw $this->createNotFoundException();
        }

        return $task;
    }

    /** Powrót na tablicę z zachowaniem filtrów (formularze przekazują je w polu `view`). */
    private function backToBoard(array $input): Response
    {
        parse_str(is_string($input['view'] ?? null) ? $input['view'] : '', $view);
        $filter = TaskFilter::fromQuery($view, $this->isGranted(Roles::ADMIN));

        return $this->redirectToRoute('app_tasks', $filter->toQuery(), Response::HTTP_SEE_OTHER);
    }

    private function denyUnlessValidToken(Request $request): void
    {
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, is_string($token) ? $token : null)) {
            throw $this->createAccessDeniedException($this->translations->get('flash.csrf'));
        }
    }
}
