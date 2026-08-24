const benefits = [
  {
    title: "Canlı vitrin",
    text: "Kaydırmalı kategori rayları ve yumuşak geçişlerle ferah keşif.",
  },
  {
    title: "Net seçenekler",
    text: "Renk, beden ve model bilgileri her kartta akıcı biçimde sunulur.",
  },
  {
    title: "Hızlı checkout",
    text: "Adres, sipariş özeti ve stok düşümü tek ve pürüzsüz akışta.",
  },
  {
    title: "Kişisel koleksiyon",
    text: "Beğendiğiniz parçaları kaydedin; istediğiniz an sepete ekleyin.",
  },
];

export function HomeExperienceSection() {
  return (
    <section className="relative overflow-hidden py-20 lg:py-28">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(184,149,107,0.08)_0%,transparent_45%)]" />

      <div className="relative mx-auto grid max-w-7xl gap-16 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-10">
        <div className="lg:sticky lg:top-28 lg:self-start">
          <p className="text-[10px] uppercase tracking-[0.5em] text-muted">Deneyim</p>
          <h2 className="mt-5 font-display text-5xl font-light leading-tight text-foreground sm:text-6xl">
            Ferah,
            <span className="block font-semibold italic text-accent"> lüks alışveriş</span>
          </h2>
          <p className="mt-6 max-w-md text-base leading-8 text-muted">
            Geniş boşluklar, editoryal bannerlar ve akıcı sliderlar ile seçkin parçaları
            keşfedin.
          </p>
        </div>

        <div className="grid gap-5 sm:grid-cols-2">
          {benefits.map((item, index) => (
            <article
              key={item.title}
              className="group rounded-[1.5rem] border border-line/80 bg-surface p-7 shadow-sm transition duration-500 hover:-translate-y-1 hover:border-gold/35 hover:shadow-[0_24px_60px_-45px_rgba(28,25,23,0.12)]"
              style={{ transitionDelay: `${index * 60}ms` }}
            >
              <p className="font-display text-4xl font-light text-gold/40 transition group-hover:text-gold/70">
                0{index + 1}
              </p>
              <h3 className="mt-5 font-display text-2xl font-light text-foreground">
                {item.title}
              </h3>
              <p className="mt-3 text-sm leading-7 text-muted">{item.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
