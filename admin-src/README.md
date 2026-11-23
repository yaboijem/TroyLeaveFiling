# Troy DTR Admin

Session-authenticated admin for Troy's DTR system. Four screens:

- **Attendance** (admin + approver): punch-level review with search, date presets, filters, pagination, selfie/GPS/flag details, and the break switch for admins. This is the default landing page.
- **Branches** (admin): create/edit, activate/deactivate, guarded delete, map picker.
- **Assignments** (admin): search/filter employees, single edit, bulk branch/work-arrangement assignment with server-side preview.
- **Home locations** (admin + approver): review pending pins, compare with the current approved pin, approve/reject with a note.

The app calls the Troy portal API at `/TroyLeaveFiling/portal/api/admin/*` and reuses the existing employee login/session. There is no new login or account system.

## Local development

1. Log in through the real Troy app at `http://localhost/TroyLeaveFiling/index.php` so the session cookie exists.
2. In this folder run:

   ```bash
   npm install
   npm run dev
   ```

3. Open `http://localhost:5174/TroyLeaveFiling/admin/`.

Vite proxies `/TroyLeaveFiling/portal` to `http://localhost` (the Apache/PHP host). Adjust `vite.config.ts` if your local host differs.

## Build

```bash
npm run typecheck
npm run build
```

`npm run build` writes to `../admin` and copies `public/.htaccess`. The built `admin/` folder is committed, matching how `portal/assets` is tracked.

The source folder is protected by its own `Require all denied` `.htaccess`. Do not deploy `admin-src/` or `node_modules/` to the public host.

## Deploy

1. Back up the live database.
2. Apply `portal/sql/002_branch_admin.sql` once (it is idempotent and adds `branches.address` and `branches.is_active`).
3. Deploy the updated `portal/` PHP files (`lib/admin_*.php`, `api/routes.php`, `api/index.php`, `lib/auth.php`, `lib/gps.php`, `lib/profile.php`, `lib/response.php`).
4. Build and deploy `admin/` (including `.htaccess`).
5. Deploy the updated root `index.php` (admin return path + `reauth=1`).

## Roles

- `employeefiletbl.usertype = admin` — Attendance, Branches, Assignments and Home locations; can toggle breaks.
- `employeefiletbl.usertype = approver` — Attendance and Home locations; read-only on breaks.
- Everyone else — no access; the API returns 403 and the UI shows an access-denied panel.

## Notes

- Branch moves take effect immediately; employees inherit the new geofence through `branch_id` with no per-employee writes.
- Deactivating a branch requires zero assigned employees. Deleting requires zero employees **and** zero attendance history.
- Bulk assignment accepts 1–2,000 employees and writes in chunks of 100. The "Select all matching filters" action refuses to exceed 2,000 rather than truncating.
- Self-review of a home location is blocked with `403 cannot_review_own_pin`.
- A stale session redirects to `index.php?reauth=1`, which clears the session before showing the login form.
