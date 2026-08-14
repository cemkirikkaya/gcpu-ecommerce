import { EditProductClient } from "./edit-product-client";

export default async function EditProductPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ merged?: string }>;
}) {
  const { id } = await params;
  const { merged } = await searchParams;

  return <EditProductClient productId={id} merged={merged === "1"} />;
}
