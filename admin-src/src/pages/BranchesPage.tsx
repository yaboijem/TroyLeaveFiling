import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { ExternalLink, Pencil, Plus, Power, PowerOff, Trash2 } from 'lucide-react';
import { ApiError } from '../api/client';
import {
  activateBranch,
  createBranch,
  deactivateBranch,
  deleteBranch,
  listBranches,
  updateBranch,
} from '../api/endpoints';
import type { Branch } from '../api/types';
import { LocationPicker } from '../components/LocationPicker';
import { PageHeader } from '../components/PageHeader';
import {
  Badge,
  Button,
  Card,
  ErrorState,
  Field,
  Input,
  Select,
  Toggle,
} from '../components/ui';
import { DataTable } from '../components/DataTable';
import { ConfirmDialog, Modal } from '../components/Modal';
import { useToast } from '../components/Toast';

interface FormState {
  name: string;
  code: string;
  address: string;
  latitude: string;
  longitude: string;
  radius_meters: string;
  accuracy_ceiling_meters: string;
  accuracy_allowance_meters: string;
  is_active: boolean;
}

function emptyForm(): FormState {
  return {
    name: '',
    code: '',
    address: '',
    latitude: '',
    longitude: '',
    radius_meters: '300',
    accuracy_ceiling_meters: '100',
    accuracy_allowance_meters: '30',
    is_active: true,
  };
}

