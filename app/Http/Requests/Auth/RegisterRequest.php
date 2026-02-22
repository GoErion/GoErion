<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'password' => ['required','confirmed','min:8'],
            'identifier'=>['required','string','regex:/^(\+?[1-9]\d{7,14}|[^@]+@[^@]+\.[^@]+)$/','unique:users,email','unique:users,phone']
        ];
    }
}
