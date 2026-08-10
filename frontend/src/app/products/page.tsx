import { ProductsCatalogClient } from "@/components/catalog/products-catalog-client";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Koleksiyon",
};

export default function ProductsPage() {
  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="max-w-3xl animate-fade-up">
        <p className="text-xs uppercase tracking-[0.45em] text-muted">GCPU</p>
        <h1 className="mt-4 font-display text-5xl font-semibold leading-tight text-foreground sm:text-6xl">
          Koleksiyon
        </h1>
        <p className="mt-6 text-lg leading-8 text-muted">
          Ürünleri arayın, kategoriye ve fiyata göre filtreleyin.
        </p>
      </div>

      <ProductsCatalogClient />
    </div>
  );
}
