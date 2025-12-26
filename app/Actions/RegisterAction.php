<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\Keypair;
use App\Services\Mnemonic;
use Illuminate\Support\Facades\Hash;
use Random\RandomException;
use SodiumException;

final readonly class RegisterAction
{
    /**
     * @throws SodiumException
     * @throws RandomException
     *
     */
    public function handle(array $userData)
    {
        //instance of Keypair class
        $keyPair = new KeyPair();

        //sensitive
        $privateKey = $keyPair->getPrivateKey();

        //safe to store
        $publicKey = $keyPair->getPublicKey();

        //salt for pin based key derived
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        //derive encryption key from pin (pin never stored)
        $pinKey = sodium_crypto_pwhash(
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            $userData['pin'],
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        //encrypt private key(raw byte no hex)
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $encryptedPrivateKey = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            hex2bin($privateKey),
            '',
            $nonce,
            $pinKey
        );
        //zero sensitive material from memory
        sodium_memzero($pinKey);
        unset($userData['pin']);

        //generate mnemonic phrases or word
        $mnemonic = new Mnemonic()->generate(config('goerion.mnemonic_length'));

        $user = User::create([
            'username' => $userData['username'],
            'password' => Hash::make($userData['password']),
            'mnemonic' => Hash::make($mnemonic),
            // messaging / crypto keys
            'msg_public_key' => $publicKey,
            'msg_private_key' => bin2hex($encryptedPrivateKey),
            'msg_private_nonce' => bin2hex($nonce),
            'msg_private_salt' => bin2hex($salt),
        ]);
        return [
            'user' => $user,
            'mnemonic' => $mnemonic,
        ];
    }
}
