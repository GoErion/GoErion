<?php
declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuid
{
    use HasUuids;
    
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
