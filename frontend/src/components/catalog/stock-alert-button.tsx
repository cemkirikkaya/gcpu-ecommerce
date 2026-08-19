"use client";

import { useState } from "react";

import { useAuth } from "@/context/auth-context";
import { useStockAlerts } from "@/context/stock-alert-context";

type StockAlertButtonProps = {
  variantId: number;
  className?: string;
};

export function StockAlertButton({ variantId, className = "" }: StockAlertButtonProps) {
  const { user } = useAuth();
  const { isSubscribed, toggleStockAlert } = useStockAlerts();
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const subscribed = isSubscribed(variantId);

  async function handleClick() {
    setLoading(true);
    setMessage(null);

    try {
      await toggleStockAlert(variantId);
      setMessage(
        subscribed
          ? "Stok bildirimi iptal edildi."
          : "Stoğa dönünce e-posta ile bilgilendirileceksiniz.",
      );
    } catch {
      setMessage("Stok bildirimi kaydedilemedi.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className={className}>
      <button
        type="button"
        disabled={loading}
        onClick={() => void handleClick()}
        className="rounded-full border border-line bg-surface px-5 py-3 text-sm font-medium transition hover:border-accent disabled:opacity-50"
      >
        {loading
          ? "Kaydediliyor..."
          : subscribed
            ? "Stok bildirimini iptal et"
            : "Stoğa dönünce haber ver"}
      </button>
      {message && <p className="mt-3 text-sm text-muted">{message}</p>}
      {user?.email && !subscribed && (
        <p className="mt-2 text-xs text-muted">Bildirim {user.email} adresine gönderilir.</p>
      )}
    </div>
  );
}
