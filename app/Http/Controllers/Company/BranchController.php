<?php
declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\BranchRequest;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class BranchController extends Controller
{
    public function create(Company $company)
    {
        return view('company.branch.create',compact('company'));
    }
    public function store(BranchRequest $request,Company $company,CreateBranch $action)
    {
        $branch = $action->handle($request->validated(),$company);

        return redirect()->route('home',$company)->with('success', 'Branch created successfully.');
    }
}
