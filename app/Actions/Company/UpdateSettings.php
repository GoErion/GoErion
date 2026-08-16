<?php
declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company\Company;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSettings
{
    public function handle(array $settings, Company $company)
    {
        return DB::transaction(function() use($company,$settings)
        {
            foreach($settings as $key => $value)
                {
                    $company->settings()->updateOrCreate(
                        ['key' => $key],
                        ['value' => $value]
                        );
                }
        },2);
    }
}
