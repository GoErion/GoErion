<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required','unique:users','alpha_num','min:4','max:12'],
            'password' => ['required','confirmed','min:8'],
            'pin'      => ['required','numeric','digits:6'],
        ];
    }
}
