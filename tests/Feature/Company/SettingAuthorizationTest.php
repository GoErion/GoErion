<?php

declare(strict_types=1);

use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests from company settings', function () {
    $company = CompanyFactory::new()->create();

    $this->get(route('settings.edit', $company))
        ->assertRedirect(route('login'));

    $this->put(route('settings', $company), ['currency' => 'KES'])
        ->assertRedirect(route('login'));
    $this->assertDatabaseMissing('company_settings', [
        'company_id' => $company->id,
        'key' => 'currency',
    ]);
});

it('forbids non-members from viewing or updating company settings', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();

    $this->actingAs($user)->get(route('settings.edit', $company))
        ->assertForbidden();

    $this->actingAs($user)->put(route('settings', $company), ['currency' => 'KES'])
        ->assertForbidden();
    $this->assertDatabaseMissing('company_settings', [
        'company_id' => $company->id,
        'key' => 'currency',
    ]);
});

it('allows members to view and update company settings', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)->get(route('settings.edit', $company))
        ->assertOk();

    $this->actingAs($user)->put(route('settings', $company), ['currency' => 'KES'])
        ->assertRedirect(route('home', $company));
    $this->assertDatabaseHas('company_settings', [
        'company_id' => $company->id,
        'key' => 'currency',
        'value' => 'KES',
    ]);
});
