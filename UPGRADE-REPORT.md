# AcademicHub Nepal upgrade

## Analysis before implementation

The application lives in the nested `Academic Hub` directory. Public pages use shared header/footer includes; most administrator pages have independent document heads and a shared sidebar. Existing functionality includes resource CRUD, student accounts, permission checkboxes, QR approval, ePay checkout, receipts, downloads, profile management, reviews and support requests. Registration redirects to the unified login/register page.

The starting working tree already contained extensive changes. These were preserved. No database schema or stored records were deliberately migrated by this upgrade.

The main findings were inconsistent styles, large page-local styles, missing mobile filters, unchecked payment callbacks, two payment demo bypasses, hardcoded authentication and password-change bypasses, permission checks missing from several admin routes, missing service-form CSRF, public receipt images, and database seeding/migration side effects on normal page loads.

## Phase 1 — shared design system

`assets/css/design-system.css` defines the requested navy, blue, teal, gold, background, card, text and border tokens, with compatibility aliases for existing components. It provides consistent cards, typography, buttons, inputs, tables, sidebar navigation, footer, focus indicators, reduced motion and responsive rules. Existing CSS remains to preserve specialized layouts.

## Phase 2 — public frontend

Shared styles cover home, catalog, resource details, services, team, about, login/register, checkout, receipts and error pages. Home has campus/program search selectors, live database statistics, animated counters and a scrollable featured-resource carousel. The catalog adds a mobile bottom drawer, maximum-price filtering and combined year/semester labeling using the existing schema. Forms have accessible label associations, submission feedback, upload selection details and image previews. Skip navigation and reduced-motion support are included. Toast and table-search text is escaped; exported CSV cells neutralize spreadsheet formula prefixes.

## Phase 3 — dashboards

Student and administrative pages inherit shared cards, table, form and navigation styling. The existing student purchase/download/order/profile workflows remain. Admin overview includes accessible bar charts for monthly revenue, campus sales, program sales and popular resources, visible to users with reporting permission. Existing visual permission checkboxes are retained. Resource add/edit use a shared file validation helper.

## Phase 4 — security hardening

- Removed hardcoded login provisioning, password bypasses and demo credential controls.
- Removed checkout's instant-demo payment unlock and callback demo simulation.
- ePay verifies exact signed fields, HMAC, UUID, merchant, amount, status and transaction reference, then confirms status over HTTPS with certificate validation and bounded timeouts. Conditional order updates prevent replay from incrementing popularity again.
- QR receipt submissions remain pending until an authorized administrator verifies them.
- Authorization refreshes account status and permissions from the database. Added missing permission guards to resource, order, student and report routes.
- All POST requests loading the auth helper require CSRF; service requests now include their token. Logout uses a confirmation form and POST.
- Added strict session mode, local redirect validation, explicit proxy trust and canonical APP_URL support.
- Login attempts are rate limited across sessions by IP and audited in protected local logs. Existing administrator and download database logs remain.
- Master uploads validate size, MIME, extension and Office archive structure; image previews validate actual image content. Random filenames prevent collisions.
- Missing master files return an error instead of generating a fake PDF. Downloads retain ownership/payment checks and use private no-store responses.
- Receipt uploads deny direct access. `payment-proof.php` checks ownership or order-management permission before streaming.
- Setup and diagnostics are CLI-only. Automatic legacy DB initialization requires both CLI execution and `ALLOW_DB_SETUP=1`.

Payment implementation reference: https://developer.esewa.com.np/pages/Epay-V2

## Phase 5 — verification

Completed:

- PHP syntax checks across the source tree.
- JavaScript syntax check with `node --check assets/js/main.js`.
- 15 helper regression checks in `tests/security.php`: internal/external redirect handling, malformed and valid CSRF, valid/tampered/incomplete payment signatures, amount validation and assigned/unassigned permissions.
- HTTP smoke tests: home, catalog, details, services, team, about, login/register and admin login return 200 with no PHP warnings/fatal errors in their responses.
- Direct master document and receipt image requests return 403.
- Web setup returns 403; diagnostics return 404.
- Tokenless service POST returns 403.
- Browser inspection of the narrow-screen catalog and keyboard activation of the mobile filter drawer.

Run helper checks from this directory: `C:\xampp\php\php.exe tests/security.php`.

Remaining acceptance checks before production:

- Complete real sandbox payment success, failure, duplicate callback and status-service timeout tests with merchant credentials. No live payment was performed.
- Exercise each CRUD operation with disposable data and each permission role, including revoked permissions and suspended sessions.
- Verify purchased/non-owned downloads and receipt access across two real user accounts.
- Verify Office/PDF/image uploads, oversize files, invalid content and interrupted uploads through HTTP.
- Review every page at desktop, tablet and phone widths. Authenticated dashboard visual tests have not been completed.
- Rotate or disable legacy seeded accounts whose known passwords may remain in the database. Removing code bypasses does not change existing hashes.
- Configure production APP_URL, HTTPS, merchant credentials, restricted DB credentials, log rotation and backups. Enable TRUST_PROXY only behind a trusted proxy that strips client-supplied forwarding headers.
- Review existing marketing claims, hardcoded ratings and seeded testimonials before publishing. The statistics strip is database-backed, but other pre-existing claims remain.

Scope limits: this is a broad design and security improvement, not a certification of production readiness. Existing page-local CSS has not been fully consolidated. Uploads show selected-file feedback and normal submission feedback, not a measured byte-progress bar. Server-rendered catalog navigation has no artificial loading skeleton. The existing preview templates remain illustrative; actual page previews require suitable source assets. No schema was added for a separate semester or language field. Dashboard notifications/settings/security are existing workflows, not newly invented modules.
