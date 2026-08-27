"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useParams } from "next/navigation";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatEstimatedDeliveryDate, formatOrderDate, formatPrice } from "@/lib/api";
import { isAdmin } from "@/lib/auth";
import type { AdminOrder } from "@/lib/types";

function hasActiveGeliverShipment(order: AdminOrder): boolean {
  return Boolean(order.geliver_shipment_id) && !["delivered", "cancelled"].includes(order.status);
}

export default function AdminOrderDetailPage() {
  const params = useParams<{ id: string }>();
  const { token, user } = useAuth();
  const [order, setOrder] = useState<AdminOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [shipmentCreating, setShipmentCreating] = useState(false);
  const [shipmentSyncing, setShipmentSyncing] = useState(false);

  useEffect(() => {
    if (!token || !params.id) return;

    api
      .adminOrder(token, Number(params.id))
      .then((loadedOrder) => {
        setOrder(loadedOrder);
      })
      .catch((error) => setMessage(error.message))
      .finally(() => setLoading(false));
  }, [token, params.id]);

  useEffect(() => {
    if (!token || !order || !isAdmin(user) || !hasActiveGeliverShipment(order)) {
      return;
    }

    let cancelled = false;

    async function syncShipmentQuietly(): Promise<void> {
      try {
        const updated = await api.adminSyncOrderShipment(token!, order!.id);

        if (!cancelled) {
          setOrder(updated);
        }
      } catch {
        // Arka plan senkronu sessizce atlanır.
      }
    }

    void syncShipmentQuietly();

    const intervalId = window.setInterval(() => {
      void syncShipmentQuietly();
    }, 60_000);

    return () => {
      cancelled = true;
      window.clearInterval(intervalId);
    };
  }, [token, user, order?.id, order?.geliver_shipment_id, order?.status]);

  async function handleCreateShipment() {
    if (!token || !order) return;

    setShipmentCreating(true);
    setMessage(null);

    try {
      const updated = await api.adminCreateOrderShipment(token, order.id);
      setOrder(updated);
      setMessage("Kargo oluşturuldu.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Kargo oluşturulamadı.");
    } finally {
      setShipmentCreating(false);
    }
  }

  async function handleSyncShipment() {
    if (!token || !order) return;

    setShipmentSyncing(true);
    setMessage(null);

    try {
      const updated = await api.adminSyncOrderShipment(token, order.id);
      setOrder(updated);
      setMessage("Kargo bilgileri Geliver ile senkronize edildi.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Kargo senkronize edilemedi.");
    } finally {
      setShipmentSyncing(false);
    }
  }

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

      {isAdmin(user) && order.payment_status === "paid" && !order.geliver_shipment_id && (
        <div className="mt-8 rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">
            Kargo otomatik oluşturuluyor. Birkaç dakika içinde tamamlanmazsa tekrar deneyin.
          </p>
          <button
            type="button"
            disabled={shipmentCreating}
            onClick={() => void handleCreateShipment()}
            className="mt-4 rounded-full border border-line bg-background px-5 py-3 text-sm font-medium transition hover:border-accent disabled:opacity-50"
          >
            {shipmentCreating ? "Kargo oluşturuluyor..." : "Kargoyu Tekrar Oluştur"}
          </button>
        </div>
      )}

      {isAdmin(user) && order.geliver_shipment_id && (
        <div className="mt-8 rounded-[1.5rem] border border-dashed border-line bg-surface p-6">
          <p className="text-sm text-muted">Geliver gönderi ID</p>
          <p className="mt-2 break-all font-mono text-sm text-foreground">
            {order.geliver_shipment_id}
          </p>
          {order.estimated_delivery_at && (
            <p className="mt-4 text-sm text-muted">
              Tahmini teslimat:{" "}
              <span className="font-medium text-foreground">
                {formatEstimatedDeliveryDate(order.estimated_delivery_at)}
              </span>
            </p>
          )}
          <p className="mt-4 text-sm text-muted">
            Kargo durumu Geliver ile otomatik senkronize edilir. Gecikme varsa aşağıdaki
            butonu kullanabilirsiniz.
          </p>
          <button
            type="button"
            disabled={shipmentSyncing}
            onClick={() => void handleSyncShipment()}
            className="mt-4 rounded-full border border-line bg-background px-5 py-3 text-sm font-medium transition hover:border-accent disabled:opacity-50"
          >
            {shipmentSyncing ? "Senkronize ediliyor..." : "Geliver'dan Senkronize Et"}
          </button>
        </div>
      )}

      {(order.tracking_number || order.tracking_url) && (
        <div className="mt-8 rounded-[1.5rem] border border-line bg-surface p-6">
          <p className="text-sm text-muted">Kargo Takibi</p>
          {order.tracking_number && (
            <p className="mt-2 font-medium">Takip No: {order.tracking_number}</p>
          )}
          {order.tracking_url && (
            <a
              href={order.tracking_url}
              target="_blank"
              rel="noreferrer"
              className="mt-3 inline-block text-sm text-accent underline-offset-4 hover:underline"
            >
              Kargoyu takip et
            </a>
          )}
        </div>
      )}

      {message && <p className="mt-4 text-sm text-muted">{message}</p>}

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
