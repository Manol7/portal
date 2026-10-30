<?php

namespace App\Controller;

use App\EventSubscriber\LocaleSubscriber;
use App\Translation\Translations;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_locale', requirements: ['locale' => '[a-z]{2}'], methods: ['GET'])]
    public function switch(string $locale, Request $request): Response
    {
        if (!in_array($locale, Translations::LOCALES, true)) {
            throw $this->createNotFoundException();
        }

        $target = $this->generateUrl('app_home');
        $referer = parse_url((string) $request->headers->get('referer'));
        if (($referer['host'] ?? null) === $request->getHost() && str_starts_with($referer['path'] ?? '', '/')) {
            $target = $referer['path'].(isset($referer['query']) ? '?'.$referer['query'] : '');
        }

        $response = new RedirectResponse($target);
        $response->headers->setCookie(
            Cookie::create(LocaleSubscriber::COOKIE, $locale)
                ->withExpires(new \DateTimeImmutable('+1 year'))
                ->withPath('/')
                ->withSameSite(Cookie::SAMESITE_LAX),
        );

        return $response;
    }
}
