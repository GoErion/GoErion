<?php

namespace App\Services\Encryption;

readonly class EncryptedMessage
{
    /**
     * @param string $ciphertext
     * @param string $nonce
     */
    public function __construct(private string $ciphertext, private string $nonce)
    {
    }

    public function getCiphertext(): string
    {
        return $this->ciphertext;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * Get the encrypted message in a format suitable for storage or transmission
     *
     * @return string
     */

    public function toString(): string
    {
        return base64_encode($this->nonce . $this->ciphertext);
    }

    /**
     * Create an EncryptedMessage from a combined string
     *
     * @param string $combined Base64 encoded nonce + ciphertext
     * @return self
     */

    public static function fromString(string $combined): self
    {
        $decoded = base64_decode($combined);
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_BOX_NONCEBYTES);
        return new self($ciphertext, $nonce);
    }
}
