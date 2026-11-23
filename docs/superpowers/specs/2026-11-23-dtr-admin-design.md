# Troy DTR Admin — Design

Date: 2026-11-23
Branch: `TLFwithadmin`
Worktree: `M:\TroyLeaveFiling-dtr-admin`

## 1. Intent

Troy already has the employee DTR portal and the attendance backend, but HR has no web UI for the things that make it work: branches, employee branch/work-arrangement assignments, and home-location approvals. Today those are CLI scripts (`portal/bin/set-profile.php`, `portal/bin/review-home-location.php`). This project adds a small admin web app at `/TroyLeaveFiling/admin/`, ported from the dtrsys React admin and trimmed to those three jobs.

The admin reuses Troy's existing employee login and session. It adds no new account system and no new employee table. Employee records (names, emails, passwords, usertypes, departments) remain owned by the legacy admin.

## 2. Goals

- Admin-only branch management: create, edit, activate/deactivate, guarded delete.
- Admin-only employee assignment: branch and work arrangement, one employee or many at once.
- Admin/approver home-location review: list, filter, inspect on a map, approve or reject with a note.
- Reuse `dtr_employee_profile`, `portal_set_profile()` semantics, `portal_review_home_location()`, portal session auth, and the existing portal API front controller.
- Serve the admin as a static React app at `/TroyLeaveFiling/admin/`, with its source in `admin-src/` inside the Troy repo.
- Keep `main` untouched until the feature branch is reviewed and merged.

## 3. Non-goals

- Employee record CRUD (names, emails, passwords, usertypes, departments, org structure).
- Attendance reports, corrections, fraud flags, open sessions, settings.
- A new login page or account system.
- The dtrsys "link to existing approved home pin" flow.
- Revoking an approved home pin directly. A newer approval supersedes it, as the portal already does.
- Offline admin support.
- Approver access to branches or assignments.
- Audit logging for branch/assignment edits in v1.
- Bulk home-location review in v1 (review is one pin at a time).

## 4. Users and access

| Role | Source | Access |
|---|---|---|
| Admin | `employeefiletbl.usertype = 'admin'` | All three screens |
| Approver | `employeefiletbl.usertype = 'approver'` | Home Locations only |
| Anyone else | `employeefiletbl.usertype` other | No access |

- **Login:** the existing root login at `/TroyLeaveFiling/index.php` ("Employee Log In"). It posts to `crudlogin.php`, which sets `idno`, `admin_or_user`, and `session_token`. The portal session cookie is path `/`, `HttpOnly`, `SameSite=Lax`, so the admin app and portal API share it.
- **No new login form.** The React app calls `/api/me` to learn the signed-in user and roles.
- **Unauthenticated:** the app redirects to `/TroyLeaveFiling/index.php?return=/TroyLeaveFiling/admin/<current-path>`.
- **Stale session:** if `/api/me` or any API call returns 401 while the app is open, redirect to `/TroyLeaveFiling/index.php?reauth=1&return=...`. With `reauth=1`, `index.php` clears the PHP session before showing the login form, so an invalidated `session_token` (or a legacy `admin_or_user` session without `idno`/`session_token`) cannot bounce the browser straight back into the app.
- **Return-path allowlist:** `index.php` currently accepts `return` only under `/TroyLeaveFiling/portal`. Extend it to accept the exact `/TroyLeaveFiling/admin` or a `/TroyLeaveFiling/admin/...` path, using the same scheme/host/`..`/encoding checks.
- **Server enforcement:** every admin endpoint must check the role itself. Hiding nav items is not security.
- **Role helper:** add `portal_require_role(?array $auth, array $roles): array` in `portal/lib/auth.php`. It throws `PortalApiException('You do not have access to this area.', 'forbidden', 403)` for a signed-in user with the wrong role. `portal_require_auth()` already handles 401.
- **Review attribution:** `home_locations.reviewed_by` is the reviewer's `employeename`, truncated to 100 characters.

## 5. Decisions locked in conversation

