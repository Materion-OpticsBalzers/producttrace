<?php
    new class extends \Livewire\Volt\Component {
        public function logout() {
            auth()->logout();

            $this->redirect('/', true);
        }
    }
?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-md w-full z-[12] fixed top-0">
    <!-- Primary Navigation Menu -->
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        @if(env('PT_SYSTEM') == 'test')
                            <h1 class="text-2xl font-extrabold text-red-600"><i class="far fa-tools mr-1"></i> Product Tracking Testsystem</h1>
                        @else
                            <h1 class="text-2xl font-extrabold"><i class="far fa-tools mr-1"></i> Product Tracking</h1>
                        @endif
                    </a>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            <div>{{ Auth::user()->name }} ({{ auth()->user()->personnel_number }})</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link href="javascript:;" wire:click="logout">
                            <i class="fal fa-sign-out"></i> {{ __('Ausloggen') }}
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link href="{{ 'javascript:;' }}" wire:click="logout">
                    <i class="fal fa-sign-out"></i> {{ __('Ausloggen') }}
                </x-responsive-nav-link>
            </div>
        </div>
    </div>
    <div class="w-full hidden lg:flex bg-white px-8 fixed-top border-t border-gray-200">
        @if(auth()->user()->is_admin)
            <a href="{{ route('orders.create') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::currentRouteName(), 'orders.create')) bg-gray-100 text-[#0085CA] @endif"><i class="fal fa-plus mr-1"></i> Auftrag erstellen</a>
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::current()->uri, 'admin')) bg-gray-100 text-[#0085CA] @endif" ><i class="fal fa-user-shield mr-1"></i> Administration</a>
        @endif
        <a href="{{ route('queries') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::currentRouteName(), 'queries')) bg-gray-100 text-[#0085CA] @endif"><i class="fal fa-database mr-1"></i> Auswertungen</a>
        <a href="{{ route('serialise') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::currentRouteName(), 'serialise')) bg-gray-100 text-[#0085CA] @endif"><i class="fal fa-link mr-1"></i> Serialisierung</a>
        <a href="{{ route('coa') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::currentRouteName(), 'coa')) bg-gray-100 text-[#0085CA] @endif"><i class="fal fa-link mr-1"></i> CofA</a>
        <a href="{{ route('changelog') }}" wire:navigate class="font-semibold hover:bg-gray-100 py-3 px-2 @if(str_contains(Route::currentRouteName(), 'changelog')) bg-gray-100 text-[#0085CA] @endif"><i class="fal fa-link mr-1"></i> Changelog</a>
    </div>
</nav>
