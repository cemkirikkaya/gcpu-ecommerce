import { CinematicHero } from "@/components/home/cinematic-hero";
import type { Product } from "@/lib/types";

type HeroSectionProps = {
  shopName: string;
  reservationMinutes: number;
  spotlightProducts: Product[];
};

export function HeroSection(props: HeroSectionProps) {
  return <CinematicHero {...props} />;
}
