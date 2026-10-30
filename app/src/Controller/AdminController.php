<?php

namespace App\Controller;

use App\Admin\AdminActions;
use App\Security\Roles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Strona główna panelu administratora: lista akcji. Ustawienia mają własne podstrony (np. ustawienia poczty).
 */
#[IsGranted(Roles::ADMIN)]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    public function index(AdminActions $actions): Response
    {
        return $this->render('admin/index.html.twig', ['actions' => $actions->list()]);
    }
}
