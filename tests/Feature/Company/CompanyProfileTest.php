<?php

declare(strict_types=1);

use App\Models\Auth\Permission;
use Database\Factories\CompanyFactory;
use Database\Factories\RoleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a member to view the company overview', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create(['company_name' => 'Visible Company']);
    $member->companies()->attach($company);

    $this->actingAs($member)->get(route('home', $company))
        ->assertOk()
        ->assertSee('Visible Company');
});

it('forbids a non-member from the company overview', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();

    $this->actingAs($user)->get(route('home', $company))->assertForbidden();
});

it('allows a permitted member to edit and update the company profile', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create(['company_name' => 'Before']);
    $permission = Permission::query()->where('name', 'company.update')->sole();
    $role = RoleFactory::new()->create(['company_id' => $company->id]);
    $role->permissions()->attach($permission);
    $member->companies()->attach($company);
    $member->roles()->attach($role);

    $this->actingAs($member)->get(route('company.edit', $company))->assertOk();
    $response = $this->actingAs($member)->patch(route('company.update', $company), [
        'company_name' => 'After',
        'legal_name' => 'After Limited',
        'registration_number' => 'REG-2',
        'tax_number' => 'TAX-2',
        'email' => 'accounts@example.com',
        'phone' => '+254700000000',
        'address' => 'Nairobi',
        'status' => 'archived',
        'currency' => 'USD',
        'uuid' => '00000000-0000-0000-0000-000000000000',
    ]);

    $response->assertRedirect(route('home', $company));
    $company->refresh();
    expect($company->company_name)->toBe('After')
        ->and($company->legal_name)->toBe('After Limited')
        ->and($company->status)->toBe('active')
        ->and($company->currency)->toBe('kes')
        ->and($company->uuid)->not->toBe('00000000-0000-0000-0000-000000000000');
});

it('forbids an ordinary member from editing or updating the company profile', function () {
    $member = UserFactory::new()->create();
    $company = CompanyFactory::new()->create(['company_name' => 'Unchanged']);
    $member->companies()->attach($company);

    $this->actingAs($member)->get(route('company.edit', $company))->assertForbidden();
    $this->actingAs($member)->patch(route('company.update', $company), ['company_name' => 'Changed'])
        ->assertForbidden();
    expect($company->fresh()->company_name)->toBe('Unchanged');
});
