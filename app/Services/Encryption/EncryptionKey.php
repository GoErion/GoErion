<?php

namespace App\Services\Encryption;

use SodiumException;

class EncryptionKey
{
    private string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        if (strlen($encryptionKey) !== SODIUM_CRYPTO_BOX_KEYPAIRBYTES)
        {
            throw new \InvalidArgumentException(
                'Encryption key must be exactly'.SODIUM_CRYPTO_BOX_KEYPAIRBYTES.'bytes long'
            );
        }
        $this->encryptionKey = $encryptionKey;
    }
    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    /**
     * @return self
     * @throws SodiumException
     * Generate a new random encryption key
     *
     */
    public static function generate(): self
    {
        $keyPair = sodium_crypto_box_keypair();
        return new self($keyPair);
    }
}
