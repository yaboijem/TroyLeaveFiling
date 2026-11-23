import type { ReactNode } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { Paginated } from '../api/types';
import { cn } from '../lib/cn';

export function DataTable<T>({
  columns,
  rows,
  keyOf,
  loading,
  emptyTitle,
  emptyDescription,
  emptyAction,
  minWidthClassName,
  onRowClick,
}: {
  columns: {
    key: string;
    header: ReactNode;
    className?: string;
    align?: 'left' | 'center' | 'right';
    render: (row: T) => ReactNode;
  }[];
  rows: T[];
  keyOf: (row: T) => string | number;
  loading?: boolean;
  emptyTitle: string;
  emptyDescription?: string;
  emptyAction?: ReactNode;
  minWidthClassName?: string;
  onRowClick?: (row: T) => void;
}) {
  if (loading) {
    return (
      <div className="overflow-hidden">
        <div className="divide-y divide-border">
          {Array.from({ length: 8 }).map((_, i) => (
            <div key={i} className="flex items-center gap-4 px-4 py-3.5">
              {columns.map((col) => (
                <div key={col.key} className={cn('h-4 flex-1 animate-pulse rounded bg-slate-200/80', col.className)} />
              ))}
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (rows.length === 0) {
    return (
      <div className="flex flex-col items-center gap-1 py-16 text-center">
        <span className="text-sm font-medium text-text">{emptyTitle}</span>
        {emptyDescription && <span className="max-w-md text-xs text-muted">{emptyDescription}</span>}
        {emptyAction && <div className="mt-3">{emptyAction}</div>}
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className={cn('w-full text-left text-sm', minWidthClassName)}>
        <thead className="sticky top-0 z-10">
          <tr className="border-b border-border bg-slate-50 text-[11px] uppercase tracking-wide text-muted">
            {columns.map((col) => (
              <th
                key={col.key}
                className={cn('px-4 py-2.5 font-semibold', col.className)}
                style={col.align ? { textAlign: col.align } : undefined}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {rows.map((row) => (
            <tr
              key={keyOf(row)}
              tabIndex={onRowClick ? 0 : undefined}
              onClick={() => onRowClick?.(row)}
              onKeyDown={(e) => {
                if (onRowClick && (e.key === 'Enter' || e.key === ' ')) {
                  e.preventDefault();
                  onRowClick(row);
                }
              }}
              className={cn(
                'transition-colors',
                onRowClick &&
                  'cursor-pointer hover:bg-teal-50/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/40',
              )}
            >
              {columns.map((col) => (
                <td
                  key={col.key}
                  className={cn('px-4 py-3 align-middle', col.className)}
                  style={col.align ? { textAlign: col.align } : undefined}
                >
                  {col.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export const PAGE_SIZES = [10, 30, 50, 100, 500] as const;
export const DEFAULT_PAGE_SIZE: number = PAGE_SIZES[0];

export function PaginationBar({
  page,
  paginated,
  perPage,
  onPageChange,
  onPerPageChange,
  pageSizes = PAGE_SIZES,
  numbered = false,
  disabled = false,
}: {
  page: number;
  paginated: Paginated<unknown>;
  perPage: number;
  onPageChange: (page: number) => void;
  onPerPageChange: (perPage: number) => void;
  pageSizes?: readonly number[];
  numbered?: boolean;
  disabled?: boolean;
}) {
  if (paginated.total === 0) return null;
  const from = paginated.from ?? 0;
  const to = paginated.to ?? 0;
  const visiblePages = Array.from(new Set([
    1,
    ...Array.from({ length: 3 }, (_, i) => page + i - 1).filter((p) => p > 1 && p < paginated.last_page),
    paginated.last_page,
  ])).sort((a, b) => a - b);
  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3 text-xs text-muted">
      <span className="font-mono tnum">
        Showing {from}–{to} of {paginated.total}
      </span>
      <div className="flex flex-wrap items-center gap-3">
        <label className="flex items-center gap-2">
          <span>Show</span>
          <select
            value={perPage}
            disabled={disabled}
            aria-label="Rows per page"
            onChange={(e) => onPerPageChange(Number(e.target.value))}
            className="h-8 rounded-lg border border-border bg-white px-2 text-xs text-text"
          >
            {pageSizes.map((size) => (
              <option key={size} value={size}>
                {size}
              </option>
            ))}
          </select>
        </label>
        <div className="flex items-center gap-1">
        <button
          type="button"
          disabled={disabled || !paginated.prev_page_url}
          onClick={() => onPageChange(page - 1)}
          className="rounded-lg border border-border bg-white p-1.5 text-text hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer"
          aria-label="Previous page"
        >
          <ChevronLeft size={14} />
        </button>
        {numbered ? visiblePages.map((p, i) => (
          <span key={p} className="inline-flex items-center gap-1">
            {i > 0 && p > visiblePages[i - 1] + 1 && <span className="px-1" aria-hidden>…</span>}
            <button
              type="button"
              aria-label={`Page ${p}`}
              aria-current={p === page ? 'page' : undefined}
              disabled={disabled}
              onClick={() => onPageChange(p)}
              className={cn(
                'min-h-8 min-w-8 rounded-lg border px-2 font-mono tnum disabled:cursor-not-allowed disabled:opacity-50',
                p === page ? 'border-primary bg-teal-50 text-primary-dark' : 'border-border bg-white text-text hover:bg-slate-50',
              )}
            >{p}</button>
          </span>
        )) : <span className="font-mono tnum px-2">
          Page {paginated.current_page} of {paginated.last_page}
        </span>}
        <button
          type="button"
          disabled={disabled || !paginated.next_page_url}
          onClick={() => onPageChange(page + 1)}
          className="rounded-lg border border-border bg-white p-1.5 text-text hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer"
          aria-label="Next page"
        >
          <ChevronRight size={14} />
        </button>
        </div>
      </div>
    </div>
  );
}
