<?php

namespace App\Translation;

use App\Security\Roles;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Funkcje Twig do tekstów i języków.
 *  t()  – tekst do wyświetlenia (w trybie dynamicznym znakowany kluczem),
 *  tp() – czysty tekst, do atrybutów, JSON-a i logiki.
 */
final class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Translations $translations,
        private readonly LiveTranslationMode $liveMode,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('t', $this->translations->mark(...)),
            new TwigFunction('tp', $this->translations->get(...)),
            new TwigFunction('live_translate', $this->liveMode->isActive(...)),
            new TwigFunction('live_cookie', static fn (): string => LiveTranslationMode::COOKIE),
            new TwigFunction('current_locale', $this->translations->locale(...)),
            new TwigFunction('locales', static fn (): array => Translations::LOCALES),
            new TwigFunction('translation_max_length', static fn (): int => Translations::MAX_LENGTH),
            new TwigFunction('roles', static fn (): array => Roles::ALL),
        ];
    }
}
