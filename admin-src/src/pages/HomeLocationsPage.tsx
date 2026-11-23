import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ApiError } from '../api/client';
import { getHomeLocation, listHomeLocations, reviewHomeLocation } from '../api/endpoints';
import type { HomeLocationPage, HomeLocationReview, ReviewStatus } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { DataTable, PaginationBar } from '../components/DataTable';
import { Drawer } from '../components/Drawer';
import { LocationMap } from '../components/LocationMap';
import { PageHeader } from '../components/PageHeader';
import {
  Badge,
  Button,
  Card,
  ErrorState,
  Field,
  Input,
  Textarea,
} from '../components/ui';
import { useToast } from '../components/Toast';
import { formatDateTime, formatHomeAddress } from '../lib/format';

type StatusFilter = ReviewStatus | 'all';

function statusTone(status: ReviewStatus): 'green' | 'amber' | 'red' {
  if (status === 'approved') return 'green';
  if (status === 'pending') return 'amber';
  return 'red';
}

export function HomeLocationsPage() {
  const { user } = useAuth();
  const { notify } = useToast();
  const [searchParams] = useSearchParams();

  const initialSearch = searchParams.get('search') ?? '';
  const [status, setStatus] = useState<StatusFilter>('pending');
  const [search, setSearch] = useState(initialSearch);
  const [query, setQuery] = useState(initialSearch);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);

  const [rows, setRows] = useState<HomeLocationReview[] | null>(null);
  const [paginated, setPaginated] = useState<HomeLocationPage | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<HomeLocationReview | null>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [note, setNote] = useState('');
  const [busy, setBusy] = useState<'approve' | 'reject' | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await listHomeLocations({
        status,
        search: query || undefined,
        page,
        per_page: perPage,
      });
      setRows(result.data);
      setPaginated(result);
    } catch (loadError) {
      setError(loadError instanceof ApiError ? loadError.message : 'Failed to load home locations.');
    } finally {
      setLoading(false);
    }
  }, [status, query, page, perPage]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    const param = searchParams.get('search') ?? '';

    if (param !== '') {
      setSearch(param);
      setQuery(param);
      setPage(1);
    }
  }, [searchParams]);

  useEffect(() => {
    if (selectedId === null) {
      setDetail(null);
      return;
    }

    let cancelled = false;
    setDetailLoading(true);
    setDetail(null);

    getHomeLocation(selectedId)
      .then((row) => {
        if (!cancelled) {
          setDetail(row);
          setNote('');
        }
      })
      .catch((detailError) => {
        if (!cancelled) {
          notify('error', detailError instanceof ApiError ? detailError.message : 'Could not load the pin.');
          setSelectedId(null);
        }
      })
      .finally(() => {
        if (!cancelled) setDetailLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [selectedId, notify]);

  async function review(action: 'approve' | 'reject') {
    if (!detail) return;

    if (action === 'reject' && note.trim() === '') {
      notify('error', 'Enter a reason before rejecting.');
      return;
    }

    setBusy(action);

    try {
      await reviewHomeLocation(detail.id, {
        action,
        note: note.trim() || undefined,
      });
      notify('success', `Home location ${action}d.`);
      setSelectedId(null);
      setNote('');
      void load();
    } catch (reviewError) {
      notify('error', reviewError instanceof ApiError ? reviewError.message : 'Review failed.');
    } finally {
      setBusy(null);
    }
  }

  const counts = paginated?.counts;
  const allCount = counts ? counts.pending + counts.approved + counts.rejected : 0;
  const isSelf = detail !== null && detail.employee.idno === user?.employee_id;

  const tabs: { key: StatusFilter; label: string; count: number }[] = [
    { key: 'pending', label: 'Pending', count: counts?.pending ?? 0 },
    { key: 'approved', label: 'Approved', count: counts?.approved ?? 0 },
    { key: 'rejected', label: 'Rejected', count: counts?.rejected ?? 0 },
    { key: 'all', label: 'All', count: allCount },
  ];

  return (
    <div>
      <PageHeader
        title="Home locations"
        description="Review WFH employee home pins submitted from the portal"
      />

      <Card className="mb-4 p-3 shadow-sm sm:p-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap gap-1.5">
            {tabs.map((tab) => (
              <button
                key={tab.key}
                type="button"
                onClick={() => {
                  setStatus(tab.key);
                  setPage(1);
                }}
                className={
                  status === tab.key
                    ? 'inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1.5 text-sm font-semibold text-primary-dark ring-1 ring-teal-200'
                    : 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-muted hover:bg-bg hover:text-text'
                }
              >
                {tab.label}
                <span className="font-mono text-[11px] tnum">{tab.count}</span>
              </button>
            ))}
          </div>

          <form
            className="flex items-center gap-2"
            onSubmit={(event) => {
              event.preventDefault();
              setPage(1);
              setQuery(search.trim());
            }}
          >
            <Input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search employee or address"
              aria-label="Search home locations"
            />
            <Button type="submit" variant="secondary">
              Search
            </Button>
          </form>
        </div>
      </Card>

      <Card>
        {error ? (
          <ErrorState message={error} onRetry={load} />
        ) : (
          <>
            <DataTable<HomeLocationReview>
              loading={loading && !rows}
              rows={rows ?? []}
              keyOf={(row) => row.id}
              emptyTitle="No home locations found"
              emptyDescription="WFH and hybrid employees submit pins from the portal."
              onRowClick={(row) => setSelectedId(row.id)}
              columns={[
                {
                  key: 'employee',
                  header: 'Employee',
                  render: (row) => (
                    <div className="min-w-0">
                      <div className="truncate font-medium text-text">{row.employee.name}</div>
                      <div className="truncate text-xs text-muted">
                        {row.employee.idno} · {row.label ?? `Home #${row.id}`}
                      </div>
                    </div>
                  ),
                },
                {
                  key: 'arrangement',
                  header: 'Arrangement',
                  render: (row) => <Badge tone="teal">{row.employee.work_arrangement}</Badge>,
                },
                {
                  key: 'address',
                  header: 'Address',
                  render: (row) => (
                    <span className="text-xs text-muted">{formatHomeAddress(row)}</span>
                  ),
                },
                {
                  key: 'submitted',
                  header: 'Submitted',
                  render: (row) => (
                    <span className="font-mono text-xs tnum text-muted">
                      {formatDateTime(row.created_at)}
                    </span>
                  ),
                },
                {
                  key: 'status',
                  header: 'Status',
                  render: (row) => <Badge tone={statusTone(row.status)}>{row.status}</Badge>,
                },
              ]}
            />

            {paginated ? (
              <PaginationBar
                page={page}
                paginated={paginated}
                perPage={perPage}
                pageSizes={[10, 20, 50, 100]}
                onPageChange={setPage}
                onPerPageChange={(next) => {
                  setPerPage(next);
                  setPage(1);
                }}
              />
            ) : null}
          </>
        )}
      </Card>

      <Drawer
        open={selectedId !== null}
        onClose={() => setSelectedId(null)}
        title="Review home location"
        wide
      >
        {detailLoading ? (
          <p className="text-sm text-muted">Loading pin…</p>
        ) : detail ? (
          <div className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <div className="text-xs font-semibold text-muted">Employee</div>
                <div className="text-sm font-medium text-text">{detail.employee.name}</div>
                <div className="text-xs text-muted">
                  {detail.employee.idno}
                  {detail.employee.department ? ` · ${detail.employee.department}` : ''}
                </div>
              </div>
              <div>
                <div className="text-xs font-semibold text-muted">Assignment</div>
                <div className="text-sm text-text">
                  {detail.employee.work_arrangement} · {detail.employee.branch?.name ?? 'No branch'}
                </div>
              </div>
            </div>

            <div>
              <div className="text-xs font-semibold text-muted">Requested location</div>
              <div className="text-sm text-text">{formatHomeAddress(detail)}</div>
              <div className="mt-1 text-xs text-muted">
                {detail.label ?? 'Home'} · {detail.latitude.toFixed(6)}, {detail.longitude.toFixed(6)} ·{' '}
                {detail.radius_meters} m radius
              </div>
            </div>

            <LocationMap requested={detail} approved={detail.current_approved} className="h-64" />

            {detail.current_approved ? (
              <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                <div className="font-semibold">Current approved pin</div>
                <div className="mt-1">{formatHomeAddress(detail.current_approved)}</div>
                <div className="mt-1 font-mono tnum">
                  {detail.current_approved.latitude.toFixed(6)},{' '}
                  {detail.current_approved.longitude.toFixed(6)}
                </div>
              </div>
            ) : (
              <div className="rounded-lg border border-border bg-bg p-3 text-xs text-muted">
                No approved home pin yet.
              </div>
            )}

            {detail.status === 'pending' ? (
              <>
                {isSelf ? (
                  <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                    You cannot review your own home location. Ask another admin or approver.
                  </div>
                ) : null}

                <Field label="Note (required to reject)">
                  <Textarea
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    rows={3}
                    placeholder="Reason for rejection, or an optional approval note"
                    disabled={busy !== null || isSelf}
                  />
                </Field>

                <div className="flex flex-wrap gap-2">
                  <Button
                    loading={busy === 'approve'}
                    disabled={busy !== null || isSelf}
                    onClick={() => void review('approve')}
                  >
                    Approve
                  </Button>
                  <Button
                    variant="secondary"
                    loading={busy === 'reject'}
                    disabled={busy !== null || isSelf || note.trim() === ''}
                    onClick={() => void review('reject')}
                  >
                    Reject
                  </Button>
                </div>
              </>
            ) : (
              <div className="rounded-lg border border-border bg-bg p-3 text-sm text-muted">
                <div>
                  Status: <strong className="text-text">{detail.status}</strong>
                </div>
                {detail.reviewed_by ? (
                  <div className="mt-1 text-xs">
                    Reviewed by {detail.reviewed_by}
                    {detail.reviewed_at ? ` on ${formatDateTime(detail.reviewed_at)}` : ''}
                  </div>
                ) : null}
                {detail.review_note ? (
                  <div className="mt-1 text-xs">Note: {detail.review_note}</div>
                ) : null}
              </div>
            )}
          </div>
        ) : null}
      </Drawer>
    </div>
  );
}
