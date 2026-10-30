<?php

namespace App\Entity;

use App\Repository\ContactMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Wiadomość z formularza kontaktowego. Zapisywana w bazie niezależnie od tego, czy wysyłka e-mailem się powiodła.
 */
#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Index(name: 'IDX_CONTACT_MESSAGE_CREATED', fields: ['createdAt'])]
class ContactMessage
{
    public const SUBJECT_MAX_LENGTH = 150;
    public const MESSAGE_MIN_LENGTH = 10;
    public const MESSAGE_MAX_LENGTH = 5000;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: User::EMAIL_MAX_LENGTH)]
    private string $email = '';

    #[ORM\Column(length: self::SUBJECT_MAX_LENGTH)]
    private string $subject = '';

    #[ORM\Column(type: 'text')]
    private string $message = '';

    #[ORM\Column(length: 5)]
    private string $locale = 'pl';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = trim($subject);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = trim(str_replace("\r\n", "\n", $message));
    }

    /** Język strony, z której wysłano formularz. */
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function markRead(bool $read = true): void
    {
        $this->readAt = $read ? new \DateTimeImmutable() : null;
    }
}
