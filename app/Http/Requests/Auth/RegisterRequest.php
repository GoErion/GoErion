<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Captcha;
use App\Services\Encryption\keypair;
use App\Services\utility\Mnemonic;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Hash;
use Illuminate\Support\Str;

/**
 * @property mixed $refid
 * @property mixed $password
 * @property mixed $username
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'captcha'=> ['required',new Captcha()],
            'username'=>['required','unique:users','alpha_num','min:4','max:12'],
            'password'=>['required','confirmed','min:8']
        ];
    }
    /**
     * Get messages for validation rules
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'captcha.required' => 'Captcha is required',
            'username.required' => 'Username is required',
            'username.min' => 'Username must be at least 4 characters',
            'username.unique' => 'Username is already taken',
            'username.max' => 'Username must be less than 12 characters',
            'username.alpha_num' => 'Username must be alphanumeric',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'password.different' => 'Passwords must be different',
        ];
    }

    /**
     * @throws \SodiumException
     * @throws EnvironmentIsBrokenException
     */
    public function persist(): void
    {
        //check if there is a referral id
        $referred_by = null;
        if($this->refid !== null)
        {
            $referred_by = User::where('referral_code', $this->refid)->first();
        }

        //create users public and private RSA keys
        $keypair = new Keypair();
        $privateKey = $keypair->getPrivateKey();
        $publicKey = $keypair->getPublicKey();
        //encrypt private key with user's password
        $encryptedPrivateKey = Crypto::encryptWithPassword($privateKey,$this->password);

        $mnemonic = (new Mnemonic())->generate(config('site.mnemonic_length'));

        $user = new User();
        $user ->username = $this->username;
        $user ->password = Hash::make($this->password);
        $user ->mnemonic = Hash::make(hash('sha256', $mnemonic));
        $user ->referral_code = strtoupper(str::random(6));
        $user ->msg_public_key = encrypt($publicKey);
        $user ->msg_private_key = $encryptedPrivateKey;
        $user ->referred_by = optional($referred_by)->id;
        $user ->save();

        //generate vendor addresses
//        $user ->generateDepositAddresses();
        session()->flash('mnemonic_key',$mnemonic);
    }
}
