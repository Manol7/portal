<?php

namespace App\Controller;

use App\Account\AccountRules;
use App\Account\AccountUpdateValidator;
use App\Entity\User;
use App\Form\CreateUserType;
use App\Repository\UserRepository;
use App\Security\Roles;
use App\Security\UserPermissions;
use App\Translation\Translations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Panel użytkowników: lista, tworzenie, zmiana roli, edycja konta i usuwanie.
 * Zasady "kto kim zarządza" są w UserPermissions, a nie tutaj.
 */
#[IsGranted(Roles::ADMIN)]
final class UserAdminController extends AbstractController
{
    public function __construct(
        private readonly Translations $translations,
        private readonly UserPermissions $permissions,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin/users', name: 'app_admin_users', methods: ['GET', 'POST'])]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(CreateUserType::class, $user, [
            'roles' => $this->permissions->grantableRoles(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('role')->getData();
            // Lista ról w formularzu jest ograniczona, ale uprawnienie sprawdzamy też po stronie serwera.
            if (!$this->permissions->canGrant($role)) {
                throw $this->createAccessDeniedException();
            }

            $user->setEmail(AccountRules::normalizeEmail($user->getEmail()));
            $user->setRoles([$role]);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translations->get('flash.created'));

            return $this->redirectToRoute('app_admin_users', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $this->users->findBy([], ['email' => 'ASC'], 100),
            'form' => $form->createView(),
            'show_create_modal' => $form->isSubmitted(),
            'grantable_roles' => $this->permissions->grantableRoles(),
        ]);
    }

    #[Route('/admin/users/{id}/role', name: 'app_admin_users_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateRole(int $id, Request $request): Response
    {
        $user = $this->findUser($id);
        $this->denyUnlessValidToken($request, 'update-user-role-'.$user->getId());
        $this->denyUnlessCanManage($user);

        $role = $request->request->get('role');
        $error = match (true) {
            $this->getUser() === $user => 'flash.own_role',
            !Roles::isValid($role) => 'flash.role_invalid',
            !$this->permissions->canGrant($role) => 'flash.role_invalid',
            default => null,
        };

        if ($error === null) {
            $user->setRoles([$role]);
            $this->entityManager->flush();
        }

        $message = $this->translations->get($error ?? 'flash.role_changed');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => $message], $error === null ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash($error === null ? 'success' : 'error', $message);

        return $this->redirectToRoute('app_admin_users', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_admin_users_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateAccount(
        int $id,
        Request $request,
        AccountUpdateValidator $validator,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->findUser($id);
        $this->denyUnlessValidToken($request, 'edit-user-'.$user->getId());
        $this->denyUnlessCanManage($user);

        $input = $request->request;
        $error = $validator->validate($user, $input->get('email'), $input->get('password'), $input->get('password_confirmation'));

        if ($error !== null) {
            $this->addFlash('error', $error);
        } else {
            $user->setEmail(AccountRules::normalizeEmail($input->get('email')));
            if ($input->get('password') !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $input->get('password')));
            }
            $this->entityManager->flush();
            $this->addFlash('success', $this->translations->get('flash.saved'));
        }

        return $this->redirectToRoute('app_admin_users', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_users_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->findUser($id);
        $this->denyUnlessValidToken($request, 'delete-user-'.$user->getId());
        $this->denyUnlessCanManage($user);

        if ($this->getUser() === $user) {
            $this->addFlash('error', $this->translations->get('flash.own_delete'));
        } else {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', $this->translations->get('flash.deleted'));
        }

        return $this->redirectToRoute('app_admin_users', status: Response::HTTP_SEE_OTHER);
    }

    private function findUser(int $id): User
    {
        return $this->users->find($id) ?? throw $this->createNotFoundException();
    }

    private function denyUnlessValidToken(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translations->get('flash.csrf'));
        }
    }

    private function denyUnlessCanManage(User $target): void
    {
        if (!$this->permissions->canManage($target)) {
            throw $this->createAccessDeniedException();
        }
    }
}
