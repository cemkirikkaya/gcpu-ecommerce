import { EditCouponClient } from "./edit-coupon-client";

export default function EditCouponPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  return <EditCouponClient params={params} />;
}
