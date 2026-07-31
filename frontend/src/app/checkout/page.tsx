"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api, formatPrice } from "@/lib/api";
import type { Address, Cart } from "@/lib/types";

export default function CheckoutPage() {
  const router = useRouter();
  const { token, loading: authLoading } = useAuth();
  const [cart, setCart] = useState<Cart | null>(null);
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [addressId, setAddressId] = useState<number | "new">("new");
  const [message, setMessage] = useState<string | null>(null);
  const [loadedForToken, setLoadedForToken] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (authLoading || !token) {
      return;
    }

    api
      .checkoutPreview(token)
      .then((data) => {
        setCart(data.cart);
        setAddresses(data.addresses);
        if (data.addresses.length > 0) {
          const defaultAddress =
            data.addresses.find((address) => address.is_default) ??
            data.addresses[0];
          setAddressId(defaultAddress.id);
        }
      })
      .catch((error) => setMessage(error.message))
      .finally(() => setLoadedForToken(token));
  }, [token, authLoading]);

  const loading = authLoading || (Boolean(token) && loadedForToken !== token);

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
      router.push(`/orders/${response.order.id}`);
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
          <div className="mt-6 flex justify-between border-t border-line pt-4">
            <span>Toplam</span>
            <span className="font-display text-2xl text-accent">
              {formatPrice(cart?.total ?? 0)}
            </span>
          </div>
          {message && <p className="mt-4 text-sm text-red-600">{message}</p>}
          <Button type="submit" disabled={submitting} className="mt-8 w-full">
            {submitting ? "Tamamlanıyor..." : "Siparişi Tamamla"}
          </Button>
        </aside>
      </form>
    </div>
  );
}
