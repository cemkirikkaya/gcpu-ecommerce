export type UserRole = "admin" | "vendor" | "customer";

export type AccountType = "customer" | "company";

export type User = {
  id: number;
  name: string;
  email: string;
  role: UserRole;
};

export type VariantAttribute = {
  name: string;
  value: string;
};

export type ProductVariant = {
  id: number;
  sku: string;
  label: string;
  attributes: VariantAttribute[];
  price: number;
  available_quantity: number;
  image_url: string | null;
};

export type VariantGroup = {
  label: string;
  variants: ProductVariant[];
};

export type Product = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  category?: {
    id: number;
    name: string;
    slug: string;
  } | null;
  image_url: string | null;
  base_variant?: string | null;
  variant_groups?: VariantGroup[];
  variants?: ProductVariant[];
  review_summary?: ProductReviewSummary;
};

export type ProductReviewSummary = {
  average: number;
  count: number;
};

export type ProductReview = {
  id: number;
  rating: number;
  comment: string;
  created_at: string | null;
  user?: {
    id: number;
    name: string;
  };
  is_own?: boolean;
};

export type ProductReviewsResponse = {
  reviews: ProductReview[];
  summary: ProductReviewSummary;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  products: Product[];
  children?: Category[];
};

export type Catalog = {
  shop_name: string;
  reservation_minutes: number;
  categories: Category[];
  uncategorized: Product[];
};

export type CatalogCategoryOption = {
  id: number;
  name: string;
  slug: string;
};

export type CategorySummary = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  products_count: number;
};

export type CategoryDetail = CategorySummary & {
  parent: Pick<CategorySummary, "id" | "name" | "slug"> | null;
  children: CategorySummary[];
};

export type CategoryDetailResponse = {
  category: CategoryDetail;
};

export type ProductListResponse = {
  products: Product[];
  categories: CatalogCategoryOption[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type ProductListParams = {
  search?: string;
  category?: string;
  min_price?: number;
  max_price?: number;
  sort?: "latest" | "price_asc" | "price_desc" | "name_asc";
  page?: number;
  per_page?: number;
};

export type CartItem = {
  id: number;
  quantity: number;
  reserved_until: string | null;
  unit_price: number;
  subtotal: number;
  variant: ProductVariant | null;
};

export type Cart = {
  id: number;
  item_count: number;
  total: number;
  reservation_minutes: number;
  items: CartItem[];
};

export type Address = {
  id: number;
  title: string | null;
  first_name: string;
  last_name: string;
  phone: string | null;
  address_line_1: string;
  address_line_2: string | null;
  city: string;
  state: string | null;
  postal_code: string;
  country: string;
  is_default: boolean;
  full_name: string;
  full_address: string;
};

export type PaymentProviderOption = {
  id: "iyzico" | "stripe";
  label: string;
  supports_direct: boolean;
  supports_installments: boolean;
};

export type InstallmentOption = {
  number: number;
  label: string;
  monthly_price: string;
  total_price: string;
};

export type PaymentOptions = {
  direct_payment: boolean;
  payment_providers: PaymentProviderOption[];
};

export type OrderCancellationRequest = {
  id: number;
  order_id: number;
  message: string;
  status: "pending" | "approved" | "rejected";
  status_label: string;
  admin_note?: string | null;
  refund_reference?: string | null;
  created_at: string | null;
  reviewed_at?: string | null;
  customer?: {
    id: number;
    name: string;
    email: string;
  };
  order?: Order;
};

export type OrderDetailResponse = {
  order: Order;
  payment_options?: PaymentOptions;
};

export type Order = {
  id: number;
  total_price: number;
  paid_price?: number | null;
  installment?: number;
  iyzico_payment_id?: string | null;
  stripe_checkout_session_id?: string | null;
  stripe_payment_intent_id?: string | null;
  status: string;
  status_label: string;
  payment_status: string;
  payment_status_label: string;
  payment_provider?: string | null;
  created_at: string | null;
  address?: Address | null;
  cancellation_request?: OrderCancellationRequest | null;
  items?: Array<{
    id: number;
    quantity: number;
    price: number;
    subtotal: number;
    product_name?: string;
    variant_label?: string;
  }>;
};

export type AuthResponse = {
  user: User;
  token: string;
};

export type AdminCategory = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
};

export type AdminProductVariant = {
  id: number;
  sku: string;
  label: string;
  stock_id: number | null;
  quantity: number;
  available_quantity: number;
  color?: string | null;
  memory?: string | null;
  model?: string | null;
  size?: string | null;
};

export type AdminProduct = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  category?: {
    id: number;
    name: string;
    slug: string;
  } | null;
  variants?: AdminProductVariant[];
  image_url?: string | null;
  vendor_email?: string | null;
  created_at?: string | null;
};

export type CatalogVariantInput = {
  sku: string;
  stock: number;
  color?: string;
  memory?: string;
  model?: string;
  size?: string;
};

export type AdminSummary = {
  products_count: number;
  total_stock: number;
  low_stock_variants: number;
  orders_count: number;
  items_sold: number;
  revenue: number;
  pending_cancellation_requests?: number;
  charts?: AdminCharts;
};

export type AdminCharts = {
  revenue_trend: Array<{
    date: string;
    label: string;
    revenue: number;
    orders: number;
  }>;
  orders_by_status: Array<{
    status: string;
    label: string;
    count: number;
  }>;
  top_products: Array<{
    name: string;
    revenue: number;
    quantity: number;
  }>;
};

export type CancellationRequestsResponse = {
  cancellation_requests: OrderCancellationRequest[];
  pending_count: number;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type AdminOrder = {
  id: number;
  total_price: number;
  order_total?: number | null;
  vendor_subtotal?: number | null;
  status: string;
  status_label: string;
  payment_status: string;
  payment_status_label: string;
  created_at: string | null;
  items_count?: number;
  address?: Address | null;
  items?: Array<{
    id: number;
    quantity: number;
    price: number;
    subtotal: number;
    product_name?: string;
    variant_label?: string | null;
    vendor_email?: string | null;
  }>;
};

export type ApiError = {
  message: string;
  errors?: Record<string, string[]>;
};
