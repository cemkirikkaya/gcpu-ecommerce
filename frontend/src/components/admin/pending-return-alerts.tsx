"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { useAuth } from "@/context/auth-context";
import { api } from "@/lib/api";
import { isAdmin } from "@/lib/auth";
import type { OrderReturnRequest } from "@/lib/types";

export function PendingReturnAlerts() {
  const { token, user } = useAuth();
  const [requests, setRequests] = useState<OrderReturnRequest[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) {
      return;
    }

    api
      .adminReturnRequests(token, "pending")
      .then((response) => setRequests(response.return_requests.slice(0, 3)))
      .catch(() => setRequests([]))
      .finally(() => setLoading(false));
  }, [token]);

  if (loading || requests.length === 0) {
    return null;
  }

  const title = isAdmin(user)
    ? "Bekleyen iade / değişim talepleri"
    : "Müşterilerden iade talebi var";

  return (
    <div className="mt-8 rounded-[1.5rem] border border-sky-200 bg-sky-50 p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-[0.3em] text-sky-700">İade</p>
          <h2 className="mt-2 font-display text-2xl font-semibold text-sky-950">{title}</h2>
          <p className="mt-2 text-sm text-sky-900/80">
            {isAdmin(user)
              ? "Onayladığınızda iade kargo etiketi oluşur. Ürün gelince stok girişi ve iade/değişim tamamlanır."
              : "Ürünlerinize ait iade taleplerini aşağıda görebilirsiniz. Onay için yönetici bekleniyor."}
          </p>
        </div>
        <Link
          href="/admin/return-requests"
          className="rounded-full border border-sky-300 bg-white px-5 py-2.5 text-sm text-sky-950 transition hover:border-sky-500"
        >
          Tümünü gör
        </Link>
      </div>

      <div className="mt-5 space-y-3">
        {requests.map((request) => (
          <div
            key={request.id}
            className="rounded-[1.25rem] border border-sky-200 bg-white px-5 py-4"
          >
            <div className="flex flex-wrap items-center justify-between gap-3">
              <p className="font-medium text-sky-950">
                Sipariş #{request.order_id}
                {request.customer?.name ? ` · ${request.customer.name}` : ""}
                {` · ${request.type_label}`}
              </p>
              <span className="text-xs uppercase tracking-[0.2em] text-sky-700">
                {request.status_label}
              </span>
            </div>
            <p className="mt-3 text-sm leading-7 text-sky-950/90">{request.message}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
