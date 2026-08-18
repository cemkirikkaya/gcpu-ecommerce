"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";

type ResetPasswordClientProps = {
  token: string;
  email: string;
};

export function ResetPasswordClient({ token, email }: ResetPasswordClientProps) {
  const router = useRouter();
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await api.resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      setMessage(response.message);
      setTimeout(() => router.push("/login"), 1500);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Şifre sıfırlanamadı.");
    } finally {
      setLoading(false);
    }
  }

  if (!token || !email) {
    return (
      <div className="mx-auto flex min-h-[70vh] max-w-lg items-center px-6 py-20 lg:px-10">
        <div className="w-full rounded-[2rem] border border-line bg-surface p-8 lg:p-10">
          <p className="text-sm text-red-600">Sıfırlama bağlantısı geçersiz.</p>
          <Link href="/forgot-password" className="mt-4 inline-block text-sm text-accent hover:underline">
            Yeni bağlantı iste
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-lg items-center px-6 py-20 lg:px-10">
      <div className="w-full rounded-[2rem] border border-line bg-surface p-8 lg:p-10">
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Şifre</p>
        <h1 className="mt-3 font-display text-4xl font-semibold">Yeni şifre belirle</h1>
        <p className="mt-3 text-sm text-muted">{email}</p>

        <form onSubmit={handleSubmit} className="mt-8 space-y-4">
          <input
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            type="password"
            required
            minLength={8}
            placeholder="Yeni şifre"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          <input
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            type="password"
            required
            minLength={8}
            placeholder="Yeni şifre tekrar"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          {message && <p className="text-sm text-green-700">{message}</p>}
          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "Kaydediliyor..." : "Şifreyi Sıfırla"}
          </Button>
        </form>
      </div>
    </div>
  );
}
