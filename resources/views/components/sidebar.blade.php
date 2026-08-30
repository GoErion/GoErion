@props(['company'])
<div>
     <div class="hidden mt-2 mb-2 border border-slate-900 rounded-2xl bg-transparent ring-1 ring-slate-950 lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <img src="{{ asset(config('marketing.logo')) }}" alt="logo" class="h-22 w-auto">
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <x-sidebar-link>
                                        Dashboard
                                    </x-sidebar-link>
                                </li>
                                <li>
                                        <x-sidebar-link href="{{ route('product.index',$company) }}">
                                            Product
                                        </x-sidebar-link>
                                </li>
                                <li>
                                  <x-sidebar-link href="">
                                      Reports
                                  </x-sidebar-link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
</div>