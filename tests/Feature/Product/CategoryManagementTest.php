<?php

declare(strict_types=1);

use App\Actions\Product\ArchiveCategory;
use App\Actions\Product\CreateCategory;
use App\Actions\Product\RestoreCategory;
use App\Actions\Product\UpdateCategory;
use Database\Factories\ProductFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Tests\Support\RefreshProductDatabase;
use Tests\Support\SupplierFixture;

uses(RefreshProductDatabase::class);

it('manages category profile hierarchy and lifecycle with classification permission alone', function () {
    [$owner, $company] = SupplierFixture::owner();
    $actor = SupplierFixture::member($company, ['products.classifications.manage']);
    $this->actingAs($actor)->post(route('category.store', $company), ['name' => 'Root', 'code' => 'R'])->assertRedirect(route('category.index', $company));
    $root = $company->categories()->sole();
    $this->post(route('category.store', $company), ['name' => 'Child', 'code' => 'C', 'parent_id' => $root->id])->assertRedirect();
    $child = $company->categories()->where('code', 'C')->sole();
    $this->post(route('category.store', $company), ['name' => 'Leaf', 'code' => 'L', 'parent_id' => $child->id])->assertRedirect();
    $leaf = $company->categories()->where('code', 'L')->sole();
    $this->get(route('category.index', $company))->assertSee('Root / Child / Leaf')->assertSee('Depth 3');
    $this->post(route('category.store', $company), ['name' => 'Fourth', 'code' => 'F', 'parent_id' => $leaf->id])->assertSessionHasErrors('parent_id');
    $this->patch(route('category.archive', [$company, $root]))->assertSessionHasErrors('category');
    $this->patch(route('category.update', [$company, $root]), ['name' => 'Root', 'code' => 'R', 'parent_id' => $leaf->id])->assertSessionHasErrors('parent_id');
    $this->patch(route('category.update', [$company, $child]), ['name' => 'Renamed', 'code' => 'C', 'parent_id' => $root->id])->assertRedirect();
    app(UpdateCategory::class)->handle($company, $child, $actor, ['name' => 'Direct rename', 'code' => 'C']);
    expect($child->fresh()->parent_id)->toBe($root->id);
    $this->patch(route('category.update', [$company, $child]), ['name' => 'Self', 'code' => 'C', 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');
    $this->patch(route('category.update', [$company, $child]), ['name' => 'Duplicate', 'code' => 'R', 'parent_id' => $root->id])->assertSessionHasErrors('code');
    foreach ([$leaf, $child, $root] as $category) {
        foreach (range(1, 2) as $i) {
            $this->patch(route('category.archive', [$company, $category]))->assertRedirect();
        }
    }
    $this->patch(route('category.restore', [$company, $child]))->assertSessionHasErrors('parent_id');
    foreach ([$root, $child, $leaf] as $category) {
        $this->patch(route('category.restore', [$company, $category]))->assertRedirect();
        expect($category->fresh()->is_active)->toBeTrue();
    }
    expect($root->fresh()->parent_id)->toBeNull()->and($child->fresh()->parent_id)->toBe($root->id)->and($leaf->fresh()->parent_id)->toBe($child->id);
});

it('rejects category foreign parents targets missing permissions and stale direct authority', function () {
    [$owner, $company] = SupplierFixture::owner();
    $product = ProductFactory::new()->create(['company_id' => $company->id]);
    $category = $product->category;
    $foreign = ProductFactory::new()->create()->category;
    $values = ['name' => 'Own', 'code' => $category->code];
    $this->get(route('category.index', $company))->assertRedirect(route('login'));
    [$outsider] = SupplierFixture::owner();
    $this->actingAs($outsider)->get(route('category.index', $company))->assertForbidden();
    $this->actingAs($owner)->patch(route('category.update', [$company, $category]), [...$values, 'parent_id' => $foreign->id])->assertSessionHasErrors('parent_id');
    expect(fn () => app(UpdateCategory::class)->handle($company, $category, $owner, [...$values, 'parent_id' => $foreign->id]))->toThrow(ValidationException::class);
    $actor = SupplierFixture::member($company, ['products.classifications.manage']);
    $actor->load('roles.permissions');
    $actor->roles()->detach();
    foreach ([ArchiveCategory::class, RestoreCategory::class] as $action) {
        expect(fn () => app($action)->handle($company, $category, $actor))->toThrow(AuthorizationException::class);
        expect(fn () => app($action)->handle($company, $foreign, $owner))->toThrow(ModelNotFoundException::class);
    }
    expect(fn () => app(UpdateCategory::class)->handle($company, $category, $actor, $values))->toThrow(AuthorizationException::class);
    foreach (['update', 'archive', 'restore'] as $verb) {
        $this->actingAs($actor)->patch(route('category.'.$verb, [$company, $category]), $values)->assertForbidden();
        $this->actingAs($owner)->patch(route('category.'.$verb, [$company, $foreign]), $values)->assertNotFound();
    }
    $this->patch(route('category.archive', [$company, $category]))->assertRedirect();
    expect($product->fresh()->category_id)->toBe($category->id);
    expect(fn () => $category->delete())->toThrow(QueryException::class);
});

it('correction preserves the archived current parent through the rendered edit form', function () {
    [$owner, $company] = SupplierFixture::owner();
    $this->actingAs($owner)->post(route('category.store', $company), ['name' => 'Parent', 'code' => 'P'])->assertRedirect();
    $parent = $company->categories()->where('code', 'P')->sole();
    $this->post(route('category.store', $company), ['name' => 'Child', 'code' => 'C', 'parent_id' => $parent->id])->assertRedirect();
    $child = $company->categories()->where('code', 'C')->sole();
    foreach ([$child, $parent] as $record) {
        $this->patch(route('category.archive', [$company, $record]))->assertRedirect();
    }
    $other = $company->categories()->create(['name' => 'Unrelated archived', 'code' => 'OTHER', 'is_active' => false]);
    $foreign = ProductFactory::new()->create()->category;
    $foreign->update(['name' => 'Foreign archived', 'is_active' => false]);
    $edit = route('category.edit', [$company, $child]);
    $page = $this->get($edit)->assertOk()->assertSee('Parent (Archived — current parent)')->assertDontSee('Unrelated archived')->assertDontSee('Foreign archived');
    $document = new DOMDocument;
    $document->loadHTML($page->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $selected = $xpath->query('//select[@name="parent_id"]/option[@selected]');
    expect($selected->length)->toBe(1)->and($selected->item(0)->getAttribute('value'))->toBe((string) $parent->id);
    $this->from($edit)->patch(route('category.update', [$company, $child]), ['name' => 'Corrected child', 'code' => 'C', 'parent_id' => $selected->item(0)->getAttribute('value')])->assertValid()->assertRedirect(route('category.index', $company));
    expect($child->fresh()->parent_id)->toBe($parent->id)->and($child->fresh()->name)->toBe('Corrected child')->and($child->fresh()->is_active)->toBeFalse();
    $this->from($edit)->patch(route('category.update', [$company, $child]), ['name' => 'Root correction', 'code' => 'C', 'parent_id' => null])->assertValid()->assertRedirect();
    expect($child->fresh()->parent_id)->toBeNull()->and($child->fresh()->is_active)->toBeFalse();
});

it('correction rejects archived parent creation and preserves selection after invalid parent submissions', function (string $invalid) {
    [$owner, $company] = SupplierFixture::owner();
    $parent = $company->categories()->create(['name' => 'Current parent', 'code' => 'P', 'is_active' => false]);
    $child = $company->categories()->create(['name' => 'Child', 'code' => 'C', 'parent_id' => $parent->id, 'is_active' => false]);
    $other = $company->categories()->create(['name' => 'Other archived', 'code' => 'O', 'is_active' => false]);
    $foreign = ProductFactory::new()->create()->category;
    $foreign->update(['is_active' => false]);
    $this->actingAs($owner)->post(route('category.store', $company), ['name' => 'New child', 'code' => 'N', 'parent_id' => $parent->id])->assertSessionHasErrors('parent_id');
    expect($company->categories()->where('code', 'N')->exists())->toBeFalse();
    $value = match ($invalid) {
        'different archived' => $other->id,
        'foreign archived' => $foreign->id,
        'nested array' => ['bad' => ['value']],
    };
    $page = $this->from(route('category.edit', [$company, $child]))->followingRedirects()->patch(route('category.update', [$company, $child]), ['name' => 'Attempted', 'code' => 'C', 'parent_id' => $value])->assertOk()->assertSee('role="alert"', false);
    $document = new DOMDocument;
    $document->loadHTML($page->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $selected = $xpath->query('//select[@name="parent_id"]/option[@selected]');
    expect($selected->length)->toBe(1)->and($selected->item(0)->getAttribute('value'))->toBe((string) $parent->id);
    expect($xpath->query('//*[@id="category-parent_id-error"]')->length)->toBe(1);
    expect($child->fresh()->parent_id)->toBe($parent->id)->and($child->fresh()->name)->toBe('Child');
})->with(['different archived', 'foreign archived', 'nested array']);

it('correction revalidates only the stored archived parent for direct Category Actions', function () {
    [$owner, $company] = SupplierFixture::owner();
    $parent = $company->categories()->create(['name' => 'Parent', 'code' => 'P', 'is_active' => false]);
    $other = $company->categories()->create(['name' => 'Other', 'code' => 'O', 'is_active' => false]);
    $child = $company->categories()->create(['name' => 'Child', 'code' => 'C', 'parent_id' => $parent->id, 'is_active' => false]);
    $foreign = ProductFactory::new()->create()->category;
    $foreign->update(['is_active' => false]);
    expect(fn () => app(CreateCategory::class)->handle($company, $owner, ['name' => 'New', 'code' => 'N', 'parent_id' => $parent->id]))->toThrow(ValidationException::class);
    $values = ['name' => 'Correction', 'code' => 'C'];
    app(UpdateCategory::class)->handle($company, $child, $owner, [...$values, 'parent_id' => $parent->id]);
    expect($child->fresh()->parent_id)->toBe($parent->id);
    $child->parent_id = $other->id;
    foreach ([$other->id, $foreign->id, $child->id] as $invalid) {
        expect(fn () => app(UpdateCategory::class)->handle($company, $child, $owner, [...$values, 'parent_id' => $invalid]))->toThrow(ValidationException::class);
    }
    app(UpdateCategory::class)->handle($company, $child, $owner, $values);
    expect($child->fresh()->parent_id)->toBe($parent->id);
    app(UpdateCategory::class)->handle($company, $child, $owner, [...$values, 'parent_id' => null]);
    expect($child->fresh()->parent_id)->toBeNull();
    expect(fn () => app(UpdateCategory::class)->handle($company, $child, $owner, [...$values, 'parent_id' => $parent->id]))->toThrow(ValidationException::class);
});
