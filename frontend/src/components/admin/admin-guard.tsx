"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/context/auth-context";
import { getHomePathForUser, isAdmin } from "@/lib/auth";

export function AdminGuard({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { user, token, loading } = useAuth();

  useEffect(() => {
    if (loading) return;

    if (!token || !user) {
      router.replace("/login");
      return;
    }

    if (!isAdmin(user)) {
      router.replace(getHomePathForUser(user));
    }
  }, [loading, router, token, user]);

  if (loading || !user || !isAdmin(user)) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center text-sm text-muted">
        Yükleniyor...
      </div>
    );
  }

  return <>{children}</>;
}
