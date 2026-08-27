"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";
import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { AdminCoupon } from "@/lib/types";

function AdminCouponsPageContent() {
  const { token } = useAuth();
  const [coupons, setCoupons] = useState<AdminCoupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    api
      .adminCoupons(token)
      .then(setCoupons)
      .catch((err) => setError(err instanceof Error ? err.message : "Kuponlar yüklenemedi."))
      .finally(() => setLoading(false));
  }, [token]);

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Kampanya</p>
          <h1 className="mt-3 font-display text-4xl font-semibold">Kuponlar</h1>
        </div>
        <ButtonLink href="/admin/coupons/new">Yeni Kupon</ButtonLink>
      </div>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      {loading ? (
        <p className="mt-8 text-sm text-muted">Yükleniyor...</p>
      ) : (
        <div className="mt-8 space-y-4">
          {coupons.map((coupon) => (
            <div
              key={coupon.id}
              className="rounded-[1.5rem] border border-line bg-surface p-6"
            >
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <div className="flex flex-wrap items-center gap-3">
                    <h2 className="text-xl font-semibold">{coupon.code}</h2>
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-medium ${
                        coupon.is_active
                          ? "bg-green-100 text-green-800"
                          : "bg-stone-100 text-stone-600"
                      }`}
                    >
                      {coupon.is_active ? "Aktif" : "Pasif"}
                    </span>
                  </div>
                  <p className="mt-2 text-sm text-muted">
                    {coupon.type_label} ·{" "}
                    {coupon.type === "percent"
                      ? `%${coupon.value}`
                      : formatPrice(coupon.value)}
                  </p>
                  <p className="mt-2 text-xs text-muted">
                    Kullanım: {coupon.used_count}
                    {coupon.usage_limit ? ` / ${coupon.usage_limit}` : ""}
                    {coupon.min_order_amount
                      ? ` · Min. sepet ${formatPrice(coupon.min_order_amount)}`
                      : ""}
                  </p>
                </div>
                <ButtonLink href={`/admin/coupons/${coupon.id}`} variant="secondary">
                  Düzenle
                </ButtonLink>
              </div>
            </div>
          ))}

          {coupons.length === 0 && (
            <div className="rounded-[1.5rem] border border-dashed border-line p-10 text-center">
              <p className="text-muted">Henüz kupon eklenmemiş.</p>
              <ButtonLink href="/admin/coupons/new" className="mt-4">
                İlk Kuponu Ekle
              </ButtonLink>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default function AdminCouponsPage() {
  return (
    <AdminOnlyGuard>
      <AdminCouponsPageContent />
    </AdminOnlyGuard>
  );
}
