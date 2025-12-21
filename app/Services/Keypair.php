<?php

declare(strict_types=1);

namespace App\Services;

use SodiumException;

final readonly class Keypair
{
    private string $keys;

    /**
     * @throws SodiumException
     */
    public function __construct()
    {
        $this->keys = sodium_crypto_box_keypair();
    }

    /**
     * @throws SodiumException
     */
    public function getPublicKey(): string
    {
        return bin2hex(sodium_crypto_box_publickey($this->keys));
    }

    /**
     * @throws SodiumException
     */
    public function getPrivateKey(): string
    {
        return bin2hex(sodium_crypto_box_secretkey($this->keys));
    }
}
