"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate, formatPrice } from "@/lib/api";
import { isAdmin } from "@/lib/auth";
import type { OrderReturnRequest } from "@/lib/types";

const STATUS_FILTERS = [
  { value: "pending", label: "Bekleyen" },
  { value: "approved", label: "Etiket hazır" },
  { value: "completed", label: "Tamamlanan" },
] as const;

export default function AdminReturnRequestsPage() {
  const { token, user } = useAuth();
  const [status, setStatus] = useState<(typeof STATUS_FILTERS)[number]["value"]>("pending");
  const [requests, setRequests] = useState<OrderReturnRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [processingId, setProcessingId] = useState<number | null>(null);

  async function loadRequests(nextStatus = status) {
    if (!token) {
      return;
    }

    setLoading(true);

    try {
      const response = await api.adminReturnRequests(token, nextStatus);
      setRequests(response.return_requests);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Talepler yüklenemedi.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadRequests();
  }, [token, status]);

  async function handleApprove(requestId: number) {
    if (!token) {
      return;
    }

    setProcessingId(requestId);
    setMessage(null);

    try {
      const response = await api.adminApproveReturn(token, requestId);
      setMessage(response.message);
      setRequests((current) => current.filter((item) => item.id !== requestId));
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Onay başarısız.");
    } finally {
      setProcessingId(null);
    }
  }

  async function handleReject(requestId: number) {
    if (!token) {
      return;
    }

    setProcessingId(requestId);
    setMessage(null);

    try {
      const response = await api.adminRejectReturn(token, requestId);
      setMessage(response.message);
      setRequests((current) => current.filter((item) => item.id !== requestId));
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Reddetme başarısız.");
    } finally {
      setProcessingId(null);
    }
  }

  async function handleReceive(requestId: number) {
    if (!token) {
      return;
    }

    setProcessingId(requestId);
    setMessage(null);

    try {
      const response = await api.adminReceiveReturn(token, requestId);
      setMessage(response.message);
      setRequests((current) => current.filter((item) => item.id !== requestId));
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Teslim alma başarısız.");
    } finally {
      setProcessingId(null);
    }
  }

  return (
    <div>
      <Link href="/admin" className="text-sm text-muted transition hover:text-accent">
        ← Panele dön
      </Link>

      <p className="mt-6 text-xs uppercase tracking-[0.35em] text-muted">Siparişler</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">İade ve Değişim</h1>
      <p className="mt-4 max-w-2xl text-sm leading-7 text-muted">
        {isAdmin(user)
          ? "Teslim edilmiş siparişlerin iade/değişim taleplerini onaylayın, kargo etiketini oluşturun ve ürün gelince stok girişini tamamlayın."
          : "Ürünlerinize ait iade ve değişim taleplerini burada görebilirsiniz."}
      </p>

      <div className="mt-6 flex flex-wrap gap-2">
        {STATUS_FILTERS.map((filter) => (
          <button
            key={filter.value}
            type="button"
            onClick={() => setStatus(filter.value)}
            className={`rounded-full px-4 py-2 text-sm transition ${
              status === filter.value
                ? "bg-accent text-white"
                : "border border-line text-stone-700 hover:border-accent"
            }`}
          >
            {filter.label}
          </button>
        ))}
      </div>

      {message && <p className="mt-6 text-sm text-muted">{message}</p>}

      <div className="mt-8 space-y-4">
        {loading && <p className="text-sm text-muted">Yükleniyor...</p>}
        {!loading && requests.length === 0 && (
          <p className="text-sm text-muted">Bu durumda talep yok.</p>
        )}

        {requests.map((request) => (
          <div
            key={request.id}
            className="rounded-[1.5rem] border border-line bg-surface p-6"
          >
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="font-medium">
                  Sipariş #{request.order_id} · {request.type_label}
                </p>
                <p className="mt-1 text-sm text-muted">
                  {request.customer?.name ?? "Müşteri"} · {formatOrderDate(request.created_at)}
                </p>
              </div>
              <span className="rounded-full bg-accent-soft px-4 py-2 text-xs uppercase tracking-[0.2em] text-accent">
                {request.status_label}
              </span>
            </div>

            <p className="mt-5 rounded-[1.25rem] bg-background px-5 py-4 text-sm leading-7 text-foreground">
              {request.message}
            </p>

            {request.items && request.items.length > 0 && (
              <ul className="mt-4 space-y-1 text-sm text-muted">
                {request.items.map((item) => (
                  <li key={item.id}>
                    {item.quantity}× {[item.product_name, item.variant_label].filter(Boolean).join(" · ")}
                    {request.type === "exchange" && item.replacement_variant_label
                      ? ` → ${item.replacement_variant_label}`
                      : ""}
                  </li>
                ))}
              </ul>
            )}

            {request.refund_amount != null && (
              <p className="mt-3 text-sm">İade tutarı: {formatPrice(request.refund_amount)}</p>
            )}

            {request.return_label_url && (
              <div className="mt-4 flex flex-wrap gap-3">
                <a
                  href={request.return_label_url}
                  target="_blank"
                  rel="noreferrer"
                  className="text-sm text-accent underline"
                >
                  İade etiketi
                </a>
                {request.return_tracking_url && (
                  <a
                    href={request.return_tracking_url}
                    target="_blank"
                    rel="noreferrer"
                    className="text-sm text-accent underline"
                  >
                    İade takibi
                  </a>
                )}
              </div>
            )}

            {isAdmin(user) && request.status === "pending" && (
              <div className="mt-5 flex flex-wrap gap-3">
                <Button
                  type="button"
                  disabled={processingId === request.id}
                  onClick={() => void handleApprove(request.id)}
                >
                  {processingId === request.id ? "İşleniyor..." : "Onayla ve Etiket Oluştur"}
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  disabled={processingId === request.id}
                  onClick={() => void handleReject(request.id)}
                >
                  Reddet
                </Button>
              </div>
            )}

            {isAdmin(user) && request.status === "approved" && (
              <div className="mt-5">
                <Button
                  type="button"
                  disabled={processingId === request.id}
                  onClick={() => void handleReceive(request.id)}
                >
                  {processingId === request.id
                    ? "İşleniyor..."
                    : request.type === "exchange"
                      ? "Teslim alındı, değişimi gönder"
                      : "Teslim alındı, stok gir ve iade et"}
                </Button>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
