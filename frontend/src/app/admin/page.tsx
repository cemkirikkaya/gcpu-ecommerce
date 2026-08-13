"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { PendingCancellationAlerts } from "@/components/admin/pending-cancellation-alerts";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatOrderDate, formatPrice } from "@/lib/api";
import type { AdminOrder, AdminSummary } from "@/lib/types";

export default function AdminDashboardPage() {
  const { token } = useAuth();
  const [summary, setSummary] = useState<AdminSummary | null>(null);
  const [recentOrders, setRecentOrders] = useState<AdminOrder[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) return;

    Promise.all([api.adminSummary(token), api.adminOrders(token)])
      .then(([summaryData, orders]) => {
        setSummary(summaryData);
        setRecentOrders(orders.slice(0, 5));
      })
      .finally(() => setLoading(false));
  }, [token]);

  return (
    <div>
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Panel</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Yönetim Özeti</h1>

      <div className="mt-8 grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        <StatCard label="Toplam Ürün" value={summary?.products_count} loading={loading} />
        <StatCard label="Toplam Stok" value={summary?.total_stock} loading={loading} />
        <StatCard
          label="Düşük Stoklu Varyant"
          value={summary?.low_stock_variants}
          loading={loading}
        />
        <StatCard label="Sipariş" value={summary?.orders_count} loading={loading} />
        <StatCard label="Satılan Adet" value={summary?.items_sold} loading={loading} />
        <StatCard
          label="Ciro"
          value={summary ? formatPrice(summary.revenue) : undefined}
          loading={loading}
          formatted
        />
      </div>

      <div className="mt-10 flex flex-wrap gap-3">
        <ButtonLink href="/admin/products/new">Yeni Ürün Ekle</ButtonLink>
        <ButtonLink href="/admin/products" variant="secondary">
          Tüm Ürünleri Gör
        </ButtonLink>
        <ButtonLink href="/admin/orders" variant="secondary">
          Siparişleri Gör
        </ButtonLink>
        <ButtonLink href="/admin/cancellation-requests" variant="secondary">
          İptal Talepleri
        </ButtonLink>
      </div>

      <PendingCancellationAlerts />

      <div className="mt-10">
        <div className="flex items-center justify-between gap-4">
          <h2 className="font-display text-2xl font-semibold">Son Siparişler</h2>
          <Link href="/admin/orders" className="text-sm text-accent">
            Tümünü gör
          </Link>
        </div>
        <div className="mt-4 space-y-3">
          {loading && <p className="text-sm text-muted">Yükleniyor...</p>}
          {!loading && recentOrders.length === 0 && (
            <p className="text-sm text-muted">Henüz sipariş yok.</p>
          )}
          {recentOrders.map((order) => (
            <Link
              key={order.id}
              href={`/admin/orders/${order.id}`}
              className="flex items-center justify-between rounded-[1.25rem] border border-line bg-surface px-5 py-4 transition hover:border-accent"
            >
              <div>
                <p className="font-medium">Sipariş #{order.id}</p>
                <p className="text-sm text-muted">
                  {formatOrderDate(order.created_at)} · {order.items_count ?? 0} kalem
                </p>
              </div>
              <p className="font-medium">{formatPrice(order.total_price)}</p>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}

function StatCard({
  label,
  value,
  loading,
  formatted = false,
}: {
  label: string;
  value?: number | string;
  loading: boolean;
  formatted?: boolean;
}) {
  const displayValue = loading
    ? "—"
    : formatted
      ? value
      : value ?? 0;

  return (
    <div className="rounded-[1.5rem] border border-line bg-surface p-6">
      <p className="text-sm text-muted">{label}</p>
      <p className="mt-2 text-3xl font-semibold">{displayValue}</p>
    </div>
  );
}
