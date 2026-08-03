"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate, formatPrice } from "@/lib/api";
import type { Order } from "@/lib/types";

export default function OrdersPage() {
  const { token, loading: authLoading } = useAuth();
  const [orders, setOrders] = useState<Order[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [loadedForToken, setLoadedForToken] = useState<string | null>(null);

  useEffect(() => {
    if (authLoading || !token) {
      return;
    }

    api
      .orders(token)
      .then(setOrders)
      .catch((error) => setMessage(error.message))
      .finally(() => setLoadedForToken(token));
  }, [token, authLoading]);

  const loading = authLoading || (Boolean(token) && loadedForToken !== token);

  if (authLoading || loading) {
    return <div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>;
  }

  if (!token) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 text-center">
        <p className="text-muted">Siparişlerinizi görmek için giriş yapmalısınız.</p>
        <ButtonLink href="/login" className="mt-6">
          Giriş Yap
        </ButtonLink>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="max-w-2xl">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Hesabım</p>
        <h1 className="mt-3 font-display text-5xl font-semibold">Siparişlerim</h1>
        <p className="mt-4 text-muted">
          Yalnızca bu hesaba ait siparişler listelenir.
        </p>
      </div>

      {message && <p className="mt-8 text-sm text-red-600">{message}</p>}

      {orders.length === 0 ? (
        <div className="mt-16 rounded-[2rem] border border-line bg-surface p-10 text-center">
          <p className="text-muted">Henüz siparişiniz yok.</p>
          <ButtonLink href="/products" className="mt-6">
            Alışverişe Başla
          </ButtonLink>
        </div>
      ) : (
        <div className="mt-16 space-y-4">
          {orders.map((order) => (
            <Link
              key={order.id}
              href={`/orders/${order.id}`}
              className="block rounded-[2rem] border border-line bg-surface p-6 transition hover:border-accent/40"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-sm text-muted">Sipariş #{order.id}</p>
                  <p className="mt-2 font-display text-2xl text-accent">
                    {formatPrice(order.total_price)}
                  </p>
                  <p className="mt-2 text-sm text-muted">
                    {formatOrderDate(order.created_at)}
                  </p>
                </div>
                <div className="flex flex-col items-end gap-2">
                  <OrderStatusBadge
                    status={order.status}
                    label={order.status_label}
                  />
                  <span className="text-xs text-muted">
                    {order.payment_status_label}
                  </span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
