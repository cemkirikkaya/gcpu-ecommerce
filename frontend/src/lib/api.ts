import type {
  Address,
  AccountType,
  AdminCategory,
  AdminProduct,
  ApiError,
  AuthResponse,
  Cart,
  Catalog,
  CatalogVariantInput,
  Order,
  Product,
  User,
} from "./types";

const getApiUrl = (): string => {
  if (typeof window === "undefined") {
    return (
      process.env.API_INTERNAL_URL ??
      process.env.NEXT_PUBLIC_API_URL ??
      "http://127.0.0.1:8000/api"
    );
  }

  return process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api";
};

export class ApiClientError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

async function request<T>(
  path: string,
  options: RequestInit = {},
  token?: string | null,
): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (!(options.body instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(`${getApiUrl()}${path}`, {
    ...options,
    headers,
    cache: "no-store",
  });

  if (!response.ok) {
    const payload = (await response.json().catch(() => null)) as ApiError | null;
    const validationMessage = payload?.errors
      ? Object.values(payload.errors).flat().join(" ")
      : null;

    throw new ApiClientError(
      validationMessage ?? payload?.message ?? "Bir hata oluştu.",
      response.status,
    );
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return response.json() as Promise<T>;
}

export const api = {
  catalog: () => request<{ data?: never } & Catalog>("/catalog").then((r) => r as Catalog),

  product: (id: number) =>
    request<{ product: Product }>(`/products/${id}`).then((r) => r.product),

  register: (payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    account_type: AccountType;
  }) => request<AuthResponse>("/auth/register", { method: "POST", body: JSON.stringify(payload) }),

  login: (payload: { email: string; password: string }) =>
    request<AuthResponse>("/auth/login", { method: "POST", body: JSON.stringify(payload) }),

  logout: (token: string) =>
    request<{ message: string }>("/auth/logout", { method: "POST" }, token),

  me: (token: string) =>
    request<{ user: User }>("/auth/me", {}, token).then((r) => r.user),

  cart: (token: string) =>
    request<{ cart: Cart }>("/cart", {}, token).then((r) => r.cart),

  addToCart: (token: string, productVariantId: number, quantity: number) =>
    request<{ cart: Cart; message: string }>(
      "/cart/items",
      {
        method: "POST",
        body: JSON.stringify({ product_variant_id: productVariantId, quantity }),
      },
      token,
    ),

  updateCartItem: (token: string, cartItemId: number, quantity: number) =>
    request<{ cart: Cart; message: string }>(
      `/cart/items/${cartItemId}`,
      { method: "PATCH", body: JSON.stringify({ quantity }) },
      token,
    ),

  removeCartItem: (token: string, cartItemId: number) =>
    request<{ cart: Cart; message: string }>(
      `/cart/items/${cartItemId}`,
      { method: "DELETE" },
      token,
    ),

  checkoutPreview: (token: string) =>
    request<{
      cart: Cart;
      addresses: Address[];
      reservation_minutes: number;
    }>("/checkout", {}, token),

  checkout: (
    token: string,
    payload: Record<string, string | number | null | undefined>,
  ) =>
    request<{ order: Order; message: string }>(
      "/checkout",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  order: (token: string, orderId: number) =>
    request<{ order: Order }>(`/orders/${orderId}`, {}, token).then((r) => r.order),

  orders: (token: string) =>
    request<{ orders: Order[] }>("/orders", {}, token).then((r) => r.orders),

  adminCategories: (token: string) =>
    request<{ categories: AdminCategory[] }>("/admin/categories", {}, token).then(
      (r) => r.categories,
    ),

  adminProducts: (token: string) =>
    request<{ products: AdminProduct[] }>("/admin/products", {}, token).then(
      (r) => r.products,
    ),

  adminProduct: (token: string, id: number) =>
    request<{ product: AdminProduct }>(`/admin/products/${id}`, {}, token).then(
      (r) => r.product,
    ),

  adminCreateProduct: (
    token: string,
    payload: {
      name: string;
      description?: string;
      price: number;
      category_id?: number | null;
      catalog_variants: CatalogVariantInput[];
    },
  ) =>
    request<{ product: AdminProduct; message: string; merged: boolean }>(
      "/admin/products",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  adminUpdateProduct: (
    token: string,
    id: number,
    payload: {
      name?: string;
      description?: string | null;
      price?: number;
      category_id?: number | null;
      catalog_variants?: CatalogVariantInput[];
    },
  ) =>
    request<{ product: AdminProduct; message: string }>(
      `/admin/products/${id}`,
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ),

  adminDeleteProduct: (token: string, id: number) =>
    request<{ message: string }>(`/admin/products/${id}`, { method: "DELETE" }, token),

  adminUpdateStock: (token: string, stockId: number, quantity: number) =>
    request<{ stock: { id: number; quantity: number }; message: string }>(
      `/admin/stocks/${stockId}`,
      { method: "PATCH", body: JSON.stringify({ quantity }) },
      token,
    ),
};

export function formatPrice(value: number): string {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    minimumFractionDigits: 2,
  }).format(value);
}
