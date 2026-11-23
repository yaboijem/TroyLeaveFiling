import { useEffect, useState, type ReactNode } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { Building2, Home, LogOut, Menu, Users, X } from 'lucide-react';
import { useAuth } from '../auth/AuthContext';
import { cn } from '../lib/cn';
import { Avatar } from './ui';

const NAV_ITEMS = [
  { to: '/branches', label: 'Branches', icon: <Building2 size={18} />, roles: ['Admin'] },
  { to: '/assignments', label: 'Assignments', icon: <Users size={18} />, roles: ['Admin'] },
  { to: '/home-locations', label: 'Home locations', icon: <Home size={18} />, roles: ['Admin', 'Approver'] },
];

function NavItems({ onNavigate }: { onNavigate?: () => void }) {
  const { hasRole } = useAuth();

  return (
    <nav className="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
      {NAV_ITEMS.filter((item) => hasRole(...item.roles)).map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          onClick={onNavigate}
          className={({ isActive }) =>
            cn(
              'group relative flex min-h-10 items-center gap-2.5 rounded-lg px-3 text-sm font-medium transition-colors',
              isActive
                ? 'bg-deep-2 text-teal-300'
                : 'text-slate-400 hover:bg-white/5 hover:text-slate-100',
            )
          }
        >
          {({ isActive }) => (
            <>
              {isActive && (
                <span
                  className="absolute inset-y-1.5 left-0 w-[3px] rounded-r bg-teal-400"
                  aria-hidden
                />
              )}
              <span className="shrink-0">{item.icon}</span>
              <span className="truncate">{item.label}</span>
            </>
          )}
        </NavLink>
      ))}
    </nav>
  );
}

function Brand() {
  return (
    <div className="flex items-center gap-2.5 border-b border-deep-border px-4 py-4">
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500/20 text-teal-300">
        <Building2 size={18} />
      </div>
      <div className="min-w-0">
        <div className="truncate text-sm font-bold leading-tight text-slate-100">Troy DTR Admin</div>
        <div className="truncate text-[11px] leading-tight text-slate-400">Time &amp; Attendance</div>
      </div>
    </div>
  );
}

export function Layout({ children }: { children: ReactNode }) {
  const { user, signOut } = useAuth();
  const location = useLocation();
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!mobileOpen) return;

    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setMobileOpen(false);
    };
    document.addEventListener('keydown', onKey);
    const previous = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = previous;
    };
  }, [mobileOpen]);

  const displayName = user?.employee?.full_name ?? user?.name ?? 'User';
  const activeLabel =
    NAV_ITEMS.find((item) => location.pathname.startsWith(item.to))?.label ?? 'Admin';

  const footer = (
    <div className="border-t border-deep-border px-3 py-3">
      <div className="mb-2 flex items-center gap-2.5 px-1">
        <Avatar name={displayName} size="sm" />
        <div className="min-w-0">
          <div className="truncate text-xs font-semibold text-slate-100">{displayName}</div>
          <div className="truncate font-mono text-[10px] text-slate-400">
            {user?.employee_id} · {user?.roles?.[0] ?? ''}
          </div>
        </div>
      </div>
      <button
        type="button"
        onClick={() => void signOut()}
        className="flex min-h-9 w-full cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-red-300"
      >
        <LogOut size={15} />
        Sign out
      </button>
    </div>
  );

  return (
    <div className="flex h-full min-h-0 bg-bg">
      <aside className="hidden w-60 shrink-0 flex-col border-r border-deep-border bg-deep lg:flex">
        <Brand />
        <NavItems />
        {footer}
      </aside>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <button
            type="button"
            aria-label="Close navigation"
            className="absolute inset-0 bg-deep/60"
            onClick={() => setMobileOpen(false)}
          />
          <aside className="absolute inset-y-0 left-0 flex w-[min(18rem,88vw)] flex-col bg-deep shadow-xl">
            <div className="absolute right-2 top-2 z-10">
              <button
                type="button"
                onClick={() => setMobileOpen(false)}
                aria-label="Close menu"
                className="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md text-slate-300 hover:bg-white/10"
              >
                <X size={18} />
              </button>
            </div>
            <Brand />
            <NavItems onNavigate={() => setMobileOpen(false)} />
            {footer}
          </aside>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-30 flex items-center gap-3 border-b border-border bg-card/95 px-3 py-2.5 backdrop-blur lg:hidden">
          <button
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label="Open navigation"
            className="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-border bg-card text-text"
          >
            <Menu size={18} />
          </button>
          <div className="min-w-0 flex-1">
            <div className="truncate text-sm font-semibold text-text">{activeLabel}</div>
            <div className="truncate text-[11px] text-muted">Troy DTR Admin</div>
          </div>
          <Avatar name={displayName} size="sm" />
        </header>

        <main className="min-h-0 flex-1 overflow-y-auto">
          <div className="page-shell">{children}</div>
        </main>
      </div>
    </div>
  );
}
