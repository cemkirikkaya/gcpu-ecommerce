@extends('layouts.app')

@section('title', 'Ödeme')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-stone-900">Ödeme</h1>
        <p class="mt-2 text-stone-600">Teslimat adresinizi seçin veya yeni adres ekleyin.</p>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <form method="POST" action="{{ route('checkout.store') }}" class="space-y-6">
            @csrf

            @if ($addresses->isNotEmpty())
                <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-stone-900">Kayıtlı Adresler</h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($addresses as $address)
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-stone-200 p-4 hover:border-shop-300">
                                <input
                                    type="radio"
                                    name="address_id"
                                    value="{{ $address->id }}"
                                    @checked(old('address_id', $addresses->firstWhere('is_default', true)?->id ?? $addresses->first()->id) == $address->id)
                                    class="mt-1"
                                >
                                <span>
                                    <span class="block font-medium text-stone-900">{{ $address->title ?? 'Adres' }}</span>
                                    <span class="mt-1 block text-sm text-stone-600">{{ $address->fullName() }}</span>
                                    <span class="mt-1 block text-sm text-stone-600">{{ $address->fullAddress() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">
                    {{ $addresses->isEmpty() ? 'Teslimat Adresi' : 'Yeni Adres Ekle' }}
                </h2>

                @if ($addresses->isNotEmpty())
                    <p class="mt-1 text-sm text-stone-500">Kayıtlı adres kullanmak istemiyorsanız aşağıyı doldurun.</p>
                @endif

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="first_name">Ad</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="last_name">Soyad</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="phone">Telefon</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="address_line_1">Adres</label>
                        <input id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('address_line_1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="address_line_2">Adres satırı 2</label>
                        <input id="address_line_2" name="address_line_2" value="{{ old('address_line_2') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="city">Şehir</label>
                        <input id="city" name="city" value="{{ old('city') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="state">İlçe</label>
                        <input id="state" name="state" value="{{ old('state') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="postal_code">Posta kodu</label>
                        <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700" for="country">Ülke</label>
                        <input id="country" name="country" value="{{ old('country', 'Türkiye') }}" class="w-full rounded-lg border border-stone-300 px-3 py-2">
                        @error('country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <button type="submit" class="rounded-xl bg-shop-600 px-6 py-3 text-sm font-semibold text-white hover:bg-shop-700">
                Siparişi Tamamla
            </button>
        </form>

        <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-900">Sepet Özeti</h2>

            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($cart->items as $item)
                    <li class="flex items-start justify-between gap-3">
                        <span class="text-stone-600">
                            {{ $item->productVariant?->product?->name }}
                            <span class="block text-xs text-stone-500">{{ $item->productVariant?->displayLabel() }} × {{ $item->quantity }}</span>
                        </span>
                        <span class="font-medium">{{ number_format($item->subtotal(), 2, ',', '.') }} ₺</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between border-t border-stone-200 pt-4">
                <span class="font-medium">Toplam</span>
                <span class="text-xl font-semibold text-shop-700">{{ number_format($cart->total(), 2, ',', '.') }} ₺</span>
            </div>

            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Rezervasyon süresi {{ $reservationMinutes }} dakikadır. Süre dolmadan siparişi tamamlayın.
            </p>
        </aside>
    </div>
@endsection
