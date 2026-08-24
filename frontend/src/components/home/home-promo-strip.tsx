import Link from "next/link";

import { ButtonLink } from "@/components/ui/button";

type HomePromoStripProps = {
  shopName: string;
};

export function HomePromoStrip({ shopName }: HomePromoStripProps) {
  return (
    <section className="mx-auto max-w-7xl px-6 py-10 lg:px-10 lg:py-14">
      <div className="overflow-hidden rounded-[2rem] border border-line/80 bg-[linear-gradient(135deg,#faf8f5_0%,#f0ebe3_50%,#e8dfd3_100%)]">
        <div className="grid lg:grid-cols-2">
          <div className="flex flex-col justify-center px-8 py-12 lg:px-14 lg:py-16">
            <p className="text-[10px] uppercase tracking-[0.5em] text-muted">{shopName}</p>
            <h2 className="mt-5 font-display text-4xl font-light leading-tight text-foreground sm:text-5xl">
              Ferah vitrin,
              <span className="block font-semibold italic text-accent"> lüks detaylar</span>
            </h2>
            <p className="mt-5 max-w-md text-base leading-8 text-muted">
              Kategorilere göz atın, favorilerinize ekleyin ve seçkin parçaları güvenle
              sipariş edin.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <ButtonLink href="/products" className="!rounded-full">
                Koleksiyonu Keşfet
              </ButtonLink>
              <ButtonLink href="/blog" variant="secondary" className="!rounded-full">
                Blog
              </ButtonLink>
            </div>
          </div>
          <div className="relative min-h-[280px] bg-[radial-gradient(circle_at_30%_30%,rgba(184,149,107,0.25)_0%,transparent_55%),linear-gradient(180deg,#ece4d8_0%,#f7f2ea_100%)] lg:min-h-full">
            <div className="absolute inset-10 rounded-[1.5rem] border border-white/50 bg-white/20 backdrop-blur-sm" />
            <div className="absolute bottom-10 left-10 right-10 rounded-2xl border border-line/60 bg-surface/90 p-6 shadow-lg backdrop-blur">
              <p className="text-[10px] uppercase tracking-[0.35em] text-gold">Öne çıkan</p>
              <p className="mt-2 font-display text-2xl text-foreground">Sezonun seçkileri</p>
              <Link
                href="/products"
                className="mt-4 inline-flex text-sm uppercase tracking-[0.25em] text-muted transition hover:text-gold"
              >
                Hemen incele →
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
