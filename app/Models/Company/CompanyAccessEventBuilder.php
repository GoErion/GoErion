<?php

declare(strict_types=1);

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends Builder<CompanyAccessEvent> */
final class CompanyAccessEventBuilder extends Builder
{
    public function update(array $values): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function delete(): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function forceDelete(): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function increment(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function decrement(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function incrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function decrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function upsert(array $values, mixed $uniqueBy, mixed $update = null): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function updateFrom(array $values): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function updateOrInsert(array $attributes, mixed $values = []): never
    {
        throw new LogicException('Company access events are append-only.');
    }

    public function truncate(): never
    {
        throw new LogicException('Company access events are append-only.');
    }
}
