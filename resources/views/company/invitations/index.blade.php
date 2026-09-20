<x-home :company="$company" :companies="$companies">
    <section class="space-y-6 py-6" aria-labelledby="invitations-heading">
        <a href="{{ route('home', $company) }}" class="text-sm text-pink-300 hover:underline">← Company overview</a>
        <x-access-feedback />
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-pink-400">Company access</p><h1 id="invitations-heading" class="mt-2 text-3xl font-bold text-white">Invitations</h1><p class="mt-2 text-slate-400">Invite teammates and keep track of their access to {{ $company->company_name }}.</p></div>
            @if ($canInviteMembers)<a href="{{ route('company.invitations.create', $company) }}" class="shrink-0 rounded-xl bg-pink-600 px-5 py-3 text-center font-semibold text-white hover:bg-pink-500 focus-visible:ring-2 focus-visible:ring-pink-400">Invite teammate</a>@endif
        </div>
        <form method="GET" action="{{ route('company.invitations.index', $company) }}" class="grid items-end gap-4 rounded-2xl border border-slate-800 bg-slate-950 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2"><label for="search" class="block text-sm font-medium text-slate-200">Search email</label><input id="search" name="search" type="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-white"></div>
            <div><label for="status" class="block text-sm font-medium text-slate-200">Status</label><select id="status" name="status" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-white"><option value="">All invitations</option>@foreach (['pending', 'accepted', 'cancelled', 'expired'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="flex items-center gap-4"><button class="rounded-xl bg-slate-700 px-5 py-2.5 font-semibold text-white hover:bg-slate-600">Filter</button><a href="{{ route('company.invitations.index', $company) }}" class="text-sm text-pink-300 hover:underline">Clear</a></div>
        </form>
        <div class="grid gap-4">
            @forelse ($invitations as $invitation)
                <article class="rounded-2xl border border-slate-800 bg-slate-950 p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
                        <div class="min-w-0"><h2 class="break-all text-lg font-semibold text-white">{{ $invitation->email }}</h2><p class="mt-1 text-sm text-slate-400">Invited by {{ $invitation->invitedBy?->name ?? 'a former member' }}</p></div>
                        <span @class(['self-start rounded-full px-3 py-1 text-xs font-semibold', 'bg-amber-500/15 text-amber-200' => $invitation->isPending(), 'bg-emerald-500/15 text-emerald-200' => $invitation->accepted_at, 'bg-slate-800 text-slate-300' => ! $invitation->isPending() && ! $invitation->accepted_at])>{{ ucfirst($invitation->lifecycle()->value) }}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2" aria-label="Selected roles">@forelse ($invitation->roles as $role)<span class="min-w-0 max-w-full break-words rounded-lg border border-slate-700 px-2.5 py-1 text-sm text-slate-200">{{ $role->display_name }}</span>@empty<span class="text-sm text-slate-400">Historical roles are no longer available.</span>@endforelse</div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                        <div><dt class="text-slate-500">Created (UTC)</dt><dd class="mt-1 text-slate-300">{{ $invitation->created_at->format('d M Y, H:i') }}</dd></div>
                        <div><dt class="text-slate-500">Expires (UTC)</dt><dd class="mt-1 text-slate-300">{{ $invitation->expires_at->format('d M Y, H:i') }}</dd></div>
                        @if ($invitation->accepted_at)<div><dt class="text-slate-500">Accepted (UTC)</dt><dd class="mt-1 text-slate-300">{{ $invitation->accepted_at->format('d M Y, H:i') }}</dd></div>@endif
                        @if ($invitation->cancelled_at)<div><dt class="text-slate-500">Cancelled (UTC)</dt><dd class="mt-1 text-slate-300">{{ $invitation->cancelled_at->format('d M Y, H:i') }}</dd></div>@endif
                    </dl>
                    @if ($invitation->isPending())
                        <div class="mt-5 flex flex-wrap items-start gap-3 border-t border-slate-800 pt-4">
                            @if ($canInviteMembers)<form method="POST" action="{{ route('company.invitations.resend', [$company, $invitation->uuid]) }}">@csrf<button class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-pink-300 hover:bg-slate-900">Resend email</button></form>@endif
                            @if ($canCancelInvitations)<details class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-rose-300"><summary class="cursor-pointer font-semibold">Cancel invitation</summary><p class="mt-3 max-w-sm text-slate-300">The invitation link will stop working. Its history will be kept.</p><form class="mt-3" method="POST" action="{{ route('company.invitations.cancel', [$company, $invitation->uuid]) }}">@csrf @method('DELETE')<button class="rounded-lg bg-rose-600 px-3 py-2 font-semibold text-white">Confirm cancellation</button></form></details>@endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 p-10 text-center"><h2 class="text-xl font-semibold text-white">No invitations found</h2><p class="mx-auto mt-3 max-w-md text-slate-400">Try adjusting your filters, or invite a teammate to start working together.</p></div>
            @endforelse
        </div>
        {{ $invitations->links() }}
    </section>
</x-home>
