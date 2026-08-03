export function SiteFooter() {
  return (
    <footer className="mt-24 border-t border-line bg-surface/60">
      <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-12 text-sm text-muted lg:flex-row lg:items-center lg:justify-between lg:px-10">
        <p className="font-display text-xl text-foreground">GCPU</p>
        <p>Sepete eklenen ürünler 15 dakika sizin için ayrılır.</p>
        <p suppressHydrationWarning>
          © {new Date().getFullYear()} Tüm hakları saklıdır.
        </p>
      </div>
    </footer>
  );
}
