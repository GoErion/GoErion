<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SettingRequest;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingController extends Controller
{
    public function edit(SettingRequest $request, Company $company): View
    {
        $settings = $company->settings()->pluck('value', 'key');

        return view('company.settings.edit', compact('company', 'settings'));
    }

    public function update(SettingRequest $request, Company $company, UpdateSettings $action): RedirectResponse
    {
        $action->handle($request->validated(), $company);

        return redirect()->route('home', $company)->with('success', 'Settings updated successfully.');
    }
}
