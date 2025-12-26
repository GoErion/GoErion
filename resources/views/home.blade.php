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
</x-app>
