<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CreateUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class UserAdminController extends AbstractController
{
    #[Route(
        '/admin/users',
        name: 'app_admin_users',
        methods: ['GET', 'POST'],
    )]
    public function index(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(CreateUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail(strtolower(trim($user->getEmail())));
            $user->setRoles([$form->get('role')->getData()]);
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData(),
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utworzono konto użytkownika.');

            return $this->redirectToRoute(
                'app_admin_users',
                status: Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findBy([], ['email' => 'ASC'], 100),
            'form' => $form->createView(),
        ]);
    }
}