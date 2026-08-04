"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate, formatPrice } from "@/lib/api";
import { isAdmin } from "@/lib/auth";
import type { AdminOrder } from "@/lib/types";

export default function AdminOrderDetailPage() {
  const params = useParams<{ id: string }>();
  const { token, user } = useAuth();
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!token || !params.id) return;

    api
      .adminOrder(token, Number(params.id))
      .then(setOrder)
      .catch((error) => setMessage(error.message))
      .finally(() => setLoading(false));
  }, [token, params.id]);

  if (loading) {
    return <p className="text-sm text-muted">Yükleniyor...</p>;
  }

  if (!order) {
    return (
      <div>
        <p className="text-sm text-red-600">{message ?? "Sipariş bulunamadı."}</p>
        <ButtonLink href="/admin/orders" className="mt-6" variant="secondary">
          Siparişlere Dön
        </ButtonLink>
      </div>
    );
  }

  return (
    <div>
      <Link href="/admin/orders" className="text-sm text-muted transition hover:text-accent">
        ← Siparişler
      </Link>

      <div className="mt-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Sipariş</p>
          <h1 className="mt-3 font-display text-4xl font-semibold">#{order.id}</h1>
          <p className="mt-3 text-sm text-muted">{formatOrderDate(order.created_at)}</p>
        </div>
        <div className="flex flex-col items-end gap-2">
          <OrderStatusBadge status={order.status} label={order.status_label} />
          <span className="text-xs text-muted">{order.payment_status_label}</span>
        </div>
      </div>

      <div className="mt-8 grid gap-4 md:grid-cols-3">
        <div className="rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">{isAdmin(user) ? "Sipariş Toplamı" : "Sizin Payınız"}</p>
          <p className="mt-2 text-3xl font-semibold">{formatPrice(order.total_price)}</p>
        </div>
        {order.order_total != null && order.order_total !== order.total_price && (
          <div className="rounded-[1.5rem] border border-line bg-surface p-6">
            <p className="text-sm text-muted">Sipariş Toplamı</p>
            <p className="mt-2 text-3xl font-semibold">{formatPrice(order.order_total)}</p>
          </div>
        )}
        <div className="rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Kalem Sayısı</p>
          <p className="mt-2 text-3xl font-semibold">{order.items?.length ?? 0}</p>
        </div>
      </div>

      {order.address && isAdmin(user) && (
        <div className="mt-8 rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Teslimat Adresi</p>
          <p className="mt-2 font-medium">{order.address.full_name}</p>
          <p className="mt-1 text-sm text-muted">{order.address.full_address}</p>
          {order.address.phone && (
            <p className="mt-2 text-sm text-muted">{order.address.phone}</p>
          )}
        </div>
      )}

      <div className="mt-10">
        <h2 className="font-display text-2xl font-semibold">Ürünler</h2>
        <div className="mt-4 space-y-3">
          {(order.items ?? []).map((item) => (
            <div
              key={item.id}
              className="flex flex-wrap items-center justify-between gap-4 rounded-[1.25rem] border border-line bg-surface px-5 py-4"
            >
              <div>
                <p className="font-medium">{item.product_name ?? "Ürün"}</p>
                {item.variant_label && (
                  <p className="text-sm text-muted">{item.variant_label}</p>
                )}
                {item.vendor_email && isAdmin(user) && (
                  <p className="mt-1 text-xs text-muted">{item.vendor_email}</p>
                )}
              </div>
              <div className="text-right">
                <p className="font-medium">{formatPrice(item.subtotal)}</p>
                <p className="text-sm text-muted">
                  {item.quantity} × {formatPrice(item.price)}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
