<?php

namespace App\Settings;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Ustawienia edytowane z panelu administratora, zapisane w bazie. Zmienne środowiskowe są wartościami domyślnymi.
 * Nowe ustawienie: dodaj stałą z nazwą oraz metody odczytu i zapisu.
 */
final class Settings
{
    public const CONTACT_RECIPIENTS = 'contact.recipient';
    public const MAILER_DSN = 'mailer.dsn';
    public const MAILER_FROM = 'mailer.from';
    public const MAILER_FROM_NAME = 'mailer.from_name';

    public function __construct(
        private readonly SettingRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecretBox $secrets,
        #[Autowire('%env(MAILER_DSN)%')]
        private readonly string $defaultMailerDsn,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $defaultMailerFrom,
        #[Autowire('%env(CONTACT_RECIPIENT)%')]
        private readonly string $defaultContactRecipient,
    ) {
    }

    // --- Poczta -----------------------------------------------------------------------------

    /** Odbiorcy wiadomości z formularza (ustawienie z bazy albo CONTACT_RECIPIENT). */
    public function contactRecipients(): array
    {
        return self::splitList($this->get(self::CONTACT_RECIPIENTS) ?? $this->defaultContactRecipient);
    }

    public function setContactRecipients(array $emails): void
    {
        $this->set(self::CONTACT_RECIPIENTS, implode("\n", $emails));
    }

    public function mailerDsn(): string
    {
        return $this->customMailerDsn() ?? $this->defaultMailerDsn;
    }

    /** DSN zapisany w bazie (odszyfrowany) albo null, gdy używany jest ten z MAILER_DSN. */
    public function customMailerDsn(): ?string
    {
        $stored = $this->get(self::MAILER_DSN);

        return $stored === null ? null : $this->secrets->decrypt($stored);
    }

    public function defaultMailerDsn(): string
    {
        return $this->defaultMailerDsn;
    }

    /** Pusty DSN usuwa ustawienie, czyli przywraca MAILER_DSN. */
    public function setMailerDsn(?string $dsn): void
    {
        $this->set(self::MAILER_DSN, $dsn === null || $dsn === '' ? '' : $this->secrets->encrypt($dsn));
    }

    public function mailerFrom(): string
    {
        return $this->get(self::MAILER_FROM) ?? $this->defaultMailerFrom;
    }

    public function setMailerFrom(string $email): void
    {
        $this->set(self::MAILER_FROM, $email);
    }

    /** Nazwa nadawcy; null = domyślna z tekstów (email.sender_name). */
    public function mailerFromName(): ?string
    {
        return $this->get(self::MAILER_FROM_NAME);
    }

    public function setMailerFromName(string $name): void
    {
        $this->set(self::MAILER_FROM_NAME, $name);
    }

    /**
     * Dzieli listę adresów rozdzieloną nowymi liniami, przecinkami lub średnikami.
     *
     * @return list<string>
     */
    public static function splitList(string $value): array
    {
        $items = preg_split('/[\s,;]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($items));
    }

    // --- Magazyn ----------------------------------------------------------------------------

    private function get(string $name): ?string
    {
        $value = $this->repository->find($name)?->getValue();

        return $value === '' ? null : $value;
    }

    private function set(string $name, string $value): void
    {
        $setting = $this->repository->find($name);
        if ($setting === null) {
            $setting = new Setting($name);
            $this->entityManager->persist($setting);
        }
        $setting->setValue($value);
        $this->entityManager->flush();
    }
}
