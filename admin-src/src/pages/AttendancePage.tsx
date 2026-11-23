import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  CalendarDays,
  Check,
  ChevronDown,
  Clock,
  Coffee,
  Loader2,
  LogIn,
  LogOut,
  MapPin,
  Search,
  SlidersHorizontal,
  StickyNote,
  X,
} from 'lucide-react';
import { API_BASE, ApiError } from '../api/client';
import {
  getAppSettings,
  listAttendance,
  listBranches,
  listDepartments,
  updateAppSettings,
} from '../api/endpoints';
import type { AttendanceAdmin, AttendanceType, Branch, Department, Paginated } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { PageHeader } from '../components/PageHeader';
import { Avatar, Badge, Button, Card, EmptyState, ErrorState, Field, Input, Select, Toggle } from '../components/ui';
import { useToast } from '../components/Toast';
import { DataTable, DEFAULT_PAGE_SIZE, PaginationBar } from '../components/DataTable';
import { Drawer } from '../components/Drawer';
import { LocationMap } from '../components/LocationMap';
import { PhotoViewer } from '../components/PhotoViewer';
import { cn } from '../lib/cn';
import { FLAG_LABELS, FLAG_TONES } from '../lib/flags';
import {
  addDays,
  formatDate,
  formatDateOnly,
  formatDateTime,
  formatMeters,
  formatMinutes,
  formatTime,
  manilaToday,
} from '../lib/format';

type DatePreset = 'all' | 'today' | 'yesterday' | 'last7' | 'last30' | 'month' | 'custom';

const MIN_QUERY_LENGTH = 3;
const SEARCH_DEBOUNCE_MS = 350;
const DEFAULT_PRESET: DatePreset = 'last7';

const PRESET_LABELS: Record<DatePreset, string> = {
  all: 'All dates',
  today: 'Today',
  yesterday: 'Yesterday',
  last7: 'Last 7 days',
  last30: 'Last 30 days',
  month: 'This month',
  custom: 'Custom range',
};

const PRESET_ORDER: DatePreset[] = ['all', 'today', 'yesterday', 'last7', 'last30', 'month'];

const TYPE_LABELS: Record<string, string> = {
  time_in: 'Time in',
  time_out: 'Time out',
  break_in: 'On Break',
  break_out: 'Done Break',
};

const FILTER_KEYS = ['type', 'branch_id', 'department_id', 'has_open_flags', 'employee_id'] as const;

function presetFromParams(params: URLSearchParams): DatePreset {
  const preset = params.get('preset');
  if (preset && preset in PRESET_LABELS) {
    return preset as DatePreset;
  }
  if (params.get('date_from') || params.get('date_to')) {
    return 'custom';
  }
  return DEFAULT_PRESET;
}

function rangeForPreset(preset: DatePreset, dateFrom: string, dateTo: string): { from: string; to: string } {
  const today = manilaToday();

  switch (preset) {
    case 'all':
      return { from: '', to: '' };
    case 'today':
      return { from: today, to: today };
    case 'yesterday': {
      const yesterday = addDays(today, -1);
      return { from: yesterday, to: yesterday };
    }
    case 'last7':
      return { from: addDays(today, -6), to: today };
    case 'last30':
      return { from: addDays(today, -29), to: today };
    case 'month':
      return { from: `${today.slice(0, 7)}-01`, to: today };
    case 'custom':
      return { from: dateFrom, to: dateTo };
  }
}

function customRangeLabel(from: string, to: string): string {
  if (from && to) {
    if (from === to) return formatDateOnly(from);
    const sameYear = from.slice(0, 4) === to.slice(0, 4);
    return `${formatDateOnly(from, !sameYear)} – ${formatDateOnly(to)}`;
  }
  if (from) return `From ${formatDateOnly(from)}`;
  if (to) return `Until ${formatDateOnly(to)}`;
  return 'Custom range';
}

function TypeBadge({ type }: { type: AttendanceType }) {
  if (type === 'time_in') {
    return (
      <Badge tone="green">
        <LogIn size={11} /> Time in
      </Badge>
    );
  }
  if (type === 'time_out') {
    return (
      <Badge tone="blue">
        <LogOut size={11} /> Time out
      </Badge>
    );
  }
  if (type === 'break_in') {
    return (
      <Badge tone="amber">
        <Coffee size={11} /> On Break
      </Badge>
    );
  }
  return (
    <Badge tone="amber">
      <Coffee size={11} /> Done Break
    </Badge>
  );
}

