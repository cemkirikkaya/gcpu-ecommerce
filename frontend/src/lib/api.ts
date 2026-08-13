import type {
  Address,
  AccountType,
  AdminCategory,
  AdminOrder,
  AdminProduct,
  AdminSummary,
  ApiError,
  AuthResponse,
  Cart,
  Catalog,
  CatalogVariantInput,
  CategoryDetail,
  CategoryDetailResponse,
  InstallmentOption,
  Order,
  OrderDetailResponse,
  PaymentProviderOption,
  Product,
  ProductListParams,
  ProductListResponse,
  ProductReview,
  ProductReviewsResponse,
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

  category: (slug: string) =>
    request<CategoryDetailResponse>(`/categories/${slug}`).then((r) => r.category),

  products: (params: ProductListParams = {}) => {
    const query = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.set(key, String(value));
      }
    });

    const suffix = query.toString() ? `?${query.toString()}` : "";

    return request<ProductListResponse>(`/products${suffix}`);
  },

  product: (id: number) =>
    request<{ product: Product }>(`/products/${id}`).then((r) => r.product),

  productReviews: (productId: number, page = 1) =>
    request<ProductReviewsResponse>(`/products/${productId}/reviews?page=${page}`),

  myProductReview: (token: string, productId: number) =>
    request<{ review: ProductReview | null }>(
      `/products/${productId}/reviews/mine`,
      {},
      token,
    ).then((r) => r.review),

  createProductReview: (
    token: string,
    productId: number,
    payload: { rating: number; comment: string },
  ) =>
    request<{ review: ProductReview; message: string }>(
      `/products/${productId}/reviews`,
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ).then((r) => r.review),

  updateProductReview: (
    token: string,
    productId: number,
    reviewId: number,
    payload: { rating: number; comment: string },
  ) =>
    request<{ review: ProductReview; message: string }>(
      `/products/${productId}/reviews/${reviewId}`,
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ).then((r) => r.review),

  deleteProductReview: (token: string, productId: number, reviewId: number) =>
    request<{ message: string }>(
      `/products/${productId}/reviews/${reviewId}`,
      { method: "DELETE" },
      token,
    ),

  register: (payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    account_type: AccountType;
  }) => request<AuthResponse>("/auth/register", { method: "POST", body: JSON.stringify(payload) }),

  login: (payload: { email: string; password: string }) =>
    request<AuthResponse>("/auth/login", { method: "POST", body: JSON.stringify(payload) }),

  loginWithGoogle: (idToken: string) =>
    request<AuthResponse>("/auth/google", {
      method: "POST",
      body: JSON.stringify({ id_token: idToken }),
    }),

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
      direct_payment: boolean;
      payment_providers: PaymentProviderOption[];
    }>("/checkout", {}, token),

  checkoutInstallments: (token: string) =>
    request<{
      installments: InstallmentOption[];
      direct_payment: boolean;
    }>("/checkout/installments", {}, token),

  checkout: (
    token: string,
    payload: Record<string, string | number | null | undefined>,
  ) =>
    request<{ order: Order; message: string }>(
      "/checkout",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  initIyzicoPayment: (token: string, orderId: number, installment = 1) =>
    request<{
      token: string;
      payment_page_url: string;
      conversation_id: string;
      redirect_url?: string;
    }>(`/orders/${orderId}/payments/iyzico/init`, {
      method: "POST",
      body: JSON.stringify({ installment }),
    }, token),

  initStripePayment: (token: string, orderId: number) =>
    request<{
      token: string;
      payment_page_url: string;
      session_id: string;
    }>(`/orders/${orderId}/payments/stripe/init`, {
      method: "POST",
    }, token),

  order: (token: string, orderId: number) =>
    request<OrderDetailResponse>(`/orders/${orderId}`, {}, token),

  orderInstallments: (token: string, orderId: number) =>
    request<{ installments: InstallmentOption[]; direct_payment: boolean }>(
      `/orders/${orderId}/installments`,
      {},
      token,
    ),

  orders: (token: string) =>
    request<{ orders: Order[] }>("/orders", {}, token).then((r) => r.orders),

  addresses: (token: string) =>
    request<{ addresses: Address[] }>("/addresses", {}, token).then((r) => r.addresses),

  createAddress: (token: string, payload: Partial<Address>) =>
    request<{ address: Address; message: string }>(
      "/addresses",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ).then((r) => r.address),

  updateAddress: (token: string, addressId: number, payload: Partial<Address>) =>
    request<{ address: Address; message: string }>(
      `/addresses/${addressId}`,
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ).then((r) => r.address),

  deleteAddress: (token: string, addressId: number) =>
    request<{ message: string }>(`/addresses/${addressId}`, { method: "DELETE" }, token),

  setDefaultAddress: (token: string, addressId: number) =>
    request<{ address: Address; message: string }>(
      `/addresses/${addressId}/default`,
      { method: "PATCH" },
      token,
    ).then((r) => r.address),

  wishlist: (token: string) =>
    request<{ products: Product[] }>("/wishlist", {}, token).then((r) => r.products),

  wishlistIds: (token: string) =>
    request<{ product_ids: number[] }>("/wishlist/ids", {}, token).then(
      (r) => r.product_ids,
    ),

  addToWishlist: (token: string, productId: number) =>
    request<{ message: string; product_id: number }>(
      `/wishlist/products/${productId}`,
      { method: "POST" },
      token,
    ),

  removeFromWishlist: (token: string, productId: number) =>
    request<{ message: string; product_id: number }>(
      `/wishlist/products/${productId}`,
      { method: "DELETE" },
      token,
    ),

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

  adminUploadProductCover: (token: string, productId: number, file: File) => {
    const formData = new FormData();
    formData.append("image", file);

    return request<{ product: AdminProduct; message: string }>(
      `/admin/products/${productId}/cover-image`,
      { method: "POST", body: formData },
      token,
    );
  },

  adminUpdateStock: (token: string, stockId: number, quantity: number) =>
    request<{ stock: { id: number; quantity: number }; message: string }>(
      `/admin/stocks/${stockId}`,
      { method: "PATCH", body: JSON.stringify({ quantity }) },
      token,
    ),

  adminSummary: (token: string) =>
    request<{ summary: AdminSummary }>("/admin/summary", {}, token).then(
      (response) => response.summary,
    ),

  adminOrders: (token: string) =>
    request<{ orders: AdminOrder[] }>("/admin/orders", {}, token).then(
      (response) => response.orders,
    ),

  adminOrder: (token: string, orderId: number) =>
    request<{ order: AdminOrder }>(`/admin/orders/${orderId}`, {}, token).then(
      (response) => response.order,
    ),

  adminUpdateOrderStatus: (token: string, orderId: number, status: string) =>
    request<{ order: AdminOrder; message: string }>(
      `/admin/orders/${orderId}`,
      { method: "PATCH", body: JSON.stringify({ status }) },
      token,
    ).then((response) => response.order),
};

export function formatPrice(value: number): string {
  const [integerPart, decimalPart = "00"] = value.toFixed(2).split(".");
  const withSeparators = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

  return `${withSeparators},${decimalPart} ₺`;
}

export function formatOrderDate(value: string | null): string {
  if (!value) {
    return "—";
  }

  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
  if (!match) {
    return "—";
  }

  const [, year, month, day, hours, minutes] = match;

  return `${day}.${month}.${year} ${hours}:${minutes}`;
}
