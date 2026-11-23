import { useId } from 'react';
import { cn } from '../lib/cn';
import { formatMeters } from '../lib/format';

const EXAMPLE_ACCURACY_METERS = 30;

function validPositive(value: number | null | undefined): number | null {
  return value != null && Number.isFinite(value) && value > 0 ? value : null;
}

function validNonNegative(value: number | null | undefined): number | null {
  return value != null && Number.isFinite(value) && value >= 0 ? value : null;
}

export function GpsCheckPreview({
  radiusMeters,
  ceilingMeters,
  allowanceMeters,
  className,
}: {
  radiusMeters: number | null;
  ceilingMeters: number | null;
  allowanceMeters: number | null;
  className?: string;
}) {
  const titleId = useId();
  const radius = validPositive(radiusMeters);
  const ceiling = validPositive(ceilingMeters);
  const allowance = validNonNegative(allowanceMeters);

  if (radius == null || ceiling == null || allowance == null) {
    return (
      <section aria-labelledby={titleId} className={cn('rounded-lg bg-bg p-3', className)}>
        <h3 id={titleId} className="text-xs font-semibold text-text">
          How the GPS check works
        </h3>
        <p className="mt-1 text-xs text-muted">
          Enter the radius, accuracy ceiling, and allowance to preview the check.
        </p>
      </section>
    );
  }

  const accepted = EXAMPLE_ACCURACY_METERS <= ceiling;
  const appliedAllowance = Math.min(EXAMPLE_ACCURACY_METERS, allowance);
  const exampleLimit = radius + appliedAllowance;
  const usableAllowance = Math.min(allowance, ceiling);
  const maxLimit = radius + usableAllowance;
  const gaugeMax = Math.max(ceiling * 1.5, EXAMPLE_ACCURACY_METERS * 1.25, 1);
  const ceilingPercent = (ceiling / gaugeMax) * 100;
  const ceilingLabelPercent = Math.min(86, Math.max(14, ceilingPercent));
  const samplePercent = Math.min(96, (EXAMPLE_ACCURACY_METERS / gaugeMax) * 100);

  const centerX = 96;
  const centerY = 82;
  const outerRadius = 58;
  const scale = outerRadius / Math.max(maxLimit, 1);
  const radiusRadius = Math.min(outerRadius, Math.max(6, radius * scale));
  const exampleRadius = Math.min(outerRadius, Math.max(radiusRadius, exampleLimit * scale));
  const accuracyRadius = Math.min(outerRadius * 0.55, Math.max(3.5, EXAMPLE_ACCURACY_METERS * scale));
  const sampleX = centerX + exampleRadius * Math.cos(Math.PI / 4);
  const sampleY = centerY + exampleRadius * Math.sin(Math.PI / 4);
  const stateColor = accepted ? '#059669' : '#dc2626';
  const stateSurface = accepted ? '#ecfdf5' : '#fef2f2';

  return (
    <section aria-labelledby={titleId} className={cn('rounded-lg bg-bg p-3 sm:p-4', className)}>
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <h3 id={titleId} className="text-xs font-semibold text-text">
            How the GPS check works
          </h3>
          <p className="mt-0.5 text-[11px] text-muted">Live preview from the values above.</p>
        </div>
        <span
          className={cn(
            'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold',
            accepted ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800',
          )}
        >
          <span className={cn('h-1.5 w-1.5 rounded-full', accepted ? 'bg-success' : 'bg-danger')} aria-hidden />
          Example {accepted ? 'accepted' : 'rejected'}
        </span>
      </div>

      <div className="mt-3 grid gap-4 sm:grid-cols-[minmax(0,226px)_minmax(0,1fr)] sm:items-center">
        <div className="mx-auto w-full max-w-[226px]">
          <svg
            viewBox="0 0 192 164"
            role="img"
            className="h-auto w-full"
            aria-label={`Office radius ${formatMeters(radius)}, allowance up to ${formatMeters(
              usableAllowance,
            )}, example phone accuracy ${EXAMPLE_ACCURACY_METERS} meters`}
          >
            {usableAllowance > 0 && (
              <circle
                cx={centerX}
                cy={centerY}
                r={outerRadius}
                fill="#f59e0b"
                fillOpacity="0.07"
                stroke="#d97706"
                strokeWidth="1.5"
                strokeDasharray="5 4"
              />
            )}
            <circle
              cx={centerX}
              cy={centerY}
              r={radiusRadius}
              fill="#0d9488"
              fillOpacity="0.12"
              stroke="#0d9488"
              strokeWidth="1.5"
            />
            <line
              x1={centerX}
              y1={centerY}
              x2={sampleX}
              y2={sampleY}
              stroke="#94a3b8"
              strokeWidth="1"
              strokeDasharray="2 3"
            />
            <circle
              cx={sampleX}
              cy={sampleY}
              r={accuracyRadius}
              fill={stateColor}
              fillOpacity="0.1"
              stroke={stateColor}
              strokeWidth="1"
              strokeDasharray="3 3"
            />
            <circle cx={centerX} cy={centerY} r="7" fill="#0d9488" fillOpacity="0.16" />
            <circle cx={centerX} cy={centerY} r="3" fill="#0d9488" />
            <circle cx={sampleX} cy={sampleY} r="4" fill={stateColor} stroke="#ffffff" strokeWidth="1.5" />
          </svg>

          <div className="mt-1.5 flex flex-wrap justify-center gap-x-3 gap-y-1 text-[10px] text-muted">
            <span className="inline-flex items-center gap-1">
              <span className="h-2 w-2 rounded-full border border-primary bg-primary/20" aria-hidden />
              Radius {formatMeters(radius)}
            </span>
            {usableAllowance > 0 && (
              <span className="inline-flex items-center gap-1">
                <span className="h-2 w-2 rounded-full border border-warning bg-amber-100" aria-hidden />
                Allowance +{formatMeters(usableAllowance)}
              </span>
            )}
            <span className="inline-flex items-center gap-1">
              <span
                className="h-2 w-2 rounded-full border"
                style={{ borderColor: stateColor, backgroundColor: stateSurface }}
                aria-hidden
              />
              Example {EXAMPLE_ACCURACY_METERS} m
            </span>
          </div>
        </div>

        <div className="min-w-0">
          <div className="flex items-baseline justify-between gap-2 text-[11px]">
            <span className="font-medium text-text">Phone GPS accuracy</span>
            <span className={accepted ? 'font-semibold text-success' : 'font-semibold text-danger'}>
              {accepted ? 'accepted' : 'rejected'}
            </span>
          </div>

          <div className="relative mt-2 h-2 rounded-full bg-red-200" aria-hidden>
            <div
              className="absolute inset-y-0 left-0 rounded-full bg-emerald-500"
              style={{ width: `${ceilingPercent}%` }}
            />
            <span className="absolute -top-1 h-4 w-0.5 rounded bg-white" style={{ left: `${ceilingPercent}%` }} />
            <span
              className="absolute -top-1 h-4 w-0.5 rounded"
              style={{ left: `${samplePercent}%`, backgroundColor: stateColor }}
            />
          </div>

          <div className="relative mt-1 h-4 text-[10px] text-muted">
            <span className="absolute left-0">0 m</span>
            <span
              className="absolute -translate-x-1/2 whitespace-nowrap"
              style={{ left: `${ceilingLabelPercent}%` }}
            >
              Ceiling {formatMeters(ceiling)}
            </span>
          </div>

          <p className="mt-3 text-xs leading-relaxed text-text">
            {accepted ? (
              <>
                A <strong>{EXAMPLE_ACCURACY_METERS} m</strong> reading is accepted. It can place the employee up to{' '}
                <strong className="tnum">{formatMeters(exampleLimit)}</strong> from the pin
                {appliedAllowance > 0 ? (
                  <>
                    {' '}
                    ({formatMeters(radius)} radius + {formatMeters(appliedAllowance)} allowance)
                  </>
                ) : (
                  <> (the full {formatMeters(radius)} radius)</>
                )}
                .
              </>
            ) : (
              <>
                A <strong>{EXAMPLE_ACCURACY_METERS} m</strong> reading would be rejected because it is above the{' '}
                <strong className="tnum">{formatMeters(ceiling)}</strong> ceiling. Distance is not checked.
              </>
            )}
          </p>

          {allowance === 0 ? (
            <p className="mt-1 text-[11px] leading-relaxed text-muted">
              No allowance: the employee must stay inside the radius even with an accurate reading.
            </p>
          ) : maxLimit > exampleLimit || usableAllowance < allowance ? (
            <p className="mt-1 text-[11px] leading-relaxed text-muted">
              Maximum allowed distance: {formatMeters(maxLimit)}.
              {usableAllowance < allowance
                ? ` The ${formatMeters(ceiling)} ceiling caps the usable allowance at ${formatMeters(
                    usableAllowance,
                  )}.`
                : ''}
            </p>
          ) : null}
        </div>
      </div>
    </section>
  );
}
