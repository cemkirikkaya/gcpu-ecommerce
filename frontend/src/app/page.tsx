import { CategoryRail } from "@/components/home/category-rail";
import { FeaturedProductsCarousel } from "@/components/home/featured-products-carousel";
import { HomeMarquee } from "@/components/home/home-marquee";
import { HomePromoStrip } from "@/components/home/home-promo-strip";
import { PromoBanners } from "@/components/home/promo-banners";
import { RevealSection } from "@/components/home/reveal-section";
import { HeroSection } from "@/components/home/hero-section";
import { HomeBottomSection } from "@/components/home/home-bottom-section";
import { HomeExperienceSection } from "@/components/home/home-sections";
import { api } from "@/lib/api";
import {
  buildCategoryOptions,
  collectCatalogProducts,
  groupProductsByCategorySlug,
} from "@/lib/home-catalog";

export const dynamic = "force-dynamic";

export default async function Home() {
  const [catalog, featuredResponse] = await Promise.all([
    api.catalog(),
    api.products({ per_page: 12, sort: "latest" }),
  ]);

  const catalogProducts = collectCatalogProducts(catalog);
  const spotlightProducts =
    featuredResponse.products.length > 0
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
    <div className="overflow-x-hidden">
      <HeroSection
        shopName={catalog.shop_name}
        reservationMinutes={catalog.reservation_minutes}
        spotlightProducts={spotlightProducts}
      />
      <PromoBanners reservationMinutes={catalog.reservation_minutes} />
      <HomeMarquee items={marqueeItems} />
      <RevealSection>
        <CategoryRail
          categories={categoryOptions}
          productsByCategory={productsByCategory}
        />
      </RevealSection>
      <HomePromoStrip shopName={catalog.shop_name} />
      <RevealSection delayMs={100}>
        <FeaturedProductsCarousel products={featuredResponse.products} />
      </RevealSection>
      <RevealSection delayMs={140}>
        <HomeExperienceSection />
      </RevealSection>
      <RevealSection delayMs={180}>
        <HomeBottomSection reservationMinutes={catalog.reservation_minutes} />
      </RevealSection>
    </div>
  );
}
