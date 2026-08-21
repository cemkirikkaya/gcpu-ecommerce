"use client";

import type { ReactNode } from "react";
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
    text: `Sepete eklediğiniz parçalar ${reservationMinutes} dakika sizin için ayrılır; acele etmenize gerek kalmaz.`,
  },
  {
    title: "Şeffaf fiyatlandırma",
    text: "Varyant, stok ve fiyat bilgisi her kartta net ve güven verici biçimde sunulur.",
  },
  {
    title: "Zarif arayüz",
    text: "Keşiften ödemeye kadar gereksiz adımlar olmadan, sakin bir deneyim.",
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
    description: "Kaydettiğiniz parçalar",
  },
  {
    href: "/cart",
    label: "Sepet",
    description: "Rezerve stok ve checkout",
  },
];

function SectionShell({
  eyebrow,
  title,
  description,
  children,
}: {
  eyebrow: string;
  title: string;
  description: string;
  children: ReactNode;
}) {
  return (
    <section className="mx-auto max-w-7xl px-6 pb-28 lg:px-10">
      <div className="overflow-hidden rounded-sm border border-line bg-surface shadow-[0_40px_100px_-70px_rgba(18,16,14,0.35)]">
        <div className="border-b border-line/80 bg-[linear-gradient(180deg,#faf7f2_0%,#f5f0e8_100%)] px-8 py-12 lg:px-14 lg:py-16">
          <div className="flex items-center gap-4">
            <span className="h-px w-10 bg-gold/60" aria-hidden="true" />
            <p className="text-[10px] uppercase tracking-[0.45em] text-muted">{eyebrow}</p>
          </div>
          <h2 className="mt-6 max-w-2xl font-display text-4xl font-light leading-tight text-foreground sm:text-5xl">
            {title}
          </h2>
          <p className="mt-5 max-w-2xl text-base leading-8 text-muted">{description}</p>
        </div>
        <div className="px-8 py-10 lg:px-14">{children}</div>
      </div>
    </section>
  );
}

export function HomeBottomSection({ reservationMinutes }: HomeBottomSectionProps) {
  const { user, token, loading } = useAuth();

  if (loading) {
    return (
      <section className="mx-auto max-w-7xl px-6 pb-28 lg:px-10">
        <div className="h-56 animate-pulse rounded-sm border border-line bg-surface" />
      </section>
    );
  }

  if (user && token) {
    if (isPanelUser(user)) {
      return (
        <SectionShell
          eyebrow="Yönetim"
          title={`Hoş geldiniz, ${user.name.split(" ")[0]}`}
          description="Siparişleri, ürünleri ve stokları yönetim panelinden takip edebilirsiniz."
        >
          <ButtonLink href="/admin">Yönetim Paneline Git</ButtonLink>
        </SectionShell>
      );
    }

    return (
      <SectionShell
        eyebrow="Hesabınız"
        title={`Tekrar hoş geldiniz, ${user.name.split(" ")[0]}`}
        description="Kaldığınız yerden devam edin — siparişleriniz, favorileriniz ve sepetiniz hazır."
      >
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {customerLinks.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="group rounded-sm border border-line bg-background p-6 transition duration-500 hover:-translate-y-0.5 hover:border-gold/35 hover:shadow-[0_24px_60px_-45px_rgba(18,16,14,0.3)]"
            >
              <p className="font-display text-2xl font-light text-foreground transition group-hover:text-gold">
                {item.label}
              </p>
              <p className="mt-3 text-sm leading-6 text-muted">{item.description}</p>
            </Link>
          ))}
        </div>
      </SectionShell>
    );
  }

  const highlights = guestHighlights(reservationMinutes);

  return (
    <section className="mx-auto max-w-7xl px-6 pb-28 lg:px-10">
      <div className="luxury-grain relative overflow-hidden rounded-sm border border-luxury-ink/10 bg-luxury-dark text-white shadow-[0_50px_120px_-70px_rgba(18,16,14,0.55)]">
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_0%_0%,rgba(184,149,107,0.15)_0%,transparent_50%)]" />

        <div className="relative border-b border-white/10 px-8 py-12 lg:px-14 lg:py-16">
          <div className="flex items-center gap-4">
            <span className="h-px w-10 bg-gold/60" aria-hidden="true" />
            <p className="text-[10px] uppercase tracking-[0.45em] text-gold-soft">Maison GCPU</p>
          </div>
          <h2 className="mt-6 max-w-2xl font-display text-4xl font-light leading-tight sm:text-5xl">
            Ayrıcalıklı alışverişin
            <span className="block font-semibold italic text-gold"> küçük dokunuşları</span>
          </h2>
        </div>

        <div className="relative grid lg:grid-cols-3">
          {highlights.map((item, index) => (
            <article
              key={item.title}
              className={`px-8 py-10 lg:px-10 lg:py-12 ${
                index < highlights.length - 1
                  ? "border-b border-white/10 lg:border-b-0 lg:border-r"
                  : ""
              }`}
            >
              <p className="font-display text-4xl font-light text-gold/35">0{index + 1}</p>
              <h3 className="mt-5 font-display text-2xl font-light text-white">{item.title}</h3>
              <p className="mt-4 text-sm leading-7 text-white/55">{item.text}</p>
            </article>
          ))}
        </div>

        <div className="relative flex flex-wrap items-center justify-between gap-6 border-t border-white/10 px-8 py-8 lg:px-14">
          <p className="max-w-xl text-sm leading-7 text-white/50">
            Koleksiyona göz atın; beğendiğiniz parçaları istediğiniz zaman sepete ekleyin.
          </p>
          <ButtonLink
            href="/products"
            className="!bg-gold !text-luxury-dark hover:!bg-gold-soft"
          >
            Koleksiyonu Keşfet
          </ButtonLink>
        </div>
      </div>
    </section>
  );
}
