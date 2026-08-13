import { CategoryShowcase } from "@/components/home/category-showcase";
import { HomeMarquee } from "@/components/home/home-marquee";
import { RevealSection } from "@/components/home/reveal-section";
import { FeaturedProductsSection } from "@/components/home/featured-products-section";
import { HeroSection } from "@/components/home/hero-section";
import { HomeBottomSection } from "@/components/home/home-bottom-section";
import { HomeExperienceSection } from "@/components/home/home-sections";
import { api } from "@/lib/api";
import { fetchAllProducts } from "@/lib/fetch-all-products";
import {
  buildCategoryOptions,
  collectCatalogProducts,
  groupProductsByCategorySlug,
} from "@/lib/home-catalog";

export const dynamic = "force-dynamic";

export default async function Home() {
  const [catalog, featuredResponse, allProducts] = await Promise.all([
    api.catalog(),
    api.products({ per_page: 8, sort: "latest" }),
    fetchAllProducts({ sort: "latest" }),
  ]);

  const catalogProducts = collectCatalogProducts(catalog);
  const spotlightProducts =
    allProducts.length > 0
      ? allProducts
      : featuredResponse.products.length > 0
        ? featuredResponse.products
        : catalogProducts;
  const categoryOptions =
    featuredResponse.categories.length > 0
      ? featuredResponse.categories
      : buildCategoryOptions(catalog);
  const productsByCategory = groupProductsByCategorySlug(catalogProducts);
  const marqueeItems = [
    ...categoryOptions.map((category) => ({
      label: category.name,
      href: `/categories/${category.slug}`,
    })),
    { label: `${catalog.reservation_minutes} dk stok rezervasyonu` },
    { label: "Net fiyatlandırma" },
    { label: "Güvenli ödeme" },
    { label: catalog.shop_name },
  ];

  return (
    <>
      <HeroSection
        shopName={catalog.shop_name}
        reservationMinutes={catalog.reservation_minutes}
        spotlightProducts={spotlightProducts}
      />
      <HomeMarquee items={marqueeItems} />
      <RevealSection>
        <CategoryShowcase
          categories={categoryOptions}
          productsByCategory={productsByCategory}
        />
      </RevealSection>
      <RevealSection delayMs={80}>
        <FeaturedProductsSection products={featuredResponse.products} />
      </RevealSection>
      <RevealSection delayMs={120}>
        <HomeExperienceSection />
      </RevealSection>
      <RevealSection delayMs={160}>
        <HomeBottomSection reservationMinutes={catalog.reservation_minutes} />
      </RevealSection>
    </>
  );
}
