"use client";

import { FormEvent, useEffect, useState } from "react";

import { AccountBackLink } from "@/components/account/account-back-link";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import type { Address } from "@/lib/types";

type AddressFormState = {
  title: string;
  first_name: string;
  last_name: string;
  phone: string;
  address_line_1: string;
  address_line_2: string;
  city: string;
  state: string;
  postal_code: string;
  country: string;
};

const emptyForm: AddressFormState = {
  title: "",
  first_name: "",
  last_name: "",
  phone: "",
  address_line_1: "",
  address_line_2: "",
  city: "",
  state: "",
  postal_code: "",
  country: "Türkiye",
};

export default function AddressesPage() {
  const { token, loading: authLoading } = useAuth();
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [form, setForm] = useState<AddressFormState>(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function loadAddresses(currentToken: string) {
    setLoading(true);
    try {
      const items = await api.addresses(currentToken);
      setAddresses(items);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Adresler yüklenemedi.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!token) {
      window.location.href = "/login";
      return;
    }

    void loadAddresses(token);
  }, [token, authLoading]);

  function startEdit(address: Address) {
    setEditingId(address.id);
    setForm({
      title: address.title ?? "",
      first_name: address.first_name,
      last_name: address.last_name,
      phone: address.phone ?? "",
      address_line_1: address.address_line_1,
      address_line_2: address.address_line_2 ?? "",
      city: address.city,
      state: address.state ?? "",
      postal_code: address.postal_code,
      country: address.country,
    });
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token) return;

    setError(null);
    setMessage(null);

    try {
      if (editingId) {
        const updated = await api.updateAddress(token, editingId, form);
        setAddresses((current) =>
          current.map((address) => (address.id === updated.id ? updated : address)),
        );
        setMessage("Adres güncellendi.");
      } else {
        const created = await api.createAddress(token, form);
        setAddresses((current) => [created, ...current]);
        setMessage("Adres eklendi.");
      }

      setEditingId(null);
      setForm(emptyForm);
    } catch (err) {
      setError(err instanceof Error ? err.message : "İşlem başarısız.");
    }
  }

  async function handleDelete(addressId: number) {
    if (!token) return;

    setError(null);
    setMessage(null);

    try {
      await api.deleteAddress(token, addressId);
      setAddresses((current) => current.filter((address) => address.id !== addressId));
      setMessage("Adres silindi.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Adres silinemedi.");
    }
  }

  async function handleSetDefault(addressId: number) {
    if (!token) return;

    setError(null);
    setMessage(null);

    try {
      const updated = await api.setDefaultAddress(token, addressId);
      setAddresses((current) =>
        current.map((address) => ({
          ...address,
          is_default: address.id === updated.id,
        })),
      );
      setMessage("Varsayılan adres güncellendi.");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Varsayılan adres güncellenemedi.");
    }
  }

  if (authLoading || loading) {
    return <p className="px-6 py-20 text-sm text-muted">Yükleniyor...</p>;
  }

  return (
    <div className="mx-auto max-w-4xl px-6 py-16 lg:px-10">
      <AccountBackLink />
      <p className="mt-6 text-xs uppercase tracking-[0.35em] text-muted">Hesabım</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Adreslerim</h1>

      {message && <p className="mt-6 text-sm text-green-700">{message}</p>}
      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      <div className="mt-10 space-y-4">
        {addresses.map((address) => (
          <div
            key={address.id}
            className="rounded-[1.5rem] border border-line bg-surface p-6"
          >
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="font-medium">
                  {address.title ?? "Adres"} {address.is_default && "· Varsayılan"}
                </p>
                <p className="mt-2 text-sm text-muted">{address.full_name}</p>
                <p className="mt-1 text-sm text-muted">{address.full_address}</p>
                {address.phone && <p className="mt-2 text-sm text-muted">{address.phone}</p>}
              </div>
              <div className="flex flex-wrap gap-2">
                {!address.is_default && (
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => void handleSetDefault(address.id)}
                  >
                    Varsayılan Yap
                  </Button>
                )}
                <Button type="button" variant="secondary" onClick={() => startEdit(address)}>
                  Düzenle
                </Button>
                <Button type="button" onClick={() => void handleDelete(address.id)}>
                  Sil
                </Button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="mt-12 space-y-4 rounded-[2rem] border border-line bg-surface p-8">
        <h2 className="font-display text-2xl font-semibold">
          {editingId ? "Adresi Düzenle" : "Yeni Adres Ekle"}
        </h2>
        <div className="grid gap-4 md:grid-cols-2">
          {[
            ["title", "Başlık"],
            ["first_name", "Ad"],
            ["last_name", "Soyad"],
            ["phone", "Telefon"],
            ["address_line_1", "Adres"],
            ["address_line_2", "Adres 2"],
            ["city", "Şehir"],
            ["state", "İlçe"],
            ["postal_code", "Posta Kodu"],
            ["country", "Ülke"],
          ].map(([name, label]) => (
            <input
              key={name}
              name={name}
              value={form[name as keyof AddressFormState]}
              onChange={(event) =>
                setForm((current) => ({ ...current, [name]: event.target.value }))
              }
              required={["first_name", "last_name", "address_line_1", "city", "postal_code", "country"].includes(name)}
              placeholder={label}
              className="rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
            />
          ))}
        </div>
        <div className="flex gap-3">
          <Button type="submit">{editingId ? "Güncelle" : "Kaydet"}</Button>
          {editingId && (
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                setEditingId(null);
                setForm(emptyForm);
              }}
            >
              İptal
            </Button>
          )}
        </div>
      </form>
    </div>
  );
}
