import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { fetchMe, logout } from '../api/endpoints';
import type { Me } from '../api/types';

interface AuthContextValue {
  user: Me | null;
  loading: boolean;
  hasRole: (...roles: string[]) => boolean;
  signOut: () => Promise<void>;
  refresh: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<Me | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async (): Promise<void> => {
    setLoading(true);
    try {
      const me = await fetchMe();
      setUser(me);
    } catch {
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const hasRole = useCallback(
    (...roles: string[]): boolean => {
      if (!user) return false;
      const granted = user.roles.map((role) => role.toLowerCase());
      return roles.some((role) => granted.includes(role.toLowerCase()));
    },
    [user],
  );

  const signOut = useCallback(async (): Promise<void> => {
    try {
      await logout();
    } catch {
      /* the login page clears a stale session anyway */
    } finally {
      window.location.replace('/TroyLeaveFiling/index.php');
    }
  }, []);

  const value = useMemo(
    () => ({ user, loading, hasRole, signOut, refresh }),
    [user, loading, hasRole, signOut, refresh],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }

  return context;
}
