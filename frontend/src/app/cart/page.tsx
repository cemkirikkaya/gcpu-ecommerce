"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { Cart } from "@/lib/types";

function Countdown({ expiresAt }: { expiresAt: string }) {
  const [label, setLabel] = useState("--:--");

  useEffect(() => {
    const tick = () => {
      const remaining = new Date(expiresAt).getTime() - Date.now();
      if (remaining <= 0) {
        setLabel("Süre doldu");
        return;
      }
      const minutes = Math.floor(remaining / 60000);
      const seconds = Math.floor((remaining % 60000) / 1000);
      setLabel(`${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`);
    };

    tick();
    const timer = window.setInterval(tick, 1000);
    return () => window.clearInterval(timer);
  }, [expiresAt]);

  return (
    <span className="rounded-full bg-accent-soft px-3 py-1 text-xs font-medium text-accent">
      Rezervasyon · {label}
    </span>
  );
}

export default function CartPage() {
  const { token, loading: authLoading } = useAuth();
  const [cart, setCart] = useState<Cart | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [loadedForToken, setLoadedForToken] = useState<string | null>(null);

  useEffect(() => {
    if (authLoading || !token) {
      return;
    }

    let cancelled = false;

    api
      .cart(token)
      .then((data) => {
        if (!cancelled) {
          setCart(data);
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setMessage(error.message);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoadedForToken(token);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [token, authLoading]);

  const loading = authLoading || (Boolean(token) && loadedForToken !== token);

  async function updateQuantity(itemId: number, quantity: number) {
    if (!token) return;
    try {
      const response = await api.updateCartItem(token, itemId, quantity);
      setCart(response.cart);
      setMessage(response.message);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata");
    }
  }

  async function removeItem(itemId: number) {
    if (!token) return;
    try {
      const response = await api.removeCartItem(token, itemId);
      setCart(response.cart);
      setMessage(response.message);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata");
    }
  }

  if (authLoading || loading) {
    return <div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>;
  }

  if (!token) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 text-center lg:px-10">
        <h1 className="font-display text-4xl">Sepetiniz</h1>
        <p className="mt-4 text-muted">Sepeti görüntülemek için giriş yapın.</p>
        <ButtonLink href="/login" className="mt-8">
          Giriş Yap
        </ButtonLink>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="max-w-2xl">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Sepet</p>
        <h1 className="mt-3 font-display text-5xl font-semibold">Seçimleriniz</h1>
        <p className="mt-4 text-muted">
          Ürünler {cart?.reservation_minutes ?? 15} dakika boyunca rezerve edilir.
        </p>
      </div>

      {message && <p className="mt-6 text-sm text-accent">{message}</p>}

      {!cart || cart.items.length === 0 ? (
        <div className="mt-16 rounded-[2rem] border border-dashed border-line bg-surface px-8 py-16 text-center">
          <p className="text-muted">Sepetiniz boş.</p>
          <ButtonLink href="/products" className="mt-6">
            Alışverişe Başla
          </ButtonLink>
        </div>
      ) : (
        <div className="mt-16 grid gap-10 lg:grid-cols-[1fr_340px]">
          <div className="space-y-6">
            {cart.items.map((item) => (
              <article
                key={item.id}
                className="rounded-[2rem] border border-line bg-surface p-6 lg:p-8"
              >
                <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <h2 className="font-display text-2xl">
                      {item.variant?.label ?? "Ürün"}
                    </h2>
                    <p className="mt-2 text-sm text-muted">SKU: {item.variant?.sku}</p>
                    {item.reserved_until && (
                      <div className="mt-4">
                        <Countdown expiresAt={item.reserved_until} />
                      </div>
                    )}
                  </div>
                  <p className="text-lg text-accent">{formatPrice(item.subtotal)}</p>
                </div>

                <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-line pt-6">
                  <input
                    type="number"
                    min={1}
                    value={item.quantity}
                    onChange={(event) =>
                      updateQuantity(item.id, Number(event.target.value))
                    }
                    className="w-20 rounded-full border border-line px-4 py-2 text-center text-sm"
                  />
                  <button
                    type="button"
                    onClick={() => removeItem(item.id)}
                    className="text-sm text-stone-500 transition hover:text-red-600"
                  >
                    Kaldır
                  </button>
                </div>
              </article>
            ))}
          </div>

          <aside className="h-fit rounded-[2rem] border border-line bg-surface p-8">
            <h2 className="font-display text-2xl">Özet</h2>
            <dl className="mt-6 space-y-4 text-sm">
              <div className="flex justify-between">
                <dt className="text-muted">Ürün adedi</dt>
                <dd>{cart.item_count}</dd>
              </div>
              <div className="flex justify-between border-t border-line pt-4 text-base">
                <dt className="font-medium">Toplam</dt>
                <dd className="font-display text-2xl text-accent">
                  {formatPrice(cart.total)}
                </dd>
              </div>
            </dl>
            <ButtonLink href="/checkout" className="mt-8 w-full">
              Ödemeye Geç
            </ButtonLink>
            <Link
              href="/products"
              className="mt-4 block text-center text-sm text-muted hover:text-accent"
            >
              Alışverişe devam et
            </Link>
          </aside>
        </div>
      )}
    </div>
  );
}
