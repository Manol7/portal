<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;
use App\Security\Roles;
use App\Translation\Translations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Panel wiadomości z formularza kontaktowego: podgląd, oznaczanie jako przeczytane i usuwanie.
 */
#[IsGranted(Roles::ADMIN)]
final class MessageAdminController extends AbstractController
{
    public function __construct(
        private readonly Translations $translations,
        private readonly ContactMessageRepository $messages,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/messages', name: 'app_admin_messages', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/messages/index.html.twig', ['messages' => $this->messages->latest()]);
    }

    #[Route('/admin/messages/{id}/read', name: 'app_admin_messages_read', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function read(int $id, Request $request): Response
    {
        $message = $this->findMessage($id);
        $this->denyUnlessValidToken($request, 'read-message-'.$id);

        $message->markRead($request->request->get('read', '1') === '1');
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_messages', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/messages/{id}/delete', name: 'app_admin_messages_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $message = $this->findMessage($id);
        $this->denyUnlessValidToken($request, 'delete-message-'.$id);

        $this->entityManager->remove($message);
        $this->entityManager->flush();
        $this->addFlash('success', $this->translations->get('messages.deleted'));

        return $this->redirectToRoute('app_admin_messages', status: Response::HTTP_SEE_OTHER);
    }

    private function findMessage(int $id): ContactMessage
    {
        return $this->messages->find($id) ?? throw $this->createNotFoundException();
    }

    private function denyUnlessValidToken(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translations->get('flash.csrf'));
        }
    }
}
