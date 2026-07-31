import { CategorySection } from "@/components/catalog/category-section";
import { ProductCard } from "@/components/catalog/product-card";
import { api } from "@/lib/api";

export const dynamic = "force-dynamic";

export const metadata = {
  title: "Koleksiyon",
};

export default async function ProductsPage() {
  const catalog = await api.catalog();

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <div className="max-w-3xl animate-fade-up">
        <p className="text-xs uppercase tracking-[0.45em] text-muted">
          {catalog.shop_name}
        </p>
        <h1 className="mt-4 font-display text-5xl font-semibold leading-tight text-foreground sm:text-6xl">
          Koleksiyon
        </h1>
        <p className="mt-6 text-lg leading-8 text-muted">
          Ürünler kategorilere göre listelenir. Sepete eklediğiniz her seçenek{" "}
          {catalog.reservation_minutes} dakika boyunca sizin için ayrılır.
        </p>
      </div>

      <div className="mt-16 space-y-20">
        {catalog.categories.map((category) => (
          <CategorySection key={category.id} category={category} />
        ))}

        {catalog.uncategorized.length > 0 && (
          <section className="space-y-8">
            <h2 className="font-display text-4xl font-semibold">Diğer Ürünler</h2>
            <div className="space-y-8">
              {catalog.uncategorized.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </section>
        )}
      </div>
    </div>
  );
}
