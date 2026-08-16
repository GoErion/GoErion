<x-app>
    <div class="flex justify-center">
        <x-slot name="title">
            Accept Invitation
        </x-slot>
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <h5 class="text-xl font-bold font-sans">{{ __('You\'re invited') }}</h5>
            <p class="mt-2 text-sm text-slate-400">{{ __('You have been invited to join a company on GoErion.') }}</p>

            <div class="grid gap-4 md:grid-cols-2 mt-6">
                <div class="md:col-span-2 border border-slate-900 rounded-lg p-4">
                    <p class="text-xs uppercase font-bold text-slate-500">{{ __('Company') }}</p>
                    <p class="mt-2 text-lg font-semibold">{{ $invitation->company->company_name }}</p>
                </div>

                <div class="border border-slate-900 rounded-lg p-4">
                    <p class="text-xs uppercase font-bold text-slate-500">{{ __('Role') }}</p>
                    <p class="mt-2 font-semibold">{{ $invitation->role->display_name ?? $invitation->role->name }}</p>
                </div>

                <div class="border border-slate-900 rounded-lg p-4">
                    <p class="text-xs uppercase font-bold text-slate-500">{{ __('Invited Email') }}</p>
                    <p class="mt-2">{{ $invitation->email }}</p>
                </div>

                <div class="md:col-span-2 border border-slate-900 rounded-lg p-4">
                    <p class="text-xs uppercase font-bold text-slate-500">{{ __('Invitation Expires') }}</p>
                    <p class="mt-2">{{ $invitation->expires_at->format('M d, Y H:i') }}</p>
                </div>

                <form class="md:col-span-2 flex gap-4 pt-2"
                    action="{{ route('invitations.confirm', ['token' => $invitation->token]) }}"
                    method="POST">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition">{{ __('Accept Invitation') }}</button>
                    <a href="{{ url()->previous() }}"
                        class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">{{ __('Cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</x-app>


{{-- Cancel --}}
<a href="{{ route('home') }}" class="mt-3 block text-center text-sm text-slate-500 hover:text-slate-300">
    Cancel
</a>

</div>

</div>
</x-app>
