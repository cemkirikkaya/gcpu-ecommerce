import Link from "next/link";

type PromoBannersProps = {
  reservationMinutes: number;
};

const promos = (reservationMinutes: number) => [
  {
    title: "Yeni Sezon",
    text: "Editoryal seçkiler ve ferah bir alışveriş deneyimi.",
    href: "/products",
    cta: "Keşfet",
    tone: "from-[#f5efe6] to-[#faf8f5]",
  },
  {
    title: "Stok Rezervasyonu",
    text: `Sepete eklediğiniz ürünler ${reservationMinutes} dakika sizin için ayrılır.`,
    href: "/products",
    cta: "Alışverişe Başla",
    tone: "from-[#eef3f0] to-[#fafcf9]",
  },
  {
    title: "Güvenli Ödeme",
    text: "Iyzico ve Stripe ile hızlı, güvenli checkout.",
    href: "/register",
    cta: "Hesap Aç",
    tone: "from-[#f3eef8] to-[#fbf9fd]",
  },
];

export function PromoBanners({ reservationMinutes }: PromoBannersProps) {
  return (
    <section className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-20">
      <div className="grid gap-5 md:grid-cols-3">
        {promos(reservationMinutes).map((promo) => (
          <Link
            key={promo.title}
            href={promo.href}
            className={`group rounded-[1.75rem] border border-line/70 bg-gradient-to-br ${promo.tone} p-8 transition duration-500 hover:-translate-y-1 hover:border-gold/40 hover:shadow-[0_24px_60px_-40px_rgba(28,25,23,0.15)]`}
          >
            <p className="text-[10px] uppercase tracking-[0.4em] text-gold">Maison</p>
            <h2 className="mt-4 font-display text-3xl font-light text-foreground">
              {promo.title}
            </h2>
            <p className="mt-4 text-sm leading-7 text-muted">{promo.text}</p>
            <span className="mt-6 inline-flex text-sm uppercase tracking-[0.25em] text-accent transition group-hover:text-gold">
              {promo.cta} →
            </span>
          </Link>
        ))}
      </div>
    </section>
  );
}
