"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate, formatPrice } from "@/lib/api";
import type { AdminOrder } from "@/lib/types";

export default function AdminOrdersPage() {
  const { token } = useAuth();
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!token) return;

    api
      .adminOrders(token)
      .then(setOrders)
      .catch((error) => setMessage(error.message))
      .finally(() => setLoading(false));
  }, [token]);

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Panel</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Siparişler</h1>
      <p className="mt-3 max-w-2xl text-sm text-muted">
        Yalnızca size ait ürünleri içeren siparişler listelenir. Tutarlar sizin
        payınızı gösterir.
      </p>

      {message && <p className="mt-6 text-sm text-red-600">{message}</p>}

      {loading ? (
        <p className="mt-10 text-sm text-muted">Yükleniyor...</p>
      ) : orders.length === 0 ? (
        <div className="mt-10 rounded-[1.5rem] border border-line bg-surface p-8 text-center">
          <p className="text-sm text-muted">Henüz sipariş yok.</p>
        </div>
      ) : (
        <div className="mt-10 space-y-4">
          {orders.map((order) => (
            <Link
              key={order.id}
              href={`/admin/orders/${order.id}`}
              className="block rounded-[1.5rem] border border-line bg-surface p-6 transition hover:border-accent/40"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-sm text-muted">Sipariş #{order.id}</p>
                  <p className="mt-2 font-display text-2xl text-accent">
                    {formatPrice(order.total_price)}
                  </p>
                  <p className="mt-2 text-sm text-muted">
                    {formatOrderDate(order.created_at)} · {order.items_count ?? 0}{" "}
                    kalem
                  </p>
                  {order.order_total != null && order.order_total !== order.total_price && (
                    <p className="mt-1 text-xs text-muted">
                      Sipariş toplamı: {formatPrice(order.order_total)}
                    </p>
                  )}
                </div>
                <div className="flex flex-col items-end gap-2">
                  <OrderStatusBadge status={order.status} label={order.status_label} />
                  <span className="text-xs text-muted">{order.payment_status_label}</span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
