<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $shopName)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-shop-50 text-shop-950 antialiased">
    <header class="sticky top-0 z-40 border-b border-stone-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('products.index') }}" class="flex items-center gap-2">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-shop-600 text-sm font-bold text-white">B</span>
                <span class="text-lg font-semibold tracking-tight">{{ $shopName }}</span>
            </a>

            <nav class="flex items-center gap-2 text-sm sm:gap-4">
                <a href="{{ route('products.index') }}" class="rounded-lg px-3 py-2 font-medium text-stone-700 hover:bg-shop-100 hover:text-shop-700">
                    Ürünler
                </a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ url('/admin') }}" class="rounded-lg px-3 py-2 font-medium text-stone-700 hover:bg-shop-100 hover:text-shop-700">
                            Admin
                        </a>
                    @else
                        <a href="{{ route('cart.index') }}" class="relative rounded-lg px-3 py-2 font-medium text-stone-700 hover:bg-shop-100 hover:text-shop-700">
                            Sepet
                            @if ($cartItemCount > 0)
                                <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-shop-600 px-1 text-xs font-semibold text-white">
                                    {{ $cartItemCount }}
                                </span>
                            @endif
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 font-medium text-stone-600 hover:bg-stone-100">
                            Çıkış
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 font-medium text-stone-700 hover:bg-shop-100">
                        Giriş
                    </a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-shop-600 px-4 py-2 font-medium text-white hover:bg-shop-700">
                        Kayıt Ol
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-16 border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>&copy; {{ date('Y') }} {{ $shopName }}. Güvenli alışveriş deneyimi.</p>
            <p>Sepete eklenen ürünler {{ $reservationMinutes }} dakika rezerve edilir.</p>
        </div>
    </footer>
</body>
</html>
