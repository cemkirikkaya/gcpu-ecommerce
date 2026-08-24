"use client";

import Link from "next/link";
import { Suspense, useEffect, useState } from "react";

import { HeaderSearch } from "@/components/layout/header-search";
import { useAuth } from "@/context/auth-context";
import { isPanelUser } from "@/lib/auth";
import { api } from "@/lib/api";

function HeaderSearchFallback() {
  return (
    <div
      aria-hidden="true"
      className="h-11 w-full rounded-full border border-line/80 bg-surface/70 md:max-w-xl"
    />
  );
}

export function SiteHeader() {
  const { user, token, logout, loading } = useAuth();
  const [mounted, setMounted] = useState(false);
  const [cartCount, setCartCount] = useState(0);

  useEffect(() => {
    setMounted(true);
  }, []);

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
    <header className="sticky top-0 z-50 border-b border-line/60 bg-surface/85 backdrop-blur-xl">
      <div className="mx-auto max-w-7xl px-6 py-5 lg:px-10 lg:py-6">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
          <div className="flex items-center justify-between gap-4 md:shrink-0">
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

            <nav className="flex items-center gap-0.5 text-sm md:hidden">
              <Link
                href="/products"
                className="rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
              >
                Koleksiyon
              </Link>
              <Link
                href="/blog"
                className="rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
              >
                Blog
              </Link>
              {mounted && !loading && user && token && !isPanelUser(user) && (
                <Link
                  href="/cart"
                  className="relative rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                >
                  Sepet
                  {visibleCartCount > 0 && (
                    <span className="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-medium text-white">
                      {visibleCartCount}
                    </span>
                  )}
                </Link>
              )}
            </nav>
          </div>

          <div className="w-full md:max-w-xl md:flex-1 lg:max-w-2xl">
            <Suspense fallback={<HeaderSearchFallback />}>
              <HeaderSearch />
            </Suspense>
          </div>

          <nav className="hidden items-center gap-1 text-sm md:flex md:shrink-0">
            <Link
              href="/products"
              className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70 hover:text-foreground"
            >
              Koleksiyon
            </Link>
            <Link
              href="/blog"
              className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70 hover:text-foreground"
            >
              Blog
            </Link>

            {mounted && !loading && user && token ? (
              <>
                {isPanelUser(user) ? (
                  <Link
                    href="/admin"
                    className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                  >
                    Yönetim
                  </Link>
                ) : (
                  <>
                    <Link
                      href="/account"
                      className="rounded-full px-4 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                    >
                      Hesabım
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

        {mounted && !loading && (
          <nav className="mt-3 flex flex-wrap items-center gap-1 border-t border-line/70 pt-3 text-sm md:hidden">
            {!user || !token ? (
              <>
                <Link
                  href="/login"
                  className="rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                >
                  Giriş
                </Link>
                <Link
                  href="/register"
                  className="rounded-full bg-accent px-4 py-2 font-medium text-white transition hover:bg-stone-800"
                >
                  Kayıt Ol
                </Link>
              </>
            ) : isPanelUser(user) ? (
              <Link
                href="/admin"
                className="rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
              >
                Yönetim
              </Link>
            ) : (
              <>
                <Link
                  href="/account"
                  className="rounded-full px-3 py-2 text-stone-700 transition hover:bg-accent-soft/70"
                >
                  Hesabım
                </Link>
                <button
                  type="button"
                  onClick={() => logout()}
                  className="rounded-full px-3 py-2 text-stone-600 transition hover:bg-stone-100"
                >
                  Çıkış
                </button>
              </>
            )}
          </nav>
        )}
      </div>
    </header>
  );
}
