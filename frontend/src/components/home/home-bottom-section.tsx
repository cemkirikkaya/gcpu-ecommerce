"use client";

import Link from "next/link";

import { ButtonLink } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { isPanelUser } from "@/lib/auth";

type HomeBottomSectionProps = {
  reservationMinutes: number;
};

const guestHighlights = (reservationMinutes: number) => [
  {
    title: "Stok rezervasyonu",
    text: `Sepete eklediğiniz ürünler ${reservationMinutes} dakika sizin için ayrılır; acele etmenize gerek kalmaz.`,
  },
  {
    title: "Net fiyatlandırma",
    text: "Varyant, stok ve fiyat bilgisi her kartta şeffaf biçimde sunulur.",
  },
  {
    title: "Sakin arayüz",
    text: "Keşiften ödemeye kadar gereksiz adımlar olmadan ilerleyin.",
  },
];

const customerLinks = [
  {
    href: "/account",
    label: "Hesabım",
    description: "Siparişler, favoriler ve adresler",
  },
  {
    href: "/orders",
    label: "Siparişlerim",
    description: "Geçmiş siparişler ve ödeme durumu",
  },
  {
    href: "/favorites",
    label: "Favorilerim",
    description: "Kaydettiğiniz ürünler",
  },
  {
    href: "/cart",
    label: "Sepet",
    description: "Rezerve stok ve checkout",
  },
  {
    href: "/addresses",
    label: "Adreslerim",
    description: "Teslimat adres defteri",
  },
];

export function HomeBottomSection({ reservationMinutes }: HomeBottomSectionProps) {
  const { user, token, loading } = useAuth();

  if (loading) {
    return (
      <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-10">
        <div className="h-48 animate-pulse rounded-[2rem] border border-line bg-surface" />
      </section>
    );
  }

  if (user && token) {
    if (isPanelUser(user)) {
      return (
        <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-10">
          <div className="rounded-[2rem] border border-line bg-surface px-8 py-12 lg:px-12 lg:py-14">
            <p className="text-xs uppercase tracking-[0.35em] text-muted">Yönetim</p>
            <h2 className="mt-3 font-display text-4xl font-semibold text-foreground">
              Hoş geldiniz, {user.name.split(" ")[0]}
            </h2>
            <p className="mt-4 max-w-2xl text-base leading-7 text-muted">
              Siparişleri, ürünleri ve stokları yönetim panelinden takip edebilirsiniz.
            </p>
            <div className="mt-8">
              <ButtonLink href="/admin">Yönetim Paneline Git</ButtonLink>
            </div>
          </div>
        </section>
      );
    }

    return (
      <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-10">
        <div className="rounded-[2rem] border border-line bg-surface px-8 py-12 lg:px-12 lg:py-14">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Hesabınız</p>
          <h2 className="mt-3 font-display text-4xl font-semibold text-foreground">
            Tekrar hoş geldiniz, {user.name.split(" ")[0]}
          </h2>
          <p className="mt-4 max-w-2xl text-base leading-7 text-muted">
            Kaldığınız yerden devam edin — siparişleriniz, favorileriniz ve sepetiniz
            hazır.
          </p>

          <div className="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {customerLinks.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className="group rounded-[1.5rem] border border-line bg-background p-5 transition hover:-translate-y-0.5 hover:border-accent/40 hover:shadow-[0_20px_50px_-40px_rgba(28,25,23,0.35)]"
              >
                <p className="font-display text-2xl text-foreground transition group-hover:text-accent">
                  {item.label}
                </p>
                <p className="mt-2 text-sm leading-6 text-muted">{item.description}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>
    );
  }

  const highlights = guestHighlights(reservationMinutes);

  return (
    <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-10">
      <div className="overflow-hidden rounded-[2rem] border border-line bg-[linear-gradient(180deg,#faf8f5_0%,#f3eee8_100%)]">
        <div className="border-b border-line/80 px-8 py-10 lg:px-12">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Neden GCPU?</p>
          <h2 className="mt-3 max-w-2xl font-display text-4xl font-semibold text-foreground sm:text-5xl">
            Alışverişi sadeleştiren küçük dokunuşlar
          </h2>
        </div>

        <div className="grid lg:grid-cols-3">
          {highlights.map((item, index) => (
            <article
              key={item.title}
              className={`px-8 py-8 lg:px-10 lg:py-10 ${
                index < highlights.length - 1 ? "border-b border-line/80 lg:border-b-0 lg:border-r" : ""
              }`}
            >
              <p className="text-xs uppercase tracking-[0.25em] text-accent">
                0{index + 1}
              </p>
              <h3 className="mt-4 font-display text-2xl text-foreground">{item.title}</h3>
              <p className="mt-3 text-sm leading-7 text-muted">{item.text}</p>
            </article>
          ))}
        </div>

        <div className="flex flex-wrap items-center justify-between gap-4 border-t border-line/80 px-8 py-6 lg:px-12">
          <p className="text-sm text-muted">
            Koleksiyona göz atın; beğendiğiniz ürünleri istediğiniz zaman sepete ekleyin.
          </p>
          <ButtonLink href="/products">Koleksiyonu Keşfet</ButtonLink>
        </div>
      </div>
    </section>
  );
}
