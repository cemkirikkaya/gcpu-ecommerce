"use client";

import Link from "next/link";

import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { isPanelUser } from "@/lib/auth";

type HeroActionsProps = {
  variant?: "light" | "dark";
};

export function HeroActions({ variant = "dark" }: HeroActionsProps) {
  const { user, token, loading } = useAuth();
  const isDark = variant === "dark";

  const primaryClass = isDark
    ? "!rounded-sm !bg-white !px-8 !text-luxury-dark hover:!bg-gold-soft"
    : "!rounded-sm !bg-luxury-dark !px-8 !text-white hover:!bg-luxury-ink";

  const secondaryClass = isDark
    ? "!rounded-sm !border-white/25 !bg-white/10 !px-8 !text-white backdrop-blur-md hover:!border-gold hover:!text-gold"
    : "!rounded-sm !border-gold/30 !bg-transparent !px-8 hover:!border-gold hover:!text-gold";

  const ghostClass = isDark
    ? "inline-flex items-center justify-center rounded-sm border border-white/20 bg-white/5 px-8 py-3 text-sm font-medium uppercase tracking-[0.12em] text-white backdrop-blur transition hover:border-gold hover:text-gold"
    : "inline-flex items-center justify-center rounded-sm border border-line bg-surface/80 px-8 py-3 text-sm font-medium uppercase tracking-[0.12em] text-foreground backdrop-blur transition hover:border-gold hover:text-gold";

  if (loading) {
    return (
      <div className="mt-12 flex flex-wrap gap-4">
        <div
          className={`h-12 w-44 animate-pulse rounded-sm ${isDark ? "bg-white/20" : "bg-accent-soft/80"}`}
        />
        <div
          className={`h-12 w-40 animate-pulse rounded-sm ${isDark ? "bg-white/10" : "bg-line"}`}
        />
      </div>
    );
  }

  if (user && token) {
    if (isPanelUser(user)) {
      return (
        <div className="mt-12 flex flex-wrap gap-4">
          <ButtonLink href="/admin" className={primaryClass}>
            Yönetim Paneli
          </ButtonLink>
          <ButtonLink href="/products" variant="secondary" className={secondaryClass}>
            Koleksiyon
          </ButtonLink>
        </div>
      );
    }

    return (
      <div className="mt-12 flex flex-wrap gap-4">
        <ButtonLink href="/products" className={primaryClass}>
          Koleksiyonu Keşfet
        </ButtonLink>
        <ButtonLink href="/account" variant="secondary" className={secondaryClass}>
          Hesabım
        </ButtonLink>
        <Link href="/cart" className={ghostClass}>
          Sepete Git
        </Link>
      </div>
    );
  }

  return (
    <div className="mt-12 flex flex-wrap gap-4">
      <ButtonLink href="/products" className={primaryClass}>
        Koleksiyonu Keşfet
      </ButtonLink>
      <ButtonLink href="/register" variant="secondary" className={secondaryClass}>
        Ücretsiz Hesap Aç
      </ButtonLink>
    </div>
  );
}
