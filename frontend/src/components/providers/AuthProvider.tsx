"use client";

import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { api, tokenStore } from "@/lib/api";
import { disconnectEcho } from "@/lib/echo";
import type { User } from "@/lib/types";

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  /** Replace the signed-in user's data, e.g. after a realtime role change. */
  updateUser: (user: User) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const restore = tokenStore.get() ? api<{ data: User }>("/auth/me").then((res) => setUser(res.data)) : Promise.resolve();
    restore.catch(() => tokenStore.clear()).finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const res = await api<{ access_token: string; user: User }>("/auth/login", { method: "POST", body: { email, password } });
    tokenStore.set(res.access_token);
    setUser(res.user);
  }, []);

  const logout = useCallback(async () => {
    await api("/auth/logout", { method: "POST" }).catch(() => undefined);
    tokenStore.clear();
    disconnectEcho();
    setUser(null);
  }, []);

  const updateUser = useCallback((next: User) => setUser(next), []);

  return <AuthContext.Provider value={{ user, loading, login, logout, updateUser }}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
  return ctx;
}
