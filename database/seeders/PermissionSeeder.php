<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Auth\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'company.update',
                'display_name' => 'Update Company',
            ],
            [
                'name' => 'companies.view',
                'display_name' => 'View Companies',
            ],
            [
                'name' => 'companies.create',
                'display_name' => 'Create Companies',
            ],
            [
                'name' => 'companies.update',
                'display_name' => 'Update Companies',
            ],
            [
                'name' => 'companies.delete',
                'display_name' => 'Delete Companies',
            ],

            [
                'name' => 'users.view',
                'display_name' => 'View Users',
            ],
            [
                'name' => 'users.create',
                'display_name' => 'Create Users',
            ],
            [
                'name' => 'users.update',
                'display_name' => 'Update Users',
            ],
            [
                'name' => 'users.delete',
                'display_name' => 'Delete Users',
            ],

            [
                'name' => 'roles.view',
                'display_name' => 'View Roles',
            ],
            [
                'name' => 'roles.create',
                'display_name' => 'Create Roles',
            ],
            [
                'name' => 'roles.update',
                'display_name' => 'Update Roles',
            ],
            [
                'name' => 'roles.delete',
                'display_name' => 'Delete Roles',
            ],

            [
                'name' => 'customers.view',
                'display_name' => 'View Customers',
            ],
            [
                'name' => 'customers.create',
                'display_name' => 'Create Customers',
            ],
            [
                'name' => 'customers.update',
                'display_name' => 'Update Customers',
            ],
            [
                'name' => 'customers.delete',
                'display_name' => 'Delete Customers',
            ],

            [
                'name' => 'invoices.view',
                'display_name' => 'View Invoices',
            ],
            [
                'name' => 'invoices.create',
                'display_name' => 'Create Invoices',
            ],
            [
                'name' => 'invoices.update',
                'display_name' => 'Update Invoices',
            ],
            [
                'name' => 'invoices.delete',
                'display_name' => 'Delete Invoices',
            ],

            [
                'name' => 'payments.view',
                'display_name' => 'View Payments',
            ],
            [
                'name' => 'payments.create',
                'display_name' => 'Create Payments',
            ],

            [
                'name' => 'reports.view',
                'display_name' => 'View Reports',
            ],
        ];
        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission['name'],
                ],
                $permission
            );
        }
    }
}
