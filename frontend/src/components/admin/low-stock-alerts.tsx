"use client";

import Link from "next/link";

import type { LowStockAlert } from "@/lib/types";

type LowStockAlertsProps = {
  alerts: LowStockAlert[];
  threshold: number;
  loading: boolean;
};

export function LowStockAlerts({ alerts, threshold, loading }: LowStockAlertsProps) {
  if (loading || alerts.length === 0) {
    return null;
  }

  return (
    <div className="mt-8 rounded-[1.5rem] border border-red-200 bg-red-50 p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.3em] text-red-700">Stok</p>
          <h2 className="mt-2 font-display text-2xl font-semibold text-red-950">
            Düşük stok uyarıları
          </h2>
          <p className="mt-2 text-sm text-red-900/80">
            {threshold} adet ve altındaki varyantlar listeleniyor. Stokları güncellemeyi unutmayın.
          </p>
        </div>
        <Link
          href="/admin/products"
          className="rounded-full border border-red-300 bg-white px-5 py-2.5 text-sm text-red-950 transition hover:border-red-500"
        >
          Ürünlere git
        </Link>
      </div>

      <div className="mt-5 space-y-3">
        {alerts.map((alert) => (
          <Link
            key={alert.variant_id}
            href={`/admin/products/${alert.product_id}`}
            className="flex flex-wrap items-center justify-between gap-3 rounded-[1.25rem] border border-red-200 bg-white px-5 py-4 transition hover:border-red-400"
          >
            <div>
              <p className="font-medium text-red-950">{alert.product_name}</p>
              <p className="mt-1 text-sm text-red-900/80">SKU: {alert.sku}</p>
            </div>
            <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">
              {alert.quantity} adet
            </span>
          </Link>
        ))}
      </div>
    </div>
  );
}
