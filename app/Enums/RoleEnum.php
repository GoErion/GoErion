<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case PROVIDER = 'provider';
    case AGENT = 'agent';

    public function labels(): array
    {
        return match ($this)
        {
            self::ADMIN=> __('Admin'),
            self::USER=> __('User'),
            self::PROVIDER => __('Provider'),
            self::AGENT => __('Agent'),
        };
    }
}