export function AttendancePage() {
  const { hasRole } = useAuth();
  const { notify } = useToast();
  const canToggleBreaks = hasRole('Admin');
  const [searchParams, setSearchParams] = useSearchParams();

  const preset = presetFromParams(searchParams);
  const appliedQuery = (searchParams.get('q') ?? '').trim();
  const dateFromParam = searchParams.get('date_from') ?? '';
  const dateToParam = searchParams.get('date_to') ?? '';
  const type = searchParams.get('type') ?? '';
  const branchId = searchParams.get('branch_id') ?? '';
  const departmentId = searchParams.get('department_id') ?? '';
  const openFlags = searchParams.get('has_open_flags') ?? '';
  const legacyEmployeeId = searchParams.get('employee_id') ?? '';
  const page = Math.max(1, Number.parseInt(searchParams.get('page') ?? '1', 10) || 1);

  const [searchText, setSearchText] = useState(() => searchParams.get('q') ?? '');
  const [searchPending, setSearchPending] = useState(false);
  const [perPage, setPerPage] = useState(DEFAULT_PAGE_SIZE);
  const [data, setData] = useState<AttendanceAdmin[] | null>(null);
  const [paginated, setPaginated] = useState<Paginated<AttendanceAdmin> | null>(null);
  const [branches, setBranches] = useState<Branch[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshKey, setRefreshKey] = useState(0);
  const [selected, setSelected] = useState<AttendanceAdmin | null>(null);
  const [dateOpen, setDateOpen] = useState(false);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [customFrom, setCustomFrom] = useState(dateFromParam);
  const [customTo, setCustomTo] = useState(dateToParam);
  const [dateError, setDateError] = useState<string | null>(null);
  const [breaksEnabled, setBreaksEnabled] = useState<boolean | null>(null);
  const [breaksToggleBusy, setBreaksToggleBusy] = useState(false);
  const searchRef = useRef<HTMLInputElement>(null);
  const dateRef = useRef<HTMLDivElement>(null);
  const filtersRef = useRef<HTMLDivElement>(null);

  const updateParams = useCallback(
    (mutate: (params: URLSearchParams) => void, options?: { replace?: boolean }) => {
      setSearchParams(
        (previous) => {
          const next = new URLSearchParams(previous);
          mutate(next);
          return next;
        },
        { replace: options?.replace ?? false },
      );
    },
    [setSearchParams],
  );

  const dateRange = useMemo(
    () => rangeForPreset(preset, dateFromParam, dateToParam),
    [preset, dateFromParam, dateToParam],
  );

  const requestParams = useMemo(() => {
    const params: Record<string, string | number | boolean | undefined> = { page, per_page: perPage };

    if (appliedQuery.length >= MIN_QUERY_LENGTH) params.q = appliedQuery;
    if (dateRange.from) params.date_from = dateRange.from;
    if (dateRange.to) params.date_to = dateRange.to;
    if (type) params.type = type;
    if (branchId) params.branch_id = branchId;
    if (departmentId) params.department_id = departmentId;
    if (openFlags === '1' || openFlags === '0') params.has_open_flags = openFlags === '1';
    if (legacyEmployeeId) params.employee_id = legacyEmployeeId;

    return params;
  }, [page, perPage, appliedQuery, dateRange, type, branchId, departmentId, openFlags, legacyEmployeeId]);

  useEffect(() => {
    if (!canToggleBreaks) {
      setBreaksEnabled(null);
      return;
    }

    let cancelled = false;

    void getAppSettings()
      .then((settings) => {
        if (!cancelled) setBreaksEnabled(settings.breaks_enabled);
      })
      .catch(() => {
        if (!cancelled) setBreaksEnabled(null);
      });

    return () => {
      cancelled = true;
    };
  }, [canToggleBreaks]);

  async function toggleBreaksEnabled() {
    if (breaksEnabled === null || breaksToggleBusy) return;
    const next = !breaksEnabled;
    setBreaksEnabled(next);
    setBreaksToggleBusy(true);

    try {
      const settings = await updateAppSettings({ breaks_enabled: next });
      setBreaksEnabled(settings.breaks_enabled);
      notify(
        'success',
        settings.breaks_enabled ? 'On Break/Done Break enabled.' : 'On Break/Done Break disabled.',
      );
    } catch (toggleError) {
      setBreaksEnabled(!next);
      notify(
        'error',
        toggleError instanceof ApiError ? toggleError.message : 'Failed to update break setting.',
      );
    } finally {
      setBreaksToggleBusy(false);
    }
  }

  useEffect(() => {
    const controller = new AbortController();
    let cancelled = false;

    void listBranches(undefined, controller.signal)
      .then((result) => {
        if (!cancelled) setBranches(result);
      })
      .catch(() => {
        if (!cancelled) setBranches([]);
      });

    void listDepartments(controller.signal)
      .then((result) => {
        if (!cancelled) setDepartments(result);
      })
      .catch(() => {
        if (!cancelled) setDepartments([]);
      });

    return () => {
      cancelled = true;
      controller.abort();
    };
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError(null);

    void listAttendance(requestParams, controller.signal)
      .then((result) => {
        if (controller.signal.aborted) return;
        setData(result.data);
        setPaginated(result);

        if (result.last_page > 0 && result.current_page > result.last_page) {
          updateParams(
            (params) => {
              if (result.last_page > 1) params.set('page', String(result.last_page));
              else params.delete('page');
            },
            { replace: true },
          );
        }
      })
      .catch((loadError) => {
        if (controller.signal.aborted) return;
        setData(null);
        setPaginated(null);
        setError(loadError instanceof ApiError ? loadError.message : 'Failed to load attendance records.');
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });

    return () => controller.abort();
  }, [requestParams, refreshKey, updateParams]);

  useEffect(() => {
    const trimmed = searchText.trim();

    if (trimmed.length < MIN_QUERY_LENGTH) {
      setSearchPending(false);
      if (appliedQuery !== '') {
        updateParams(
          (params) => {
            params.delete('q');
            params.delete('page');
          },
          { replace: true },
        );
      }
      return;
    }

    if (trimmed === appliedQuery) {
      setSearchPending(false);
      return;
    }

    setSearchPending(true);
    const timer = window.setTimeout(() => {
      updateParams(
        (params) => {
          params.set('q', trimmed);
          params.delete('page');
        },
        { replace: true },
      );
      setSearchPending(false);
    }, SEARCH_DEBOUNCE_MS);

    return () => window.clearTimeout(timer);
  }, [searchText, appliedQuery, updateParams]);

  useEffect(() => {
    const syncFromUrl = () => setSearchText(new URLSearchParams(window.location.search).get('q') ?? '');
    window.addEventListener('popstate', syncFromUrl);
    return () => window.removeEventListener('popstate', syncFromUrl);
  }, []);

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) return;
      const target = event.target as HTMLElement | null;
      if (
        target &&
        (target.tagName === 'INPUT' ||
          target.tagName === 'TEXTAREA' ||
          target.tagName === 'SELECT' ||
          target.isContentEditable)
      ) {
        return;
      }
      event.preventDefault();
      searchRef.current?.focus();
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  useEffect(() => {
    const onPointerDown = (event: PointerEvent) => {
      const target = event.target as Node;

      if (dateRef.current && !dateRef.current.contains(target)) {
        setDateOpen(false);
      }
      if (filtersRef.current && !filtersRef.current.contains(target)) {
        setFiltersOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setDateOpen(false);
        setFiltersOpen(false);
      }
    };

    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);

    return () => {
      document.removeEventListener('pointerdown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, []);

  const branchName = branches.find((branch) => String(branch.id) === branchId)?.name;
  const departmentName = departments.find((department) => department.id === departmentId)?.name;

  const chips = useMemo(() => {
    const active: { key: string; label: string; value: string }[] = [];

    if (type) active.push({ key: 'type', label: 'Type', value: TYPE_LABELS[type] ?? type });
    if (branchId) active.push({ key: 'branch_id', label: 'Branch', value: branchName ?? `Branch #${branchId}` });
    if (departmentId) {
      active.push({ key: 'department_id', label: 'Department', value: departmentName ?? departmentId });
    }
    if (openFlags === '1' || openFlags === '0') {
      active.push({
        key: 'has_open_flags',
        label: 'Open flags',
        value: openFlags === '1' ? 'With open flags' : 'Without open flags',
      });
    }
    if (legacyEmployeeId) {
      active.push({ key: 'employee_id', label: 'Employee', value: legacyEmployeeId });
    }

    return active;
  }, [type, branchId, departmentId, openFlags, legacyEmployeeId, branchName, departmentName]);

  const dateLabel = preset === 'custom' ? customRangeLabel(dateFromParam, dateToParam) : PRESET_LABELS[preset];
  const filtersActive = chips.length > 0;
  const searchActive = appliedQuery.length >= MIN_QUERY_LENGTH;
  const trimmedSearch = searchText.trim();
  const showMinHint = trimmedSearch.length > 0 && trimmedSearch.length < MIN_QUERY_LENGTH;
  const busy = loading || searchPending;
  const total = paginated?.total ?? 0;

  function setFilter(key: string, value: string) {
    updateParams((params) => {
      if (value) params.set(key, value);
      else params.delete(key);
      params.delete('page');
    });
  }

  function clearFilters() {
    updateParams((params) => {
      FILTER_KEYS.forEach((key) => params.delete(key));
      params.delete('page');
    });
  }

  function clearSearchAndFilters() {
    setSearchText('');
    updateParams(
      (params) => {
        params.delete('q');
        FILTER_KEYS.forEach((key) => params.delete(key));
        params.delete('page');
      },
      { replace: true },
    );
  }

  function showAllDates() {
    updateParams((params) => {
      params.set('preset', 'all');
      params.delete('date_from');
      params.delete('date_to');
      params.delete('page');
    });
  }

  function selectPreset(next: DatePreset) {
    updateParams((params) => {
      if (next === DEFAULT_PRESET) params.delete('preset');
      else params.set('preset', next);
      params.delete('date_from');
      params.delete('date_to');
      params.delete('page');
    });
    setDateOpen(false);
  }

  function applyCustomRange() {
    if (customFrom && customTo && customFrom > customTo) {
      setDateError('The from date cannot be after the to date.');
      return;
    }
    if (!customFrom && !customTo) return;

    updateParams((params) => {
      params.set('preset', 'custom');
      if (customFrom) params.set('date_from', customFrom);
      else params.delete('date_from');
      if (customTo) params.set('date_to', customTo);
      else params.delete('date_to');
      params.delete('page');
    });
    setDateError(null);
    setDateOpen(false);
  }

  function resetDateRange() {
    updateParams((params) => {
      params.delete('preset');
      params.delete('date_from');
      params.delete('date_to');
      params.delete('page');
    });
    setCustomFrom('');
    setCustomTo('');
    setDateError(null);
  }

  function goToPage(next: number) {
    updateParams((params) => {
      if (next <= 1) params.delete('page');
      else params.set('page', String(next));
    });
  }

  function changePageSize(next: number) {
    setPerPage(next);
    updateParams((params) => params.delete('page'));
  }

  const emptyCopy = (() => {
    if (searchActive) {
      return {
        title: 'No attendance records match',
        description: `No punches for “${appliedQuery}” in ${dateLabel}. Try another name, employee ID, or a wider date range.`,
      };
    }
    if (filtersActive) {
      return {
        title: 'No attendance records match these filters',
        description: `There are no punches for the selected filters in ${dateLabel}. Remove a filter or widen the date range.`,
      };
    }
    if (preset === 'all') {
      return {
        title: 'No attendance records found',
        description: 'There are no punches recorded yet.',
      };
    }
    return {
      title: 'No attendance records found',
      description: `There are no punches in ${dateLabel}.`,
    };
  })();

  const emptyAction =
    searchActive || filtersActive ? (
      <Button size="sm" onClick={clearSearchAndFilters}>
        Clear search &amp; filters
      </Button>
    ) : preset !== 'all' ? (
      <Button size="sm" onClick={showAllDates}>
        Show all dates
      </Button>
    ) : undefined;

  const triggerClass = (active: boolean) =>
    cn(
      'inline-flex h-10 shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border px-3 text-xs font-medium whitespace-nowrap transition',
      active
        ? 'border-teal-200 bg-teal-50 text-primary-dark hover:bg-teal-100'
        : 'border-border bg-white text-text hover:bg-slate-50',
    );

  return (
    <div>
      <PageHeader
        title="Attendance"
        description="Review punches, selfies and verification results"
        actions={
          canToggleBreaks && breaksEnabled !== null ? (
            <div className="flex items-center gap-2 rounded-lg border border-border bg-white px-3 py-1.5">
              <Toggle
                checked={breaksEnabled}
                onChange={() => void toggleBreaksEnabled()}
                disabled={breaksToggleBusy}
                label="On Break/Done Break"
              />
              <span className="text-xs font-medium text-text">On Break/Done Break</span>
            </div>
          ) : null
        }
      />

      <Card className="shadow-sm">
        <div className="relative z-20 border-b border-border px-3.5 py-3">
          <div className="flex flex-wrap items-center gap-2 sm:flex-nowrap">
            <div className="relative w-full min-w-0 sm:max-w-[560px] sm:flex-1">
              <Search
                size={16}
                className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted"
                aria-hidden
              />
              <input
                ref={searchRef}
                type="search"
                value={searchText}
                onChange={(event) => setSearchText(event.target.value)}
                placeholder="Search employee name or ID…"
                autoComplete="off"
                spellCheck={false}
                aria-label="Search employee name or employee ID"
                aria-describedby="attendance-search-help"
                className="h-10 w-full rounded-lg border border-border bg-white pl-9 pr-24 text-sm text-text outline-none transition placeholder:text-muted/70 hover:border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 [&::-webkit-search-cancel-button]:appearance-none"
              />
              <span id="attendance-search-help" className="sr-only">
                Search by employee name or employee ID. Minimum 3 characters.
              </span>
              <div className="absolute right-2.5 top-1/2 flex -translate-y-1/2 items-center gap-1.5">
                {showMinHint && (
                  <span className="text-[10.5px] whitespace-nowrap text-muted">Min. 3 characters</span>
                )}
                {busy && <Loader2 size={14} className="animate-spin text-primary" aria-hidden />}
                {searchText ? (
                  <button
                    type="button"
                    onClick={() => {
                      setSearchText('');
                      searchRef.current?.focus();
                    }}
                    className="inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded text-muted hover:bg-slate-100 hover:text-text"
                    aria-label="Clear search"
                  >
                    <X size={13} />
                  </button>
                ) : (
                  <kbd
                    className="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded border border-border border-b-slate-300 bg-slate-50 px-1 font-mono text-[10px] text-muted"
                    aria-hidden
                  >
                    /
                  </kbd>
                )}
              </div>
            </div>

            <div className="relative shrink-0" ref={dateRef}>
              <button
                type="button"
                className={triggerClass(false)}
                aria-label="Date range"
                aria-expanded={dateOpen}
                onClick={() => {
                  const next = !dateOpen;
                  setDateOpen(next);
                  setFiltersOpen(false);
                  if (next) {
                    setCustomFrom(dateFromParam);
                    setCustomTo(dateToParam);
                    setDateError(null);
                  }
                }}
              >
                <CalendarDays size={15} />
                <span>{dateLabel}</span>
                <ChevronDown size={14} className={cn('transition-transform', dateOpen && 'rotate-180')} />
              </button>

              {dateOpen && (
                <div
                  className="absolute left-0 top-full z-50 mt-2 w-[300px] max-w-[calc(100vw-32px)] rounded-xl border border-border bg-white shadow-xl outline-none"
                  aria-label="Date range"
                >
                  <div className="flex items-center justify-between gap-3 border-b border-border px-3.5 py-3">
                    <span className="text-[13px] font-semibold text-text">Date range</span>
                    <button
                      type="button"
                      onClick={resetDateRange}
                      className="cursor-pointer rounded px-1 py-0.5 text-[11px] font-semibold text-primary-dark hover:bg-teal-50"
                    >
                      Reset
                    </button>
                  </div>
                  <div className="p-1">
                    {PRESET_ORDER.map((option) => (
                      <button
                        key={option}
                        type="button"
                        onClick={() => selectPreset(option)}
                        aria-pressed={preset === option}
                        className={cn(
                          'flex h-9 w-full cursor-pointer items-center gap-2 rounded-md px-2.5 text-left text-[12.5px] hover:bg-slate-50',
                          preset === option ? 'font-semibold text-primary-dark' : 'text-text',
                        )}
                      >
                        <Check size={15} className={preset === option ? 'text-primary' : 'text-transparent'} />
                        <span>{PRESET_LABELS[option]}</span>
                      </button>
                    ))}
                  </div>
                  <div className="border-t border-border p-3">
                    <div className="mb-2 text-[10.5px] font-semibold text-muted">Custom range</div>
                    <div className="grid grid-cols-2 gap-2">
                      <Field label="From">
                        <Input
                          type="date"
                          value={customFrom}
                          onChange={(event) => {
                            setCustomFrom(event.target.value);
                            setDateError(null);
                          }}
                        />
                      </Field>
                      <Field label="To">
                        <Input
                          type="date"
                          value={customTo}
                          onChange={(event) => {
                            setCustomTo(event.target.value);
                            setDateError(null);
                          }}
                        />
                      </Field>
                    </div>
                    {dateError && <p className="mt-1.5 text-[10.5px] text-danger">{dateError}</p>}
                    <Button
                      size="sm"
                      className="mt-2.5 w-full"
                      onClick={applyCustomRange}
                      disabled={!customFrom && !customTo}
                    >
                      Apply range
                    </Button>
                  </div>
                </div>
              )}
            </div>

            <div className="relative shrink-0" ref={filtersRef}>
              <button
                type="button"
                className={triggerClass(filtersActive)}
                aria-label="Filters"
                aria-expanded={filtersOpen}
                onClick={() => {
                  setFiltersOpen((open) => !open);
                  setDateOpen(false);
                }}
              >
                <SlidersHorizontal size={15} />
                <span>Filters</span>
                {chips.length > 0 && (
                  <span className="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-primary-dark px-1.5 text-[10px] font-bold leading-none text-white">
                    {chips.length}
                  </span>
                )}
                <ChevronDown size={14} className={cn('transition-transform', filtersOpen && 'rotate-180')} />
              </button>

              {filtersOpen && (
                <div
                  className="absolute right-0 top-full z-50 mt-2 w-[min(380px,calc(100vw-32px))] rounded-xl border border-border bg-white shadow-xl outline-none"
                  aria-label="Filters"
                >
                  <div className="flex items-center justify-between gap-3 border-b border-border px-3.5 py-3">
                    <span className="text-[13px] font-semibold text-text">Filters</span>
                    <button
                      type="button"
                      onClick={clearFilters}
                      className="cursor-pointer rounded px-1 py-0.5 text-[11px] font-semibold text-primary-dark hover:bg-teal-50"
                    >
                      Reset
                    </button>
                  </div>
                  <div className="grid grid-cols-1 gap-3 p-3.5 sm:grid-cols-2">
                    <Field label="Type">
                      <Select value={type} onChange={(event) => setFilter('type', event.target.value)}>
                        <option value="">All types</option>
                        <option value="time_in">Time in</option>
                        <option value="time_out">Time out</option>
                        <option value="break_in">On Break</option>
                        <option value="break_out">Done Break</option>
                      </Select>
                    </Field>
                    <Field label="Branch">
                      <Select value={branchId} onChange={(event) => setFilter('branch_id', event.target.value)}>
                        <option value="">All branches</option>
                        {branches.map((branch) => (
                          <option key={branch.id} value={branch.id}>
                            {branch.name}
                          </option>
                        ))}
                      </Select>
                    </Field>
                    <Field label="Department">
                      <Select
                        value={departmentId}
                        onChange={(event) => setFilter('department_id', event.target.value)}
                      >
                        <option value="">All departments</option>
                        {departments.map((department) => (
                          <option key={department.id} value={department.id}>
                            {department.name}
                          </option>
                        ))}
                      </Select>
                    </Field>
                    <Field label="Open red flags">
                      <Select
                        value={openFlags}
                        onChange={(event) => setFilter('has_open_flags', event.target.value)}
                      >
                        <option value="">All records</option>
                        <option value="1">With open flags</option>
                        <option value="0">Without open flags</option>
                      </Select>
                    </Field>
                  </div>
                  <div className="flex items-center justify-between gap-2.5 border-t border-border px-3.5 py-2.5">
                    <span className="text-[10.5px] text-muted">Changes apply instantly.</span>
                    <Button variant="secondary" size="sm" onClick={() => setFiltersOpen(false)}>
                      Done
                    </Button>
                  </div>
                </div>
              )}
            </div>

            <div
              className="ml-auto hidden items-center gap-1.5 pr-0.5 text-[11.5px] whitespace-nowrap text-muted min-[1180px]:flex"
              aria-live="polite"
              aria-atomic="true"
            >
              {busy ? (
                <>
                  <Loader2 size={13} className="animate-spin text-primary" aria-hidden />
                  <span>Searching…</span>
                </>
              ) : (
                <>
                  <strong className="font-semibold tnum text-text">{total}</strong>
                  <span>
                    {appliedQuery
                      ? `${total === 1 ? 'result' : 'results'} for “${appliedQuery}”`
                      : total === 1
                        ? 'record'
                        : 'records'}
                  </span>
                </>
              )}
            </div>
          </div>

          {chips.length > 0 && (
            <div className="mt-2.5 flex flex-wrap items-center gap-1.5 border-t border-border pt-2.5">
              {chips.map((chip) => (
                <span
                  key={chip.key}
                  className="inline-flex h-[26px] items-center gap-1 rounded-md border border-border bg-bg py-0 pl-2 pr-1 text-[11px] whitespace-nowrap text-text"
                >
                  <span className="text-muted">{chip.label}:</span> {chip.value}
                  <button
                    type="button"
                    onClick={() => setFilter(chip.key, '')}
                    className="inline-flex h-[18px] w-[18px] cursor-pointer items-center justify-center rounded text-muted hover:bg-slate-200 hover:text-text"
                    aria-label={`Remove ${chip.label} filter`}
                  >
                    <X size={12} />
                  </button>
                </span>
              ))}
              <button
                type="button"
                onClick={clearFilters}
                className="h-[26px] cursor-pointer rounded px-1.5 text-[11px] font-semibold text-primary-dark hover:bg-teal-50"
              >
                Clear filters
              </button>
            </div>
          )}
        </div>

        {error ? (
          <ErrorState message={error} onRetry={() => setRefreshKey((key) => key + 1)} />
        ) : (
          <>
            <div className={cn('transition-opacity', loading && data !== null && 'opacity-60')}>
              <DataTable<AttendanceAdmin>
                loading={loading && data === null}
                rows={data ?? []}
                keyOf={(row) => row.id}
                emptyTitle={emptyCopy.title}
                emptyDescription={emptyCopy.description}
                emptyAction={emptyAction}
                minWidthClassName="min-w-[880px]"
                onRowClick={setSelected}
                columns={[
                  {
                    key: 'employee',
                    header: 'Employee',
                    render: (row) => (
                      <div className="flex items-center gap-2.5">
                        <Avatar name={row.employee.name} size="sm" />
                        <div className="min-w-0">
                          <div className="truncate font-medium text-text">{row.employee.name}</div>
                          <div className="font-mono text-[11px] tnum text-muted">{row.employee.employee_id}</div>
                        </div>
                      </div>
                    ),
                  },
                  {
                    key: 'time',
                    header: 'Time',
                    render: (row) => (
                      <div>
                        <div className="font-mono text-sm font-semibold tnum text-text">
                          {formatTime(row.timestamp)}
                        </div>
                        <div className="whitespace-nowrap text-[11px] text-muted">
                          {formatDate(row.timestamp)}
                        </div>
                      </div>
                    ),
                  },
                  {
                    key: 'type',
                    header: 'Type',
                    render: (row) => <TypeBadge type={row.type} />,
                  },
                  {
                    key: 'status',
                    header: 'Status',
                    render: (row) => (
                      <div className="flex flex-wrap gap-1">
                        {row.is_overbreak && <Badge tone="red">Overbreak</Badge>}
                        {row.photo && <Badge tone="teal">Selfie</Badge>}
                        {!row.is_overbreak && !row.photo && <span className="text-xs text-muted">—</span>}
                      </div>
                    ),
                  },
                  {
                    key: 'work',
                    header: 'Duration',
                    render: (row) => (
                      <span className="inline-flex items-center gap-1 font-mono text-xs tnum text-muted">
                        <Clock size={12} />
                        {row.type === 'break_out' && row.break_minutes != null
                          ? `${row.break_minutes}m`
                          : formatMinutes(row.work_minutes)}
                      </span>
                    ),
                  },
                  {
                    key: 'branch',
                    header: 'Branch',
                    render: (row) => <span className="text-sm text-text">{row.branch?.name ?? '—'}</span>,
                  },
                  {
                    key: 'flags',
                    header: 'Flags',
                    render: (row) =>
                      row.fraud_flags.length === 0 ? (
                        <span className="text-xs text-muted">—</span>
                      ) : (
                        <div className="flex flex-wrap gap-1">
                          {row.fraud_flags.map((flag) => (
                            <Badge key={flag.id} tone={FLAG_TONES[flag.type] ?? 'gray'}>
                              {FLAG_LABELS[flag.type] ?? flag.type.replace(/_/g, ' ')}
                            </Badge>
                          ))}
                        </div>
                      ),
                  },
                ]}
              />
            </div>
            {paginated && (
              <PaginationBar
                page={page}
                paginated={paginated}
                perPage={perPage}
                onPageChange={goToPage}
                onPerPageChange={changePageSize}
                disabled={loading}
              />
            )}
          </>
        )}
      </Card>

      <Drawer open={selected !== null} onClose={() => setSelected(null)} title="Attendance record" wide>
        {selected && <AttendanceDetail record={selected} />}
      </Drawer>
    </div>
  );
}

function DetailRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-start justify-between gap-4 py-2">
      <span className="text-xs font-medium text-muted">{label}</span>
      <span className="text-right text-sm text-text">{children}</span>
    </div>
  );
}

