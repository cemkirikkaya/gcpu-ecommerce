"use client";

import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import type { Order, OrderCancellationRequest } from "@/lib/types";

type OrderCancellationPanelProps = {
  token: string;
  order: Order;
  onUpdated: (request: OrderCancellationRequest) => void;
};

export function OrderCancellationPanel({
  token,
  order,
  onUpdated,
}: OrderCancellationPanelProps) {
  const [message, setMessage] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const canRequest =
    order.payment_status === "paid" &&
    ["pending", "processing"].includes(order.status) &&
    !order.cancellation_request;

  const existingRequest = order.cancellation_request;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    setFeedback(null);

    try {
      const response = await api.requestOrderCancellation(token, order.id, message.trim());
      onUpdated(response.cancellation_request);
      setMessage("");
      setFeedback(response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "İptal talebi gönderilemedi.");
    } finally {
      setSubmitting(false);
    }
  }

  if (existingRequest) {
    return (
      <div className="mt-10 rounded-[2rem] border border-line bg-surface p-8">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">İptal Talebi</p>
        <h2 className="mt-3 font-display text-2xl font-semibold">
          {existingRequest.status_label}
        </h2>
        <p className="mt-4 text-sm leading-7 text-muted">{existingRequest.message}</p>
        {existingRequest.admin_note && (
          <p className="mt-4 rounded-2xl bg-accent-soft/50 px-4 py-3 text-sm text-foreground">
            Yönetici notu: {existingRequest.admin_note}
          </p>
        )}
        {existingRequest.status === "pending" && (
          <p className="mt-4 text-sm text-muted">
            Talebiniz inceleniyor. Satıcı bilgilendirildi; onaylandığında ödemeniz iade edilecek.
          </p>
        )}
        {existingRequest.status === "approved" && (
          <p className="mt-4 text-sm text-accent">
            İptal talebiniz onaylandı. Ödemeniz iade edildi.
          </p>
        )}
      </div>
    );
  }

  if (!canRequest) {
    return null;
  }

  return (
    <div className="mt-10 rounded-[2rem] border border-line bg-surface p-8">
      <p className="text-xs uppercase tracking-[0.35em] text-muted">İptal</p>
      <h2 className="mt-3 font-display text-2xl font-semibold">Siparişi iptal etmek istiyorum</h2>
      <p className="mt-3 text-sm leading-7 text-muted">
        İptal talebiniz ürün sahibine iletilecek. Yönetici onayı sonrası ödemeniz iade edilir.
      </p>

      <form onSubmit={(event) => void handleSubmit(event)} className="mt-6 space-y-4">
        <textarea
          value={message}
          onChange={(event) => setMessage(event.target.value)}
          required
          minLength={10}
          maxLength={1000}
          rows={4}
          placeholder="İptal gerekçenizi yazın..."
          className="w-full rounded-[1.5rem] border border-line bg-background px-5 py-4 text-sm outline-none focus:border-accent"
        />
        <Button type="submit" disabled={submitting || message.trim().length < 10}>
          {submitting ? "Gönderiliyor..." : "İptal Talebi Gönder"}
        </Button>
      </form>

      {feedback && <p className="mt-4 text-sm text-accent">{feedback}</p>}
      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
    </div>
  );
}
