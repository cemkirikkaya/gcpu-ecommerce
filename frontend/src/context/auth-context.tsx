"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

import { api } from "@/lib/api";
import { clearAuth, getStoredUser, getToken, persistAuth } from "@/lib/auth-storage";
import type { AccountType, User } from "@/lib/types";

type AuthContextValue = {
  user: User | null;
  token: string | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<User>;
  register: (
    name: string,
    email: string,
    password: string,
    accountType: AccountType,
  ) => Promise<User>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

function readStoredAuth(): {
  user: User | null;
  token: string | null;
} {
  const storedToken = getToken();
  const storedUser = getStoredUser();

  if (!storedToken || !storedUser) {
    return { user: null, token: null };
  }

  return {
    user: storedUser,
    token: storedToken,
  };
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const stored = readStoredAuth();

    if (!stored.token || !stored.user) {
      setLoading(false);
      return;
    }

    setToken(stored.token);
    setUser(stored.user);

    api
      .me(stored.token)
      .then((freshUser) => {
        setUser(freshUser);
        persistAuth(stored.token!, freshUser);
      })
      .catch(() => {
        clearAuth();
        setToken(null);
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const response = await api.login({ email, password });
    persistAuth(response.token, response.user);
    setToken(response.token);
    setUser(response.user);
    setLoading(false);
    return response.user;
  }, []);

  const register = useCallback(
    async (
      name: string,
      email: string,
      password: string,
      accountType: AccountType,
    ) => {
      const response = await api.register({
        name,
        email,
        password,
        password_confirmation: password,
        account_type: accountType,
      });
      persistAuth(response.token, response.user);
      setToken(response.token);
      setUser(response.user);
      setLoading(false);
      return response.user;
    },
    [],
  );

  const logout = useCallback(async () => {
    if (token) {
      try {
        await api.logout(token);
      } catch {
        // ignore
      }
    }
    clearAuth();
    setToken(null);
    setUser(null);
    setLoading(false);
  }, [token]);

  const refreshUser = useCallback(async () => {
    if (!token) return;
    const freshUser = await api.me(token);
    setUser(freshUser);
    persistAuth(token, freshUser);
  }, [token]);

  const value = useMemo(
    () => ({ user, token, loading, login, register, logout, refreshUser }),
    [user, token, loading, login, register, logout, refreshUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
}