export function BranchesPage() {
  const navigate = useNavigate();
  const { notify } = useToast();

  const [status, setStatus] = useState('all');
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [data, setData] = useState<Branch[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Branch | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm());
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);

  const [deactivating, setDeactivating] = useState<Branch | null>(null);
  const [activating, setActivating] = useState<Branch | null>(null);
  const [deleting, setDeleting] = useState<Branch | null>(null);
  const [lifecycleBusy, setLifecycleBusy] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result = await listBranches({ status, search: query });
      setData(result);
    } catch (loadError) {
      setError(loadError instanceof ApiError ? loadError.message : 'Failed to load branches.');
    } finally {
      setLoading(false);
    }
  }, [status, query]);

  useEffect(() => {
    void load();
  }, [load]);

  function openCreate() {
    setEditing(null);
    setForm(emptyForm());
    setFieldErrors({});
    setModalOpen(true);
  }

  function openEdit(branch: Branch) {
    setEditing(branch);
    setForm({
      name: branch.name,
      code: branch.code,
      address: branch.address ?? '',
      latitude: String(branch.latitude),
      longitude: String(branch.longitude),
      radius_meters: String(branch.radius_meters),
      accuracy_ceiling_meters: String(branch.accuracy_ceiling_meters),
      accuracy_allowance_meters: String(branch.accuracy_allowance_meters),
      is_active: branch.is_active,
    });
    setFieldErrors({});
    setModalOpen(true);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();

    const latitude = form.latitude.trim() === '' ? null : Number(form.latitude);
    const longitude = form.longitude.trim() === '' ? null : Number(form.longitude);
    const coordinateErrors: Record<string, string[]> = {};

    if (latitude === null || Number.isNaN(latitude)) {
      coordinateErrors.latitude = ['Drop a pin or enter a latitude.'];
    }
    if (longitude === null || Number.isNaN(longitude)) {
      coordinateErrors.longitude = ['Drop a pin or enter a longitude.'];
    }

    if (latitude === null || Number.isNaN(latitude) || longitude === null || Number.isNaN(longitude)) {
      setFieldErrors(coordinateErrors);
      notify('error', 'Choose the branch location on the map or enter coordinates.');
      return;
    }

    setSaving(true);
    setFieldErrors({});

    try {
      const payload = {
        name: form.name.trim(),
        code: form.code.trim().toUpperCase(),
        address: form.address.trim() || null,
        latitude,
        longitude,
        radius_meters: Number(form.radius_meters),
        accuracy_ceiling_meters: Number(form.accuracy_ceiling_meters),
        accuracy_allowance_meters: Number(form.accuracy_allowance_meters),
        is_active: form.is_active,
      };

      if (editing) {
        await updateBranch(editing.id, payload);
        notify('success', 'Branch updated.');
      } else {
        await createBranch(payload);
        notify('success', 'Branch created.');
      }

      setModalOpen(false);
      void load();
    } catch (submitError) {
      if (submitError instanceof ApiError) {
        setFieldErrors(submitError.errors ?? {});
        notify('error', submitError.message);
      } else {
        notify('error', 'Unexpected error. Please try again.');
      }
    } finally {
      setSaving(false);
    }
  }

  async function handleDeactivate() {
    if (!deactivating) return;
    setLifecycleBusy(true);

    try {
      await deactivateBranch(deactivating.id);
      notify('success', 'Branch deactivated.');
      setDeactivating(null);
      void load();
    } catch (lifecycleError) {
      notify('error', lifecycleError instanceof ApiError ? lifecycleError.message : 'Failed to deactivate the branch.');
    } finally {
      setLifecycleBusy(false);
    }
  }

  async function handleActivate() {
    if (!activating) return;
    setLifecycleBusy(true);

    try {
      await activateBranch(activating.id);
      notify('success', 'Branch activated.');
      setActivating(null);
      void load();
    } catch (lifecycleError) {
      notify('error', lifecycleError instanceof ApiError ? lifecycleError.message : 'Failed to activate the branch.');
    } finally {
      setLifecycleBusy(false);
    }
  }

  async function handleDelete() {
    if (!deleting) return;

    if (deleting.employee_count > 0 || deleting.attendance_count > 0) {
      notify('error', 'This branch has employees or attendance history. Deactivate it instead.');
      setDeleting(null);
      return;
    }

    setLifecycleBusy(true);

    try {
      await deleteBranch(deleting.id);
      notify('success', 'Branch deleted.');
      setDeleting(null);
      void load();
    } catch (deleteError) {
      notify('error', deleteError instanceof ApiError ? deleteError.message : 'Failed to delete the branch.');
    } finally {
      setLifecycleBusy(false);
    }
  }

  const parsedLat = form.latitude.trim() === '' ? null : Number(form.latitude);
  const parsedLng = form.longitude.trim() === '' ? null : Number(form.longitude);
  const parsedRadius = form.radius_meters.trim() === '' ? null : Number(form.radius_meters);
  const mapLat =
    parsedLat != null && !Number.isNaN(parsedLat) && parsedLat >= -90 && parsedLat <= 90 ? parsedLat : null;
  const mapLng =
    parsedLng != null && !Number.isNaN(parsedLng) && parsedLng >= -180 && parsedLng <= 180 ? parsedLng : null;

  function handleMapPick(lat: number, lng: number) {
    setForm((previous) => ({ ...previous, latitude: String(lat), longitude: String(lng) }));
    setFieldErrors((previous) => {
      const next = { ...previous };
      delete next.latitude;
      delete next.longitude;
      return next;
    });
  }

  return (
    <div>
      <PageHeader
        title="Branches"
        description="Locations and GPS geo-fencing settings"
        actions={
          <Button onClick={openCreate}>
            <Plus size={15} />
            Add branch
          </Button>
        }
      />

      <Card className="mb-4 p-3 shadow-sm sm:p-4">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <Select
              value={status}
              onChange={(event) => setStatus(event.target.value)}
              className="sm:w-44"
              aria-label="Branch status"
            >
              <option value="all">All branches</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </Select>
            <form
              className="flex items-center gap-2"
              onSubmit={(event) => {
                event.preventDefault();
                setQuery(search.trim());
              }}
            >
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search name, code, or address"
                aria-label="Search branches"
              />
              <Button type="submit" variant="secondary">
                Search
              </Button>
            </form>
          </div>
          <div className="text-xs text-muted">
            {data ? `${data.length} branch${data.length === 1 ? '' : 'es'}` : 'Loading…'}
          </div>
        </div>
      </Card>

      <Card>
        {error ? (
          <ErrorState message={error} onRetry={load} />
        ) : (
          <DataTable<Branch>
            loading={loading && !data}
            rows={data ?? []}
            keyOf={(row) => row.id}
            emptyTitle="No branches found"
            emptyDescription="Create a branch or adjust the filters."
            columns={[
              {
                key: 'name',
                header: 'Branch',
                render: (row) => (
                  <div>
                    <div className="font-medium text-text">{row.name}</div>
                    <div className="text-xs text-muted">{row.code}</div>
                  </div>
                ),
              },
              {
                key: 'address',
                header: 'Address',
                render: (row) => <span className="text-xs text-muted">{row.address ?? '—'}</span>,
              },
              {
                key: 'coords',
                header: 'Location',
                render: (row) => (
                  <a
                    href={`https://www.google.com/maps?q=${row.latitude},${row.longitude}&z=16`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                  >
                    View on map
                    <ExternalLink size={11} />
                  </a>
                ),
              },
              {
                key: 'radius',
                header: 'Radius',
                render: (row) => <span className="text-xs text-muted">{row.radius_meters} m</span>,
              },
              {
                key: 'employees',
                header: 'Employees',
                render: (row) => (
                  <button
                    type="button"
                    onClick={() => navigate(`/assignments?branch_id=${row.id}`)}
                    className="text-sm font-semibold text-primary hover:underline"
                    title="Open assignments for this branch"
                  >
                    {row.employee_count}
                  </button>
                ),
              },
              {
                key: 'attendance',
                header: 'Records',
                render: (row) => <span className="text-sm text-text">{row.attendance_count}</span>,
              },
              {
                key: 'status',
                header: 'Status',
                render: (row) => (
                  <div className="flex flex-col items-start gap-1">
                    {row.is_active ? <Badge tone="green">Active</Badge> : <Badge tone="gray">Inactive</Badge>}
                    {row.is_active && row.employee_count > 0 ? (
                      <button
                        type="button"
                        onClick={() => navigate(`/assignments?branch_id=${row.id}`)}
                        className="text-left text-[11px] font-semibold text-amber-700 hover:underline"
                      >
                        Move {row.employee_count} employee{row.employee_count === 1 ? '' : 's'} first
                      </button>
                    ) : null}
                  </div>
                ),
              },
              {
                key: 'actions',
                header: '',
                className: 'w-28',
                render: (row) => {
                  const blockedByEmployees = row.employee_count > 0;
                  const blockedByHistory = row.employee_count > 0 || row.attendance_count > 0;

                  return (
                    <div className="flex items-center gap-1">
                      <button
                        type="button"
                        onClick={() => openEdit(row)}
                        aria-label={`Edit ${row.name}`}
                        className="cursor-pointer rounded p-1.5 text-muted hover:bg-bg hover:text-primary"
                        title="Edit"
                      >
                        <Pencil size={14} />
                      </button>

                      {row.is_active ? (
                        <button
                          type="button"
                          onClick={() => setDeactivating(row)}
                          disabled={blockedByEmployees}
                          aria-label={`Deactivate ${row.name}`}
                          className="cursor-pointer rounded p-1.5 text-muted hover:bg-bg hover:text-amber-700 disabled:cursor-not-allowed disabled:opacity-35"
                          title={blockedByEmployees ? `Move ${row.employee_count} employees first` : 'Deactivate'}
                        >
                          <PowerOff size={14} />
                        </button>
                      ) : (
                        <button
                          type="button"
                          onClick={() => setActivating(row)}
                          aria-label={`Activate ${row.name}`}
                          className="cursor-pointer rounded p-1.5 text-muted hover:bg-bg hover:text-emerald-700"
                          title="Activate"
                        >
                          <Power size={14} />
                        </button>
                      )}

                      <button
                        type="button"
                        onClick={() => setDeleting(row)}
                        disabled={blockedByHistory}
                        aria-label={`Delete ${row.name}`}
                        className="cursor-pointer rounded p-1.5 text-muted hover:bg-bg hover:text-danger disabled:cursor-not-allowed disabled:opacity-35"
                        title={blockedByHistory ? 'Has history — deactivate instead' : 'Delete'}
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  );
                },
              },
            ]}
          />
        )}
      </Card>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? `Edit ${editing.name}` : 'Add branch'} wide>
        <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Field label="Name" required error={fieldErrors.name?.[0]}>
            <Input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Code" required error={fieldErrors.code?.[0]}>
            <Input
              value={form.code}
              onChange={(event) => setForm({ ...form, code: event.target.value })}
              placeholder="e.g. MKT"
            />
          </Field>
          <div className="sm:col-span-2">
            <Field label="Address" error={fieldErrors.address?.[0]}>
              <Input value={form.address} onChange={(event) => setForm({ ...form, address: event.target.value })} />
            </Field>
          </div>
          <div className="sm:col-span-2">
            <div className="mb-1.5 flex flex-wrap items-center justify-between gap-2">
              <span className="text-xs font-medium text-muted">Branch location</span>
              <span className="text-[11px] text-muted">Click map or drag pin · or type coordinates</span>
            </div>
            {modalOpen && (
              <LocationPicker
                key={editing ? `edit-${editing.id}` : 'create'}
                latitude={mapLat}
                longitude={mapLng}
                radiusMeters={parsedRadius != null && !Number.isNaN(parsedRadius) ? parsedRadius : null}
                onChange={handleMapPick}
                className="h-72"
              />
            )}
          </div>

          <Field label="Latitude" required error={fieldErrors.latitude?.[0]}>
            <Input
              type="number"
              step="any"
              required
              value={form.latitude}
              onChange={(event) => setForm({ ...form, latitude: event.target.value })}
              placeholder="e.g. 14.554729"
            />
          </Field>
          <Field label="Longitude" required error={fieldErrors.longitude?.[0]}>
            <Input
              type="number"
              step="any"
              required
              value={form.longitude}
              onChange={(event) => setForm({ ...form, longitude: event.target.value })}
              placeholder="e.g. 121.024445"
            />
          </Field>
          <Field label="Radius (meters)" required error={fieldErrors.radius_meters?.[0]}>
            <Input
              type="number"
              min="1"
              max="10000"
              value={form.radius_meters}
              onChange={(event) => setForm({ ...form, radius_meters: event.target.value })}
            />
          </Field>
          <Field label="GPS accuracy ceiling (m)" required error={fieldErrors.accuracy_ceiling_meters?.[0]}>
            <Input
              type="number"
              min="1"
              max="10000"
              value={form.accuracy_ceiling_meters}
              onChange={(event) => setForm({ ...form, accuracy_ceiling_meters: event.target.value })}
            />
          </Field>
          <Field label="GPS accuracy allowance (m)" required error={fieldErrors.accuracy_allowance_meters?.[0]}>
            <Input
              type="number"
              min="0"
              max="10000"
              value={form.accuracy_allowance_meters}
              onChange={(event) => setForm({ ...form, accuracy_allowance_meters: event.target.value })}
            />
          </Field>
          <div className="flex items-center gap-2">
            <Toggle
              checked={form.is_active}
              onChange={(value) => setForm({ ...form, is_active: value })}
              label="Branch active"
            />
            <span className="text-sm text-text">Branch active</span>
          </div>

          <div className="mt-4 flex justify-end gap-2 sm:col-span-2">
            <Button variant="secondary" onClick={() => setModalOpen(false)} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" loading={saving}>
              {editing ? 'Save changes' : 'Create branch'}
            </Button>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        open={deactivating !== null}
        onClose={() => setDeactivating(null)}
        onConfirm={handleDeactivate}
        title="Deactivate branch"
        confirmLabel="Deactivate"
        loading={lifecycleBusy}
        message={
          <>
            Deactivate <strong>{deactivating?.name}</strong>? It will disappear from new assignments and
            punch validation. Employees and attendance history are preserved.
          </>
        }
      />

      <ConfirmDialog
        open={activating !== null}
        onClose={() => setActivating(null)}
        onConfirm={handleActivate}
        title="Activate branch"
        confirmLabel="Activate"
        loading={lifecycleBusy}
        message={
          <>
            Activate <strong>{activating?.name}</strong>? It will become available for assignments and punches.
          </>
        }
      />

      <ConfirmDialog
        open={deleting !== null}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        title="Delete branch"
        confirmLabel="Delete"
        danger
        loading={lifecycleBusy}
        message={
          <>
            Delete <strong>{deleting?.name}</strong>? This cannot be undone. Only branches with no employees
            and no attendance history can be deleted.
          </>
        }
      />
    </div>
  );
}
