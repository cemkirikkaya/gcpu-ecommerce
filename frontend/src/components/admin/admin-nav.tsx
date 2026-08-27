"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { useAuth } from "@/context/auth-context";
import { isAdmin } from "@/lib/auth";

const links = [
  { href: "/admin", label: "Özet" },
  { href: "/admin/products", label: "Ürünler" },
  { href: "/admin/orders", label: "Siparişler" },
  { href: "/admin/cancellation-requests", label: "İptal Talepleri" },
  { href: "/admin/coupons", label: "Kuponlar", adminOnly: true },
  { href: "/admin/search-analytics", label: "Arama Analitiği", adminOnly: true },
  { href: "/admin/posts", label: "Blog", adminOnly: true },
  { href: "/admin/products/new", label: "Yeni Ürün" },
  { href: "/admin/coupons/new", label: "Yeni Kupon", adminOnly: true },
  { href: "/admin/posts/new", label: "Yeni Yazı", adminOnly: true },
];

function isNavLinkActive(pathname: string, href: string): boolean {
  if (href === "/admin") {
    return pathname === "/admin";
  }

  if (href === "/admin/products/new") {
    return pathname === "/admin/products/new";
  }

  if (href === "/admin/products") {
    return (
      pathname === "/admin/products" ||
      (pathname.startsWith("/admin/products/") && pathname !== "/admin/products/new")
    );
  }

  if (href === "/admin/posts/new") {
    return pathname === "/admin/posts/new";
  }

  if (href === "/admin/coupons/new") {
    return pathname === "/admin/coupons/new";
  }

  if (href === "/admin/coupons") {
    return (
      pathname === "/admin/coupons" ||
      (pathname.startsWith("/admin/coupons/") && pathname !== "/admin/coupons/new")
    );
  }

  if (href === "/admin/search-analytics") {
    return pathname === "/admin/search-analytics";
  }

  if (href === "/admin/posts") {
    return (
      pathname === "/admin/posts" ||
      (pathname.startsWith("/admin/posts/") && pathname !== "/admin/posts/new")
    );
  }

  return pathname === href || pathname.startsWith(`${href}/`);
}

export function AdminNav() {
  const pathname = usePathname();
  const { user, logout } = useAuth();

  const visibleLinks = links.filter((link) => !link.adminOnly || isAdmin(user));

  return (
    <aside className="flex w-full flex-col gap-8 border-b border-line bg-surface p-6 lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
      <div>
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Yönetim</p>
        <h2 className="mt-2 font-display text-2xl font-semibold">GCPU Panel</h2>
        {user && <p className="mt-2 text-sm text-muted">{user.name}</p>}
      </div>

      <nav className="flex flex-col gap-1">
        {visibleLinks.map((link) => {
          const active = isNavLinkActive(pathname, link.href);

          return (
            <Link
              key={link.href}
              href={link.href}
              className={`rounded-full px-4 py-2 text-sm transition ${
                active
                  ? "bg-accent text-white"
                  : "text-stone-700 hover:bg-accent-soft/70"
              }`}
            >
              {link.label}
            </Link>
          );
        })}
      </nav>

      <div className="mt-auto flex flex-col gap-2">
        <Link
          href="/products"
          className="rounded-full px-4 py-2 text-sm text-stone-600 transition hover:bg-stone-100"
        >
          Vitrine Git
        </Link>
        <button
          type="button"
          onClick={() => logout()}
          className="rounded-full px-4 py-2 text-left text-sm text-stone-600 transition hover:bg-stone-100"
        >
          Çıkış
        </button>
      </div>
    </aside>
  );
}
