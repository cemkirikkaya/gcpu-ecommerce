@extends('layouts.app')

@section('title', 'Sepetim')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-stone-900">Sepetim</h1>
        <p class="mt-2 text-stone-600">
            Ürünler sepete eklendiğinde {{ $reservationMinutes }} dakika rezerve edilir. Süre dolmadan ödeme yapın.
        </p>
    </div>

    @if ($cart->items->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
            <p class="text-stone-600">Sepetiniz boş.</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex rounded-lg bg-shop-600 px-4 py-2 text-sm font-semibold text-white hover:bg-shop-700">
                Alışverişe Başla
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <div class="divide-y divide-stone-200">
                    @foreach ($cart->items as $item)
                        @php
                            $variant = $item->productVariant;
                            $product = $variant?->product;
                            $expiresAt = $item->reserved_until?->timezone(config('app.timezone'))?->toIso8601String();
                        @endphp
                        <div class="p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <h2 class="font-semibold text-stone-900">{{ $product?->name ?? 'Ürün' }}</h2>
                                    <p class="mt-1 text-sm text-stone-600">{{ $variant?->displayLabel() }}</p>
                                    <p class="mt-1 text-xs text-stone-500">SKU: {{ $variant?->sku }}</p>

                                    @if ($item->reserved_until)
                                        <p
                                            class="mt-3 inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800"
                                            data-reservation-expires="{{ $expiresAt }}"
                                        >
                                            Rezervasyon: <span class="reservation-countdown ml-1 font-semibold">--:--</span>
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right">
                                    <p class="text-sm text-stone-500">Birim fiyat</p>
                                    <p class="font-medium">{{ number_format($product?->price ?? 0, 2, ',', '.') }} ₺</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="text-sm text-stone-600" for="quantity-{{ $item->id }}">Adet</label>
                                    <input
                                        id="quantity-{{ $item->id }}"
                                        type="number"
                                        name="quantity"
                                        value="{{ $item->quantity }}"
                                        min="1"
                                        max="{{ min(99, ($variant?->availableQuantity($item->id) ?? 0) + $item->quantity) }}"
                                        class="w-20 rounded-lg border border-stone-300 px-2 py-2 text-sm"
                                    >
                                    <button type="submit" class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-stone-50">
                                        Güncelle
                                    </button>
                                </form>

                                <div class="flex items-center justify-between gap-4 sm:justify-end">
                                    <p class="text-lg font-semibold text-shop-700">
                                        {{ number_format($item->subtotal(), 2, ',', '.') }} ₺
                                    </p>
                                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">
                                            Kaldır
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Sipariş Özeti</h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-stone-600">Ürün adedi</dt>
                        <dd class="font-medium">{{ $cart->items->sum('quantity') }}</dd>
                    </div>
                    <div class="flex items-center justify-between border-t border-stone-200 pt-3">
                        <dt class="font-medium text-stone-900">Genel Toplam</dt>
                        <dd class="text-2xl font-semibold text-shop-700">{{ number_format($cart->total(), 2, ',', '.') }} ₺</dd>
                    </div>
                </dl>

                <a
                    href="{{ route('checkout.index') }}"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-shop-600 px-4 py-3 text-sm font-semibold text-white hover:bg-shop-700"
                >
                    Ödemeye Geç
                </a>
            </aside>
        </div>
    @endif

    @if ($cart->items->isNotEmpty())
        <script>
            document.querySelectorAll('[data-reservation-expires]').forEach((element) => {
                const countdown = element.querySelector('.reservation-countdown');
                const expiresAt = new Date(element.dataset.reservationExpires);

                const tick = () => {
                    const remainingMs = expiresAt.getTime() - Date.now();

                    if (remainingMs <= 0) {
                        countdown.textContent = 'Süre doldu';
                        element.classList.remove('bg-amber-50', 'text-amber-800');
                        element.classList.add('bg-red-50', 'text-red-700');
                        return;
                    }

                    const totalSeconds = Math.floor(remainingMs / 1000);
                    const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                    const seconds = String(totalSeconds % 60).padStart(2, '0');
                    countdown.textContent = `${minutes}:${seconds}`;
                    window.setTimeout(tick, 1000);
                };

                tick();
            });
        </script>
    @endif
@endsection
