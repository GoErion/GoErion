<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Actions\Company\AssignMemberRoles;
use App\Actions\Company\RemoveCompanyMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\MemberIndexRequest;
use App\Http\Requests\Company\RemoveMemberRequest;
use App\Http\Requests\Company\UpdateMemberRolesRequest;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Models\Company\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CompanyMemberController extends Controller
{
    public function index(MemberIndexRequest $request, Company $company): View
    {
        $filters = $request->validated();
        $members = $company->users()->select('users.*')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($nested) => $nested->where('users.name', 'like', "%{$search}%")->orWhere('users.email', 'like', '%'.strtolower(trim($search)).'%'));
            })
            ->when($filters['role'] ?? null, fn ($query, string $uuid) => $query->whereHas('roles', fn ($roles) => $roles->where('roles.company_id', $company->id)->where('roles.uuid', $uuid)))
            ->with(['roles' => fn ($query) => $query->where('roles.company_id', $company->id)->orderBy('display_name')])
            ->orderBy('users.name')->orderBy('users.id')->paginate(12)->withQueryString();
        $members->getCollection()->each(function (User $member): void {
            $member->setAttribute('is_company_owner', $member->roles->contains(
                static fn (Role $role): bool => $role->isOwner(),
            ));
        });
        $roles = $company->roles()->orderByDesc('is_system')->orderBy('display_name')->get();
        $customRoles = $roles->where('is_system', false);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.members.index', compact('company', 'companies', 'members', 'roles', 'customRoles', 'filters'));
    }

    public function updateRoles(UpdateMemberRolesRequest $request, Company $company, string $memberUuid, AssignMemberRoles $action): RedirectResponse
    {
        try {
            $action->handle($company, $this->member($company, $memberUuid), $request->user(), $request->validated('role_ids'));
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return back()->with('success', 'Member roles updated.');
    }

    public function confirmRemoval(RemoveMemberRequest $request, Company $company, string $memberUuid): View
    {
        $member = $this->member($company, $memberUuid);
        $companies = $request->user()->companies()->orderBy('company_name')->get();

        return view('company.members.remove', compact('company', 'companies', 'member'));
    }

    public function destroy(RemoveMemberRequest $request, Company $company, string $memberUuid, RemoveCompanyMember $action): RedirectResponse
    {
        try {
            $action->handle($company, $this->member($company, $memberUuid), $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['access' => $exception->getMessage()]);
        }

        return redirect()->route('company.members.index', $company)->with('success', 'Member removed.');
    }

    private function member(Company $company, string $uuid): User
    {
        return $company->users()->where('users.uuid', $uuid)->firstOrFail();
    }
}
