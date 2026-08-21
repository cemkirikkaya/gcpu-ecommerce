const benefits = [
  {
    title: "Canlı vitrin",
    text: "Kaydırmalı kategori rayları ve sinematik geçişlerle modern keşif.",
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
    <section className="relative overflow-hidden bg-luxury-dark py-24 text-white lg:py-32">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(184,149,107,0.12)_0%,transparent_45%)]" />

      <div className="relative mx-auto grid max-w-7xl gap-16 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-10">
        <div className="lg:sticky lg:top-28 lg:self-start">
          <p className="text-[10px] uppercase tracking-[0.55em] text-gold-soft">Deneyim</p>
          <h2 className="mt-5 font-display text-5xl font-light leading-tight sm:text-6xl">
            Hareketli,
            <span className="block font-semibold italic text-gold">modern alışveriş</span>
          </h2>
          <p className="mt-6 max-w-md text-base leading-8 text-white/55">
            GCPU; video vitrinler, yumuşak scroll geçişleri ve editoryal grid ile
            alışverişi yeniden kurgular.
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          {benefits.map((item, index) => (
            <article
              key={item.title}
              className="group rounded-lg border border-white/10 bg-white/[0.03] p-7 backdrop-blur-sm transition duration-700 hover:-translate-y-1 hover:border-gold/35 hover:bg-white/[0.06]"
              style={{ transitionDelay: `${index * 60}ms` }}
            >
              <p className="font-display text-4xl font-light text-gold/30 transition group-hover:text-gold/60">
                0{index + 1}
              </p>
              <h3 className="mt-5 font-display text-2xl font-light">{item.title}</h3>
              <p className="mt-3 text-sm leading-7 text-white/55">{item.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
