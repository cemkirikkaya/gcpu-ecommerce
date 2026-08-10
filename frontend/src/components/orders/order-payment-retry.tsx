"use client";

import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";
import type {
  InstallmentOption,
  PaymentOptions,
  PaymentProviderOption,
} from "@/lib/types";

type OrderPaymentRetryProps = {
  token: string;
  orderId: number;
  paymentOptions: PaymentOptions;
  onError: (message: string) => void;
};

export function OrderPaymentRetry({
  token,
  orderId,
  paymentOptions,
  onError,
}: OrderPaymentRetryProps) {
  const [paymentProviders] = useState<PaymentProviderOption[]>(
    paymentOptions.payment_providers,
  );
  const [selectedProvider, setSelectedProvider] = useState<PaymentProviderOption["id"]>(
    paymentOptions.payment_providers[0]?.id ?? "iyzico",
  );
  const [installments, setInstallments] = useState<InstallmentOption[]>([]);
  const [selectedInstallment, setSelectedInstallment] = useState(1);
  const [submitting, setSubmitting] = useState(false);

  const selectedProviderOption = paymentProviders.find(
    (provider) => provider.id === selectedProvider,
  );

  useEffect(() => {
    if (!paymentOptions.direct_payment || selectedProvider !== "iyzico") {
      setInstallments([]);
      setSelectedInstallment(1);
      return;
    }

    api
      .orderInstallments(token, orderId)
      .then((response) => {
        setInstallments(response.installments);
        setSelectedInstallment(response.installments[0]?.number ?? 1);
      })
      .catch((error) => {
        onError(error instanceof Error ? error.message : "Taksit seçenekleri yüklenemedi.");
      });
  }, [token, orderId, paymentOptions.direct_payment, selectedProvider, onError]);

  async function handleRetryPayment() {
    setSubmitting(true);
    onError("");

    try {
      if (selectedProvider === "stripe") {
        const payment = await api.initStripePayment(token, orderId);
        window.location.href = payment.payment_page_url;

        return;
      }

      const payment = await api.initIyzicoPayment(token, orderId, selectedInstallment);

      if (payment.redirect_url) {
        window.location.href = payment.redirect_url;
      } else if (payment.payment_page_url) {
        window.location.href = payment.payment_page_url;
      } else {
        throw new Error("Ödeme yönlendirmesi alınamadı.");
      }
    } catch (error) {
      onError(error instanceof Error ? error.message : "Ödeme başlatılamadı.");
      setSubmitting(false);
    }
  }

  if (paymentProviders.length === 0) {
    return null;
  }

  return (
    <section className="mt-10 rounded-[2rem] border border-amber-200 bg-amber-50/80 p-8">
      <h2 className="font-display text-2xl font-semibold text-amber-950">Ödeme Bekliyor</h2>
      <p className="mt-2 text-sm text-amber-900/80">
        Bu sipariş için ödeme tamamlanmadı. Aşağıdan ödemeyi tekrar deneyebilirsiniz.
      </p>

      <div className="mt-6 space-y-3">
        {paymentProviders.map((provider) => (
          <label
            key={provider.id}
            className="flex cursor-pointer items-center gap-3 rounded-[1.25rem] border border-line bg-background px-4 py-3"
          >
            <input
              type="radio"
              name="retry_payment_provider"
              checked={selectedProvider === provider.id}
              onChange={() => setSelectedProvider(provider.id)}
            />
            <span className="text-sm font-medium">{provider.label}</span>
          </label>
        ))}
      </div>

      {selectedProviderOption?.supports_installments && installments.length > 0 && (
        <div className="mt-6">
          <p className="text-sm font-medium text-amber-950">Taksit</p>
          <select
            value={selectedInstallment}
            onChange={(event) => setSelectedInstallment(Number(event.target.value))}
            className="mt-2 w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          >
            {installments.map((option) => (
              <option key={option.number} value={option.number}>
                {option.label} — {option.total_price}
              </option>
            ))}
          </select>
        </div>
      )}

      <Button
        type="button"
        disabled={submitting}
        onClick={() => void handleRetryPayment()}
        className="mt-6 w-full sm:w-auto"
      >
        {submitting ? "Yönlendiriliyor..." : "Ödemeyi Tekrar Dene"}
      </Button>
    </section>
  );
}
