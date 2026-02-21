<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex min-w-0 items-center">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-bold text-amber-700">
                        <x-application-logo class="h-8 w-8" />
                        <span>Choyxona CRM</span>
                    </a>
                </div>

                <div class="hidden md:-my-px md:ms-6 md:flex md:min-w-0 md:flex-1 md:items-center md:space-x-5 md:overflow-x-auto md:whitespace-nowrap lg:ms-10 lg:space-x-8">
                    @php $role = Auth::user()->role; @endphp

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Asosiy panel
                    </x-nav-link>

                    @if (in_array($role, ['admin', 'manager', 'cashier'], true))
                        <x-nav-link :href="route('orders.history')" :active="request()->routeIs('orders.history')">
                            Buyurtmalar Tarixi
                        </x-nav-link>
                    @endif

                    @if (in_array($role, ['admin', 'manager'], true))
                        <x-nav-link :href="route('menu.index')" :active="request()->routeIs('menu.*')">
                            Menyu
                        </x-nav-link>

                        <x-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.*')">
                            Xonalar
                        </x-nav-link>

                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            Hisobotlar
                        </x-nav-link>

                        <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                            Sozlamalar
                        </x-nav-link>
                    @endif

                    @if ($role === 'admin')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            Foydalanuvchilar
                        </x-nav-link>

                        <x-nav-link :href="route('activity-logs.index')" :active="request()->routeIs('activity-logs.*')">
                            Faoliyat jurnali
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden shrink-0 md:ms-4 md:flex md:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }} ({{ Auth::user()->role }})</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Chiqish
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center md:hidden">
                <button
                    @click="open = ! open"
                    :aria-expanded="open.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Navigatsiyani ochish"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-nav" :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Asosiy panel
            </x-responsive-nav-link>

            @if (in_array(Auth::user()->role, ['admin', 'manager', 'cashier'], true))
                <x-responsive-nav-link :href="route('orders.history')" :active="request()->routeIs('orders.history')">
                    Buyurtmalar Tarixi
                </x-responsive-nav-link>
            @endif

            @if (in_array(Auth::user()->role, ['admin', 'manager'], true))
                <x-responsive-nav-link :href="route('menu.index')" :active="request()->routeIs('menu.*')">
                    Menyu
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.*')">
                    Xonalar
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    Hisobotlar
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                    Sozlamalar
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->role === 'admin')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    Foydalanuvchilar
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('activity-logs.index')" :active="request()->routeIs('activity-logs.*')">
                    Faoliyat jurnali
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        Chiqish
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
