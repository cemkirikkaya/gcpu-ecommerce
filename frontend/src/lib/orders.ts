import type { Order } from "@/lib/types";

export function isActiveOrder(order: Order): boolean {
  return order.status !== "cancelled";
}

export function splitOrdersByStatus(orders: Order[]): {
  activeOrders: Order[];
  cancelledOrders: Order[];
} {
  const activeOrders: Order[] = [];
  const cancelledOrders: Order[] = [];

  for (const order of orders) {
    if (isActiveOrder(order)) {
      activeOrders.push(order);
    } else {
      cancelledOrders.push(order);
    }
  }

  return { activeOrders, cancelledOrders };
}

export function countActiveOrders(orders: Order[]): number {
  return orders.filter(isActiveOrder).length;
}
