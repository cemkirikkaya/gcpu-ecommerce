"use client";

type CouponFormFieldsProps = {
  defaultValues?: {
    code?: string;
    type?: "percent" | "fixed";
    value?: number;
    min_order_amount?: number | null;
    max_discount_amount?: number | null;
    usage_limit?: number | null;
    starts_at?: string | null;
    expires_at?: string | null;
    is_active?: boolean;
  };
};

function toDateTimeLocalValue(value?: string | null): string {
  if (!value) {
    return "";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "";
  }

  const offset = date.getTimezoneOffset();
  const local = new Date(date.getTime() - offset * 60_000);

  return local.toISOString().slice(0, 16);
}

export function CouponFormFields({ defaultValues }: CouponFormFieldsProps) {
  return (
    <div className="space-y-4 rounded-[1.5rem] border border-line bg-surface p-6">
      <input
        name="code"
        required
        defaultValue={defaultValues?.code ?? ""}
        placeholder="KUPONKODU"
        className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm uppercase outline-none focus:border-accent"
      />

      <div className="grid gap-4 md:grid-cols-2">
        <select
          name="type"
          defaultValue={defaultValues?.type ?? "percent"}
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        >
          <option value="percent">Yüzde indirim</option>
          <option value="fixed">Sabit tutar</option>
        </select>
        <input
          name="value"
          type="number"
          min={0.01}
          step="0.01"
          required
          defaultValue={defaultValues?.value ?? ""}
          placeholder="İndirim değeri"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <input
          name="min_order_amount"
          type="number"
          min={0}
          step="0.01"
          defaultValue={defaultValues?.min_order_amount ?? ""}
          placeholder="Minimum sepet tutarı (opsiyonel)"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          name="max_discount_amount"
          type="number"
          min={0}
          step="0.01"
          defaultValue={defaultValues?.max_discount_amount ?? ""}
          placeholder="Maks. indirim tutarı (opsiyonel)"
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
      </div>

      <input
        name="usage_limit"
        type="number"
        min={1}
        defaultValue={defaultValues?.usage_limit ?? ""}
        placeholder="Kullanım limiti (opsiyonel)"
        className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
      />

      <div className="grid gap-4 md:grid-cols-2">
        <input
          name="starts_at"
          type="datetime-local"
          defaultValue={toDateTimeLocalValue(defaultValues?.starts_at)}
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
        <input
          name="expires_at"
          type="datetime-local"
          defaultValue={toDateTimeLocalValue(defaultValues?.expires_at)}
          className="w-full rounded-full border border-line bg-background px-5 py-3 text-sm outline-none focus:border-accent"
        />
      </div>

      <label className="flex items-center gap-3 text-sm">
        <input
          name="is_active"
          type="checkbox"
          defaultChecked={defaultValues?.is_active ?? true}
          className="h-4 w-4 rounded border-line"
        />
        Kupon aktif
      </label>
    </div>
  );
}