1. Branch **move** (coordinate change) takes effect immediately. Employees follow by `branch_id` reference; there is no per-employee propagation and no batching.
2. Branch **deactivate** is the normal close: allowed only when zero employees are assigned. It hides the branch from assignment pickers and punch validation, and keeps the row for history.
3. Branch **delete** is mistake cleanup only: allowed only when zero employees **and** zero attendance rows reference it. Otherwise return 409 and tell the admin to deactivate instead.
4. Add `branches.is_active` and nullable `branches.address`.
5. Approvers are limited to the home-location approvals screen.
6. Bulk assignment uses one combined form. Branch and arrangement each default to "No change"; at least one must be chosen. Writes run in chunks of 100.
7. Use the existing employee login; no portal-only login screen.
8. Source lives inside Troy: `admin-src/` for React/Vite source, `admin/` for the built app served at `/TroyLeaveFiling/admin/`.

## 6. Architecture

```
M:\TroyLeaveFiling\
  index.php                     # existing login; widen return allowlist
  admin-src\                    # React/Vite source (not web-served)
    .htaccess                   # Require all denied
    src\...
    package.json, vite.config.ts, tsconfig.json
  admin\                        # built static app, web-served
    index.html, assets\, .htaccess
  portal\
    api\routes.php              # add /api/admin/* routes
    api\index.php               # require new admin libs
    lib\admin_branches.php
    lib\admin_employees.php
    lib\admin_home_locations.php
    lib\auth.php                # role helper
    sql\002_branch_admin.sql    # address + is_active
    tests\...
```

- The admin app calls the same-origin portal API at `/TroyLeaveFiling/portal/api/...`.
- Writes must send `X-Requested-With: XMLHttpRequest`; the portal API already rejects writes without it (`portal_require_ajax()`).
- The frontend uses React + Vite + TypeScript + React Router + Leaflet, matching the dtrsys web stack.
- Vite `base` is `/TroyLeaveFiling/admin/`; React Router `basename` is `/TroyLeaveFiling/admin`.
- The app is a static SPA. `admin/.htaccess` does the history fallback rewrite, sets security headers and a CSP that allows OpenStreetMap tiles.

## 7. Data model and migration

Add to `branches`:

| Column | Type | Notes |
|---|---|---|
| `address` | `VARCHAR(500) NULL` | Optional; placed after `code` |
| `is_active` | `TINYINT(1) NOT NULL DEFAULT 1` | Existing rows become active |

Migration: `portal/sql/002_branch_admin.sql`, idempotent. MySQL 8 does not support `ADD COLUMN IF NOT EXISTS`, so use an `information_schema.COLUMNS` check plus prepared `ALTER TABLE` statements. Also update `portal/tests/troy_schema.sql` so tests start from the final shape. `001_schema.sql` may optionally gain the columns for fresh installs, but the idempotent 002 migration is the authoritative upgrade path.

No other schema changes. `dtr_employee_profile.branch_id` and `attendance.branch_id` keep `ON DELETE SET NULL`; the guards make that path unreachable for used branches.

## 8. API contract

Base: `/TroyLeaveFiling/portal/api`. All endpoints require a valid session. Response errors keep the existing shape `{message, code, details?, errors?}`.

List responses match the dtrsys paginated adapter:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 0,
    "last_page": 1,
    "counts": { "pending": 0, "approved": 0, "rejected": 0 }
  }
}
```

`counts` is optional and used by the home-location tabs.

### 8.1 Branches (admin only)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/admin/branches` | List branches with counts |
| POST | `/api/admin/branches` | Create |
| PATCH | `/api/admin/branches/{id}` | Update |
| POST | `/api/admin/branches/{id}/deactivate` | Soft close |
| POST | `/api/admin/branches/{id}/activate` | Reopen |
| DELETE | `/api/admin/branches/{id}` | Guarded hard delete |

Branch DTO:

```json
{
  "id": 1,
  "name": "Main Office",
  "code": "MAIN",
  "address": "123 Example St",
  "latitude": 14.5995,
  "longitude": 120.9842,
  "radius_meters": 300,
  "accuracy_ceiling_meters": 100,
  "accuracy_allowance_meters": 30,
  "is_active": true,
  "employee_count": 6,
  "attendance_count": 412,
  "created_at": "2026-11-23T08:00:00+08:00",
  "updated_at": "2026-11-23T08:00:00+08:00"
}
```

- `employee_count` counts all `dtr_employee_profile` rows with that `branch_id`.
- `attendance_count` counts all `attendance` rows with that `branch_id`.
- The list supports `?status=active|inactive|all` (default `all`) and `?search=` over name/code/address.

