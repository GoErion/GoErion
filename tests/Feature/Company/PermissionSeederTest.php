<?php

declare(strict_types=1);

use App\Models\Auth\Permission;
use Database\Factories\PermissionFactory;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the complete legacy catalog and canonical company customer and supplier permissions', function () {
    $this->seed(PermissionSeeder::class);
    $expected = [
        'company.update', 'company.members.view', 'company.members.manage', 'company.roles.view',
        'company.roles.manage', 'company.invitations.manage', 'company.ownership.manage',
        'companies.view', 'companies.create', 'companies.update', 'companies.delete',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        'customers.archive', 'customers.credit.manage', 'customers.groups.manage',
        'suppliers.archive', 'suppliers.create', 'suppliers.terms.manage', 'suppliers.update', 'suppliers.view',
        'invoices.view', 'invoices.create', 'invoices.update', 'invoices.delete',
        'payments.view', 'payments.create', 'reports.view',
    ];
    expect(Permission::query()->pluck('name')->all())->toEqualCanonicalizing($expected);
    expect(Permission::query()->where('assignable_to_custom_roles', true)->pluck('name')->all())
        ->toEqualCanonicalizing(['company.update', 'company.members.view', 'company.members.manage', 'company.roles.view', 'company.roles.manage', 'company.invitations.manage', 'customers.view', 'customers.create', 'customers.update', 'customers.archive', 'customers.credit.manage', 'customers.groups.manage', 'suppliers.archive', 'suppliers.create', 'suppliers.terms.manage', 'suppliers.update', 'suppliers.view']);
    expect(Permission::query()->where('name', 'reports.view')->sole()->display_name)->toBe('View Reports');
});

it('seeds idempotently while preserving existing permission identities and unrelated records', function () {
    $unrelated = PermissionFactory::new()->create(['name' => 'retained.custom.permission']);
    $this->seed(PermissionSeeder::class);
    $before = Permission::query()->orderBy('id')->get(['id', 'uuid', 'name', 'display_name', 'assignable_to_custom_roles'])->toArray();
    $this->seed(PermissionSeeder::class);
    expect(Permission::query()->orderBy('id')->get(['id', 'uuid', 'name', 'display_name', 'assignable_to_custom_roles'])->toArray())->toBe($before);
    expect($unrelated->fresh())->not->toBeNull();
    expect(Permission::query()->assignableToCustomRoles()->count())->toBe(17);
});
