import { api } from "@/lib/api";
import type { Product, ProductListParams } from "@/lib/types";

export async function fetchAllProducts(
  params: Omit<ProductListParams, "page" | "per_page"> = {},
): Promise<Product[]> {
  const products: Product[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await api.products({
      ...params,
      per_page: 48,
      page,
    });

    products.push(...response.products);
    lastPage = response.meta.last_page;
    page += 1;
  } while (page <= lastPage);

  return products;
}
