<?php

namespace App\Translation;

use App\Entity\Translation;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Słownik tekstów aplikacji.
 * Nowy język: dopisz go do LOCALES, dodaj flagę w templates/_partials/flags/ oraz teksty `lang.to_<język>` i `tr.col_<język>`.
 * Nowy tekst: dopisz go do DefaultTexts (strona Tłumaczenia pokaże go automatycznie).
 */
final class Translations implements ResetInterface
{
    public const LOCALES = ['pl', 'en'];
    public const DEFAULT_LOCALE = 'pl';
    public const MAX_LENGTH = 2000;

    /** @var array<string, array<string, string>>|null język => klucz => tekst zapisany w bazie */
    private ?array $overrides = null;

    public function __construct(
        private readonly TranslationRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly LiveTranslationMode $liveMode,
    ) {
    }

    public function locale(): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();

        return in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT_LOCALE;
    }

    public function has(string $key): bool
    {
        return isset(DefaultTexts::TEXTS[$key]);
    }

    // --- Odczyt -----------------------------------------------------------------------------

    /**
     * Kolejność: tłumaczenie w bieżącym języku, polski z bazy, polski domyślny.
     *
     * @param array<string, scalar> $params
     */
    public function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->locale();
        $overrides = $this->overrides();

        $text = $overrides[$locale][$key] ?? '';
        if ($text === '') {
            $text = $overrides[self::DEFAULT_LOCALE][$key] ?? '';
        }
        if ($text === '') {
            $text = DefaultTexts::TEXTS[$key] ?? $key;
        }

        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements['{'.$name.'}'] = (string) $value;
        }

        return str_replace("\r\n", "\n", strtr($text, $replacements));
    }

    /**
     * Jak get(), ale w trybie dynamicznego tłumaczenia znakuje tekst kluczem.
     * Używać tam, gdzie tekst trafia wprost do widoku; do JSON-a i atrybutów czytanych przez kod służy get().
     *
     * @param array<string, scalar> $params
     */
    public function mark(string $key, array $params = []): string
    {
        $text = $this->get($key, $params);

        return $text !== '' && $this->liveMode->isActive() ? $this->liveMode->wrap($key, $text) : $text;
    }

    /**
     * Wartości do edycji: polski (z bazy lub domyślny) oraz pozostałe języki (pusty = brak tłumaczenia).
     *
     * @return array{key: string, values: array<string, string>, multiline: bool}
     */
    public function entry(string $key): array
    {
        $default = DefaultTexts::TEXTS[$key];
        $values = [];
        foreach (self::LOCALES as $locale) {
            $values[$locale] = $this->overrides()[$locale][$key] ?? ($locale === self::DEFAULT_LOCALE ? $default : '');
        }

        return ['key' => $key, 'values' => $values, 'multiline' => str_contains($default, "\n")];
    }

    /**
     * @return list<array{key: string, values: array<string, string>, multiline: bool}>
     */
    public function entries(): array
    {
        return array_map($this->entry(...), array_keys(DefaultTexts::TEXTS));
    }

    // --- Zapis ------------------------------------------------------------------------------

    public function saveValue(string $locale, string $key, string $value): void
    {
        if (!in_array($locale, self::LOCALES, true) || !$this->has($key)) {
            throw new \InvalidArgumentException('Nieznany język lub klucz.');
        }

        $this->store($locale, $key, $value, $this->repository->findOneBy(['locale' => $locale, 'messageKey' => $key]));
        $this->entityManager->flush();
        $this->reset();
    }

    /**
     * Zapisuje cały język naraz (formularz strony Tłumaczenia).
     *
     * @param array<string, string> $values klucz => tekst, pusty tekst usuwa tłumaczenie
     */
    public function save(string $locale, array $values): void
    {
        $existing = [];
        foreach ($this->repository->findBy(['locale' => $locale]) as $translation) {
            $existing[$translation->getMessageKey()] = $translation;
        }

        foreach (array_keys(DefaultTexts::TEXTS) as $key) {
            $this->store($locale, $key, $values[$key] ?? '', $existing[$key] ?? null);
        }

        $this->entityManager->flush();
        $this->reset();
    }

    public function reset(): void
    {
        $this->overrides = null;
    }

    private function store(string $locale, string $key, string $value, ?Translation $existing): void
    {
        $value = mb_substr(trim(str_replace("\r\n", "\n", $value)), 0, self::MAX_LENGTH);

        // Polski zapisujemy tylko, gdy różni się od wartości domyślnej.
        if ($locale === self::DEFAULT_LOCALE && $value === DefaultTexts::TEXTS[$key]) {
            $value = '';
        }

        if ($value === '') {
            if ($existing !== null) {
                $this->entityManager->remove($existing);
            }
        } elseif ($existing !== null) {
            $existing->setValue($value);
        } else {
            $this->entityManager->persist(new Translation($locale, $key, $value));
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function overrides(): array
    {
        if ($this->overrides === null) {
            $this->overrides = [];
            try {
                $stored = $this->repository->findAll();
            } catch (\Throwable) {
                // Strona błędu nie może zależeć od działającej bazy danych.
                $stored = [];
            }

            foreach ($stored as $translation) {
                $this->overrides[$translation->getLocale()][$translation->getMessageKey()] = $translation->getValue();
            }
        }

        return $this->overrides;
    }
}
