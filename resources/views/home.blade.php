<x-app>
    <x-slot name="title">
        Home
    </x-slot>
    <div class="flex justify-center">
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <div class="border border-slate-400 rounded-lg py-2 px-4">
                <button type="submit">Logout</button>
            </div>
        </form>
      <div class="text-center">
          <h1 class="mt-12 block">
              Welcome home
          </h1>
      </div>
    </div>
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-transparent border border-slate-400 rounded-lg">
            <div class="">
                @foreach($users as $user)
                    <h1>{{ $user->username }}</h1>
                    <p>
                        {{ $user->id }}
                    </p>
                @endforeach
            </div>
        </div>
    </div>
</x-app>
