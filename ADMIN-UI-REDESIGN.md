# Admin enterprise UI handoff

This pass changes presentation only. It preserves existing PHP processing, SQL, authentication, roles, permissions, routes, form names, and CRUD handlers.

## Implemented
- Shared enterprise sidebar/topbar, desktop collapse and mobile navigation.
- Navigation grouped from existing rendered links, preserving server-side visibility rules; CMS shortcuts target existing tabs.
- Page finder searches the visible navigation. Profile/settings/logout destinations retain existing routes.
- Notifications reuse the existing pending-payment count.
- Refined dashboard cards and existing analytics charts; existing pending service/payment values displayed in cards.
- Modern table styling, existing client-side search/sort/pagination, mobile row cards, compact action groups, and a read-only order detail modal built from the displayed row.
- Resource form section headings, file picker/drop-zone presentation, filename/size/removal UI, and permission checkbox cards.
- Light/dark colors, Bootstrap modals, existing native confirmation prompts, loading and empty states retained.

## Verification
- 29 admin/include PHP files linted without errors; admin-ui.js syntax check passed.
- Shared design preview tested at 320, 480, 768, 1024, 1440 and 1920 pixels without document overflow.
- Sidebar collapse, navigation search, mobile expansion, dark mode and console checked in the sample-data preview.
- The real admin dashboard redirects to admin login in the available browser session. No credentials were supplied or changed, and no authentication bypass was used. Authenticated CRUD screens remain unverified.

The standalone docs/admin-shell-preview.html uses clearly labeled sample data and loads the same admin CSS/JS. It is for visual QA, not a replacement admin application.

No new growth calculations, resource-upload trend queries, download totals, or unsupported management modules were invented. They require data not loaded by the existing admin dashboard; the backend was kept fixed. Existing native confirmation prompts are retained to preserve cancel/submit behavior.

Main files: assets/css/admin-enterprise.css, assets/js/admin-ui.js, includes/admin-topbar.php, admin/sidebar.php, shared/standalone admin document heads.