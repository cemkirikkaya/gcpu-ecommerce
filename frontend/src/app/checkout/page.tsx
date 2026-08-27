"use client";

import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { Address, Cart, InstallmentOption, PaymentProviderOption } from "@/lib/types";

export default function CheckoutPage() {
  const { token, loading: authLoading } = useAuth();
  const [cart, setCart] = useState<Cart | null>(null);
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressId, setAddressId] = useState<number | "new">("new");
  const [message, setMessage] = useState<string | null>(null);
  const [loadedForToken, setLoadedForToken] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [directPayment, setDirectPayment] = useState(false);
  const [paymentProviders, setPaymentProviders] = useState<PaymentProviderOption[]>([]);
  const [selectedProvider, setSelectedProvider] = useState<PaymentProviderOption["id"]>("iyzico");
  const [installments, setInstallments] = useState<InstallmentOption[]>([]);
  const [selectedInstallment, setSelectedInstallment] = useState(1);

  useEffect(() => {
    if (authLoading || !token) {
      return;
    }

    api
      .checkoutPreview(token)
      .then((preview) => {
        setCart(preview.cart);
        setAddresses(preview.addresses);
        setDirectPayment(preview.direct_payment);
        setPaymentProviders(preview.payment_providers);
        setSelectedProvider(preview.payment_providers[0]?.id ?? "iyzico");

        if (preview.addresses.length > 0) {
          const defaultAddress =
            preview.addresses.find((address) => address.is_default) ??
            preview.addresses[0];
          setAddressId(defaultAddress.id);
        }
      })
      .catch((error) => setMessage(error.message))
      .finally(() => setLoadedForToken(token));
  }, [token, authLoading]);

  const selectedProviderOption = paymentProviders.find(
    (provider) => provider.id === selectedProvider,
  );
  const showInstallments =
    selectedProvider === "iyzico" &&
    Boolean(selectedProviderOption?.supports_installments) &&
    directPayment;

  useEffect(() => {
    if (!token || !showInstallments) {
      setInstallments([]);
      setSelectedInstallment(1);

      return;
    }

    api
      .checkoutInstallments(token)
      .then((installmentData) => {
        setInstallments(installmentData.installments);
        setSelectedInstallment(installmentData.installments[0]?.number ?? 1);
      })
      .catch((error) => setMessage(error.message));
  }, [token, showInstallments]);

  const loading = authLoading || (Boolean(token) && loadedForToken !== token);

  const selectedInstallmentOption = installments.find(
    (option) => option.number === selectedInstallment,
  );
  const cartTotal = cart?.total ?? 0;
  const payableTotal = selectedInstallmentOption
    ? Number(selectedInstallmentOption.total_price)
    : cartTotal;
  const hasInstallmentMarkup = payableTotal !== cartTotal;

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token) return;

    setSubmitting(true);
    setMessage(null);

    const formData = new FormData(event.currentTarget);
    const payload: Record<string, string | number> = {};

    if (addressId !== "new") {
      payload.address_id = addressId;
    } else {
      for (const [key, value] of formData.entries()) {
        payload[key] = String(value);
      }
    }

    try {
      const response = await api.checkout(token, payload);

      if (selectedProvider === "stripe") {
        const payment = await api.initStripePayment(token, response.order.id);
        window.location.href = payment.payment_page_url;

        return;
      }

      const payment = await api.initIyzicoPayment(
        token,
        response.order.id,
        selectedInstallment,
      );

      if (payment.redirect_url) {
        window.location.href = payment.redirect_url;
      } else if (payment.payment_page_url) {
        window.location.href = payment.payment_page_url;
      } else {
        throw new Error("Ödeme yönlendirmesi alınamadı.");
      }
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Hata");
    } finally {
      setSubmitting(false);
    }
  }

  if (authLoading || loading) {
    return <div className="px-6 py-24 text-center text-muted">Yükleniyor...</div>;
  }

  if (!token) {
    return <div className="px-6 py-24 text-center text-muted">Giriş gerekli.</div>;
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="max-w-2xl">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Ödeme</p>
        <h1 className="mt-3 font-display text-5xl font-semibold">Teslimat</h1>
      </div>

      <form onSubmit={handleSubmit} className="mt-16 grid gap-10 lg:grid-cols-[1fr_340px]">
        <div className="space-y-8">
          {addresses.length > 0 && (
            <section className="rounded-[2rem] border border-line bg-surface p-8">
              <h2 className="font-display text-2xl">Kayıtlı Adresler</h2>
              <div className="mt-6 space-y-3">
                {addresses.map((address) => (
                  <label
                    key={address.id}
                    className="flex cursor-pointer gap-4 rounded-[1.5rem] border border-line p-4 transition hover:border-accent/40"
                  >
                    <input
                      type="radio"
                      name="address_choice"
                      checked={addressId === address.id}
                      onChange={() => setAddressId(address.id)}
                      className="mt-1"
                    />
                    <span>
                      <span className="block font-medium">{address.full_name}</span>
                      <span className="mt-1 block text-sm text-muted">
                        {address.full_address}
                      </span>
                    </span>
                  </label>
                ))}
                <label className="flex cursor-pointer gap-4 rounded-[1.5rem] border border-line p-4">
                  <input
                    type="radio"
                    checked={addressId === "new"}
                    onChange={() => setAddressId("new")}
                    className="mt-1"
                  />
                  <span className="font-medium">Yeni adres kullan</span>
                </label>
              </div>
            </section>
          )}

          {(addresses.length === 0 || addressId === "new") && (
            <section className="rounded-[2rem] border border-line bg-surface p-8">
              <h2 className="font-display text-2xl">Teslimat Adresi</h2>
              <div className="mt-6 grid gap-4 sm:grid-cols-2">
                {[
                  ["first_name", "Ad"],
                  ["last_name", "Soyad"],
                  ["phone", "Telefon"],
                  ["address_line_1", "Adres"],
                  ["address_line_2", "Adres satırı 2"],
                  ["city", "Şehir"],
                  ["state", "İlçe"],
                  ["postal_code", "Posta kodu"],
                  ["country", "Ülke"],
                ].map(([name, label]) => (
                  <div key={name} className={name.includes("address") ? "sm:col-span-2" : ""}>
                    <label className="mb-2 block text-sm text-muted">{label}</label>
                    <input
                      name={name}
                      defaultValue={name === "country" ? "Türkiye" : ""}
                      required={!["phone", "address_line_2", "state"].includes(name)}
                      className="w-full rounded-full border border-line bg-background px-4 py-3 text-sm outline-none focus:border-accent"
                    />
                  </div>
                ))}
              </div>
            </section>
          )}

          {paymentProviders.length > 0 && (
            <section className="rounded-[2rem] border border-line bg-surface p-8">
              <h2 className="font-display text-2xl">Ödeme Yöntemi</h2>
              <div className="mt-6 space-y-3">
                {paymentProviders.map((provider) => (
                  <label
                    key={provider.id}
                    className="flex cursor-pointer gap-4 rounded-[1.5rem] border border-line p-4 transition hover:border-accent/40"
                  >
                    <input
                      type="radio"
                      name="payment_provider"
                      checked={selectedProvider === provider.id}
                      onChange={() => setSelectedProvider(provider.id)}
                      className="mt-1"
                    />
                    <span>
                      <span className="block font-medium">{provider.label}</span>
                      <span className="mt-1 block text-sm text-muted">
                        {provider.id === "stripe"
                          ? "Kart bilgilerinizi güvenli Stripe Checkout ekranında gireceksiniz."
                          : provider.supports_direct
                            ? "Sunucu tarafı test ödemesi"
                            : "Iyzico ödeme sayfasına yönlendirilirsiniz."}
                      </span>
                    </span>
                  </label>
                ))}
              </div>
            </section>
          )}
        </div>

        <aside className="h-fit rounded-[2rem] border border-line bg-surface p-8">
          <h2 className="font-display text-2xl">Sipariş Özeti</h2>
          <ul className="mt-6 space-y-3 text-sm text-muted">
            {cart?.items.map((item) => (
              <li key={item.id} className="flex justify-between gap-4">
                <span>{item.variant?.label}</span>
                <span>{formatPrice(item.subtotal)}</span>
              </li>
            ))}
          </ul>
          {(cart?.discount_amount ?? 0) > 0 && (
            <div className="mt-6 space-y-2 border-t border-line pt-4 text-sm">
              <div className="flex justify-between text-muted">
                <span>Ara toplam</span>
                <span>{formatPrice(cart?.subtotal ?? cartTotal)}</span>
              </div>
              <div className="flex justify-between text-accent">
                <span>İndirim {cart?.coupon ? `(${cart.coupon.code})` : ""}</span>
                <span>-{formatPrice(cart?.discount_amount ?? 0)}</span>
              </div>
            </div>
          )}
          {showInstallments && installments.length > 0 && (
            <section className="mt-6 border-t border-line pt-4">
              <h3 className="font-medium">Taksit Seçenekleri</h3>
              <div className="mt-3 space-y-2">
                {installments.map((option) => (
                  <label
                    key={option.number}
                    className="flex cursor-pointer items-center justify-between gap-3 rounded-[1.25rem] border border-line px-4 py-3 text-sm transition hover:border-accent/40"
                  >
                    <span className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="installment"
                        checked={selectedInstallment === option.number}
                        onChange={() => setSelectedInstallment(option.number)}
                      />
                      <span>{option.label}</span>
                    </span>
                    <span className="text-muted">
                      {option.number === 1
                        ? formatPrice(Number(option.total_price))
                        : `${option.number} x ${formatPrice(Number(option.monthly_price))}`}
                    </span>
                  </label>
                ))}
              </div>
            </section>
          )}
          <div className="mt-6 flex justify-between border-t border-line pt-4">
            <span>{showInstallments && installments.length > 0 ? "Ödenecek tutar" : "Toplam"}</span>
            <span className="text-right">
              {hasInstallmentMarkup && (
                <span className="block text-sm text-muted line-through">
                  {formatPrice(cartTotal)}
                </span>
              )}
              <span className="font-display text-2xl text-accent">
                {formatPrice(payableTotal)}
              </span>
              {selectedInstallmentOption && selectedInstallmentOption.number > 1 && (
                <span className="mt-1 block text-xs text-muted">
                  {selectedInstallmentOption.number} x{" "}
                  {formatPrice(Number(selectedInstallmentOption.monthly_price))}
                </span>
              )}
            </span>
          </div>
          {message && <p className="mt-4 text-sm text-red-600">{message}</p>}
          <Button type="submit" disabled={submitting} className="mt-8 w-full">
            {submitting ? "Yönlendiriliyor..." : "Ödeme"}
          </Button>
        </aside>
      </form>
    </div>
  );
}
