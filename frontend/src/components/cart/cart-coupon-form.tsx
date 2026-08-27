"use client";

import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { api, formatPrice } from "@/lib/api";
import type { Cart } from "@/lib/types";

type CartCouponFormProps = {
  cart: Cart;
  token: string;
  onUpdated: (cart: Cart, message: string) => void;
};

export function CartCouponForm({ cart, token, onUpdated }: CartCouponFormProps) {
  const [code, setCode] = useState(cart.coupon?.code ?? "");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleApply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!code.trim()) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await api.applyCartCoupon(token, code.trim());
      onUpdated(response.cart, response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kupon uygulanamadı");
    } finally {
      setLoading(false);
    }
  }

  async function handleRemove() {
    setLoading(true);
    setError(null);

    try {
      const response = await api.removeCartCoupon(token);
      setCode("");
      onUpdated(response.cart, response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kupon kaldırılamadı");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mt-6 border-t border-line pt-6">
      <p className="text-xs uppercase tracking-[0.28em] text-muted">İndirim kodu</p>

      {cart.coupon ? (
        <div className="mt-4 space-y-3">
          <div className="flex items-center justify-between rounded-[1rem] border border-accent/20 bg-accent/5 px-4 py-3 text-sm">
            <span>
              <strong>{cart.coupon.code}</strong> uygulandı
            </span>
            <span className="text-accent">-{formatPrice(cart.discount_amount)}</span>
          </div>
          <Button type="button" variant="secondary" disabled={loading} onClick={handleRemove}>
            Kuponu Kaldır
          </Button>
        </div>
      ) : (
        <form onSubmit={handleApply} className="mt-4 flex gap-2">
          <input
            value={code}
            onChange={(event) => setCode(event.target.value.toUpperCase())}
            placeholder="KUPONKODU"
            className="min-w-0 flex-1 rounded-full border border-line bg-background px-4 py-2 text-sm uppercase outline-none focus:border-accent"
          />
          <Button type="submit" variant="secondary" disabled={loading}>
            {loading ? "..." : "Uygula"}
          </Button>
        </form>
      )}

      {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
    </div>
  );
}
