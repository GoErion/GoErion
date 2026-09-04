<?php

declare(strict_types=1);

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates a unique uuid and uses it as the route key', function () {
    $firstUser = UserFactory::new()->create();
    $secondUser = UserFactory::new()->create();

    expect(Str::isUuid($firstUser->uuid))->toBeTrue();
    expect($firstUser->uuid)->not->toBe($secondUser->uuid);
    expect($firstUser->getRouteKeyName())->toBe('uuid');
    expect($firstUser->getRouteKey())->toBe($firstUser->uuid);
});
