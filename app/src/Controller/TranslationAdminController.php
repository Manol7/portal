<?php

namespace App\Controller;

use App\Security\Roles;
use App\Translation\Translations;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Edycja słownika tekstów: pełna tabela oraz pojedynczy wpis (tryb dynamicznego tłumaczenia).
 * Języki pochodzą z Translations::LOCALES, więc nowy język nie wymaga zmian tutaj.
 */
#[IsGranted(Roles::ADMIN)]
final class TranslationAdminController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'save-translations';

    public function __construct(private readonly Translations $translations)
    {
    }

    #[Route('/admin/translations', name: 'app_admin_translations', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->denyUnlessValidToken($request);

            foreach (Translations::LOCALES as $locale) {
                $values = $request->request->all($locale);
                $this->translations->save($locale, array_map('strval', $values));
            }

            $this->addFlash('success', $this->translations->get('tr.saved'));

            return $this->redirectToRoute('app_admin_translations', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/translations/index.html.twig', [
            'entries' => $this->translations->entries(),
        ]);
    }

    #[Route('/admin/translations/entry', name: 'app_admin_translations_entry', methods: ['GET', 'POST'])]
    public function entry(Request $request): Response
    {
        if ($request->isMethod('GET')) {
            $key = (string) $request->query->get('key');
            if (!$this->translations->has($key)) {
                throw $this->createNotFoundException();
            }

            return new JsonResponse($this->translations->entry($key));
        }

        $this->denyUnlessValidToken($request);

        $key = (string) $request->request->get('key');
        if (!$this->translations->has($key)) {
            throw $this->createNotFoundException();
        }

        foreach (Translations::LOCALES as $locale) {
            if ($request->request->has($locale)) {
                $this->translations->saveValue($locale, $key, (string) $request->request->get($locale));
            }
        }

        return new JsonResponse(['ok' => true]);
    }

    private function denyUnlessValidToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translations->get('flash.csrf'));
        }
    }
}
