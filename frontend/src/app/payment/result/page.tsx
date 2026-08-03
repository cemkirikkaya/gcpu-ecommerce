"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { Suspense } from "react";

import { ButtonLink } from "@/components/ui/button";

function PaymentResultContent() {
  const searchParams = useSearchParams();
  const status = searchParams.get("status");
  const orderId = searchParams.get("order_id");

  if (status === "success") {
    return (
      <div className="mx-auto max-w-2xl px-6 py-24 text-center">
        <p className="text-xs uppercase tracking-[0.35em] text-accent">Ödeme</p>
        <h1 className="mt-4 font-display text-5xl font-semibold">Ödeme Başarılı</h1>
        <p className="mt-4 text-muted">
          Siparişiniz alındı{orderId ? ` (#${orderId})` : ""}. Teşekkür ederiz.
        </p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          {orderId && <ButtonLink href={`/orders/${orderId}`}>Sipariş Detayı</ButtonLink>}
          <ButtonLink href="/orders" variant="secondary">
            Siparişlerim
          </ButtonLink>
        </div>
      </div>
    );
  }

  if (status === "failed") {
    return (
      <div className="mx-auto max-w-2xl px-6 py-24 text-center">
        <p className="text-xs uppercase tracking-[0.35em] text-red-600">Ödeme</p>
        <h1 className="mt-4 font-display text-5xl font-semibold">Ödeme Başarısız</h1>
        <p className="mt-4 text-muted">
          Ödeme tamamlanamadı. Sepetiniz korunur; tekrar deneyebilirsiniz.
        </p>
        <div className="mt-8 flex flex-wrap justify-center gap-3">
          <ButtonLink href="/checkout">Tekrar Dene</ButtonLink>
          <ButtonLink href="/cart" variant="secondary">
            Sepete Dön
          </ButtonLink>
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-2xl px-6 py-24 text-center">
      <p className="text-muted">Ödeme sonucu alınamadı.</p>
      <Link href="/" className="mt-6 inline-block text-sm text-accent">
        Ana sayfaya dön
      </Link>
    </div>
  );
}

export default function PaymentResultPage() {
  return (
    <Suspense fallback={<div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>}>
      <PaymentResultContent />
    </Suspense>
  );
}
