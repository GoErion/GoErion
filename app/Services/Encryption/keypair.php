<?php

namespace App\Services\Encryption;

use SodiumException;

class keypair
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
        return sodium_crypto_box_publickey($this->keys);
    }

    /**
     * @throws SodiumException
     */
    public function getPrivateKey(): string
    {
        return sodium_crypto_box_secretkey($this->keys);
    }
}
