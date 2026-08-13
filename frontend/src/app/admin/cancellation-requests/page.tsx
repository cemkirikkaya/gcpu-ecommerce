"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate } from "@/lib/api";
import { isAdmin } from "@/lib/auth";
import type { OrderCancellationRequest } from "@/lib/types";

export default function AdminCancellationRequestsPage() {
  const { token, user } = useAuth();
  const [requests, setRequests] = useState<OrderCancellationRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [processingId, setProcessingId] = useState<number | null>(null);

  async function loadRequests() {
    if (!token) {
      return;
    }

    setLoading(true);

    try {
      const response = await api.adminCancellationRequests(token, "pending");
      setRequests(response.cancellation_requests);
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Talepler yüklenemedi.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    void loadRequests();
  }, [token]);

  async function handleApprove(requestId: number) {
    if (!token) {
      return;
    }

    setProcessingId(requestId);
    setMessage(null);

    try {
      const response = await api.adminApproveCancellation(token, requestId);
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
      const response = await api.adminRejectCancellation(token, requestId);
      setMessage(response.message);
      setRequests((current) => current.filter((item) => item.id !== requestId));
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Reddetme başarısız.");
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
      <h1 className="mt-3 font-display text-4xl font-semibold">İptal Talepleri</h1>
      <p className="mt-4 max-w-2xl text-sm leading-7 text-muted">
        {isAdmin(user)
          ? "Müşteri iptal taleplerini inceleyin. Onayladığınızda ödeme otomatik iade edilir."
          : "Ürünlerinize ait bekleyen iptal taleplerini burada görebilirsiniz."}
      </p>

      {message && <p className="mt-6 text-sm text-muted">{message}</p>}

      <div className="mt-8 space-y-4">
        {loading && <p className="text-sm text-muted">Yükleniyor...</p>}
        {!loading && requests.length === 0 && (
          <p className="text-sm text-muted">Bekleyen iptal talebi yok.</p>
        )}

        {requests.map((request) => (
          <div
            key={request.id}
            className="rounded-[1.5rem] border border-line bg-surface p-6"
          >
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="font-medium">Sipariş #{request.order_id}</p>
                <p className="mt-1 text-sm text-muted">
                  {request.customer?.name ?? "Müşteri"} ·{" "}
                  {formatOrderDate(request.created_at)}
                </p>
              </div>
              <span className="rounded-full bg-accent-soft px-4 py-2 text-xs uppercase tracking-[0.2em] text-accent">
                {request.status_label}
              </span>
            </div>

            <p className="mt-5 rounded-[1.25rem] bg-background px-5 py-4 text-sm leading-7 text-foreground">
              {request.message}
            </p>

            {isAdmin(user) && (
              <div className="mt-5 flex flex-wrap gap-3">
                <Button
                  type="button"
                  disabled={processingId === request.id}
                  onClick={() => void handleApprove(request.id)}
                >
                  {processingId === request.id ? "İşleniyor..." : "Onayla ve İade Et"}
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
          </div>
        ))}
      </div>
    </div>
  );
}
