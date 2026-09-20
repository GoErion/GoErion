<x-home :company="$company" :companies="$companies">
    <section class="mx-auto max-w-3xl py-6" aria-labelledby="invite-heading">
        <a href="{{ route('company.invitations.index', $company) }}" class="text-sm text-pink-300 hover:underline">← Invitations</a>
        <h1 id="invite-heading" class="mt-4 text-3xl font-bold text-white">Invite someone to your company</h1>
        <p class="mt-3 text-slate-400">Give a teammate access to {{ $company->company_name }}. Choose only the roles they need.</p>
        <div class="mt-6"><x-access-feedback /></div>
        <form method="POST" action="{{ route('company.invitations.store', $company) }}" class="mt-6 space-y-6 rounded-2xl border border-slate-800 bg-slate-950 p-5 sm:p-8">
            @csrf
            <div>
                <label for="email" class="block font-medium text-white">Recipient email</label>
                <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ is_string(old('email')) ? old('email') : '' }}" aria-describedby="email-help email-error" @error('email') aria-invalid="true" @enderror class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-white focus:border-pink-500 focus:ring-pink-500">
                <p id="email-help" class="mt-2 text-sm text-slate-400">They must sign in with this email and complete normal email verification.</p>
                <p id="email-error" class="mt-2 text-sm text-rose-300">@error('email') {{ $message }} @enderror</p>
            </div>
            <fieldset aria-describedby="roles-help roles-error">
                <legend class="font-medium text-white">Company roles</legend>
                <p id="roles-help" class="mt-2 text-sm text-slate-400">Select one or more roles. Invitations cannot grant ownership or system roles.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @forelse ($roles as $role)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-700 bg-slate-900 p-4 has-checked:border-pink-500">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, array_map('strval', array_filter((array) old('role_ids', []), 'is_scalar')), true)) class="mt-1 rounded border-slate-600 text-pink-600 focus:ring-pink-500">
                            <span class="min-w-0"><span class="block break-words font-medium text-white">{{ $role->display_name }}</span><span class="mt-1 block text-sm text-slate-400">{{ $role->description }}</span></span>
                        </label>
                    @empty
                        <p class="text-sm text-amber-200">No assignable roles are available. Create a company role before inviting someone.</p>
                    @endforelse
                </div>
                <div id="roles-error" class="mt-2 text-sm text-rose-300">@error('role_ids') <p>{{ $message }}</p> @enderror @foreach ($errors->get('role_ids.*') as $messages) @foreach ($messages as $message) <p>{{ $message }}</p> @endforeach @endforeach</div>
            </fieldset>
            <p class="rounded-xl bg-slate-900 p-4 text-sm text-slate-300">This invitation expires in {{ $lifetimeDays }} days and can be accepted once. Opening the email link does not join the company.</p>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('company.invitations.index', $company) }}" class="rounded-xl border border-slate-700 px-5 py-3 text-center text-slate-200">Cancel</a>
                <button @disabled($roles->isEmpty()) class="rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white hover:bg-pink-500 focus-visible:ring-2 focus-visible:ring-pink-400 disabled:cursor-not-allowed disabled:opacity-50">Send invitation</button>
            </div>
        </form>
    </section>
</x-home>
