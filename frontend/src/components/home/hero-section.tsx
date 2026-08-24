import { HeroBannerSlider } from "@/components/home/hero-banner-slider";
import type { Product } from "@/lib/types";

type HeroSectionProps = {
  shopName: string;
  reservationMinutes: number;
  spotlightProducts: Product[];
};

export function HeroSection({
  shopName,
  reservationMinutes,
  spotlightProducts,
}: HeroSectionProps) {
  return (
    <HeroBannerSlider
      shopName={shopName}
      reservationMinutes={reservationMinutes}
      products={spotlightProducts}
    />
  );
}
