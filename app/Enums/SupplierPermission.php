<?php

declare(strict_types=1);

namespace App\Enums;

enum SupplierPermission: string
{
    case View = 'suppliers.view';
    case Create = 'suppliers.create';
    case Update = 'suppliers.update';
    case Archive = 'suppliers.archive';
    case TermsManage = 'suppliers.terms.manage';

    public function displayName(): string
    {
        return match ($this) {
            self::View => 'View Suppliers',
            self::Create => 'Create Suppliers',
            self::Update => 'Update Suppliers',
            self::Archive => 'Archive and Restore Suppliers',
            self::TermsManage => 'Manage Supplier Commercial Terms',
        };
    }
}
