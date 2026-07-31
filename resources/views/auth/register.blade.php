@extends('layouts.app')

@section('title', 'Kayıt Ol')

@section('content')
    <div class="mx-auto max-w-md rounded-xl border border-stone-200 bg-white p-6 shadow-sm">
        <h1 class="mb-6 text-2xl font-semibold">Kayıt Ol</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Ad Soyad</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="w-full rounded-md border border-stone-300 px-3 py-2"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">E-posta</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full rounded-md border border-stone-300 px-3 py-2"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Şifre</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full rounded-md border border-stone-300 px-3 py-2"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Şifre Tekrar</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    class="w-full rounded-md border border-stone-300 px-3 py-2"
                >
            </div>

            <button type="submit" class="w-full rounded-md bg-amber-500 px-4 py-2 font-medium text-white hover:bg-amber-600">
                Kayıt Ol
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-stone-600">
            Zaten hesabınız var mı?
            <a href="{{ route('login') }}" class="font-medium text-amber-600 hover:underline">Giriş yapın</a>
        </p>
    </div>
@endsection
