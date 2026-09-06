<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyPermission: string
{
    case Update = 'company.update';
    case MembersView = 'company.members.view';
    case MembersManage = 'company.members.manage';
    case RolesView = 'company.roles.view';
    case RolesManage = 'company.roles.manage';
    case InvitationsManage = 'company.invitations.manage';
    case OwnershipManage = 'company.ownership.manage';

    /** @return list<self> */
    public static function assignable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $permission): bool => $permission !== self::OwnershipManage));
    }

    public function displayName(): string
    {
        return match ($this) {
            self::Update => 'Update Company',
            self::MembersView => 'View Company Members',
            self::MembersManage => 'Manage Company Members',
            self::RolesView => 'View Company Roles',
            self::RolesManage => 'Manage Company Roles',
            self::InvitationsManage => 'Manage Company Invitations',
            self::OwnershipManage => 'Manage Company Ownership',
        };
    }

    public function isAssignableToCustomRoles(): bool
    {
        return $this !== self::OwnershipManage;
    }
}
