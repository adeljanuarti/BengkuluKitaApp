@php
    $settings  = $settings ?? \App\Models\SiteSetting::first();
    $cartCount = collect(session('cart', []))->sum('quantity');
    $isLogin   = auth()->check();
    $isAdmin   = $isLogin && auth()->user()->role === 'admin';

    $linkBase   = 'text-sm font-semibold transition';
    $linkNormal = 'text-[#5e5143] hover:text-[#8b6b45]';
    $linkActive = 'text-[#8b6b45]';
@endphp

<header class="sticky top-0 z-50 bg-[#fffaf2]/95 backdrop-blur border-b border-[#e2d5c3]">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="h-20 flex items-center justify-between">

            {{-- LOGO (admin ke dashboard, lainnya ke home) --}}
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('home') }}" class="flex items-center gap-3 group">

                <div class="w-12 h-12 rounded-xl bg-[#f5e7c7] border border-[#dfcfb8] flex items-center justify-center overflow-hidden shadow-sm group-hover:-translate-y-1 group-hover:shadow-md transition-all duration-200">

                    @if(!empty($settings?->logo))
                        <img
                            src="{{ asset('storage/' . $settings->logo) }}"
                            alt="{{ $settings->business_name }}"
                            class="w-[78%] h-[78%] object-contain"
                        >
                    @else
                        <img
                            src="{{ asset('images/logo-kue.png') }}"
                            alt="Logo BengkuluKita"
                            class="w-[78%] h-[78%] object-contain"
                        >
                    @endif

                </div>

                <div class="leading-tight">
                    <div class="text-xl font-extrabold tracking-tight text-[#29251f]">
                        {{ $settings->business_name ?? 'BengkuluKita' }}
                    </div>
                    <div class="text-[10px] uppercase tracking-[0.18em] font-semibold text-[#8b6b45]">
                        Oleh-Oleh Bengkulu
                    </div>
                </div>

            </a>


            {{-- DESKTOP --}}
            <nav class="hidden md:flex items-center gap-7">

                @if($isAdmin)

                    <a href="{{ route('admin.dashboard') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('admin.dashboard') ? $linkActive : $linkNormal }}">
                        Dashboard Admin
                    </a>

                    <a href="{{ route('admin.products.index') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('admin.products.*') ? $linkActive : $linkNormal }}">
                        Produk
                    </a>

                    <a href="{{ route('admin.orders.index') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('admin.orders.*') ? $linkActive : $linkNormal }}">
                        Pesanan
                    </a>

                @elseif($isLogin)

                    <a href="{{ route('home') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('home') ? $linkActive : $linkNormal }}">
                        Beranda
                    </a>

                    <a href="{{ route('products.index') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('products.*') ? $linkActive : $linkNormal }}">
                        Belanja
                    </a>

                    <a href="{{ route('orders.index') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('orders.*') ? $linkActive : $linkNormal }}">
                        Pesanan Saya
                    </a>

                    <a href="{{ route('cart.index') }}"
                       class="relative {{ $linkBase }} {{ request()->routeIs('cart.*') ? $linkActive : $linkNormal }}">

                        Keranjang

                        @if($cartCount > 0)
                            <span class="absolute -top-3 -right-5 min-w-5 h-5 px-1 rounded-full bg-[#8b6b45] text-white text-[10px] font-black flex items-center justify-center">
                                {{ $cartCount }}
                            </span>
                        @endif

                    </a>

                    <span class="text-sm font-semibold text-[#8b6b45]">
                        Hai, {{ auth()->user()->name }}
                    </span>

                @else

                    <a href="{{ route('home') }}"
                       class="{{ $linkBase }} {{ request()->routeIs('home') ? $linkActive : $linkNormal }}">
                        Beranda
                    </a>

                @endif


                @if($isLogin)

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800 transition">
                            Logout
                        </button>
                    </form>

                @else

                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-xl bg-[#29251f] text-white text-sm font-semibold hover:bg-[#8b6b45] transition">
                        Login
                    </a>

                @endif

            </nav>


            {{-- MOBILE --}}
            <div class="flex items-center gap-2 md:hidden">

                @if($isAdmin)

                    <a href="{{ route('admin.dashboard') }}"
                       class="px-4 py-2 rounded-xl bg-[#29251f] text-white text-sm font-semibold">
                        Admin
                    </a>

                @elseif($isLogin)

                    <a href="{{ route('orders.index') }}"
                       class="w-10 h-10 rounded-xl bg-[#f5e7c7] flex items-center justify-center"
                       title="Pesanan Saya">
                        📦
                    </a>

                    <a href="{{ route('cart.index') }}"
                       class="relative w-10 h-10 rounded-xl bg-[#f5e7c7] flex items-center justify-center">

                        🛒

                        @if($cartCount > 0)
                            <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-[#8b6b45] text-white text-[10px] font-black flex items-center justify-center">
                                {{ $cartCount }}
                            </span>
                        @endif

                    </a>

                @endif

                @if($isLogin)

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-semibold">
                            Logout
                        </button>
                    </form>

                @else

                    <a href="{{ route('login') }}"
                       class="px-4 py-2 rounded-xl bg-[#29251f] text-white text-sm font-semibold">
                        Login
                    </a>

                @endif

            </div>

        </div>

    </div>

</header>
