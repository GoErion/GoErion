<?php

namespace App\Services;

use Exception;
use OpenPGP;
use OpenPGP_Crypt_Symmetric;
use OpenPGP_LiteralDataPacket;
use OpenPGP_Message;

class PGP
{
    const NEW_PGP_ENCRYPTED_MESSAGE = "new_pgp_encrypted_message";
    const NEW_PGP_SESSION_KEY = "new_pgp_key";
    const NEW_PGP_VALIDATION_NUMBER_KEY = "pgp_validation_number_key";

    /**
     * @throws Exception
     */
    public static function EncryptMessage($message,$key): string
    {
        if ($message == null)
        {
            throw new Exception("Please specify a message to encrypt",1);
        }
        if ($key == null)
        {
            throw new Exception("Please specify a key to encrypt with",1);
        }
        $pubkey = $key;
        $key = OpenPGP_Message::parse(
            OpenPGP::unarmor($pubkey,'PGP PUBLIC KEY BLOCK')
        );
        $data = new OpenPGP_LiteralDataPacket($message,['format' => 'u']);
        $encrypted = OpenPGP_Crypt_Symmetric::encrypt(
            $key,
            new OpenPGP_Message([$data])
        );
        return OpenPGP::enarmor($encrypted->to_bytes(),'PGP MESSAGE');
    }
}
