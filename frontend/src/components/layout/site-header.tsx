"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { useAuth } from "@/context/auth-context";
import { isAdmin } from "@/lib/auth";
import { api } from "@/lib/api";

export function SiteHeader() {
  const { user, token, logout, loading } = useAuth();
  const [cartCount, setCartCount] = useState(0);

  useEffect(() => {
    if (!token) {
      return;
    }

    api
      .cart(token)
      .then((cart) => setCartCount(cart.item_count))
      .catch(() => setCartCount(0));
  }, [token]);

  const visibleCartCount = token ? cartCount : 0;

  return (
    <header className="sticky top-0 z-50 border-b border-line/80 bg-background/85 backdrop-blur-md">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
        <Link href="/" className="group flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-full border border-line bg-surface text-sm font-semibold tracking-[0.15em] text-accent transition group-hover:border-accent">
            G
          </span>
          <div>
            <p className="font-display text-2xl font-semibold leading-none tracking-[0.08em]">
              GCPU
            </p>
            <p className="mt-1 text-[11px] uppercase tracking-[0.35em] text-muted">
              Store
            </p>
          </div>
        </Link>

        <nav className="flex items-center gap-1 text-sm">
          <Link
            href="/products"
            className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70 hover:text-foreground"
          >
            Koleksiyon
          </Link>

          {!loading && user && token ? (
            <>
              {isAdmin(user) ? (
                <Link
                  href="/admin"
                  className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                >
                  Yönetim
                </Link>
              ) : (
                <>
                  <Link
                    href="/orders"
                    className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                  >
                    Siparişlerim
                  </Link>
                  <Link
                    href="/cart"
                    className="relative rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                  >
                    Sepet
                    {visibleCartCount > 0 && (
                      <span className="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-[11px] font-medium text-white">
                        {visibleCartCount}
                      </span>
                    )}
                  </Link>
                </>
              )}
              <button
                type="button"
                onClick={() => logout()}
                className="rounded-full px-4 py-2 text-stone-600 transition hover:bg-stone-100"
              >
                Çıkış
              </button>
            </>
          ) : (
            <>
              <Link
                href="/login"
                className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
              >
                Giriş
              </Link>
              <Link
                href="/register"
                className="rounded-full bg-accent px-5 py-2 font-medium text-white transition hover:bg-stone-800"
              >
                Kayıt Ol
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
