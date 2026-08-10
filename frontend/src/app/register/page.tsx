"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { Button } from "@/components/ui/button";
import { GoogleSignInButton } from "@/components/auth/google-sign-in-button";
import { useAuth } from "@/context/auth-context";
import { getHomePathForUser } from "@/lib/auth";
import type { AccountType } from "@/lib/types";

export default function RegisterPage() {
  const router = useRouter();
  const { register, loginWithGoogle } = useAuth();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [accountType, setAccountType] = useState<AccountType>("customer");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);
    const password = String(formData.get("password"));
    const confirmation = String(formData.get("password_confirmation"));

    if (password !== confirmation) {
      setError("Şifreler eşleşmiyor.");
      setLoading(false);
      return;
    }

    try {
      const user = await register(
        String(formData.get("name")),
        String(formData.get("email")),
        password,
        accountType,
      );
      router.push(getHomePathForUser(user));
    } catch (err) {
      setError(err instanceof Error ? err.message : "Kayıt başarısız");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-lg items-center px-6 py-20 lg:px-10">
      <div className="w-full rounded-[2rem] border border-line bg-surface p-8 lg:p-10">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Kayıt</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">Hesap oluştur</h1>

        <div className="mt-8 grid grid-cols-2 gap-3">
          <button
            type="button"
            onClick={() => setAccountType("customer")}
            className={`rounded-[1.25rem] border px-4 py-4 text-left transition ${
              accountType === "customer"
                ? "border-accent bg-accent-soft/40"
                : "border-line bg-background hover:border-accent/50"
            }`}
          >
            <p className="font-medium">Kullanıcı Hesabı</p>
            <p className="mt-1 text-xs text-muted">Ürünleri görüntüle ve alışveriş yap</p>
          </button>
          <button
            type="button"
            onClick={() => setAccountType("company")}
            className={`rounded-[1.25rem] border px-4 py-4 text-left transition ${
              accountType === "company"
                ? "border-accent bg-accent-soft/40"
                : "border-line bg-background hover:border-accent/50"
            }`}
          >
            <p className="font-medium">Şirket Hesabı</p>
            <p className="mt-1 text-xs text-muted">Ürün ekle, stok ve vitrin yönet</p>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
          <input
            name="name"
            required
            placeholder={accountType === "company" ? "Şirket adı" : "Ad Soyad"}
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          <input
            name="email"
            type="email"
            required
            placeholder="E-posta"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          <input
            name="password"
            type="password"
            required
            minLength={8}
            placeholder="Şifre (en az 8 karakter)"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          <input
            name="password_confirmation"
            type="password"
            required
            minLength={8}
            placeholder="Şifre tekrar"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "Kaydediliyor..." : "Kayıt Ol"}
          </Button>
        </form>

        {accountType === "customer" && (
          <>
            <div className="my-6 flex items-center gap-4">
              <div className="h-px flex-1 bg-line" />
              <span className="text-xs uppercase tracking-[0.2em] text-muted">veya</span>
              <div className="h-px flex-1 bg-line" />
            </div>

            <GoogleSignInButton
              disabled={loading}
              onError={setError}
              onSuccess={async (idToken) => {
                setLoading(true);
                setError(null);
                try {
                  const user = await loginWithGoogle(idToken);
                  router.push(getHomePathForUser(user));
                } finally {
                  setLoading(false);
                }
              }}
            />
          </>
        )}

        <p className="mt-6 text-center text-sm text-muted">
          Zaten hesabınız var mı?{" "}
          <Link href="/login" className="text-accent hover:underline">
            Giriş yapın
          </Link>
        </p>
      </div>
    </div>
  );
}
