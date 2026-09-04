<?php

declare(strict_types=1);

use Database\Factories\BrandFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function companyMember(): array
{
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();
    $user->companies()->attach($company);

    return [$user, $company];
}

it('redirects guests from the brand index', function () {
    $company = CompanyFactory::new()->create();

    $response = $this->get(route('brand.index', $company));

    $response->assertRedirect(route('login'));
});

it('forbids users who do not belong to the company', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();

    $response = $this->actingAs($user)->get(route('brand.index', $company));

    $response->assertForbidden();
});

it('forbids non-members from creating a brand', function () {
    $user = UserFactory::new()->create();
    $company = CompanyFactory::new()->create();

    $response = $this->actingAs($user)->post(route('brand.store', $company), [
        'name' => 'Acme',
        'code' => 'ACM',
    ]);

    $response->assertForbidden();
    expect($company->brands()->count())->toBe(0);
});

it('lists only brands owned by the company', function () {
    [$user, $company] = companyMember();
    $otherCompany = CompanyFactory::new()->create();
    BrandFactory::new()->for($company)->create(['name' => 'owned brand']);
    BrandFactory::new()->for($otherCompany)->create(['name' => 'other brand']);

    $response = $this->actingAs($user)->get(route('brand.index', $company));

    $response
        ->assertSee('owned brand')
        ->assertDontSee('other brand');
});

it('creates a brand for the company', function () {
    [$user, $company] = companyMember();

    $response = $this->actingAs($user)->post(route('brand.store', $company), [
        'name' => 'Acme',
        'code' => 'acm',
    ]);

    $response
        ->assertRedirect(route('brand.index', $company))
        ->assertSessionHas('success', 'Brand created successfully.');
    $this->assertDatabaseHas('brands', [
        'company_id' => $company->id,
        'name' => 'acme',
        'code' => 'ACM',
    ]);
});

it('rejects an existing name and code combination within the company', function () {
    [$user, $company] = companyMember();
    BrandFactory::new()->for($company)->create([
        'name' => 'acme',
        'code' => 'ACM',
    ]);

    $response = $this->actingAs($user)->from(route('brand.create', $company))->post(route('brand.store', $company), [
        'name' => 'Acme',
        'code' => 'acm',
    ]);

    $response
        ->assertRedirect(route('brand.create', $company))
        ->assertInvalid(['name' => 'The name has already been taken.']);
    expect($company->brands()->count())->toBe(1);
});

it('allows the same name and code combination in different companies', function () {
    [$user, $company] = companyMember();
    $otherCompany = CompanyFactory::new()->create();
    BrandFactory::new()->for($otherCompany)->create([
        'name' => 'acme',
        'code' => 'ACM',
    ]);

    $response = $this->actingAs($user)->post(route('brand.store', $company), [
        'name' => 'Acme',
        'code' => 'acm',
    ]);

    $response->assertRedirect(route('brand.index', $company));
    $this->assertDatabaseHas('brands', [
        'company_id' => $company->id,
        'name' => 'acme',
        'code' => 'ACM',
    ]);
});

it('does not resolve a brand through another company', function () {
    [$user, $company] = companyMember();
    $otherCompany = CompanyFactory::new()->create();
    $brand = BrandFactory::new()->for($otherCompany)->create();

    $response = $this->actingAs($user)->get(route('brand.edit', [$company, $brand]));

    $response->assertNotFound();
});

it('updates a brand owned by the company', function () {
    [$user, $company] = companyMember();
    $brand = BrandFactory::new()->for($company)->create([
        'name' => 'old brand',
        'code' => 'OLD',
    ]);

    $response = $this->actingAs($user)->put(route('brand.update', [$company, $brand]), [
        'name' => 'New Brand',
        'code' => 'new',
    ]);

    $response
        ->assertRedirect(route('brand.index', $company))
        ->assertSessionHas('success', 'Brand updated successfully.');
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'new brand',
        'code' => 'NEW',
    ]);
});

it('allows a brand to retain its current name and code', function () {
    [$user, $company] = companyMember();
    $brand = BrandFactory::new()->for($company)->create([
        'name' => 'acme',
        'code' => 'ACM',
    ]);

    $response = $this->actingAs($user)->put(route('brand.update', [$company, $brand]), [
        'name' => 'Acme',
        'code' => 'acm',
    ]);

    $response->assertRedirect(route('brand.index', $company));
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'acme',
        'code' => 'ACM',
    ]);
});

it('does not update a brand through another company', function () {
    [$user, $company] = companyMember();
    $otherCompany = CompanyFactory::new()->create();
    $brand = BrandFactory::new()->for($otherCompany)->create([
        'name' => 'original brand',
        'code' => 'ORG',
    ]);

    $response = $this->actingAs($user)->put(route('brand.update', [$company, $brand]), [
        'name' => 'Changed Brand',
        'code' => 'CHG',
    ]);

    $response->assertNotFound();
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'original brand',
        'code' => 'ORG',
    ]);
});

it('deletes a brand owned by the company', function () {
    [$user, $company] = companyMember();
    $brand = BrandFactory::new()->for($company)->create();

    $response = $this->actingAs($user)->delete(route('brand.destroy', [$company, $brand]));

    $response
        ->assertRedirect(route('brand.index', $company))
        ->assertSessionHas('success', 'Brand deleted successfully.');
    $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
});

it('does not delete a brand through another company', function () {
    [$user, $company] = companyMember();
    $otherCompany = CompanyFactory::new()->create();
    $brand = BrandFactory::new()->for($otherCompany)->create();

    $response = $this->actingAs($user)->delete(route('brand.destroy', [$company, $brand]));

    $response->assertNotFound();
    $this->assertModelExists($brand);
});
