import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Pencil } from 'lucide-react';
import { ApiError } from '../api/client';
import {
  bulkAssign,
  listBranches,
  listEmployees,
  previewAssignment,
} from '../api/endpoints';
import type {
  Branch,
  BulkAssignmentPayload,
  BulkAssignmentResult,
  EmployeeRow,
  Paginated,
  WorkArrangement,
} from '../api/types';
import { DataTable, PaginationBar } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { PageHeader } from '../components/PageHeader';
import { Badge, Button, Card, ErrorState, Field, Input, Select } from '../components/ui';
import { useToast } from '../components/Toast';

const PAGE_SIZE = 50;
const MAX_BATCH = 2000;

function arrangementTone(value: WorkArrangement): 'blue' | 'violet' | 'teal' {
  if (value === 'wfh') return 'violet';
  if (value === 'hybrid') return 'teal';
  return 'blue';
}

function homeTone(value: EmployeeRow['home_location_status']): 'green' | 'amber' | 'gray' {
  if (value === 'approved') return 'green';
  if (value === 'pending') return 'amber';
  return 'gray';
}

function summary(result: BulkAssignmentResult): string {
  const parts = [`Updated ${result.updated}`];

  if (result.created > 0) parts.push(`created ${result.created}`);
  if (result.skipped > 0) parts.push(`skipped ${result.skipped}`);

  const warnings: string[] = [];

  if (result.warnings.no_home_pin > 0) {
    warnings.push(`${result.warnings.no_home_pin} need home-pin approval`);
  }
  if (result.warnings.no_branch_for_onsite > 0) {
    warnings.push(`${result.warnings.no_branch_for_onsite} need a branch`);
  }

  return `${parts.join(', ')}${warnings.length > 0 ? `. ${warnings.join('; ')}.` : '.'}`;
}

