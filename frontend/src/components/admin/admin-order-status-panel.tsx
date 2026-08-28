"use client";

import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import {
  ORDER_STATUS_LABELS,
  allowedOrderStatusTransitions,
  type OrderStatusValue,
} from "@/lib/order-status";
import { api } from "@/lib/api";
import type { AdminOrder } from "@/lib/types";

type AdminOrderStatusPanelProps = {
  token: string;
  order: AdminOrder;
  onUpdated: (order: AdminOrder) => void;
  onMessage?: (message: string | null) => void;
};

export function AdminOrderStatusPanel({
  token,
  order,
  onUpdated,
  onMessage,
}: AdminOrderStatusPanelProps) {
  const options = allowedOrderStatusTransitions(order.status);
  const [selectedStatus, setSelectedStatus] = useState<OrderStatusValue | "">(
    options[0] ?? "",
  );
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const nextOptions = allowedOrderStatusTransitions(order.status);
    setSelectedStatus(nextOptions[0] ?? "");
    setError(null);
  }, [order.status]);

  if (options.length === 0) {
    return null;
  }

  async function handleUpdateStatus() {
    if (!selectedStatus) {
      return;
    }

    setUpdating(true);
    setError(null);
    onMessage?.(null);

    try {
      const updated = await api.adminUpdateOrderStatus(token, order.id, selectedStatus);
      onUpdated(updated);
      onMessage?.("Sipariş durumu güncellendi.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Sipariş durumu güncellenemedi.");
    } finally {
      setUpdating(false);
    }
  }

  return (
    <div className="mt-8 rounded-[1.5rem] border border-line bg-surface p-6">
      <p className="text-sm text-muted">Manuel durum güncelleme</p>
      <p className="mt-2 text-sm text-muted">
        Geliver dışında kalan durumlar için sipariş aşamasını buradan değiştirebilirsiniz.
      </p>

      <div className="mt-4 flex flex-wrap items-end gap-3">
        <label className="min-w-[220px] flex-1">
          <span className="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-muted">
            Yeni durum
          </span>
          <select
            value={selectedStatus}
            onChange={(event) => setSelectedStatus(event.target.value as OrderStatusValue)}
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          >
            {options.map((status) => (
              <option key={status} value={status}>
                {ORDER_STATUS_LABELS[status]}
              </option>
            ))}
          </select>
        </label>

        <Button
          type="button"
          disabled={!selectedStatus || updating}
          onClick={() => void handleUpdateStatus()}
        >
          {updating ? "Güncelleniyor..." : "Durumu Güncelle"}
        </Button>
      </div>

      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
    </div>
  );
}
