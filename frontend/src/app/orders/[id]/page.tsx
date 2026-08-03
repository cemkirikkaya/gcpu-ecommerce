"use client";

import { useEffect, useState } from "react";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { Order } from "@/lib/types";

function formatOrderDate(value: string | null): string {
  if (!value) {
    return "—";
  }

  return new Intl.DateTimeFormat("tr-TR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}

export default function OrderPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { token, loading: authLoading } = useAuth();
  const [order, setOrder] = useState<Order | null>(null);
  const [loadedForToken, setLoadedForToken] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    params.then(({ id }) => {
      api
        .order(token, Number(id))
        .then(setOrder)
        .finally(() => setLoadedForToken(token));
    });
  }, [params, token]);

  const loading = authLoading || (Boolean(token) && loadedForToken !== token);

  if (loading) {
    return <div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>;
  }

  if (!order) {
    return <div className="px-6 py-24 text-center text-muted">Sipariş bulunamadı.</div>;
  }

  return (
    <div className="mx-auto max-w-3xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="rounded-[2rem] border border-line bg-accent-soft/40 p-8 text-center lg:p-10">
        <p className="text-xs uppercase tracking-[0.35em] text-accent">Sipariş</p>
        <h1 className="mt-4 font-display text-4xl font-semibold">Sipariş #{order.id}</h1>
        <div className="mt-5 flex flex-wrap items-center justify-center gap-3">
          <OrderStatusBadge status={order.status} label={order.status_label} />
          <span className="text-sm text-muted">{order.payment_status_label}</span>
        </div>
        <p className="mt-4 text-muted">{formatOrderDate(order.created_at)}</p>
      </div>

      <div className="mt-10 space-y-6 rounded-[2rem] border border-line bg-surface p-8">
        <div className="flex justify-between text-sm">
          <span className="text-muted">Toplam</span>
          <span className="font-display text-2xl text-accent">
            {formatPrice(order.total_price)}
          </span>
        </div>
        {order.address && (
          <div className="border-t border-line pt-6 text-sm">
            <p className="font-medium">{order.address.full_name}</p>
            <p className="mt-1 text-muted">{order.address.full_address}</p>
          </div>
        )}
        <ul className="space-y-3 border-t border-line pt-6 text-sm">
          {order.items?.map((item) => (
            <li key={item.id} className="flex justify-between gap-4">
              <span>
                {[item.product_name, item.variant_label].filter(Boolean).join(" · ") ||
                  "Ürün"}
              </span>
              <span>{formatPrice(item.subtotal)}</span>
            </li>
          ))}
        </ul>
      </div>

      <div className="mt-8 flex flex-wrap gap-3">
        <ButtonLink href="/orders">Siparişlerim</ButtonLink>
        <ButtonLink href="/products" variant="secondary">
          Alışverişe Devam Et
        </ButtonLink>
      </div>
    </div>
  );
}
