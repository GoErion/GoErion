<?php

namespace App\Services\Encryption;

use Random\RandomException;
use SodiumException;

class Cipher
{
    /**
     * @param $message
     * @param EncryptionKey $encryptionKey
     * @return EncryptedMessage
     * @throws RandomException
     * @throws SodiumException
     */

    public static function encryptMessage($message,EncryptionKey $encryptionKey): EncryptedMessage
    {
        $message_nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
        $ciphertext = sodium_crypto_box(
            $message,
            $message_nonce,
            $encryptionKey->getEncryptionKey()
        );
        return new EncryptedMessage(
            $ciphertext,
            $message_nonce
        );
    }

    /**
     * @throws SodiumException
     * @throws
     * @return string
     * @param EncryptedMessage $encryptedMessage
     * @param EncryptionKey $encryptionKey
     *Decrypts an encrypted message using the provided encryption key
     *
     */
    public static function decryptMessage(EncryptedMessage $encryptedMessage,EncryptionKey $encryptionKey): string
    {
        $plaintext = sodium_crypto_box_open(
            $encryptedMessage->getCiphertext(),
            $encryptedMessage->getNonce(),
            $encryptionKey->getEncryptionKey()
        );
        if ($plaintext === false)
        {
            throw new \SodiumException('Decryption failed');
        }
        return $plaintext;
    }
}
