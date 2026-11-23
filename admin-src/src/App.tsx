import type { ReactNode } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import { ErrorBoundary } from './components/ErrorBoundary';
import { Layout } from './components/Layout';
import { ToastProvider } from './components/Toast';
import { Spinner } from './components/ui';
import { AssignmentsPage } from './pages/AssignmentsPage';
import { AttendancePage } from './pages/AttendancePage';
import { BranchesPage } from './pages/BranchesPage';
import { HomeLocationsPage } from './pages/HomeLocationsPage';

function AccessDenied() {
  return (
    <div className="py-20 text-center">
      <p className="text-sm font-medium text-text">You do not have access to this page.</p>
      <p className="mt-1 text-xs text-muted">
        Contact an administrator if you believe this is a mistake.
      </p>
      <a
        href="/TroyLeaveFiling/dashboard.php"
        className="mt-4 inline-flex text-sm font-semibold text-primary hover:underline"
      >
        Back to the dashboard
      </a>
    </div>
  );
}

function RequireAuth({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <Spinner label="Checking session…" />;
  }

  if (!user) {
    return <AccessDenied />;
  }

  return <>{children}</>;
}

function RequireRole({ roles, children }: { roles: string[]; children: ReactNode }) {
  const { hasRole } = useAuth();

  if (!hasRole(...roles)) {
    return <AccessDenied />;
  }

  return <>{children}</>;
}

function Shell({ children }: { children: ReactNode }) {
  return (
    <RequireAuth>
      <Layout>{children}</Layout>
    </RequireAuth>
  );
}

function HomeRedirect() {
  const { hasRole } = useAuth();

  if (hasRole('Admin', 'Approver')) return <Navigate to="/attendance" replace />;

  return <AccessDenied />;
}

function AppRoutes() {
  return (
    <Routes>
      <Route
        path="/"
        element={
          <Shell>
            <HomeRedirect />
          </Shell>
        }
      />
      <Route
        path="/attendance"
        element={
          <Shell>
            <RequireRole roles={['Admin', 'Approver']}>
              <AttendancePage />
            </RequireRole>
          </Shell>
        }
      />
      <Route
        path="/branches"
        element={
          <Shell>
            <RequireRole roles={['Admin']}>
              <BranchesPage />
            </RequireRole>
          </Shell>
        }
      />
      <Route
        path="/assignments"
        element={
          <Shell>
            <RequireRole roles={['Admin']}>
              <AssignmentsPage />
            </RequireRole>
          </Shell>
        }
      />
      <Route
        path="/home-locations"
        element={
          <Shell>
            <RequireRole roles={['Admin', 'Approver']}>
              <HomeLocationsPage />
            </RequireRole>
          </Shell>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <ErrorBoundary>
      <AuthProvider>
        <ToastProvider>
          <AppRoutes />
        </ToastProvider>
      </AuthProvider>
    </ErrorBoundary>
  );
}
