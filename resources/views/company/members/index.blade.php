<x-home :company="$company" :companies="$companies">
    <section class="space-y-6 py-6" aria-labelledby="members-heading"><x-access-feedback />
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 id="members-heading" class="text-3xl font-bold text-white">Company members</h1>
                <p class="mt-1 text-sm text-slate-400">Manage member access for {{ $company->company_name }}.</p>
            </div>
            <a href="{{ route('company.membership.leave', $company) }}" class="rounded-xl border border-red-500/50 px-4 py-2 text-center text-sm font-semibold text-red-300">Leave company</a>
        </div>




        <form method="GET" action="{{ route('company.members.index', $company) }}" class="grid gap-3 rounded-2xl border border-slate-800 bg-slate-950 p-4 md:grid-cols-[1fr_16rem_auto]">
            <label class="grid gap-1 text-sm text-slate-300"><span>Name or email</span><input name="search" value="{{ $filters['search'] ?? '' }}" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white"></label>
            <label class="grid gap-1 text-sm text-slate-300"><span>Role</span><select name="role" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white"><option value="">All roles</option>@foreach ($roles as $role)<option value="{{ $role->uuid }}" @selected(($filters['role'] ?? '') === $role->uuid)>{{ $role->display_name ?: $role->name }}</option>@endforeach</select></label>
            <button class="self-end rounded-xl bg-pink-600 px-4 py-2 font-semibold text-white">Filter</button>
        </form>

        @if ($members->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400">No members match these filters.</div>
        @else
            <div class="grid gap-4 md:hidden">
                @foreach ($members as $member)
                    <article class="rounded-2xl border border-slate-800 bg-slate-950 p-5">
                        <h2 class="font-semibold text-white">{{ $member->name }}</h2>
                        <p class="mb-3 text-sm text-slate-400">{{ $member->email }}</p>
                        @include('company.members._access', ['company' => $company, 'member' => $member, 'customRoles' => $customRoles])
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto rounded-2xl border border-slate-800 md:block">
                <table class="min-w-full divide-y divide-slate-800 bg-slate-950">
                    <caption class="sr-only">Members and access for {{ $company->company_name }}</caption>
                    <thead class="bg-slate-900/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr><th scope="col" class="px-5 py-3">Member</th><th scope="col" class="px-5 py-3">Access</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach ($members as $member)
                            <tr class="align-top">
                                <th scope="row" class="whitespace-nowrap px-5 py-5 text-left"><span class="block font-semibold text-white">{{ $member->name }}</span><span class="block text-sm font-normal text-slate-400">{{ $member->email }}</span></th>
                                <td class="min-w-[34rem] px-5 py-5">@include('company.members._access', ['company' => $company, 'member' => $member, 'customRoles' => $customRoles])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $members->links() }}</div>
        @endif
    </section>
</x-home>