Validation, mirroring `StoreBranchRequest`:

| Field | Rule |
|---|---|
| `name` | required, string, max 255 |
| `code` | required, string, max 50, unique in `branches` |
| `address` | nullable, string, max 500 |
| `latitude` | required, numeric, -90..90 |
| `longitude` | required, numeric, -180..180 |
| `radius_meters` | required, integer, 1..10000 |
| `accuracy_ceiling_meters` | required, integer, 1..10000 |
| `accuracy_allowance_meters` | required, numeric, 0..10000 |
| `is_active` | optional boolean, default true on create |

Guards:

- `PATCH` is a partial update: omitted fields keep their current values. Setting `is_active=false` through `PATCH` enforces the same zero-employee guard as the deactivate endpoint, so the edit toggle cannot bypass it.
- **Deactivate:** in a transaction, lock the branch row `FOR UPDATE`, count profiles, reject with 409 `branch_has_employees` and `{employees: n}` if any. Then set `is_active=0`.
- **Delete:** in a transaction, lock the branch row, count profiles and attendance. Reject with 409 `branch_in_use` and `{employees: n, attendance: n}` if either is non-zero. Then delete.
- **Activate:** set `is_active=1`.
- A duplicate code returns 422 on the `code` field.

### 8.2 Employees and assignment (admin only)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/admin/employees` | Paginated employee list |
| POST | `/api/admin/employees/assignments/preview` | Validate a batch and return counts/warnings without writing |
| POST | `/api/admin/employees/assignments/bulk` | Assign branch and/or arrangement to 1..2000 employees |

Employee row:

```json
{
  "idno": "999999",
  "name": "Dela Cruz, Juan",
  "email": "juan@example.com",
  "department": "IT",
  "position": "Developer",
  "is_active": true,
  "has_profile": true,
  "branch": { "id": 1, "name": "Main Office", "code": "MAIN", "is_active": true },
  "work_arrangement": "hybrid",
  "home_location_status": "approved"
}
```

- `home_location_status` is `approved` if any approved pin exists, else `pending` if any pending pin exists, else `none`.
- `branch` is null when unassigned. If the assigned branch is inactive (legacy data), include it with `is_active: false` so the UI can flag it.
- `has_profile` is false when no `dtr_employee_profile` row exists. Such employees are returned with the effective `work_arrangement: "onsite"` (matching punch resolution) so the arrangement filter and warnings do not hide them; the UI can add a "No DTR profile" hint.
- The employee list is capped at `per_page=100` like the other lists, but "Select all matching filters" may page through up to 2,000 ids.
- List params: `search` (name/email/idno), `branch_id`, `arrangement` (`onsite|wfh|hybrid`), `home_location_status` (`approved|pending|none`), `active` (`1|0`; default `1`), `page`, `per_page` (default 50, max 100).
- Sort by `employeename`. Use prepared statements and escape `%`/`_` in `LIKE`.
- Joins between `employeefiletbl` and `dtr_employee_profile`/`home_locations` must use the existing `COLLATE utf8mb4_general_ci` pattern from `portal_user_dto()`; the tables do not share a collation.

Bulk assignment request:

```json
{
  "idnos": ["999999", "1015"],
  "branch_id": 1,
  "work_arrangement": "hybrid"
}
```

- At least one of `branch_id` or `work_arrangement` must be present. Sending neither is 422.
- `branch_id` must reference an **active** branch. Assigning to an inactive branch is 422.
- `work_arrangement` must be `onsite`, `wfh`, or `hybrid`.
- `idnos` is capped at 2,000 per request. "Select all matching filters" refuses to exceed it rather than silently truncating.
- The preview endpoint takes the same body and performs the same validation, then returns the same `updated/created/skipped/warnings` shape with `updated`/`created` computed but no writes. The UI calls it when the modal opens and whenever a field changes.
- All idnos are validated against `employeefiletbl`. Inactive employees are skipped, not failed.
- Employees without a profile get one created. If `work_arrangement` is not supplied, the new profile defaults to `onsite` (same as `portal_set_profile()`).
- Writes happen in chunks of 100 using `INSERT ... ON DUPLICATE KEY UPDATE` with `COALESCE` so an omitted field is not overwritten. All chunks run in one transaction; any SQL failure rolls the whole batch back.
- Lock the target branch row while validating so a concurrent deactivate cannot race the batch.
- Response:

