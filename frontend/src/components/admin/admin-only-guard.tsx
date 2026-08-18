"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/context/auth-context";
import { isAdmin } from "@/lib/auth";

export function AdminOnlyGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { user, loading } = useAuth();

  useEffect(() => {
    if (loading) {
      return;
    }

    if (!isAdmin(user)) {
      router.replace("/admin");
    }
  }, [loading, router, user]);

  if (loading || !isAdmin(user)) {
    return (
      <div className="flex min-h-[40vh] items-center justify-center text-sm text-muted">
        Yükleniyor...
      </div>
    );
  }

  return <>{children}</>;
}
