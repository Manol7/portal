<?php

namespace App\Controller;

use App\Account\AccountRules;
use App\Mail\MailerFactory;
use App\Mail\MailTester;
use App\Security\Roles;
use App\Settings\Settings;
use App\Translation\Translations;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Ustawienia poczty: transport (DSN), nadawca i odbiorcy wiadomości z formularza oraz wysyłka próbna.
 * DSN może zawierać hasło, dlatego jest zapisywany szyfrowany i nigdy nie jest wyświetlany w całości.
 */
#[IsGranted(Roles::ADMIN)]
final class MailerSettingsController extends AbstractController
{
    private const MAX_RECIPIENTS = 10;
    private const FROM_NAME_MAX_LENGTH = 100;

    public function __construct(
        private readonly Translations $translations,
        private readonly Settings $settings,
        private readonly MailerFactory $mailers,
        private readonly MailTester $tester,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/admin/mailer', name: 'app_admin_mailer', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/mailer/index.html.twig', [
            'dsn_masked' => self::maskDsn($this->settings->mailerDsn()),
            'dsn_custom' => $this->settings->customMailerDsn() !== null,
            'dsn_default_masked' => self::maskDsn($this->settings->defaultMailerDsn()),
            'from' => $this->settings->mailerFrom(),
            'from_name' => $this->settings->mailerFromName() ?? $this->translations->get('email.sender_name', [], Translations::DEFAULT_LOCALE),
            'recipients' => implode("\n", $this->settings->contactRecipients()),
            'from_name_max' => self::FROM_NAME_MAX_LENGTH,
            'email_max' => AccountRules::EMAIL_MAX_LENGTH,
        ]);
    }

    #[Route('/admin/mailer', name: 'app_admin_mailer_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->denyUnlessValidToken($request, 'mailer-settings');
        $input = $request->request;

        $from = AccountRules::normalizeEmail((string) $input->get('from', ''));
        $fromName = trim((string) $input->get('from_name', ''));
        $recipients = Settings::splitList((string) $input->get('recipients', ''));
        $dsn = trim((string) $input->get('dsn', ''));
        $resetDsn = $input->getBoolean('dsn_reset');

        $error = $this->firstError($from, $fromName, $recipients)
            ?? ($dsn !== '' && !$resetDsn ? $this->dsnError($dsn) : null);

        if ($error !== null) {
            $this->addFlash('error', $error);

            return $this->redirectToRoute('app_admin_mailer', status: Response::HTTP_SEE_OTHER);
        }

        $this->settings->setMailerFrom($from);
        $this->settings->setMailerFromName($fromName);
        $this->settings->setContactRecipients($recipients);
        // Puste pole DSN zostawia zapisaną wartość (hasło nie jest nigdy odsyłane do formularza).
        if ($resetDsn) {
            $this->settings->setMailerDsn(null);
        } elseif ($dsn !== '') {
            $this->settings->setMailerDsn($dsn);
        }

        $this->addFlash('success', $this->translations->get('admin.settings.saved'));

        return $this->redirectToRoute('app_admin_mailer', status: Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/mailer/test', name: 'app_admin_mailer_test', methods: ['POST'])]
    public function test(Request $request): Response
    {
        $this->denyUnlessValidToken($request, 'mailer-test');

        $error = $this->tester->send();
        if ($error === null) {
            $this->addFlash('success', $this->translations->get('admin.mailer.test_sent'));
        } else {
            $this->addFlash('error', $this->translations->get('admin.mailer.test_failed', ['error' => mb_substr($error, 0, 300)]));
        }

        return $this->redirectToRoute('app_admin_mailer', status: Response::HTTP_SEE_OTHER);
    }

    /** @param list<string> $recipients */
    private function firstError(string $from, string $fromName, array $recipients): ?string
    {
        $violations = $this->validator->validate($from, AccountRules::email($this->translations));
        if (count($violations) > 0) {
            return $this->translations->get('admin.mailer.from_invalid');
        }

        if (mb_strlen($fromName) > self::FROM_NAME_MAX_LENGTH) {
            return $this->translations->get('admin.mailer.from_name_long');
        }

        if ($recipients === [] || count($recipients) > self::MAX_RECIPIENTS) {
            return $this->translations->get('admin.mailer.recipients_count', ['max' => self::MAX_RECIPIENTS]);
        }

        foreach ($recipients as $recipient) {
            if (count($this->validator->validate($recipient, [new Assert\Email(), new Assert\Length(max: AccountRules::EMAIL_MAX_LENGTH)])) > 0) {
                return $this->translations->get('admin.mailer.recipient_invalid', ['email' => $recipient]);
            }
        }

        return null;
    }

    private function dsnError(string $dsn): ?string
    {
        try {
            $this->mailers->create($dsn);
        } catch (ExceptionInterface|\LogicException) {
            return $this->translations->get('admin.mailer.dsn_invalid');
        }

        return null;
    }

    /** Ukrywa hasło w DSN (smtp://user:haslo@host => smtp://user:••••@host). */
    private static function maskDsn(string $dsn): string
    {
        return preg_replace('#(://[^:/@]*:)[^@]*@#', '$1••••@', $dsn) ?? $dsn;
    }

    private function denyUnlessValidToken(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException($this->translations->get('flash.csrf'));
        }
    }
}
