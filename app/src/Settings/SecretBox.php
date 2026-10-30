<?php

namespace App\Settings;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Szyfruje sekrety zapisywane w bazie (np. hasło w DSN poczty) kluczem pochodzącym z APP_SECRET.
 * Zmiana APP_SECRET unieważnia zapisane sekrety: trzeba je wtedy wpisać ponownie.
 */
final class SecretBox
{
    public function __construct(#[Autowire('%kernel.secret%')] private readonly string $secret)
    {
    }

    public function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($plain, $nonce, $this->key()));
    }

    /** Zwraca null, gdy dane są uszkodzone albo zaszyfrowano je innym kluczem. */
    public function decrypt(string $encrypted): ?string
    {
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key());

        return $plain === false ? null : $plain;
    }

    private function key(): string
    {
        return sodium_crypto_generichash($this->secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
