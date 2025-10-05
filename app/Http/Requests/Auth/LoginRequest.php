<?php

namespace App\Http\Requests\Auth;

use App\Exceptions\RequestException;
use App\Models\User;
use App\Rules\Captcha;
use App\Services\PGP;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property mixed $username
 * @property mixed $password
 */
class LoginRequest extends FormRequest
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
            'username' => 'required|exists:users,username',
            'password' => 'required',
            'captcha' => ['required',new Captcha()],
        ];
    }
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required',
            'username.exists' => 'Username does not exist',
            'password.required' => 'Password is required',
            'captcha.required' => 'Captcha is required',
        ];
    }
    /**
     * @throws RequestException
     * @throws Exception
     *
     */
    public function persist(): RedirectResponse
    {
        $user = User::where('username',$this->username)->first();
        if ($user == null)
        {
            throw new RequestException('User not found');
        }
        //check if the password matches
        if (!Hash::check($this->password,$user->password))
        {
            throw new RequestException('Invalid password');
        }
        auth()->login($user);
        $user->last_seen = Carbon::now();
        $user->save();
        session()->regenerate();

        //user does not have 2fa enabled, log him in straight away
        if (!$user->login_2fa)
        {
            return redirect()
                ->route('home')
                ->with('success','Logged in successfully');
        }

        $validationString = Str::random(10);
        $messageToEncrypt = 'To login, please enter the following code: '.$validationString;
        $encryptedMessage = PGP::EncryptMessage($messageToEncrypt,$user->pgp_key);

        //save validation string to session
        session()->put('login_validation_string',Hash::make($validationString));
        session()->put('login_encrypted_message',$encryptedMessage);
        //redirect to 2fa page
        return redirect()
            ->route('verify')
            ->with('encrypted_message',$encryptedMessage);
    }
}
