"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { AdminOnlyGuard } from "@/components/admin/admin-only-guard";
import { CouponFormFields } from "@/components/admin/coupon-form-fields";
import { Button, ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";

export function NewCouponClient() {
  const router = useRouter();
  const { token } = useAuth();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!token) {
      return;
    }

    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);

    try {
      const response = await api.adminCreateCoupon(token, {
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

      router.push(`/admin/coupons/${response.coupon.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kupon oluşturulamadı.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <AdminOnlyGuard>
      <div>
        <Link href="/admin/coupons" className="text-sm text-muted transition hover:text-accent">
          ← Kuponlar
        </Link>
        <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Kampanya</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">Yeni Kupon</h1>

        <form onSubmit={handleSubmit} className="mt-8 max-w-3xl space-y-6">
          <CouponFormFields />

          {error && <p className="text-sm text-red-600">{error}</p>}

          <div className="flex flex-wrap gap-3">
            <Button type="submit" disabled={loading}>
              {loading ? "Kaydediliyor..." : "Kuponu Kaydet"}
            </Button>
            <ButtonLink href="/admin/coupons" variant="secondary">
              İptal
            </ButtonLink>
          </div>
        </form>
      </div>
    </AdminOnlyGuard>
  );
}
