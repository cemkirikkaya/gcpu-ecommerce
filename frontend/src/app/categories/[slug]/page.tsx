import type { Metadata } from "next";
import { Suspense } from "react";

import { CategoryLandingClient } from "@/components/catalog/category-landing-client";
import { api } from "@/lib/api";

export const dynamic = "force-dynamic";

type CategoryPageProps = {
  params: Promise<{ slug: string }>;
};

export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  const { slug } = await params;

  try {
    const category = await api.category(slug);

    return {
      title: category.name,
      description:
        category.description ?? `${category.name} kategorisindeki ürünleri keşfedin.`,
    };
  } catch {
    return {
      title: "Kategori",
    };
  }
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = await params;

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-24">
      <Suspense fallback={<p className="text-sm text-muted">Yükleniyor...</p>}>
        <CategoryLandingClient slug={slug} />
      </Suspense>
    </div>
  );
}
