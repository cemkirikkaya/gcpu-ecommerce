"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await api.forgotPassword(email);
      setMessage(response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Talep gönderilemedi.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-lg items-center px-6 py-20 lg:px-10">
      <div className="w-full rounded-[2rem] border border-line bg-surface p-8 lg:p-10">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Şifre</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">Şifremi unuttum</h1>
        <p className="mt-3 text-sm text-muted">
          E-posta adresinize şifre sıfırlama bağlantısı göndereceğiz.
        </p>

        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
          <input
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            type="email"
            required
            placeholder="E-posta"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          {message && <p className="text-sm text-green-700">{message}</p>}
          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "Gönderiliyor..." : "Sıfırlama Bağlantısı Gönder"}
          </Button>
        </form>

        <p className="mt-6 text-center text-sm text-muted">
          <Link href="/login" className="text-accent hover:underline">
            Giriş sayfasına dön
          </Link>
        </p>
      </div>
    </div>
  );
}
