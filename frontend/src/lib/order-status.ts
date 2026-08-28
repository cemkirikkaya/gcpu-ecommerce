export type OrderStatusValue =
  | "pending"
  | "processing"
  | "shipped"
  | "delivered"
  | "cancelled";

export const ORDER_STATUS_LABELS: Record<OrderStatusValue, string> = {
  pending: "Beklemede",
  processing: "Hazırlanıyor",
  shipped: "Kargoda",
  delivered: "Teslim Edildi",
  cancelled: "İptal Edildi",
};

export function allowedOrderStatusTransitions(status: string): OrderStatusValue[] {
  switch (status) {
    case "pending":
      return ["processing", "cancelled"];
    case "processing":
      return ["shipped", "cancelled"];
    case "shipped":
      return ["delivered"];
    default:
      return [];
  }
}

export function isTerminalOrderStatus(status: string): boolean {
  return status === "delivered" || status === "cancelled";
}
