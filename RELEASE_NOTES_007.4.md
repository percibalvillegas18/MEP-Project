# Version 007.4 — Release Specification

## Version 007.4 conversion-readiness implementation

### Stage 4 — User Experience and Miscellaneous Fixes

- Encodes server-provided JavaScript data with HTML-safe JSON flags and escapes dynamic modal values.
- Requires POST plus CSRF validation for logout.
- Restricts evidence to verified image uploads with uploaded-file origin, positive bounded size, MIME/decoded-image agreement, dimensions, generated safe names, and SHA-256 verification.
- Performs evidence deletion only after database commit, deletes newly created objects on rollback, and queues failed cleanup.
- Flags duplicate BOQ activities in both Work Plan and Submittal selectors and rejects stale or ambiguous links server-side.

### Stage 3 — Workflow and Calculation Accuracy

- Validates Work Plan date chronology and rejects invalid or negative installed quantities.
- Validates Project and Submittal dates on the server, including lifecycle-required dates.
- Uses the maximum cumulative installed quantity across stages so quantity progress is not counted repeatedly.
- Recalculates both old and new BOQ references whenever an edit changes the linked BOQ.
- Provides PO, required-on-site, expected-delivery, and actual-delivery dates throughout Procurement.
- Defines Work Pending as exactly `0.00%` in PHP and JavaScript calculation paths.

- Makes approved cumulative installed quantity the authoritative item-progress source; Activity Weight is used only for discipline and project roll-up.
- Returns `0.00%` and Not Started when total eligible Activity Weight is zero.
- Enforces `DECIMAL(5,2)` progress and asymmetric rounding: an exact result of 100 becomes `100.00`, while every lower result is capped at `99.99`.

- Detects `localhost`, `127.0.0.1`, and `::1` automatically for XAMPP/PHP local development, preventing the production database-credential guard and secure-cookie policy from causing a generic local HTTP 500.
- Keeps production and debug settings explicit and secure for all non-local hosts; `.htaccess` no longer forces development mode globally.

- Defines quantity-based item progress and weighted project roll-up precedence.
- Prevents division-by-zero and `NaN` project progress, returning `0.00%` for zero total weight.
- Stores deterministic two-decimal progress and never rounds a value below 100% into completion.
- Adds `completion_date_source` so recalculation cannot clear manual completion dates.
- Requires quoted numeric `If-Match` entity tags for assignment updates and deletes; missing tags return 428 and stale tags return 412.
- Adds explicit procurement currency with SAR as the configurable default.
- Adds queue lifecycle, retry, error, next-attempt, and idempotency fields plus a CLI queue processor.
- Adds Riyadh business-display timezone and UTC database timestamp policy.
- Migrates Work Plan evidence to native S3-compatible object storage for hosted deployments, with SigV4 uploads, integrity-checked HEAD verification, private presigned GET URLs, and object-store deletion/cleanup. Local storage remains available for XAMPP development.
- Bundles `MEP_Project_Portal_Version_007.4.md` as the authoritative implementation and conversion specification.

- Visible running-version identity sourced from `VERSION.txt` and displayed throughout the application.
- Production-safe default error mode with detailed diagnostics available only when explicitly enabled.
- Session invalidation after account status, role, or password changes.
- Project-scoped RBAC for Project Manager, Project Engineer, MEP Engineer, Coordinator, and Viewer; changes require a reason and are audited.
- Versioned assignment REST endpoints with validation, soft revocation, least privilege, row filtering, and optimistic concurrency.
- One-time password reset links stored only as SHA-256 hashes and delivered through environment-configured SMTP.
- Work Plan BOQ/discipline and Procurement BOQ/MAS metadata are derived from server-side records, never trusted from browser fields.
- Both the old and new BOQ references are recalculated after linked-record edits.
- Direct web access to internal tools and database migrations is denied by Apache rules.
- Idempotent compatibility repairs prevent duplicate-column failures on partially upgraded databases.
- Obsolete PostgreSQL/UUID controllers and models removed.
- CSRF-protected signup, administrator setup, and POST-only logout.
- Same-project authorization for Procurement, Material Submittals, and Work Plans.
- Correct Work Plan upload path, transactional replacement cleanup, 0% pending status, date validation, and non-negative installed quantities.
- Safe HTTP/HTTPS validation for MAS document links and safer new-window links.
- Project date validation and Work Plan image cleanup when a project is deleted.
- Procurement PO, required-on-site, expected-delivery, and actual-delivery dates.
- Management alerts based on expected delivery dates with completed activity false positives excluded.
- Quantity-based progress uses cumulative installed quantity without counting the same quantity at every stage.
- Automatic completion dates clear when automated progress falls below 100%.
- Material Submittal Actual Start is recalculated from the earliest valid date after add, edit, or delete.
- Project Progress GridView retains one Task Description column, aligned actions, and responsive Standard, Compact, and Full modes.
- Clean installer with the current schema and four default disciplines only.
- Version identifier: `007.4`.
- Encodes every server-provided JavaScript data object with HTML-safe JSON flags.
- Keeps logout POST-only and CSRF protected.
- Accepts only decoded image formats; macro, formula, document, and executable uploads are not supported.
- Stores and verifies SHA-256 checksums before and after moving Work Plan evidence.
- Queues failed post-commit evidence deletion for recoverable cleanup.
- Stores the exact BOQ record ID on Submittals and blocks stale or duplicated measurable references.
- Rejects non-numeric, negative, or quantity-based cumulative installed quantities above the approved BOQ quantity.
- Quantity-based progress uses the maximum cumulative quantity across stages, preventing repeat counting.
- Requires Submitted Date after a MAS leaves Planned status and whenever an Approved Date is present.
- Removes all remaining PHP and JavaScript fallbacks that displayed Work Pending as 1%.
- Legacy unsafe MAS links are suppressed at every render path as well as rejected during save.
- Work Plan evidence files are deleted only after the database deletion, audit entry, and BOQ recalculation commit successfully.
