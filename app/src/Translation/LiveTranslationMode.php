<?php

namespace App\Translation;

use App\Security\Roles;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tryb dynamicznego tłumaczenia: włączany ciasteczkiem, dostępny tylko dla administratorów.
 * W tym trybie teksty są otaczane znacznikami z kluczem (znaki prywatne Unicode),
 * po których public/scripts/modules/live-translation.js rozpoznaje, co można poprawić.
 */
final class LiveTranslationMode
{
    public const COOKIE = 'i18n_live';

    // Układ znacznika: START klucz KEY_END tekst END. Te same znaki są w live-translation.js.
    public const START = "\u{E000}";
    public const KEY_END = "\u{E001}";
    public const END = "\u{E002}";

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function isActive(): bool
    {
        if ($this->requestStack->getCurrentRequest()?->cookies->get(self::COOKIE) !== '1') {
            return false;
        }

        try {
            return $this->security->isGranted(Roles::ADMIN);
        } catch (\Throwable) {
            // Np. strona błędu wyrenderowana przed uruchomieniem zabezpieczeń.
            return false;
        }
    }

    /** Znakuje każdą linię osobno, żeby nl2br nie rozcinał znaczników. */
    public function wrap(string $key, string $text): string
    {
        return implode("\n", array_map(
            static fn (string $line): string => $line === '' ? $line : self::START.$key.self::KEY_END.$line.self::END,
            explode("\n", $text),
        ));
    }
}
