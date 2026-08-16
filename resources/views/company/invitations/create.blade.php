<x-app>
    <div class="flex justify-center">
        <x-slot name="title">
            Send Invitation
        </x-slot>
        <div class="w-full max-w-2xl border border-slate-900 p-4 rounded-2xl sm:p-6 md:p-8">
            <form class="space-y-6" action="{{ route('invitations.store', $company) }}" method="POST">
                @csrf
                <h5 class="text-xl font-bold font-sans">Invite user to {{ $company->company_name }}</h5>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="email" class="block mb-2 font-bold">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('email') border-red-500 @enderror block w-full p-2.5"
                            placeholder="user@example.com" required />
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="role_id" class="block mb-2 font-bold">{{ __('Role') }}</label>
                        <select name="role_id" id="role_id"
                            class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5"
                            required>
                            <option value="">{{ __('Select role') }}</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                    {{ $role->display_name ?? $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex gap-4">
                        <button type="submit"
                            class="px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition">{{ __('Send Invitation') }}</button>
                        <a href="{{ url()->previous() }}"
                            class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app>
