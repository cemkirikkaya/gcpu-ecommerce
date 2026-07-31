import type { User } from "./types";

export function isAdmin(user: User | null | undefined): boolean {
  return user?.role === "admin";
}

export function isCustomer(user: User | null | undefined): boolean {
  return user?.role === "customer";
}

export function getHomePathForUser(user: User | null | undefined): string {
  return isAdmin(user) ? "/admin" : "/products";
}
