<?php

namespace App\Actions\Auth;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterAction
{
    /**
     * @var string[] $table ->uuid('uuid')->unique();
     * $table->foreignId('role_id')->constrained()->cascadeOnDelete();
     * $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
     * $table->string('name');
     * $table->string('email')->unique()->nullable();
     * $table->string('phone')->unique()->nullable();
     * $table->string('status',20);
     */

    public function handle(array $input)
    {
        $role = Role::where('name', RoleEnum::USER->value)->value('id');
        $status = StatusEnum::ACTIVE->value;

        $identifier = $input['identifier'];

        //obvious data
        $data  = [
            'role_id'=>$role,
            'status'=>$status,
            'name'=>$input['name'],
            'password'=>Hash::make($input['password']),
        ];

        //check the user input if is email or phone number

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL))
        {
            $data['email'] = $identifier;

            // Check uniqueness again because our previous validation can't differentiate
            if (User::where('email', $identifier)->exists())
            {
                throw ValidationException::withMessages([
                    'identifier' => 'This email already exists',
                ]);
            }
        }
        elseif (preg_match('/^\+?[1-9]\d{7,14}$/', $identifier))
        {
            $data['phone'] = $identifier;
            //check if number is unique
            if (User::where('phone', $identifier)->exists())
            {
                throw ValidationException::withMessages([
                    'phone' => 'This phone number already exists',
                ]);
            }
        }
        else
        {
            throw ValidationException::withMessages([
                'identifier' => 'Enter a valid email or phone number.',
            ]);
        }

        //create user
        return User::firstOrCreate($data);
    }
}
