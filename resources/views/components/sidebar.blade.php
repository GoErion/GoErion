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
                                    <x-sidebar-link href="{{ route('home', $company) }}">
                                        Dashboard
                                    </x-sidebar-link>
                                </li>
                                <li>
                                    <x-sidebar-link href="{{ route('companies.select') }}">
                                        Switch company
                                    </x-sidebar-link>
                                </li>
                                @can('viewMembers', $company)
                                    <li><x-sidebar-link href="{{ route('company.members.index', $company) }}">Members</x-sidebar-link></li>
                                @endcan
                                @can('inviteMembers', $company)
                                    <li><x-sidebar-link href="{{ route('company.invitations.index', $company) }}">Invitations</x-sidebar-link></li>
                                @endcan
                                @can('viewRoles', $company)
                                    <li><x-sidebar-link href="{{ route('company.roles.index', $company) }}">Roles</x-sidebar-link></li>
                                @endcan
                                @can('viewAny', [\App\Models\Supplier\Supplier::class, $company])
                                    <li><x-sidebar-link href="{{ route('company.suppliers.index', $company) }}">Suppliers</x-sidebar-link></li>
                                @endcan
                                @can('viewAny', [\App\Models\Customer\Customer::class, $company])
                                    <li><x-sidebar-link href="{{ route('company.customers.index', $company) }}">Customers</x-sidebar-link></li>
                                @endcan
@can('viewAny', [\App\Models\Product\Product::class, $company])
                                <li>
                                        <x-sidebar-link href="{{ route('product.index',$company) }}">
                                            Product
                                        </x-sidebar-link>
                                </li>
@endcan
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