```json
{
  "data": {
    "updated": 42,
    "created": 3,
    "skipped": 2,
    "warnings": {
      "no_home_pin": 5,
      "no_branch_for_onsite": 0
    }
  }
}
```

`no_home_pin` counts selected employees whose resulting arrangement is `wfh`/`hybrid` and who have no approved pin. `no_branch_for_onsite` counts employees whose resulting arrangement is `onsite` and who have no branch after the update. These are warnings, not errors; the UI shows them before and after applying.

`portal_set_profile()` should also reject inactive branches, so the CLI and the web agree.

### 8.3 Home locations (admin and approver)

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/admin/home-locations` | Paginated review list, default pending |
| GET | `/api/admin/home-locations/{id}` | Detail with current approved pin |
| POST | `/api/admin/home-locations/{id}/review` | Approve or reject |

Review row:

```json
{
  "id": 7,
  "employee": {
    "idno": "999999",
    "name": "Dela Cruz, Juan",
    "email": "juan@example.com",
    "department": "IT",
    "work_arrangement": "hybrid",
    "branch": { "id": 1, "name": "Main Office", "code": "MAIN" }
  },
  "label": "Home",
  "latitude": 14.6,
  "longitude": 121.0,
  "radius_meters": 200,
  "address_text": "...",
  "street": "...",
  "city": "...",
  "province": "...",
  "status": "pending",
  "created_at": "2026-11-20T18:00:00+08:00",
  "reviewed_by": null,
  "reviewed_at": null,
  "review_note": null,
  "current_approved": null
}
```

- List params: `status=pending|approved|rejected|all` (default `pending`), `search` (employee name/email or address), `page`, `per_page` (default 20, max 100).
- Sort pending first by `created_at` ascending (oldest waiting first); other statuses newest first.
- `meta.counts` returns pending/approved/rejected totals.
- Detail adds `current_approved`: the employee's currently approved pin (id, label, coords, address) or null, for side-by-side comparison in the UI.
- Review request: `{ "action": "approve|reject", "note": "..." }`.
- `note` is required for reject (422 if missing/blank), optional for approve.
- Review delegates to `portal_review_home_location($db, $id, $action, $note, $reviewedBy)`, so superseding the previous approved pin, status transitions, locking, and employee notification keep working.
- Self-review is not allowed: when the reviewer's `idno` equals the pin owner's `idno`, return 403 `cannot_review_own_pin`. This applies to admins and approvers; another admin/approver can review the pin.
- Approvers may call all three home-location endpoints; they may not call branch or employee endpoints.

## 9. Punch and assignment behavior

- `portal_resolve_location()` changes its branch join to `LEFT JOIN branches b ON b.id=p.branch_id AND b.is_active=1`. An inactive branch contributes no geofence. This is defense in depth; the deactivate guard should mean no employee is assigned to an inactive branch.
- `portal_set_profile()` rejects an inactive branch.
- Moving a branch's coordinates is one `UPDATE branches ...`; all assigned employees use the new geofence on their next punch. No employee rows are touched.
- Deactivation never deletes history, so an old attendance row still joins to its branch name.
- A hard delete is only possible when the branch has never been used, so no history can be nulled through the UI.

## 10. Frontend app

### 10.1 Shell and routing

- Routes: `/branches`, `/assignments`, `/home-locations`; index redirects by role.
- Admin sees all three nav items. Approver sees Home Locations only and lands there.
- Header shows the app name, signed-in employee name, and a logout action that posts `/api/logout` and returns to `index.php`.
- On load, call `/api/me`:
  - 401 → redirect to `index.php?reauth=1&return=...` so the stale session is cleared before the login form renders.
  - role Employee → show an access-denied panel with a link back to the portal/dashboard.
- Shared UI: page header, data table, modal/drawer, buttons, badges, inputs, selects, toasts, empty states, loading states, error boundary.
- Desktop-first, usable down to tablet width; tables become horizontally scrollable, not card-per-row.
- All timestamps display in Asia/Manila.

### 10.2 Branches page

- Table columns: Name, Code, Address, Radius, Status badge, Employees, Attendance, Actions.
- Filters: All / Active / Inactive, plus search.
- New/Edit modal fields: name, code, address, map picker, latitude, longitude, radius, accuracy ceiling, accuracy allowance, active toggle.
- Map picker: Leaflet with OpenStreetMap tiles, click/drag marker, live coordinate readout. Leaflet CSS and marker images are bundled locally; no unpkg/CDN dependency beyond map tiles.
- Row actions:
  - Edit.
  - Deactivate (active branch): blocked with a clear message when `employee_count > 0`, with a link to Assignments filtered to that branch.
  - Activate (inactive branch).
  - Delete: shown only when `employee_count = 0 && attendance_count = 0`; otherwise the menu item is disabled with "Has history — deactivate instead".
- Confirmations explain the effect. Deactivate says employees keep their history and the branch disappears from new assignments and punch checks. Delete says it cannot be undone.
- After save/activate/deactivate/delete, refresh the list and show a toast.

### 10.3 Assignments page

- Table columns: checkbox, Employee (name + idno + email), Department, Branch, Arrangement badge, Home pin status, Actions.
- Filters: search, branch (including "Unassigned"), arrangement, home-pin status, active/inactive toggle (default active).
- Pagination default 50.
- Selection:
  - Header checkbox selects the current page.
  - Selection persists across pages within the current filter.
  - A banner shows "N selected" with a "Select all M matching filters" action.
- Bulk action bar: "Assign branch / arrangement" opens the combined modal with:
  - Branch select: "No change" plus active branches.
  - Arrangement select: "No change", Onsite, WFH, Hybrid.
  - Apply disabled until at least one field is not "No change".
  - Preview warnings come from the server preview endpoint, so they are correct even for employees selected via "Select all matching filters":
    - employees without profiles will be created;
    - setting WFH/hybrid for N employees without an approved pin will block their punches until approval;
    - setting onsite for N employees with no branch will block their punches;
    - inactive branches are not offered.
  - "Select all matching filters" pages through the filtered list up to 2,000 ids; above that it refuses with a message instead of truncating.
  - Apply → bulk endpoint → result summary → refresh.
- Single-row edit uses the same modal with one idno.
- Rows whose assigned branch is inactive show a warning badge and "needs reassignment".
- WFH/hybrid employees with `home_location_status` `pending` or `none` get a link to Home Locations filtered to them.

### 10.4 Home Locations page

- Tabs: Pending (default), Approved, Rejected, All, with counts.
- Search by employee or address.
- Table columns: Employee, Arrangement, Label, Address, Submitted, Status.
- Selecting a row opens a detail drawer/panel:
  - Map with the requested pin; the current approved pin is shown in a different style so they can be compared.
  - Coordinates, radius, label, address.
  - Employee name, department, arrangement, branch.
  - Pending: Approve (optional note) and Reject (required note).
  - Reviewed: status, reviewer, date, note.
- Approve/reject calls the review endpoint, refreshes the row and counts, and shows a toast.
- No link-existing-pin, no edit, no bulk review.
- Admin and approver see the same screen.

### 10.5 Frontend source layout

```
admin-src/
  .htaccess                 # Require all denied
  index.html
  package.json
  vite.config.ts
  tsconfig.json
  src/
    main.tsx
    App.tsx
    api/client.ts           # fetch wrapper, 401 redirect, AJAX header
    api/endpoints.ts
    api/types.ts
    components/Layout.tsx
    components/DataTable.tsx
    components/Modal.tsx
    components/ui.tsx
    components/LocationPicker.tsx
    components/LocationMap.tsx
    pages/BranchesPage.tsx
    pages/AssignmentsPage.tsx
    pages/HomeLocationsPage.tsx
    styles.css
