"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { useAuth } from "@/context/auth-context";
import { useWishlist } from "@/context/wishlist-context";
import { api } from "@/lib/api";
import { countActiveOrders } from "@/lib/orders";

type AccountStats = {
  orderCount: number;
  favoriteCount: number;
  addressCount: number;
  cartCount: number;
};

const accountLinks = [
  {
    href: "/orders",
    label: "Siparişlerim",
    description: "Geçmiş siparişler ve ödeme durumu",
    statKey: "orderCount" as const,
    statLabel: "sipariş",
  },
  {
    href: "/favorites",
    label: "Favorilerim",
    description: "Kaydettiğiniz ürünler",
    statKey: "favoriteCount" as const,
    statLabel: "ürün",
  },
  {
    href: "/addresses",
    label: "Adreslerim",
    description: "Teslimat adres defteri",
    statKey: "addressCount" as const,
    statLabel: "adres",
  },
  {
    href: "/cart",
    label: "Sepetim",
    description: "Rezerve stok ve ödeme",
    statKey: "cartCount" as const,
    statLabel: "ürün",
  },
  {
    href: "/account/settings",
    label: "Hesap Ayarları",
    description: "Profil ve şifre yönetimi",
  },
];

export default function AccountPage() {
  const { user, token, loading: authLoading } = useAuth();
  const { productIds } = useWishlist();
  const [stats, setStats] = useState<AccountStats>({
    orderCount: 0,
    favoriteCount: 0,
    addressCount: 0,
    cartCount: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!token) {
      window.location.href = "/login";
      return;
    }

    setLoading(true);
    setError(null);

    Promise.all([
      api.orders(token),
      api.addresses(token),
      api.cart(token),
    ])
      .then(([orders, addresses, cart]) => {
        setStats({
          orderCount: countActiveOrders(orders),
          favoriteCount: productIds.length,
          addressCount: addresses.length,
          cartCount: cart.item_count,
        });
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Hesap bilgileri yüklenemedi.");
      })
      .finally(() => setLoading(false));
  }, [token, authLoading, productIds.length]);

  if (authLoading || loading) {
    return <p className="px-6 py-20 text-sm text-muted">Yükleniyor...</p>;
  }

  if (!user || !token) {
    return null;
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <p className="text-xs uppercase tracking-[0.35em] text-muted">Hesabım</p>
      <h1 className="mt-3 font-display text-4xl font-semibold sm:text-5xl">
        Merhaba, {user.name.split(" ")[0]}
      </h1>
      <p className="mt-3 text-sm text-muted">{user.email}</p>

      {error && <p className="mt-6 text-sm text-red-600">{error}</p>}

      <div className="mt-12 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {accountLinks.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className="group rounded-[1.5rem] border border-line bg-surface p-6 transition hover:-translate-y-0.5 hover:border-accent/40 hover:shadow-[0_20px_50px_-40px_rgba(28,25,23,0.35)]"
          >
            {"statKey" in item && item.statKey ? (
              <>
                <p className="font-display text-3xl text-accent">{stats[item.statKey]}</p>
                <p className="mt-1 text-xs uppercase tracking-[0.2em] text-muted">
                  {item.statLabel}
                </p>
              </>
            ) : (
              <p className="font-display text-3xl text-accent">⚙︎</p>
            )}
            <p className="mt-5 font-display text-2xl text-foreground transition group-hover:text-accent">
              {item.label}
            </p>
            <p className="mt-2 text-sm leading-6 text-muted">{item.description}</p>
          </Link>
        ))}
      </div>

      <div className="mt-12 rounded-[1.5rem] border border-line bg-surface p-6 sm:p-8">
        <p className="text-xs uppercase tracking-[0.25em] text-muted">Hızlı erişim</p>
        <div className="mt-5 flex flex-wrap gap-3">
          <Link
            href="/products"
            className="rounded-full border border-line bg-background px-5 py-2.5 text-sm transition hover:border-accent hover:text-accent"
          >
            Koleksiyonu keşfet
          </Link>
          <Link
            href="/checkout"
            className="rounded-full border border-line bg-background px-5 py-2.5 text-sm transition hover:border-accent hover:text-accent"
          >
            Ödemeye git
          </Link>
        </div>
      </div>
    </div>
  );
}
