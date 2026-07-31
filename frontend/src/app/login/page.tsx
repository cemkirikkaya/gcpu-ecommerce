"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { getHomePathForUser } from "@/lib/auth";

export default function LoginPage() {
  const router = useRouter();
  const { login } = useAuth();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const formData = new FormData(event.currentTarget);

    try {
      const user = await login(
        String(formData.get("email")),
        String(formData.get("password")),
      );
      router.push(getHomePathForUser(user));
    } catch (err) {
      setError(err instanceof Error ? err.message : "Giriş başarısız");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-lg items-center px-6 py-20 lg:px-10">
      <div className="w-full rounded-[2rem] border border-line bg-surface p-8 lg:p-10">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Giriş</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">Tekrar hoş geldiniz</h1>
        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
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
            placeholder="Şifre"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "Giriş yapılıyor..." : "Giriş Yap"}
          </Button>
        </form>
        <p className="mt-6 text-center text-sm text-muted">
          Hesabınız yok mu?{" "}
          <Link href="/register" className="text-accent hover:underline">
            Kayıt olun
          </Link>
        </p>
      </div>
    </div>
  );
}
