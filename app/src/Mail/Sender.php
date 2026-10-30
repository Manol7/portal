<?php

namespace App\Mail;

use App\Settings\Settings;
use App\Translation\Translations;
use Symfony\Component\Mime\Address;

/**
 * Wspólne dla wszystkich maili: nadawca z ustawień oraz nazwa nadawcy.
 */
final class Sender
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Translations $translations,
    ) {
    }

    public function address(string $locale = Translations::DEFAULT_LOCALE): Address
    {
        $name = $this->settings->mailerFromName() ?? $this->translations->get('email.sender_name', [], $locale);

        return new Address($this->settings->mailerFrom(), $name);
    }
}
