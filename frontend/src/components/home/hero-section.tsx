import { HeroActions } from "@/components/home/hero-actions";
import { HeroCollage } from "@/components/home/hero-collage";
import { HeroVideoBackdrop } from "@/components/home/hero-video-backdrop";
import type { Product } from "@/lib/types";

type HeroSectionProps = {
  shopName: string;
  reservationMinutes: number;
  spotlightProducts: Product[];
};

export function HeroSection({
  shopName,
  reservationMinutes,
  spotlightProducts,
}: HeroSectionProps) {
  return (
    <section className="relative overflow-hidden border-b border-line/70">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,#ebe4da,transparent_42%),radial-gradient(circle_at_85%_0%,#f3eee8,transparent_35%),linear-gradient(180deg,#faf8f5_0%,#f7f5f2_100%)]" />
      <HeroVideoBackdrop />
      <div className="pointer-events-none absolute -left-24 top-32 h-72 w-72 animate-float-soft rounded-full bg-accent-soft/40 blur-3xl" />
      <div className="pointer-events-none absolute -right-16 bottom-0 h-80 w-80 animate-gradient-drift rounded-full bg-stone-200/50 blur-3xl [animation-delay:2s]" />

      <div className="relative mx-auto grid max-w-7xl gap-14 px-6 py-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-16 lg:px-10 lg:py-28">
        <div className="animate-fade-up">
          <p className="inline-flex items-center gap-2 rounded-full border border-line bg-surface/80 px-4 py-1.5 text-[11px] uppercase tracking-[0.35em] text-muted backdrop-blur">
            <span className="h-1.5 w-1.5 animate-pulse-dot rounded-full bg-accent" />
            {shopName}
          </p>
          <h1 className="mt-8 max-w-2xl font-display text-5xl font-semibold leading-[1.02] text-foreground sm:text-6xl lg:text-[4.5rem]">
            Seçkin parçalar,
            <span className="block text-accent">sakin bir vitrin.</span>
          </h1>
          <p className="mt-8 max-w-xl text-lg leading-8 text-muted">
            Kategorilere göre düzenlenmiş koleksiyon, net fiyatlandırma ve{" "}
            {reservationMinutes} dakikalık stok rezervasyonu ile güvenle alışveriş
            yapın.
          </p>
          <HeroActions />

          <dl className="mt-12 grid max-w-lg grid-cols-3 gap-4 border-t border-line/80 pt-8">
            {[
              [`${reservationMinutes} dk`, "Stok rezervasyonu"],
              ["Anlık", "Sepet senkronu"],
              ["Güvenli", "Ödeme akışı"],
            ].map(([value, label]) => (
              <div key={label}>
                <dt className="font-display text-2xl text-foreground">{value}</dt>
                <dd className="mt-1 text-xs leading-5 text-muted">{label}</dd>
              </div>
            ))}
          </dl>
        </div>

        <div className="animate-fade-up [animation-delay:120ms] min-w-0">
          <HeroCollage initialProducts={spotlightProducts} />
        </div>
      </div>
    </section>
  );
}
