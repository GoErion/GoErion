<div class="flex flex-wrap gap-2">
    @forelse ($member->roles as $role)
        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $role->isOwner() ? 'bg-pink-500/20 text-pink-200' : 'bg-slate-800 text-slate-200' }}">
            {{ $role->display_name ?: $role->name }}
        </span>
    @empty
        <span class="text-xs text-slate-500">No roles</span>
    @endforelse
</div>

<div class="mt-3 flex flex-wrap gap-2">
    @can('manageOwnership', $company)
        @if ($member->is_company_owner)
            <a href="{{ route('company.owners.demote', [$company, $member->uuid]) }}" class="rounded-lg border border-amber-500/40 px-3 py-2 text-sm text-amber-200">Demote owner</a>
        @else
            <a href="{{ route('company.owners.promote', [$company, $member->uuid]) }}" class="rounded-lg border border-pink-500/40 px-3 py-2 text-sm text-pink-200">Promote owner</a>
        @endif
    @endcan
    @can('manageMembers', $company)
        @unless ($member->is_company_owner)
            <a href="{{ route('company.members.remove', [$company, $member->uuid]) }}" class="rounded-lg border border-red-500/40 px-3 py-2 text-sm text-red-200">Remove</a>
        @endunless
    @endcan
</div>

@can('manageMembers', $company)
    <form method="POST" action="{{ route('company.members.roles.update', [$company, $member->uuid]) }}" class="mt-4 border-t border-slate-800 pt-4">
        @csrf
        @method('PUT')
        <fieldset>
            <legend class="text-sm font-semibold text-slate-200">Custom roles</legend>
            <div class="mt-2 flex flex-wrap gap-4">
                @foreach ($customRoles as $role)
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked($member->roles->contains('id', $role->id)) class="rounded border-slate-600 bg-slate-900 text-pink-600">
                        {{ $role->display_name ?: $role->name }}
                    </label>
                @endforeach
            </div>
            <button class="mt-3 rounded-lg bg-slate-700 px-3 py-2 text-sm font-semibold text-white">Save roles</button>
        </fieldset>
    </form>
@endcan
