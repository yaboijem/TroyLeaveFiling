import { api } from './client';
import type {
  Branch,
  BranchPayload,
  BulkAssignmentPayload,
  BulkAssignmentResult,
  EmployeeRow,
  HomeLocationCounts,
  HomeLocationPage,
  HomeLocationReview,
  Me,
  PageMeta,
  Paginated,
} from './types';

interface PageEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

function toPaginated<T>(raw: PageEnvelope<T>): Paginated<T> {
  return {
    data: raw.data,
    current_page: raw.meta.current_page,
    per_page: raw.meta.per_page,
    total: raw.meta.total,
    last_page: raw.meta.last_page,
    from: raw.meta.from,
    to: raw.meta.to,
    next_page_url: raw.meta.current_page < raw.meta.last_page ? 'next' : null,
    prev_page_url: raw.meta.current_page > 1 ? 'prev' : null,
  };
}

export function fetchMe(): Promise<Me> {
  return api.get<{ data: Me }>('/api/me').then((result) => result.data);
}

export function logout(): Promise<void> {
  return api.post<unknown>('/api/logout', {}).then(() => undefined);
}

export function listBranches(
  params?: { status?: string; search?: string },
  signal?: AbortSignal,
): Promise<Branch[]> {
  return api
    .get<{ data: Branch[] }>('/api/admin/branches', params, signal)
    .then((result) => result.data);
}

export function createBranch(payload: BranchPayload): Promise<Branch> {
  return api
    .post<{ data: Branch }>('/api/admin/branches', payload)
    .then((result) => result.data);
}

export function updateBranch(id: number, payload: Partial<BranchPayload>): Promise<Branch> {
  return api
    .patch<{ data: Branch }>(`/api/admin/branches/${id}`, payload)
    .then((result) => result.data);
}

export function deactivateBranch(id: number): Promise<Branch> {
  return api
    .post<{ data: Branch }>(`/api/admin/branches/${id}/deactivate`, {})
    .then((result) => result.data);
}

export function activateBranch(id: number): Promise<Branch> {
  return api
    .post<{ data: Branch }>(`/api/admin/branches/${id}/activate`, {})
    .then((result) => result.data);
}

export function deleteBranch(id: number): Promise<void> {
  return api.delete<unknown>(`/api/admin/branches/${id}`).then(() => undefined);
}

export function listEmployees(
  params: Record<string, string | number | boolean | undefined>,
  signal?: AbortSignal,
): Promise<Paginated<EmployeeRow>> {
  return api
    .get<PageEnvelope<EmployeeRow>>('/api/admin/employees', params, signal)
    .then(toPaginated);
}

export function previewAssignment(payload: BulkAssignmentPayload): Promise<BulkAssignmentResult> {
  return api
    .post<{ data: BulkAssignmentResult }>('/api/admin/employees/assignments/preview', payload)
    .then((result) => result.data);
}

export function bulkAssign(payload: BulkAssignmentPayload): Promise<BulkAssignmentResult> {
  return api
    .post<{ data: BulkAssignmentResult }>('/api/admin/employees/assignments/bulk', payload)
    .then((result) => result.data);
}

export function listHomeLocations(
  params: Record<string, string | number | undefined>,
  signal?: AbortSignal,
): Promise<HomeLocationPage> {
  return api
    .get<PageEnvelope<HomeLocationReview> & { meta: PageMeta & { counts: HomeLocationCounts } }>(
      '/api/admin/home-locations',
      params,
      signal,
    )
    .then((raw) => ({
      ...toPaginated(raw),
      counts: raw.meta.counts,
    }));
}

export function getHomeLocation(id: number): Promise<HomeLocationReview> {
  return api
    .get<{ data: HomeLocationReview }>(`/api/admin/home-locations/${id}`)
    .then((result) => result.data);
}

export function reviewHomeLocation(
  id: number,
  payload: { action: 'approve' | 'reject'; note?: string },
): Promise<HomeLocationReview> {
  return api
    .post<{ data: HomeLocationReview }>(`/api/admin/home-locations/${id}/review`, payload)
    .then((result) => result.data);
}
