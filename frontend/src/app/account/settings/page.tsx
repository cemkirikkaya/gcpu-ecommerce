"use client";

import { useEffect } from "react";

import { AccountSettingsClient } from "./account-settings-client";
import { useAuth } from "@/context/auth-context";

export default function AccountSettingsPage() {
  const { token, loading } = useAuth();

  useEffect(() => {
    if (!loading && !token) {
      window.location.href = "/login";
    }
  }, [loading, token]);

  if (loading || !token) {
    return <p className="px-6 py-20 text-sm text-muted">Yükleniyor...</p>;
  }

  return <AccountSettingsClient />;
}
