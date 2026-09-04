# Version 007.4 Validation Record

## Stage 4 user experience and miscellaneous fixes

- Inline server data is HTML-safe JSON encoded, and user-visible dynamic values are escaped.
- Logout requires POST and CSRF validation.
- Work Plan evidence requires a genuine PHP upload with positive bounded size, an allowlisted MIME type matching the decoded image, safe generated object keys, dimension limits, and SHA-256 verification.
- Existing evidence is removed after commit; rollback removes new uploads; failed deletion is queued.
- Work Plan and Submittal selectors visibly disable duplicated BOQ references, and all linked-record actions reject stale or ambiguous references server-side.

## Stage 3 workflow and calculation accuracy

- Work Plan dates are format-checked and chronological; installed quantities must be numeric, non-negative, and cannot exceed the approved quantity for quantity-based activities.
- Project and Submittal dates are validated server-side, including required lifecycle dates for submitted and approved MAS records.
- Quantity-based progress uses the maximum cumulative installed quantity across stages to prevent duplicate counting.
- BOQ edits and linked Work Plan/Procurement edits recalculate the previous and current BOQ references.
- Procurement PO, required-on-site, expected-delivery, and actual-delivery dates are present in the schema, action, form, and detail view.
- Work Pending maps to exactly `0.00%`, and the validator rejects obsolete one-percent fallbacks.

Validated on 2 September 2026.

## Release identity

- `VERSION.txt`, the project directory, ZIP package, release specification, and validation record use Version 007.4 naming.
- The application reads the running version from `VERSION.txt` and displays it in the authenticated and authentication interfaces.
- Existing installations require only the consolidated `database_upgrade.sql`; it includes all Version 003–007 additive changes in one import.

## Static validation scope

- Route, controller, action, middleware, view, and wrapper references.
- CSRF and POST-only logout contracts.
- Cross-project edit guards and current-session invalidation controls.
- Project role schema, permission catalogue, REST routes, soft revocation, dual-write rollback compatibility, optimistic concurrency, and assignment audit contracts.
- Apache denial rules for internal tools, source directories, configuration, migrations, and SQL files.
- Server-derived Work Plan and Procurement reference contracts and old/new BOQ recalculation.
- Hashed, expiring, single-use password reset schema and SMTP environment contract.
- Production-safe database, session, signup, cookie, and diagnostic defaults.
- Projects action supplies typed edit/assignment permission flags to its view; templates do not call PDO authorization helpers directly.
- Project Progress GridView column and responsive-view contracts.
- Procurement date schema and Management Alert query compatibility.
- Work Plan upload path, image cleanup, numeric/date validation, and BOQ recalculation rules.
- Work Plan record deletion commits its database, audit, and progress changes before deleting evidence files.
- Stored MAS links are restricted to HTTP/HTTPS on save and again at every server/JavaScript render path.
- Clean database schema and seed-data policy.
- JavaScript syntax and ZIP compressed-data integrity.

## Runtime acceptance

This build environment does not contain PHP or MySQL/MariaDB executables, so it cannot truthfully execute XAMPP runtime acceptance. Run `C:\xampp\php\php.exe tools\xampp_acceptance.php` and the browser scenarios in `INSTALL.txt` against a disposable XAMPP database before production deployment. The CLI test is read-only and exits non-zero on any missing runtime extension, table, column, role seed, permission seed, or membership backfill.
