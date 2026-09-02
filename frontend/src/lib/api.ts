import type {
  Address,
  AccountType,
  AdminCategory,
  AdminCoupon,
  AdminOrder,
  AdminPost,
  AdminProduct,
  AdminSummary,
  BulkProductImportResult,
  BulkProductUpdateResult,
  SearchAnalytics,
  ApiError,
  AuthResponse,
  CancellationRequestsResponse,
  Cart,
  Catalog,
  CatalogVariantInput,
  CategoryDetailResponse,
  InstallmentOption,
  OrderCancellationRequest,
  OrderReturnRequest,
  Order,
  OrderDetailResponse,
  PaymentProviderOption,
  Product,
  ProductListParams,
  ProductListResponse,
  ProductSearchPopularResponse,
  ProductSearchSuggestResponse,
  ProductReview,
  ProductReviewsResponse,
  MyProductReviewResponse,
  Post,
  PostListResponse,
  ReturnRequestsResponse,
  User,
} from "./types";

const getApiUrl = (): string => {
  const publicUrl =
    process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api";

  if (typeof window === "undefined") {
    const internalUrl = process.env.API_INTERNAL_URL;

    if (
      internalUrl?.includes("laravel.test") &&
      process.env.LARAVEL_SAIL !== "1"
    ) {
      return publicUrl;
    }

    return internalUrl ?? publicUrl;
  }

  return publicUrl;
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

  productSearchSuggest: (q: string, limit = 8) => {
    const query = new URLSearchParams({ q, limit: String(limit) });

    return request<ProductSearchSuggestResponse>(`/products/search/suggest?${query.toString()}`);
  },

  productSearchPopular: () => request<ProductSearchPopularResponse>("/products/search/popular"),

  product: (id: number) =>
    request<{ product: Product }>(`/products/${id}`).then((r) => r.product),

  productCrossSell: (id: number) =>
    request<{ products: Product[] }>(`/products/${id}/cross-sell`).then((r) => r.products),

  posts: (page = 1) => request<PostListResponse>(`/posts?page=${page}`),

  post: (slug: string) => request<{ post: Post }>(`/posts/${slug}`).then((r) => r.post),

  productReviews: (productId: number, page = 1) =>
    request<ProductReviewsResponse>(`/products/${productId}/reviews?page=${page}`),

  myProductReview: (token: string, productId: number) =>
    request<MyProductReviewResponse>(
      `/products/${productId}/reviews/mine`,
      {},
      token,
    ),

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

  updateProfile: (token: string, payload: { name: string; email: string }) =>
    request<{ user: User; message: string }>(
      "/auth/profile",
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ),

  updatePassword: (
    token: string,
    payload: {
      current_password?: string;
      password: string;
      password_confirmation: string;
    },
  ) =>
    request<{ user: User; message: string }>(
      "/auth/password",
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ),

  forgotPassword: (email: string) =>
    request<{ message: string }>("/auth/forgot-password", {
      method: "POST",
      body: JSON.stringify({ email }),
    }),

  resetPassword: (payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) =>
    request<{ message: string }>("/auth/reset-password", {
      method: "POST",
      body: JSON.stringify(payload),
    }),

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

  applyCartCoupon: (token: string, code: string) =>
    request<{ cart: Cart; message: string }>(
      "/cart/coupon",
      { method: "POST", body: JSON.stringify({ code }) },
      token,
    ),

  removeCartCoupon: (token: string) =>
    request<{ cart: Cart; message: string }>("/cart/coupon", { method: "DELETE" }, token),

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

  downloadOrderInvoice: async (token: string, orderId: number) => {
    const response = await fetch(`${getApiUrl()}/orders/${orderId}/invoice`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/pdf",
      },
      cache: "no-store",
    });

    if (!response.ok) {
      const payload = (await response.json().catch(() => null)) as ApiError | null;
      throw new ApiClientError(payload?.message ?? "Fatura indirilemedi.", response.status);
    }

    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `fatura-siparis-${orderId}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  },

  requestOrderCancellation: (token: string, orderId: number, message: string) =>
    request<{ cancellation_request: OrderCancellationRequest; message: string }>(
      `/orders/${orderId}/cancellation-request`,
      { method: "POST", body: JSON.stringify({ message }) },
      token,
    ).then((response) => response),

  adminCancellationRequests: (token: string, status = "pending") =>
    request<CancellationRequestsResponse>(
      `/admin/cancellation-requests?status=${status}`,
      {},
      token,
    ),

  adminApproveCancellation: (token: string, requestId: number, adminNote?: string) =>
    request<{ cancellation_request: OrderCancellationRequest; message: string }>(
      `/admin/cancellation-requests/${requestId}/approve`,
      { method: "POST", body: JSON.stringify({ admin_note: adminNote ?? null }) },
      token,
    ),

  adminRejectCancellation: (token: string, requestId: number, adminNote?: string) =>
    request<{ cancellation_request: OrderCancellationRequest; message: string }>(
      `/admin/cancellation-requests/${requestId}/reject`,
      { method: "POST", body: JSON.stringify({ admin_note: adminNote ?? null }) },
      token,
    ),

  requestOrderReturn: (
    token: string,
    orderId: number,
    payload: {
      type: "return" | "exchange";
      message: string;
      items: Array<{
        order_item_id: number;
        quantity: number;
        replacement_product_variant_id?: number | null;
      }>;
    },
  ) =>
    request<{ return_request: OrderReturnRequest; message: string }>(
      `/orders/${orderId}/return-request`,
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  adminReturnRequests: (token: string, status = "pending") =>
    request<ReturnRequestsResponse>(`/admin/return-requests?status=${status}`, {}, token),

  adminApproveReturn: (token: string, requestId: number, adminNote?: string) =>
    request<{ return_request: OrderReturnRequest; message: string }>(
      `/admin/return-requests/${requestId}/approve`,
      { method: "POST", body: JSON.stringify({ admin_note: adminNote ?? null }) },
      token,
    ),

  adminRejectReturn: (token: string, requestId: number, adminNote?: string) =>
    request<{ return_request: OrderReturnRequest; message: string }>(
      `/admin/return-requests/${requestId}/reject`,
      { method: "POST", body: JSON.stringify({ admin_note: adminNote ?? null }) },
      token,
    ),

  adminReceiveReturn: (token: string, requestId: number) =>
    request<{ return_request: OrderReturnRequest; message: string }>(
      `/admin/return-requests/${requestId}/receive`,
      { method: "POST" },
      token,
    ),

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

  stockAlertVariantIds: (token: string) =>
    request<{ variant_ids: number[] }>("/stock-alerts/variant-ids", {}, token).then(
      (response) => response.variant_ids,
    ),

  subscribeStockAlert: (token: string, variantId: number) =>
    request<{ message: string; variant_id: number }>(
      `/stock-alerts/variants/${variantId}`,
      { method: "POST" },
      token,
    ),

  unsubscribeStockAlert: (token: string, variantId: number) =>
    request<{ message: string; variant_id: number }>(
      `/stock-alerts/variants/${variantId}`,
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

  adminDownloadProductBulkTemplate: async (
    token: string,
    type: "import" | "update",
  ) => {
    const response = await fetch(`${getApiUrl()}/admin/products/bulk/template/${type}`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "text/csv",
      },
      cache: "no-store",
    });

    if (!response.ok) {
      throw new ApiClientError("CSV şablonu indirilemedi.", response.status);
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download =
      type === "import" ? "product-import-template.csv" : "product-update-template.csv";
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },

  adminBulkImportProducts: (token: string, file: File) => {
    const formData = new FormData();
    formData.append("file", file);

    return request<{ result: BulkProductImportResult; message: string }>(
      "/admin/products/bulk/import",
      { method: "POST", body: formData },
      token,
    );
  },

  adminBulkUpdateProducts: (token: string, file: File) => {
    const formData = new FormData();
    formData.append("file", file);

    return request<{ result: BulkProductUpdateResult; message: string }>(
      "/admin/products/bulk/update",
      { method: "POST", body: formData },
      token,
    );
  },

  adminUploadProductCover: (token: string, productId: number, file: File) => {
    const formData = new FormData();
    formData.append("image", file);

    return request<{ product: AdminProduct; message: string }>(
      `/admin/products/${productId}/cover-image`,
      { method: "POST", body: formData },
      token,
    );
  },

  adminUploadProductImage: (token: string, productId: number, file: File) => {
    const formData = new FormData();
    formData.append("image", file);

    return request<{ product: AdminProduct; message: string }>(
      `/admin/products/${productId}/images`,
      { method: "POST", body: formData },
      token,
    );
  },

  adminDeleteProductImage: (token: string, productId: number, imageId: number) =>
    request<{ product: AdminProduct; message: string }>(
      `/admin/products/${productId}/images/${imageId}`,
      { method: "DELETE" },
      token,
    ),

  adminSetProductCoverImage: (token: string, productId: number, imageId: number) =>
    request<{ product: AdminProduct; message: string }>(
      `/admin/products/${productId}/images/${imageId}/cover`,
      { method: "POST" },
      token,
    ),

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

  adminSearchAnalytics: (
    token: string,
    params?: {
      limit?: number;
      days?: number;
    },
  ) => {
    const searchParams = new URLSearchParams();

    if (params?.limit) {
      searchParams.set("limit", String(params.limit));
    }

    if (params?.days) {
      searchParams.set("days", String(params.days));
    }

    const query = searchParams.toString();

    return request<{ analytics: SearchAnalytics }>(
      `/admin/search-analytics${query ? `?${query}` : ""}`,
      {},
      token,
    ).then((response) => response.analytics);
  },

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

  adminCreateOrderShipment: (token: string, orderId: number) =>
    request<{ order: AdminOrder; message: string }>(
      `/admin/orders/${orderId}/shipment`,
      { method: "POST" },
      token,
    ).then((response) => response.order),

  adminSyncOrderShipment: (token: string, orderId: number) =>
    request<{ order: AdminOrder; message: string }>(
      `/admin/orders/${orderId}/shipment/sync`,
      { method: "POST" },
      token,
    ).then((response) => response.order),

  adminPosts: (token: string) =>
    request<{ posts: AdminPost[] }>("/admin/posts", {}, token).then(
      (response) => response.posts,
    ),

  adminPost: (token: string, postId: number) =>
    request<{ post: AdminPost }>(`/admin/posts/${postId}`, {}, token).then(
      (response) => response.post,
    ),

  adminCreatePost: (
    token: string,
    payload: {
      title: string;
      slug: string;
      excerpt?: string | null;
      content: string;
      published_at?: string | null;
    },
  ) =>
    request<{ post: AdminPost; message: string }>(
      "/admin/posts",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  adminUpdatePost: (
    token: string,
    postId: number,
    payload: {
      title: string;
      slug: string;
      excerpt?: string | null;
      content: string;
      published_at?: string | null;
    },
  ) =>
    request<{ post: AdminPost; message: string }>(
      `/admin/posts/${postId}`,
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ).then((response) => response.post),

  adminDeletePost: (token: string, postId: number) =>
    request<{ message: string }>(`/admin/posts/${postId}`, { method: "DELETE" }, token),

  adminCoupons: (token: string) =>
    request<{ coupons: AdminCoupon[] }>("/admin/coupons", {}, token).then(
      (response) => response.coupons,
    ),

  adminCoupon: (token: string, couponId: number) =>
    request<{ coupon: AdminCoupon }>(`/admin/coupons/${couponId}`, {}, token).then(
      (response) => response.coupon,
    ),

  adminCreateCoupon: (
    token: string,
    payload: {
      code: string;
      type: "percent" | "fixed";
      value: number;
      min_order_amount?: number | null;
      max_discount_amount?: number | null;
      usage_limit?: number | null;
      starts_at?: string | null;
      expires_at?: string | null;
      is_active?: boolean;
    },
  ) =>
    request<{ coupon: AdminCoupon; message: string }>(
      "/admin/coupons",
      { method: "POST", body: JSON.stringify(payload) },
      token,
    ),

  adminUpdateCoupon: (
    token: string,
    couponId: number,
    payload: {
      code?: string;
      type?: "percent" | "fixed";
      value?: number;
      min_order_amount?: number | null;
      max_discount_amount?: number | null;
      usage_limit?: number | null;
      starts_at?: string | null;
      expires_at?: string | null;
      is_active?: boolean;
    },
  ) =>
    request<{ coupon: AdminCoupon; message: string }>(
      `/admin/coupons/${couponId}`,
      { method: "PUT", body: JSON.stringify(payload) },
      token,
    ).then((response) => response.coupon),

  adminDeleteCoupon: (token: string, couponId: number) =>
    request<{ message: string }>(`/admin/coupons/${couponId}`, { method: "DELETE" }, token),
};

export function formatPrice(value: number): string {
  const [integerPart, decimalPart = "00"] = value.toFixed(2).split(".");
  const withSeparators = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

  return `${withSeparators},${decimalPart} ₺`;
}

export function formatEstimatedDeliveryDate(value: string | null): string {
  if (!value) {
    return "—";
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat("tr-TR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZone: "Europe/Istanbul",
  }).format(date);
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

export function formatPublishDate(value: string | null): string {
  if (!value) {
    return "—";
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return date.toLocaleDateString("tr-TR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
