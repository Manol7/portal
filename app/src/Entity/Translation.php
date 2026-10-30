<?php

namespace App\Entity;

use App\Repository\TranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_TRANSLATION_LOCALE_KEY', fields: ['locale', 'messageKey'])]
class Translation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private string $locale;

    #[ORM\Column(length: 120)]
    private string $messageKey;

    #[ORM\Column(type: 'text')]
    private string $value;

    public function __construct(string $locale, string $messageKey, string $value)
    {
        $this->locale = $locale;
        $this->messageKey = $messageKey;
        $this->value = $value;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
