<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

#[Middleware('auth')]
final class CompanyController extends Controller
{
    public function create()
    {
        return view('company.create');
    }
    public function store(CompanyRequest $request,CreateCompany $action)
    {
        $user = $request->user();

        $company = $action->handle($request->validated(),$user);

        return redirect()->route('home')->with('success','Company successful created');
    }
}