```

- Reuse and trim `M:\dtrsys-backend\web\src\pages\BranchesPage.tsx` (it already has address and `is_active`) and `HomeLocationsPage.tsx` (drop the shared-pin flow).
- Build `AssignmentsPage.tsx` from the assignment parts of `EmployeesPage.tsx`; do not copy employee CRUD.
- Reuse the dtrsys UI atoms/styles so the admin feels like the same product.
- All employee identifiers are strings (Troy `idno` is varchar); do not type them as numbers.

## 11. Security

- Same-origin only. No CORS changes.
- Session cookie is `HttpOnly`, `SameSite=Lax`, path `/`.
- Writes require the AJAX header; this is the existing CSRF mitigation and must be sent by the client.
- Role checks run on the server for every endpoint; UI gating is cosmetic.
- All SQL uses prepared statements. LIKE input escapes `%` and `_`.
- Validation errors are returned per field; no stack traces.
- `admin-src/` is denied through its own `.htaccess`; `node_modules` is gitignored and never deployed.
- `admin/.htaccess` CSP:
  - `default-src 'self'`
  - `img-src 'self' data: blob: https://tile.openstreetmap.org https://*.tile.openstreetmap.org`
  - `style-src 'self' 'unsafe-inline'` (Leaflet uses inline styles)
  - `script-src 'self'`
  - `connect-src 'self'`
  - `font-src 'self'`
  - `frame-ancestors 'none'`, `base-uri 'self'`, `form-action 'self'`