function AssignmentModal({
  open,
  onClose,
  title,
  idnos,
  branches,
  initialBranch = '',
  initialArrangement = '',
  onApplied,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  idnos: string[];
  branches: Branch[];
  initialBranch?: string;
  initialArrangement?: string;
  onApplied: (result: BulkAssignmentResult) => void;
}) {
  const [branch, setBranch] = useState(initialBranch);
  const [arrangement, setArrangement] = useState(initialArrangement);
  const [preview, setPreview] = useState<BulkAssignmentResult | null>(null);
  const [previewError, setPreviewError] = useState<string | null>(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [applying, setApplying] = useState(false);
  const [applyError, setApplyError] = useState<string | null>(null);

  useEffect(() => {
    if (!open) return;
    setBranch(initialBranch);
    setArrangement(initialArrangement);
    setPreview(null);
    setPreviewError(null);
    setApplyError(null);
  }, [open, initialBranch, initialArrangement]);

  const payload = useMemo<BulkAssignmentPayload | null>(() => {
    if (idnos.length === 0 || (branch === '' && arrangement === '')) return null;

    return {
      idnos,
      ...(branch !== '' ? { branch_id: Number(branch) } : {}),
      ...(arrangement !== '' ? { work_arrangement: arrangement as WorkArrangement } : {}),
    };
  }, [idnos, branch, arrangement]);

  useEffect(() => {
    if (!open || payload === null) {
      setPreview(null);
      setPreviewLoading(false);
      return;
    }

    let cancelled = false;
    setPreviewLoading(true);
    setPreviewError(null);

    previewAssignment(payload)
      .then((result) => {
        if (!cancelled) setPreview(result);
      })
      .catch((error) => {
        if (!cancelled) {
          setPreview(null);
          setPreviewError(error instanceof ApiError ? error.message : 'Could not preview this change.');
        }
      })
      .finally(() => {
        if (!cancelled) setPreviewLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [open, payload]);

  async function apply() {
    if (payload === null || preview === null) return;

    setApplying(true);
    setApplyError(null);

    try {
      const result = await bulkAssign(payload);
      onApplied(result);
    } catch (error) {
      setApplyError(error instanceof ApiError ? error.message : 'Could not apply this change.');
    } finally {
      setApplying(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title={title} wide>
      <div className="space-y-4">
        <p className="text-sm text-muted">
          {idnos.length} employee{idnos.length === 1 ? '' : 's'} selected.
        </p>

        <Field label="Branch">
          <Select value={branch} onChange={(event) => setBranch(event.target.value)}>
            <option value="">No change</option>
            {branches.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name} ({item.code})
              </option>
            ))}
          </Select>
        </Field>

        <Field label="Work arrangement">
          <Select value={arrangement} onChange={(event) => setArrangement(event.target.value)}>
            <option value="">No change</option>
            <option value="onsite">Onsite</option>
            <option value="wfh">WFH</option>
            <option value="hybrid">Hybrid</option>
          </Select>
        </Field>

        {branch === '' && arrangement === '' ? (
          <p className="text-xs text-muted">Choose a branch, a work arrangement, or both.</p>
        ) : null}

        {previewLoading ? <p className="text-xs text-muted">Checking the batch…</p> : null}

        {previewError ? <p className="text-xs text-danger">{previewError}</p> : null}

        {preview ? (
          <div className="rounded-lg border border-border bg-bg p-3 text-xs text-text">
            <div className="font-semibold">Preview</div>
            <div className="mt-1">
              Updated: {preview.updated} · New profiles: {preview.created} · Skipped: {preview.skipped}
            </div>
            {preview.warnings.no_home_pin > 0 ? (
              <div className="mt-1 text-amber-700">
                {preview.warnings.no_home_pin} WFH/hybrid employee
                {preview.warnings.no_home_pin === 1 ? '' : 's'} have no approved home pin and cannot punch
                until approved.
              </div>
            ) : null}
            {preview.warnings.no_branch_for_onsite > 0 ? (
              <div className="mt-1 text-amber-700">
                {preview.warnings.no_branch_for_onsite} onsite employee
                {preview.warnings.no_branch_for_onsite === 1 ? '' : 's'} will have no branch and cannot punch.
              </div>
            ) : null}
          </div>
        ) : null}

        {applyError ? <p className="text-xs text-danger">{applyError}</p> : null}

        <div className="flex justify-end gap-2">
          <Button variant="secondary" onClick={onClose} disabled={applying}>
            Cancel
          </Button>
          <Button onClick={() => void apply()} loading={applying} disabled={!preview || previewLoading}>
            Apply
          </Button>
        </div>
      </div>
    </Modal>
  );
}

export function AssignmentsPage() {
  const [searchParams] = useSearchParams();
  const { notify } = useToast();

  const [branches, setBranches] = useState<Branch[]>([]);
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [branchFilter, setBranchFilter] = useState(searchParams.get('branch_id') ?? '');
  const [arrangementFilter, setArrangementFilter] = useState('');
  const [homeFilter, setHomeFilter] = useState('');
  const [activeFilter, setActiveFilter] = useState('1');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(PAGE_SIZE);

  const [rows, setRows] = useState<EmployeeRow[] | null>(null);
  const [paginated, setPaginated] = useState<Paginated<EmployeeRow> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [selectingAll, setSelectingAll] = useState(false);
  const [bulkOpen, setBulkOpen] = useState(false);
  const [single, setSingle] = useState<EmployeeRow | null>(null);

  const activeBranches = useMemo(() => branches.filter((branch) => branch.is_active), [branches]);
  const selectedIds = useMemo(() => Array.from(selected), [selected]);
  const singleIds = useMemo(() => (single ? [single.idno] : []), [single]);

  const filterKey = `${query}|${branchFilter}|${arrangementFilter}|${homeFilter}|${activeFilter}`;

  useEffect(() => {
    setSelected(new Set());
  }, [filterKey]);

  useEffect(() => {
    listBranches()
      .then(setBranches)
      .catch((loadError) => {
        notify('error', loadError instanceof ApiError ? loadError.message : 'Could not load branches.');
      });
  }, [notify]);

  const baseParams = useCallback(
    () => ({
      search: query || undefined,
      branch_id: branchFilter || undefined,
      arrangement: arrangementFilter || undefined,
      home_location_status: homeFilter || undefined,
      active: activeFilter,
    }),
    [query, branchFilter, arrangementFilter, homeFilter, activeFilter],
  );

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await listEmployees({ ...baseParams(), page, per_page: perPage });
      setRows(result.data);
      setPaginated(result);
    } catch (loadError) {
      setError(loadError instanceof ApiError ? loadError.message : 'Failed to load employees.');
    } finally {
      setLoading(false);
    }
  }, [baseParams, page, perPage]);

  useEffect(() => {
    void load();
  }, [load]);

  const pageIds = useMemo(() => (rows ?? []).map((row) => row.idno), [rows]);
  const pageSelected = pageIds.length > 0 && pageIds.every((id) => selected.has(id));

  function togglePage() {
    setSelected((previous) => {
      const next = new Set(previous);

      if (pageSelected) {
        pageIds.forEach((id) => next.delete(id));
      } else {
        pageIds.forEach((id) => next.add(id));
      }

      return next;
    });
  }

  function toggleOne(idno: string) {
    setSelected((previous) => {
      const next = new Set(previous);

      if (next.has(idno)) {
        next.delete(idno);
      } else {
        next.add(idno);
      }

      return next;
    });
  }

  async function selectAllMatching() {
    if (!paginated) return;

    if (paginated.total > MAX_BATCH) {
      notify(
        'error',
        `This filter matches ${paginated.total} employees. Narrow it to ${MAX_BATCH} or fewer first.`,
      );
      return;
    }

    setSelectingAll(true);

    try {
      const ids = new Set<string>();
      let currentPage = 1;
      let lastPage = 1;

      do {
        const result = await listEmployees({ ...baseParams(), page: currentPage, per_page: 100 });
        result.data.forEach((row) => ids.add(row.idno));
        lastPage = result.last_page;
        currentPage++;
      } while (currentPage <= lastPage && ids.size < MAX_BATCH);

      setSelected(ids);
    } catch (selectError) {
      notify('error', selectError instanceof ApiError ? selectError.message : 'Could not select all rows.');
    } finally {
      setSelectingAll(false);
    }
  }

  return (
    <div>
      <PageHeader
        title="Assignments"
        description="Branch and work-arrangement assignments"
      />

      <Card className="mb-4 p-3 shadow-sm sm:p-4">
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
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
              placeholder="Search name, email, or ID"
              aria-label="Search employees"
            />
            <Button type="submit" variant="secondary">
              Search
            </Button>
          </form>

          <Select
            value={branchFilter}
            onChange={(event) => {
              setPage(1);
              setBranchFilter(event.target.value);
            }}
            aria-label="Filter by branch"
          >
            <option value="">All branches</option>
            <option value="unassigned">Unassigned</option>
            {branches.map((branch) => (
              <option key={branch.id} value={branch.id}>
                {branch.name}
                {branch.is_active ? '' : ' (inactive)'}
              </option>
            ))}
          </Select>

          <Select
            value={arrangementFilter}
            onChange={(event) => {
              setPage(1);
              setArrangementFilter(event.target.value);
            }}
            aria-label="Filter by work arrangement"
          >
            <option value="">All arrangements</option>
            <option value="onsite">Onsite</option>
            <option value="wfh">WFH</option>
            <option value="hybrid">Hybrid</option>
          </Select>

          <Select
            value={homeFilter}
            onChange={(event) => {
              setPage(1);
              setHomeFilter(event.target.value);
            }}
            aria-label="Filter by home-location status"
          >
            <option value="">All home pins</option>
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="none">No pin</option>
          </Select>

          <Select
            value={activeFilter}
            onChange={(event) => {
              setPage(1);
              setActiveFilter(event.target.value);
            }}
            aria-label="Filter by employee status"
          >
            <option value="1">Active employees</option>
            <option value="0">Inactive employees</option>
            <option value="all">All employees</option>
          </Select>
        </div>
      </Card>

      {selected.size > 0 ? (
        <div className="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm">
          <span className="font-semibold text-teal-900">{selected.size} selected</span>
          {paginated && selected.size < paginated.total ? (
            <button
              type="button"
              onClick={() => void selectAllMatching()}
              disabled={selectingAll}
              className="cursor-pointer text-xs font-semibold text-primary hover:underline disabled:opacity-50"
            >
              {selectingAll ? 'Selecting…' : `Select all ${paginated.total} matching filters`}
            </button>
          ) : null}
          <button
            type="button"
            onClick={() => setSelected(new Set())}
            className="cursor-pointer text-xs font-semibold text-muted hover:text-text"
          >
            Clear
          </button>
          <div className="ml-auto">
            <Button size="sm" onClick={() => setBulkOpen(true)}>
              Assign branch / arrangement
            </Button>
          </div>
        </div>
      ) : null}

      <Card>
        {error ? (
          <ErrorState message={error} onRetry={load} />
        ) : (
          <>
            <DataTable<EmployeeRow>
              loading={loading && !rows}
              rows={rows ?? []}
              keyOf={(row) => row.idno}
              emptyTitle="No employees found"
              emptyDescription="Adjust the filters or search for another employee."
              minWidthClassName="min-w-[56rem]"
              columns={[
                {
                  key: 'select',
                  header: (
                    <input
                      type="checkbox"
                      checked={pageSelected}
                      onChange={togglePage}
                      aria-label="Select this page"
                      className="h-4 w-4 accent-teal-600"
                    />
                  ),
                  className: 'w-10',
                  render: (row) => (
                    <input
                      type="checkbox"
                      checked={selected.has(row.idno)}
                      onChange={() => toggleOne(row.idno)}
                      aria-label={`Select ${row.name}`}
                      className="h-4 w-4 accent-teal-600"
                    />
                  ),
                },
                {
                  key: 'employee',
                  header: 'Employee',
                  render: (row) => (
                    <div className="min-w-0">
                      <div className="truncate font-medium text-text">{row.name}</div>
                      <div className="truncate text-xs text-muted">
                        {row.idno} · {row.email}
                      </div>
                    </div>
                  ),
                },
                {
                  key: 'department',
                  header: 'Department',
                  render: (row) => (
                    <span className="text-xs text-muted">{row.department ?? '—'}</span>
                  ),
                },
                {
                  key: 'branch',
                  header: 'Branch',
                  render: (row) =>
                    row.branch ? (
                      <div className="flex flex-wrap items-center gap-1.5">
                        <span className="text-sm text-text">{row.branch.name}</span>
                        {row.branch.is_active === false ? (
                          <>
                            <Badge tone="amber">Inactive</Badge>
                            <button
                              type="button"
                              onClick={() => setSingle(row)}
                              className="text-[11px] font-semibold text-amber-700 hover:underline"
                            >
                              needs reassignment
                            </button>
                          </>
                        ) : null}
                      </div>
                    ) : (
                      <Badge tone="gray">Unassigned</Badge>
                    ),
                },
                {
                  key: 'arrangement',
                  header: 'Arrangement',
                  render: (row) => (
                    <Badge tone={arrangementTone(row.work_arrangement)}>{row.work_arrangement}</Badge>
                  ),
                },
                {
                  key: 'home',
                  header: 'Home pin',
                  render: (row) => (
                    <div className="flex flex-wrap items-center gap-1.5">
                      <Badge tone={homeTone(row.home_location_status)}>{row.home_location_status}</Badge>
                      {row.work_arrangement !== 'onsite' && row.home_location_status !== 'approved' ? (
                        <Link
                          to={`/home-locations?search=${encodeURIComponent(row.name)}`}
                          className="text-[11px] font-semibold text-primary hover:underline"
                        >
                          Review pin
                        </Link>
                      ) : null}
                    </div>
                  ),
                },
                {
                  key: 'actions',
                  header: '',
                  className: 'w-12',
                  render: (row) => (
                    <button
                      type="button"
                      onClick={() => setSingle(row)}
                      aria-label={`Edit ${row.name}`}
                      className="cursor-pointer rounded p-1.5 text-muted hover:bg-bg hover:text-primary"
                      title="Edit assignment"
                    >
                      <Pencil size={14} />
                    </button>
                  ),
                },
              ]}
            />

            {paginated ? (
              <PaginationBar
                page={page}
                paginated={paginated}
                perPage={perPage}
                pageSizes={[10, 30, 50, 100]}
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

      <AssignmentModal
        open={bulkOpen}
        onClose={() => setBulkOpen(false)}
        title="Assign branch / arrangement"
        idnos={selectedIds}
        branches={activeBranches}
        onApplied={(result) => {
          notify('success', summary(result));
          setBulkOpen(false);
          setSelected(new Set());
          void load();
        }}
      />

      <AssignmentModal
        open={single !== null}
        onClose={() => setSingle(null)}
        title={single ? `Edit ${single.name}` : 'Edit assignment'}
        idnos={singleIds}
        branches={activeBranches}
        initialBranch={single?.branch && single.branch.is_active !== false ? String(single.branch.id) : ''}
        initialArrangement={single?.work_arrangement ?? ''}
        onApplied={(result) => {
          notify('success', summary(result));
          setSingle(null);
          void load();
        }}
      />
    </div>
  );
}
