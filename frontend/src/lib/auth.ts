import type { User } from "./types";

export function isAdmin(user: User | null | undefined): boolean {
  return user?.role === "admin";
}

export function isVendor(user: User | null | undefined): boolean {
  return user?.role === "vendor";
}

export function isPanelUser(user: User | null | undefined): boolean {
  return isAdmin(user) || isVendor(user);
}

export function isCustomer(user: User | null | undefined): boolean {
  return user?.role === "customer";
}

export function getHomePathForUser(user: User | null | undefined): string {
  return isPanelUser(user) ? "/admin" : "/products";
}
