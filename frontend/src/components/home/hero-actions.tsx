"use client";

import Link from "next/link";

import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { isPanelUser } from "@/lib/auth";

export function HeroActions() {
  const { user, token, loading } = useAuth();

  if (loading) {
    return (
      <div className="mt-10 flex flex-wrap gap-4">
        <div className="h-11 w-40 animate-pulse rounded-full bg-accent-soft/80" />
        <div className="h-11 w-36 animate-pulse rounded-full bg-line" />
      </div>
    );
  }

  if (user && token) {
    if (isPanelUser(user)) {
      return (
        <div className="mt-10 flex flex-wrap gap-4">
          <ButtonLink href="/admin">Yönetim Paneli</ButtonLink>
          <ButtonLink href="/products" variant="secondary">
            Koleksiyon
          </ButtonLink>
        </div>
      );
    }

    return (
      <div className="mt-10 flex flex-wrap gap-4">
        <ButtonLink href="/products">Koleksiyonu Keşfet</ButtonLink>
        <ButtonLink href="/account" variant="secondary">
          Hesabım
        </ButtonLink>
        <Link
          href="/cart"
          className="inline-flex items-center justify-center rounded-full border border-line bg-surface px-6 py-3 text-sm font-medium text-foreground transition hover:border-accent hover:text-accent"
        >
          Sepete Git
        </Link>
      </div>
    );
  }

  return (
    <div className="mt-10 flex flex-wrap gap-4">
      <ButtonLink href="/products">Koleksiyonu Keşfet</ButtonLink>
      <ButtonLink href="/register" variant="secondary">
        Ücretsiz Hesap Aç
      </ButtonLink>
    </div>
  );
}
