"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { useAuth } from "@/context/auth-context";

const links = [
  { href: "/admin", label: "Özet" },
  { href: "/admin/products", label: "Ürünler" },
  { href: "/admin/orders", label: "Siparişler" },
  { href: "/admin/products/new", label: "Yeni Ürün" },
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

  return pathname === href || pathname.startsWith(`${href}/`);
}

export function AdminNav() {
  const pathname = usePathname();
  const { user, logout } = useAuth();

  return (
    <aside className="flex w-full flex-col gap-8 border-b border-line bg-surface p-6 lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
      <div>
        <p className="text-xs uppercase tracking-[0.35em] text-muted">Yönetim</p>
        <h2 className="mt-2 font-display text-2xl font-semibold">GCPU Panel</h2>
        {user && <p className="mt-2 text-sm text-muted">{user.name}</p>}
      </div>

      <nav className="flex flex-col gap-1">
        {links.map((link) => {
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