function AttendanceDetail({ record }: { record: AttendanceAdmin }) {
  const gps = record.gps_location;
  const latitude = gps?.latitude;
  const longitude = gps?.longitude;
  const employeeMeta = [record.employee.employee_id, record.employee.department, record.employee.position]
    .filter((value): value is string => Boolean(value))
    .join(' · ');

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <div className="text-base font-semibold text-text">{record.employee.name}</div>
          <div className="font-mono text-xs tnum text-muted">{employeeMeta}</div>
        </div>
        <TypeBadge type={record.type} />
      </div>

      <div className="overflow-hidden rounded-md border border-border">
        <div className="bg-bg/50 px-3 py-1.5 text-xs font-medium text-muted">Selfie</div>
        {record.photo ? (
          <PhotoViewer
            url={`${API_BASE}/api/admin/attendance/${record.id}/photo`}
            alt={`Selfie of ${record.employee.name}`}
            className="h-56 w-full"
          />
        ) : (
          <div className="flex h-40 items-center justify-center bg-bg text-xs text-muted">
            No selfie captured for this record
          </div>
        )}
      </div>

      <Card className="divide-y divide-border px-4 py-3">
        <DetailRow label="Timestamp">
          <span className="font-mono tnum">{formatDateTime(record.timestamp)}</span>
        </DetailRow>
        <DetailRow label="Branch">
          {record.branch ? `${record.branch.name} (${record.branch.code})` : '—'}
        </DetailRow>
        <DetailRow label="Work minutes">{formatMinutes(record.work_minutes)}</DetailRow>
      </Card>

      <Card className="overflow-hidden p-0">
        <div className="flex items-center gap-1.5 border-b border-border px-4 py-2.5 text-xs font-semibold text-muted">
          <MapPin size={13} />
          GPS location
        </div>
        <div className="p-3">
          {latitude != null && longitude != null ? (
            <>
              <LocationMap
                requested={{ latitude, longitude }}
                legendLabel="Punch location"
                showRadius={false}
                className="h-64"
              />
              <div className="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                <div>
                  <div className="text-muted">Distance</div>
                  <div className="mt-0.5 font-mono font-semibold tnum text-text">
                    {formatMeters(gps?.distance_from_branch_meters)}
                  </div>
                </div>
                <div>
                  <div className="text-muted">Accuracy</div>
                  <div className="mt-0.5 font-mono font-semibold tnum text-text">
                    {formatMeters(gps?.accuracy_meters)}
                  </div>
                </div>
                <div>
                  <div className="text-muted">Geofence</div>
                  <div className="mt-0.5">
                    {gps?.is_within_radius === true ? (
                      <Badge tone="green">Inside radius</Badge>
                    ) : gps?.is_within_radius === false ? (
                      <Badge tone="amber">Outside radius</Badge>
                    ) : (
                      <span className="text-xs text-muted">—</span>
                    )}
                  </div>
                </div>
              </div>
            </>
          ) : (
            <div className="px-1 py-6 text-center text-xs text-muted">
              No GPS data captured for this record.
            </div>
          )}
        </div>
      </Card>

      {record.fraud_flags.length > 0 && (
        <Card className="px-4 py-3">
          <div className="mb-2 text-xs font-semibold text-muted">Red flags</div>
          <div className="flex flex-wrap gap-1.5">
            {record.fraud_flags.map((flag) => (
              <Badge key={flag.id} tone={FLAG_TONES[flag.type] ?? 'gray'}>
                {FLAG_LABELS[flag.type] ?? flag.type.replace(/_/g, ' ')}
              </Badge>
            ))}
          </div>
        </Card>
      )}

      {record.notes && (
        <Card className="px-4 py-3">
          <div className="mb-1.5 flex items-center gap-1.5 text-xs font-semibold text-muted">
            <StickyNote size={13} />
            Notes
          </div>
          <p className="text-sm text-text">{record.notes}</p>
        </Card>
      )}

      {!record.photo && !record.gps_location && record.fraud_flags.length === 0 && (
        <EmptyState title="No additional data" description="This record has no selfie, GPS data or red flags." />
      )}
    </div>
  );
}
