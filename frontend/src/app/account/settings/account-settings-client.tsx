"use client";

import { FormEvent, useState } from "react";

import { AccountBackLink } from "@/components/account/account-back-link";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";

export function AccountSettingsClient() {
  const { user, token, refreshUser } = useAuth();
  const [name, setName] = useState(user?.name ?? "");
  const [email, setEmail] = useState(user?.email ?? "");
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [profileMessage, setProfileMessage] = useState<string | null>(null);
  const [passwordMessage, setPasswordMessage] = useState<string | null>(null);
  const [profileError, setProfileError] = useState<string | null>(null);
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [profileSubmitting, setProfileSubmitting] = useState(false);
  const [passwordSubmitting, setPasswordSubmitting] = useState(false);

  if (!user || !token) {
    return null;
  }

  async function handleProfileSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setProfileSubmitting(true);
    setProfileError(null);
    setProfileMessage(null);

    try {
      await api.updateProfile(token, { name, email });
      await refreshUser();
      setProfileMessage("Profil bilgileriniz güncellendi.");
    } catch (err) {
      setProfileError(err instanceof Error ? err.message : "Profil güncellenemedi.");
    } finally {
      setProfileSubmitting(false);
    }
  }

  async function handlePasswordSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPasswordSubmitting(true);
    setPasswordError(null);
    setPasswordMessage(null);

    try {
      await api.updatePassword(token, {
        ...(user.has_password ? { current_password: currentPassword } : {}),
        password,
        password_confirmation: passwordConfirmation,
      });
      setCurrentPassword("");
      setPassword("");
      setPasswordConfirmation("");
      await refreshUser();
      setPasswordMessage(
        user.has_password ? "Şifreniz güncellendi." : "Şifreniz oluşturuldu.",
      );
    } catch (err) {
      setPasswordError(err instanceof Error ? err.message : "Şifre güncellenemedi.");
    } finally {
      setPasswordSubmitting(false);
    }
  }

  return (
    <div className="mx-auto max-w-3xl px-6 py-16 lg:px-10 lg:py-24">
      <AccountBackLink />
      <p className="mt-8 text-xs uppercase tracking-[0.35em] text-muted">Hesap</p>
      <h1 className="mt-3 font-display text-4xl font-semibold">Hesap Ayarları</h1>

      <form
        onSubmit={handleProfileSubmit}
        className="mt-10 space-y-4 rounded-[1.5rem] border border-line bg-surface p-6 sm:p-8"
      >
        <h2 className="font-display text-2xl font-semibold">Profil</h2>
        <input
          value={name}
          onChange={(event) => setName(event.target.value)}
          required
          placeholder="Ad soyad"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          type="email"
          required
          placeholder="E-posta"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        {profileError && <p className="text-sm text-red-600">{profileError}</p>}
        {profileMessage && <p className="text-sm text-green-700">{profileMessage}</p>}
        <Button type="submit" disabled={profileSubmitting}>
          {profileSubmitting ? "Kaydediliyor..." : "Profili Kaydet"}
        </Button>
      </form>

      <form
        onSubmit={handlePasswordSubmit}
        className="mt-6 space-y-4 rounded-[1.5rem] border border-line bg-surface p-6 sm:p-8"
      >
        <h2 className="font-display text-2xl font-semibold">Şifre</h2>
        <p className="text-sm text-muted">
          {user.has_password
            ? "Güvenliğiniz için mevcut şifrenizi girin."
            : "Google ile giriş yaptınız. İsterseniz e-posta ve şifre ile de giriş yapabilmeniz için bir şifre belirleyin."}
        </p>
        {user.has_password && (
          <input
            value={currentPassword}
            onChange={(event) => setCurrentPassword(event.target.value)}
            type="password"
            required
            placeholder="Mevcut şifre"
            className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
          />
        )}
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
        {passwordError && <p className="text-sm text-red-600">{passwordError}</p>}
        {passwordMessage && <p className="text-sm text-green-700">{passwordMessage}</p>}
        <Button type="submit" disabled={passwordSubmitting}>
          {passwordSubmitting
            ? "Kaydediliyor..."
            : user.has_password
              ? "Şifreyi Güncelle"
              : "Şifre Oluştur"}
        </Button>
      </form>
    </div>
  );
}
