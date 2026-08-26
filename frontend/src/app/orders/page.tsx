"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { AccountBackLink } from "@/components/account/account-back-link";
import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatEstimatedDeliveryDate, formatOrderDate, formatPrice } from "@/lib/api";
import { splitOrdersByStatus } from "@/lib/orders";
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

  const { activeOrders, cancelledOrders } = splitOrdersByStatus(orders);

  function renderOrderCard(order: Order) {
    const showShipping =
      order.tracking_number || order.tracking_url || order.estimated_delivery_at;

    return (
      <div
        key={order.id}
        className="rounded-[2rem] border border-line bg-surface transition hover:border-accent/40"
      >
        <Link href={`/orders/${order.id}`} className="block p-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="text-sm text-muted">Sipariş #{order.id}</p>
              <p className="mt-2 font-display text-2xl text-accent">
                {formatPrice(order.total_price)}
              </p>
              <p className="mt-2 text-sm text-muted">{formatOrderDate(order.created_at)}</p>
            </div>
            <div className="flex flex-col items-end gap-2">
              <OrderStatusBadge status={order.status} label={order.status_label} />
              <span className="text-xs text-muted">{order.payment_status_label}</span>
            </div>
          </div>
        </Link>

        {showShipping && (
          <div className="border-t border-line px-6 py-4 text-sm">
            {order.estimated_delivery_at && (
              <p className="text-muted">
                Tahmini teslimat:{" "}
                <span className="font-medium text-foreground">
                  {formatEstimatedDeliveryDate(order.estimated_delivery_at)}
                </span>
              </p>
            )}
            {order.tracking_number && (
              <p className={order.estimated_delivery_at ? "mt-2 text-muted" : "text-muted"}>
                Takip No:{" "}
                <span className="font-medium text-foreground">{order.tracking_number}</span>
              </p>
            )}
            {order.tracking_url && (
              <a
                href={order.tracking_url}
                target="_blank"
                rel="noreferrer"
                className="mt-2 inline-block text-accent underline-offset-4 hover:underline"
              >
                Kargoyu takip et
              </a>
            )}
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl px-6 py-16 lg:px-10 lg:py-24">
      <AccountBackLink />
      <div className="mt-6 max-w-2xl">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Hesabım</p>
        <h1 className="mt-3 font-display text-5xl font-semibold">Siparişlerim</h1>
        <p className="mt-4 text-muted">
          {activeOrders.length > 0
            ? `${activeOrders.length} aktif sipariş${cancelledOrders.length > 0 ? ` · ${cancelledOrders.length} iptal edildi` : ""}`
            : cancelledOrders.length > 0
              ? `${cancelledOrders.length} iptal edilmiş sipariş`
              : "Yalnızca bu hesaba ait siparişler listelenir."}
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
        <div className="mt-16 space-y-10">
          {activeOrders.length > 0 && (
            <div className="space-y-4">
              {activeOrders.map(renderOrderCard)}
            </div>
          )}

          {activeOrders.length === 0 && cancelledOrders.length > 0 && (
            <div className="rounded-[2rem] border border-line bg-surface p-10 text-center">
              <p className="text-muted">Aktif siparişiniz yok.</p>
              <ButtonLink href="/products" className="mt-6">
                Alışverişe Başla
              </ButtonLink>
            </div>
          )}

          {cancelledOrders.length > 0 && (
            <section>
              <p className="text-xs uppercase tracking-[0.35em] text-muted">
                İptal edilen siparişler
              </p>
              <div className="mt-4 space-y-4 opacity-80">
                {cancelledOrders.map(renderOrderCard)}
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
