<?php

namespace App\EventSubscriber;

use App\Translation\Translations;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public const COOKIE = 'locale';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 40]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $request->cookies->get(self::COOKIE);

        // Brak ciasteczka: język z ustawień przeglądarki, a gdy go nie obsługujemy, polski.
        if (!is_string($locale) || !in_array($locale, Translations::LOCALES, true)) {
            $locale = $request->getPreferredLanguage(Translations::LOCALES) ?? Translations::DEFAULT_LOCALE;
        }

        $request->setLocale($locale);
    }
}
