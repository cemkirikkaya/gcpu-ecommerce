"use client";

import { useEffect, useState } from "react";

import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { OrderStatusTimeline } from "@/components/orders/order-status-timeline";
import { OrderPaymentRetry } from "@/components/orders/order-payment-retry";
import { OrderCancellationPanel } from "@/components/orders/order-cancellation-panel";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatEstimatedDeliveryDate, formatOrderDate, formatPrice } from "@/lib/api";
import type { Order, OrderCancellationRequest, PaymentOptions } from "@/lib/types";

export function OrderDetailClient({ orderId }: { orderId: string }) {
  const parsedOrderId = Number(orderId);
  const { token, loading: authLoading } = useAuth();
  const [order, setOrder] = useState<Order | null>(null);
  const [paymentOptions, setPaymentOptions] = useState<PaymentOptions | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [ready, setReady] = useState(false);
  const [invoiceDownloading, setInvoiceDownloading] = useState(false);

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!token) {
      setOrder(null);
      setMessage(null);
      setReady(true);
      return;
    }

    if (!orderId || Number.isNaN(parsedOrderId)) {
      setOrder(null);
      setMessage("Geçersiz sipariş.");
      setReady(true);
      return;
    }

    let cancelled = false;
    setReady(false);
    setOrder(null);
    setPaymentOptions(null);
    setMessage(null);

    api
      .order(token, parsedOrderId)
      .then((data) => {
        if (!cancelled) {
          setOrder(data.order);
          setPaymentOptions(data.payment_options ?? null);
          setMessage(null);
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setOrder(null);
          setMessage(error instanceof Error ? error.message : "Sipariş yüklenemedi.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setReady(true);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [authLoading, token, orderId, parsedOrderId]);

  if (authLoading || !ready) {
    return <div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>;
  }

  if (!token) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 text-center">
        <p className="text-muted">Sipariş detayını görmek için giriş yapmalısınız.</p>
        <ButtonLink href="/login" className="mt-6">
          Giriş Yap
        </ButtonLink>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 text-center">
        <p className="text-muted">{message ?? "Sipariş bulunamadı."}</p>
        <ButtonLink href="/orders" className="mt-6">
          Siparişlerime Dön
        </ButtonLink>
      </div>
    );
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

      <OrderStatusTimeline
        status={order.status}
        statusLabel={order.status_label}
        paymentStatus={order.payment_status}
        paymentStatusLabel={order.payment_status_label}
      />

      {order.tracking_url && (
        <div className="mt-8 rounded-[2rem] border border-line bg-surface p-6 text-center">
          <p className="text-sm text-muted">Kargo takibi</p>
          {order.tracking_number && (
            <p className="mt-2 font-medium">Takip No: {order.tracking_number}</p>
          )}
          {order.estimated_delivery_at && (
            <p className="mt-2 text-sm text-muted">
              Tahmini teslimat:{" "}
              <span className="font-medium text-foreground">
                {formatEstimatedDeliveryDate(order.estimated_delivery_at)}
              </span>
            </p>
          )}
          <a
            href={order.tracking_url}
            target="_blank"
            rel="noreferrer"
            className="mt-4 inline-flex rounded-full bg-accent px-5 py-3 text-sm font-medium text-white transition hover:bg-stone-800"
          >
            Kargoyu Takip Et
          </a>
        </div>
      )}

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

      {paymentOptions && token && (
        <OrderPaymentRetry
          token={token}
          orderId={order.id}
          paymentOptions={paymentOptions}
          onError={setMessage}
        />
      )}

      {token && (
        <OrderCancellationPanel
          token={token}
          order={order}
          onUpdated={(request: OrderCancellationRequest) =>
            setOrder((current) =>
              current ? { ...current, cancellation_request: request } : current,
            )
          }
        />
      )}

      {message && <p className="mt-4 text-sm text-red-600">{message}</p>}

      <div className="mt-8 flex flex-wrap gap-3">
        {order.can_download_invoice && token && (
          <Button
            type="button"
            variant="secondary"
            disabled={invoiceDownloading}
            onClick={async () => {
              setInvoiceDownloading(true);
              setMessage(null);

              try {
                await api.downloadOrderInvoice(token, order.id);
              } catch (error) {
                setMessage(error instanceof Error ? error.message : "Fatura indirilemedi.");
              } finally {
                setInvoiceDownloading(false);
              }
            }}
          >
            {invoiceDownloading ? "İndiriliyor..." : "Faturayı İndir"}
          </Button>
        )}
        <ButtonLink href="/orders">Siparişlerim</ButtonLink>
        <ButtonLink href="/products" variant="secondary">
          Alışverişe Devam Et
        </ButtonLink>
      </div>
    </div>
  );
}
