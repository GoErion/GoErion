<x-auth>
    <section class="w-full max-w-xl rounded-3xl border border-slate-800 bg-slate-950 p-6 sm:p-10" aria-labelledby="review-heading">
        <p class="text-sm font-semibold uppercase tracking-widest text-pink-400">GoErion · Company invitation</p>
        <div class="mt-5"><x-access-feedback /></div>
        @if (! $invitation)
            <h1 id="review-heading" class="mt-3 text-3xl font-bold text-white">Invitation unavailable</h1>
            <p class="mt-4 text-slate-300">This invitation cannot be used. Ask the sender for a new invitation.</p>
            <a href="{{ route('marketing') }}" class="mt-6 inline-block text-pink-300 hover:underline">Return to GoErion</a>
        @else
            <h1 id="review-heading" class="mt-3 break-words text-3xl font-bold text-white">Join {{ $invitation->company->company_name }}</h1>
            <p class="mt-4 text-slate-300">{{ $invitation->invitedBy?->name ?? 'A company administrator' }} invited you to work together. Review your access before joining.</p>
            <h2 class="mt-6 text-sm font-semibold text-white">Your company roles</h2>
            <ul class="mt-3 flex flex-wrap gap-2">@foreach ($invitation->roles as $role)<li class="min-w-0 max-w-full break-words rounded-lg bg-slate-800 px-3 py-1.5 text-sm text-slate-200">{{ $role->display_name }}</li>@endforeach</ul>
            <p class="mt-5 text-sm text-slate-400">Expires {{ $invitation->expires_at->format('d M Y, H:i') }} UTC. This invitation can be accepted once.</p>
            @guest
                <p class="mt-6 text-slate-300">Sign in or create an account using the email address that received the invitation. You will return here before joining.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2"><a href="{{ route('login') }}" class="rounded-xl bg-pink-600 px-5 py-3 text-center font-semibold text-white hover:bg-pink-500">Sign in</a><a href="{{ route('register') }}" class="rounded-xl border border-slate-700 px-5 py-3 text-center font-semibold text-white">Create account</a></div>
            @else
                @if (! $matches)
                    <p role="alert" class="mt-6 rounded-xl bg-amber-500/10 p-4 text-amber-200">You are signed in with a different email address. Use the account that received this invitation.</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf<input type="hidden" name="return_to_invitation" value="1"><button class="rounded-xl border border-slate-700 px-5 py-3 font-semibold text-white">Sign out and use another account</button></form>
                @elseif (! auth()->user()->hasVerifiedEmail())
                    <p class="mt-6 text-slate-300">Verify your email address before accepting. Your invitation will be waiting when you return.</p><a href="{{ route('verification.notice') }}" class="mt-6 inline-block rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white">Verify email</a>
                @else
                    <p class="mt-6 text-slate-300">Joining will add you to this company with the roles above.</p><form method="POST" action="{{ route('invitations.accept') }}" class="mt-6">@csrf<button class="w-full rounded-xl bg-pink-600 px-5 py-3 font-semibold text-white hover:bg-pink-500 focus-visible:ring-2 focus-visible:ring-pink-400">Accept invitation and join company</button></form>
                @endif
            @endguest
        @endif
    </section>
</x-auth>
