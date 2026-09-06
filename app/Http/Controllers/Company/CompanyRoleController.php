<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\CreateRole;
use App\Actions\Company\DeleteRole;
use App\Actions\Company\UpdateRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\DeleteRoleRequest;
use App\Http\Requests\Company\StoreRoleRequest;
use App\Http\Requests\Company\UpdateRoleRequest;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CompanyRoleController extends Controller
{
    public function index(Request $request, Company $company): View
    {
        Gate::authorize('viewRoles', $company);
        $roles = $company->roles()->withCount('users')->with('permissions')->orderByDesc('is_system')->orderBy('display_name')->paginate(12);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.roles.index', compact('company', 'companies', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Company $company): View
    {
        Gate::authorize('manageRoles', $company);
        $permissions = Permission::query()->assignableToCustomRoles()->orderBy('display_name')->get();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.roles.create', compact('company', 'companies', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, Company $company, CreateRole $action): RedirectResponse
    {
        $values = $request->validated();
        try {
            $action->handle($company, $request->user(), $values['name'], $values['display_name'] ?? null, $values['permission_ids']);
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.roles.index', $company)->with('success', 'Role created.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Request $request, Company $company, string $roleUuid): View
    {
        Gate::authorize('manageRoles', $company);
        $role = $this->role($company, $roleUuid);
        abort_unless($role->isEditable(), 404);
        $permissions = Permission::query()->assignableToCustomRoles()->orderBy('display_name')->get();
        $selectedPermissionIds = $role->permissions()->pluck('permissions.id')->all();
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.roles.edit', compact('company', 'companies', 'role', 'permissions', 'selectedPermissionIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Company $company, string $roleUuid, UpdateRole $action): RedirectResponse
    {
        $role = $this->role($company, $roleUuid);
        $values = $request->validated();
        try {
            $action->handle($company, $role, $request->user(), $values['name'], $values['display_name'] ?? null, $values['permission_ids']);
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.roles.index', $company)->with('success', 'Role updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteRoleRequest $request, Company $company, string $roleUuid, DeleteRole $action): RedirectResponse
    {
        try {
            $action->handle($company, $this->role($company, $roleUuid), $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.roles.index', $company)->with('success', 'Role deleted.');
    }

    private function role(Company $company, string $uuid): Role
    {
        return $company->roles()->where('uuid', $uuid)->firstOrFail();
    }
}
