<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerPermission: string
{
    case View = 'customers.view';
    case Create = 'customers.create';
    case Update = 'customers.update';
    case Archive = 'customers.archive';
    case CreditManage = 'customers.credit.manage';
    case GroupsManage = 'customers.groups.manage';

    public function displayName(): string
    {
        return match ($this) {
            self::View => 'View Customers',
            self::Create => 'Create Customers',
            self::Update => 'Update Customers',
            self::Archive => 'Archive and Restore Customers',
            self::CreditManage => 'Manage Customer Commercial Terms',
            self::GroupsManage => 'Manage Customer Groups',
        };
    }
}
