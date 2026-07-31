import { ButtonLink } from "@/components/ui/button";

export function HeroSection() {
  return (
    <>
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,#ebe4da,transparent_45%),radial-gradient(circle_at_bottom_left,#f3eee8,transparent_40%)]" />
        <div className="relative mx-auto grid max-w-7xl gap-12 px-6 py-24 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:px-10 lg:py-32">
          <div className="animate-fade-up">
            <p className="text-xs uppercase tracking-[0.45em] text-muted">
              Yeni sezon
            </p>
            <h1 className="mt-6 max-w-2xl font-display text-5xl font-semibold leading-[1.05] text-foreground sm:text-6xl lg:text-7xl">
              Sakin bir alışveriş deneyimi, seçilmiş koleksiyonlar.
            </h1>
            <p className="mt-8 max-w-xl text-lg leading-8 text-muted">
              Ferah bir arayüz, net fiyatlandırma ve 15 dakikalık stok
              rezervasyonu ile güvenle alışveriş yapın.
            </p>
            <div className="mt-10 flex flex-wrap gap-4">
              <ButtonLink href="/products">Koleksiyonu Keşfet</ButtonLink>
              <ButtonLink href="/register" variant="secondary">
                Hesap Oluştur
              </ButtonLink>
            </div>
          </div>

          <div className="animate-fade-up [animation-delay:120ms]">
            <div className="rounded-[2.5rem] border border-line bg-surface/80 p-8 shadow-[0_40px_100px_-50px_rgba(28,25,23,0.35)] backdrop-blur">
              <div className="space-y-8">
                <div>
                  <p className="text-xs uppercase tracking-[0.3em] text-muted">
                    Deneyim
                  </p>
                  <p className="mt-3 font-display text-3xl leading-tight">
                    Kategorilere göre düzenlenmiş, varyant detaylı ürün kartları.
                  </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-3">
                  {[
                    ["15 dk", "Stok rezervasyonu"],
                    ["Anlık", "Sepet güncelleme"],
                    ["Güvenli", "Sipariş akışı"],
                  ].map(([title, subtitle]) => (
                    <div
                      key={title}
                      className="rounded-[1.5rem] border border-line bg-background px-4 py-5"
                    >
                      <p className="font-display text-2xl text-accent">{title}</p>
                      <p className="mt-2 text-sm text-muted">{subtitle}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-10">
        <div className="rounded-[2rem] border border-line bg-surface px-8 py-10 lg:px-12 lg:py-14">
          <div className="grid gap-8 lg:grid-cols-3">
            {[
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
            ].map((item) => (
              <div key={item.title} className="space-y-3">
                <h2 className="font-display text-2xl">{item.title}</h2>
                <p className="text-sm leading-7 text-muted">{item.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
