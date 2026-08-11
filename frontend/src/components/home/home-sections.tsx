const benefits = [
  {
    title: "Sezgisel keşif",
    text: "Elektronikten giyime, alt kategorilerle düzenlenmiş vitrin.",
  },
  {
    title: "Net seçenekler",
    text: "Renk, beden ve model bilgileri her kartta açıkça görünür.",
  },
  {
    title: "Hızlı checkout",
    text: "Adres kaydı, sipariş özeti ve stok düşümü tek akışta.",
  },
  {
    title: "Favoriler",
    text: "Beğendiğiniz ürünleri kaydedin, istediğiniz zaman sepete ekleyin.",
  },
];

export function HomeExperienceSection() {
  return (
    <section className="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-24">
      <div className="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
        <div className="max-w-md">
          <p className="text-xs uppercase tracking-[0.35em] text-muted">Deneyim</p>
          <h2 className="mt-3 font-display text-4xl font-semibold text-foreground sm:text-5xl">
            Alışveriş akışı baştan sona düşünüldü
          </h2>
          <p className="mt-5 text-base leading-7 text-muted">
            GCPU, sade bir arayüzle ürün keşfinden ödemeye kadar tüm adımları
            birbirine bağlar.
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          {benefits.map((item, index) => (
            <article
              key={item.title}
              className="rounded-[1.5rem] border border-line bg-surface p-6 shadow-[0_16px_40px_-35px_rgba(28,25,23,0.35)]"
            >
              <p className="text-xs uppercase tracking-[0.25em] text-accent">
                0{index + 1}
              </p>
              <h3 className="mt-4 font-display text-2xl text-foreground">{item.title}</h3>
              <p className="mt-3 text-sm leading-7 text-muted">{item.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
