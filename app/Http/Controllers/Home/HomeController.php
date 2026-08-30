<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Services\Company\NumberSequenceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class HomeController extends Controller
{
    public function home(Request $request,Company $company)
    {
        //nested relationship using with()
        $companies = $request->user()->companies()->get();
        abort_unless(
            $companies->contains($company->getKey()),403);

        return view('index', compact('companies','company',));
    }
}