- The home-location data is PII. Only admin/approver endpoints return it.
- The app ships no secrets and no direct database access.

## 12. Deployment

1. Back up the live database.
2. Apply `portal/sql/002_branch_admin.sql` once.
3. Deploy the updated portal PHP files and route table.
4. Build `admin-src` (`npm ci && npm run build`), which writes to `admin/` with hashed assets.
5. Deploy `admin/` and its `.htaccess`.
6. Verify login return path, admin role, approver role, branch CRUD guards, bulk assignment, and one home-location approval.
7. Merge `feature/dtr-admin` to `main` only after review and tests; deployment follows the repo's normal pull/upload flow.

The built `admin/` output is committed, matching how `portal/assets` and `portal/index.html` are tracked. Vite `emptyOutDir` removes stale hashed files on each build.

## 13. Testing

### PHP tests (`portal/tests`)

- `schema_test.php`: `branches.address` and `branches.is_active` exist; default is active.
- New `admin_branches_test.php`:
  - create/update validation, duplicate code, coordinate/radius bounds;
  - deactivate blocked with employees, allowed at zero;
  - delete blocked with employees, blocked with attendance, allowed when unused;
  - list counts and status filter.
- New `admin_employees_test.php`:
  - list search/filter/pagination; missing profiles are effective onsite;
  - preview returns counts/warnings and writes nothing;
  - bulk branch-only, arrangement-only, and both;
  - no-change request rejected;
  - inactive branch rejected;
  - missing profiles created; existing profiles updated with fields preserved;
  - inactive employees skipped;
  - chunking over 100 rows (use a smaller test chunk override or 101 fixtures);
  - warnings for no home pin and no branch;
  - role gate: approver gets 403.
- New `admin_home_locations_test.php`:
  - pending default, status filter, counts, search;
  - detail includes current approved pin;
  - approve supersedes previous approved pin and notifies;
  - reject requires a note;
  - self-review returns 403;
  - approver allowed; employee/other role denied;
  - role gate: approver denied branch/employee endpoints.
- Update `gps_test.php`: inactive branch yields no branch geofence.
- Update `profile`/`core` tests: inactive branch rejected by `portal_set_profile()`.

### Frontend

- `npm run typecheck` and `npm run build` must pass.
- Manual browser checks: admin sees three screens; approver sees only Home Locations; unauthenticated redirect round-trip; branch deactivate/delete guards; combined bulk modal with "No change"; home-location approve/reject; map tiles load under the admin CSP.

## 14. Calls made by the builder (not user decisions)

- `address` is nullable and optional in the form.
- Folder names `admin-src/` and `admin/`.
- Review reject note is required; approve note is optional.
- Home-location list defaults to pending, oldest first; counts appear on tabs.
- Assignment selection persists across pages, with "select all matching filters".
- Bulk limit 1,000 idnos, chunks of 100.
- Built `admin/` output is committed, following the portal pattern.
- No audit log for branch/assignment changes in v1.
- No direct home-pin revocation in v1.

## 15. Anti-goals

- A silent `NULL` on historical attendance because a branch was deleted.
- Two different login systems or a second employee table.
- An admin UI that can edit employee identity fields.
- An approver who can change branches or assignments.
- A bulk action that overwrites a field the admin did not mean to touch.
- Shipping the full dtrsys admin bundle to Troy.
