<?php
declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Company\NumberSequence;
use Illuminate\Support\Facades\DB;

final readonly class NumberSequenceService
{
    public function next(NumberSequence $sequence)
    {
        return DB::transaction(function() use($sequence): string
        {
            $sequence = NumberSequence::query()
                ->whereKey($sequence->getKey())
                ->lockForUpdate()->firstOrFail();

            $number = $sequence->next_number;
            
            $sequence->increment('next_number');

            return sprintf(
                '%s-%06d',
                $sequence->prefix ?? '',
                $number
            );
        });
    }
}
