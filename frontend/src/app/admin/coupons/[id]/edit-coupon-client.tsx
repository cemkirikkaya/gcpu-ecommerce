"use client";

import Link from "next/link";
import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";
import { CouponFormFields } from "@/components/admin/coupon-form-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import type { AdminCoupon } from "@/lib/types";

type EditCouponClientProps = {
  params: Promise<{ id: string }>;
};

export function EditCouponClient({ params }: EditCouponClientProps) {
  const router = useRouter();
  const { token } = useAuth();
  const [coupon, setCoupon] = useState<AdminCoupon | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!token) {
      return;
    }

    params.then(({ id }) => {
      api
        .adminCoupon(token, Number(id))
        .then(setCoupon)
        .catch((err) => setError(err instanceof Error ? err.message : "Kupon yüklenemedi."));
    });
  }, [params, token]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token || !coupon) {
      return;
    }

    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);

    try {
      await api.adminUpdateCoupon(token, coupon.id, {
        code: String(formData.get("code")),
        type: String(formData.get("type")) as "percent" | "fixed",
        value: Number(formData.get("value")),
        min_order_amount: formData.get("min_order_amount")
          ? Number(formData.get("min_order_amount"))
          : null,
        max_discount_amount: formData.get("max_discount_amount")
          ? Number(formData.get("max_discount_amount"))
          : null,
        usage_limit: formData.get("usage_limit")
          ? Number(formData.get("usage_limit"))
          : null,
        starts_at: String(formData.get("starts_at") || "") || null,
        expires_at: String(formData.get("expires_at") || "") || null,
        is_active: formData.get("is_active") === "on",
      });

      router.push("/admin/coupons");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kupon güncellenemedi.");
    } finally {
      setLoading(false);
    }
  }

  if (!coupon) {
    return (
      <AdminOnlyGuard>
        <p className="text-sm text-muted">{error ?? "Yükleniyor..."}</p>
      </AdminOnlyGuard>
    );
  }

  return (
    <AdminOnlyGuard>
      <div>
        <Link href="/admin/coupons" className="text-sm text-muted transition hover:text-accent">
          ← Kuponlar
        </Link>
        <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Düzenle</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">{coupon.code}</h1>

        <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
          <CouponFormFields defaultValues={coupon} />

          {error && <p className="text-sm text-red-600">{error}</p>}

          <div className="flex flex-wrap gap-3">
            <Button type="submit" disabled={loading}>
              {loading ? "Kaydediliyor..." : "Değişiklikleri Kaydet"}
            </Button>
            <ButtonLink href="/admin/coupons" variant="secondary">
              Geri
            </ButtonLink>
          </div>
        </form>
      </div>
    </AdminOnlyGuard>
  );
}
