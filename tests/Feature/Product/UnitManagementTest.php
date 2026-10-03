<?php

declare(strict_types=1);

use App\Actions\Product\ArchiveUnit;
use App\Actions\Product\RestoreUnit;
use App\Actions\Product\UpdateUnit;
use Database\Factories\ProductFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('updates archives and restores a referenced Unit with classification authority alone', function () {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $unit = $product->unit;
    $actor = SupplierFixture::member($company, ['products.classifications.manage']);
    $this->actingAs($actor)->get(route('unit.edit', [$company, $unit]))->assertOk();
    $this->patch(route('unit.update', [$company, $unit]), ['name' => 'Piece', 'symbol' => 'pc'])->assertRedirect();
    expect($unit->fresh()->name)->toBe('Piece')->and($unit->fresh()->symbol)->toBe('pc');
    foreach (['archive' => false, 'restore' => true] as $verb => $active) {
        foreach (range(1, 2) as $i) {
            $this->patch(route('unit.'.$verb, [$company, $unit]))->assertRedirect();
        }
        expect($unit->fresh()->is_active)->toBe($active)->and($product->fresh()->unit_id)->toBe($unit->id);
    }
    $other = $company->units()->create(['name' => 'Other', 'symbol' => 'other']);
    $this->patch(route('unit.update', [$company, $unit]), ['name' => 'Other', 'symbol' => 'other'])->assertSessionHasErrors(['name', 'symbol']);
    expect(fn () => $unit->delete())->toThrow(QueryException::class);
});

it('protects Unit targets and direct Actions from outsiders stale authority and ownership input', function () {
    [$owner, $company] = SupplierFixture::owner();
    $unit = ProductFactory::new()->create(['company_id' => $company->id])->unit;
    $foreign = ProductFactory::new()->create()->unit;
    $values = ['name' => 'Each', 'symbol' => 'ea'];
    $this->get(route('unit.edit', [$company, $unit]))->assertRedirect(route('login'));
    [$outsider] = SupplierFixture::owner();
    $actor = SupplierFixture::member($company, ['products.classifications.manage']);
    $actor->load('roles.permissions');
    $actor->roles()->detach();
    foreach (['update', 'archive', 'restore'] as $verb) {
        $this->actingAs($outsider)->patch(route('unit.'.$verb, [$company, $unit]), $values)->assertForbidden();
        $this->actingAs($actor)->patch(route('unit.'.$verb, [$company, $unit]), $values)->assertForbidden();
        $this->actingAs($owner)->patch(route('unit.'.$verb, [$company, $foreign]), $values)->assertNotFound();
    }
    foreach ([ArchiveUnit::class, RestoreUnit::class] as $action) {
        expect(fn () => app($action)->handle($company, $unit, $actor))->toThrow(AuthorizationException::class);
        expect(fn () => app($action)->handle($company, $foreign, $owner))->toThrow(ModelNotFoundException::class);
    }
    expect(fn () => app(UpdateUnit::class)->handle($company, $unit, $actor, $values))->toThrow(AuthorizationException::class);
    $this->patch(route('unit.update', [$company, $unit]), [...$values, 'company_id' => $foreign->company_id])->assertSessionHasErrors('company_id');
});
