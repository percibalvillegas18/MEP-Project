# MEP Projects Portal

## Version 007.4

**Source package:** `MEP_Projects_Version_007.4.zip`  
**Version identifier:** `007.4`  
**Application type:** PHP and MySQL/MariaDB web application  
**Release status:** Implemented and statically validated release

## 1. Project Overview

MEP Projects Portal is a project-management application for tracking MEP project records from BOQ planning through material submittal, procurement, work execution, progress measurement, reporting, and closeout.

Version 007.4 retains the earlier security and calculation controls and adds evidence checksums, recovery-safe file cleanup, stable BOQ record linking, project-scoped role assignments, improved diagnostics, and consolidated database installation and upgrade files.

## Stage 3: Workflow and Calculation Accuracy

Version 007.4 incorporates the following Stage 3 requirements:

- Chronological planned/actual date validation and non-negative cumulative installed-quantity validation for Work Plan entries.
- Strict server-side Project and Material Submittal date-format, ordering, and lifecycle validation.
- Quantity-based progress uses the maximum cumulative installed quantity across Work Plan stages, preventing repeated counting of the same physical quantity.
- Edits that change a BOQ reference recalculate both the previous and new BOQ records.
- Procurement includes PO Date, Required on Site Date, Expected Delivery Date, and Actual Delivery Date in the schema, actions, forms, and detail views.
- Work Pending always contributes `0.00%`; obsolete one-percent fallbacks are prohibited by the release validator.

## Stage 4: User Experience and Miscellaneous Fixes

Version 007.4 incorporates the following Stage 4 requirements while keeping the visible application label as **Version 007.4** without a stage prefix:

- User-provided names and other server data embedded in JavaScript use HTML-safe JSON encoding, while dynamically rendered values use text/HTML escaping.
- Logout is POST-only and CSRF protected, preventing external GET links from signing users out.
- Uploads accept only server-detected JPG, PNG, GIF, and WebP images; uploaded-file origin, size, decoded dimensions, MIME agreement, and SHA-256 integrity are verified. Original filenames and document/macro/formula extensions are never stored.
- Work Plan evidence is removed only after database commit; rollback removes newly uploaded objects, and failed post-commit deletion enters the cleanup queue.
- Duplicate BOQ references are marked and disabled in the Work Plan and Submittal selectors, while stale or duplicated references are rejected again by the backend.

These controls are implemented in the PHP package and enforced by the Version 007.4 static release validator.

## 2. Main Capabilities

- Project creation, editing, selection, and project-scoped access
- Project member and role assignment management
- BOQ-based and weighted progress tracking
- Material submittal tracking
- Procurement tracking with PO and delivery dates
- Work plans with before-and-after photographic evidence
- Supplier management
- User administration and password reset
- Workflow and audit history
- Management alerts and dashboards
- XLSX exports
- Print-ready Project Progress and Work Plan reports

## 3. Technical Requirements

- XAMPP or an equivalent Apache/PHP environment
- PHP 8.1 or later
- MySQL 8.0 or later, or MariaDB 10.4 or later
- PHP PDO MySQL extension
- PHP `fileinfo` extension
- PHP GD extension for image validation
- Chrome or Microsoft Edge for Print / Save as PDF

## 4. Architecture

The application uses an MVC-oriented structure with root-level PHP compatibility wrappers.

| Layer | Location | Responsibility |
|---|---|---|
| Front controller | `public/index.php` | Boots the application and dispatches requests |
| Routes | `routes/` | Defines web and API routes |
| Core | `app/Core/` | Request, response, routing, view rendering, and error handling |
| Middleware | `app/Middleware/` | Authentication and CSRF enforcement |
| Controllers | `app/Controllers/` | Coordinates module requests and responses |
| Actions | `app/Actions/` | Handles module use cases and write operations |
| Models | `app/Models/` | Database access and persistence |
| Services | `app/Services/` | Authorization, calculations, workflows, assignments, dashboards, and exports |
| Views | `app/Views/` | User-interface templates |
| Public wrappers | Root `*.php` files | Forward legacy URLs to centralized routes |

### Request Lifecycle

1. A request enters `index.php` or a root endpoint wrapper.
2. `public/index.php` loads the application bootstrap and route files.
3. `App\Core\Router` identifies the route and executes its middleware.
4. The controller coordinates the appropriate action, model, and service.
5. The application returns HTML, JSON, XLSX, or a print-ready report.
6. Central error handling records technical information and returns an environment-appropriate response.

## 5. Application Modules

| Module | Purpose |
|---|---|
| Dashboard | Displays project and management summaries and alerts |
| Projects | Maintains project information and active project selection |
| Project Members | Assigns project-specific users and roles |
| Project Progress | Maintains BOQ tasks, quantities, dates, weights, and completion |
| Material Submittals | Tracks MAS planning, submission, review, approval, and linked BOQ records |
| Procurement | Tracks purchasing, PO, required-on-site, expected-delivery, and actual-delivery dates |
| Work Plan | Tracks execution stages, installed quantities, dates, status, and evidence photos |
| Suppliers | Maintains supplier information |
| Users | Maintains accounts, roles, status, and authentication changes |
| Audit History | Records important workflow, assignment, and data changes |
| Reports and Exports | Provides print-ready reports and XLSX outputs |

## 6. Database Design

The canonical database is `mep_database`, using MySQL/MariaDB integer keys and plural table names.

### Main Tables

| Table | Purpose |
|---|---|
| `users` | User accounts, roles, status, passwords, and authentication version |
| `disciplines` | MEP discipline master data |
| `login_attempts` | Authentication throttling by username, IP address, and username-plus-IP |
| `password_reset_tokens` | Hashed, expiring, one-time password-reset tokens |
| `schema_migrations` | Applied database migration history |
| `roles` | Project-role definitions |
| `permissions` | Permission catalogue |
| `role_permissions` | Role-to-permission mapping |
| `projects` | Project master records |
| `project_members` | Temporary compatibility project membership records used only during the rollback window |
| `project_role_assignments` | Versioned project-specific role assignments |
| `rbac_outbox` | Transactional role-assignment events with worker status, attempts, and idempotency tracking |
| `project_progress` | BOQ row type, task, unit, approved quantity, dates, completion-date source, weight, status, and progress |
| `submittals` | Material submittals linked to projects and BOQ records |
| `procurement` | Procurement records linked to project, BOQ, and MAS data, including explicit currency |
| `suppliers` | Supplier master records |
| `workflow_status_history` | Status-transition history |
| `workplan` | Work Plan activities and installed quantities |
| `workplan_photos` | Before-and-after Work Plan evidence |
| `file_cleanup_queue` | Recoverable evidence-cleanup queue with worker status, attempts, and idempotency tracking |
| `audit_logs` | Application audit records |

### Database Files

- `database.sql` is the destructive clean installer for Version 007.4. It creates the complete current schema and four default disciplines. It removes existing application tables and must not be imported over a database containing records that must be retained.
- `database_upgrade.sql` is the single additive and data-preserving upgrade for an existing Version 001–006 database. It combines the required Version 003–007 migrations.

### Conversion Schema Requirements

The hosted conversion must add or confirm these fields and constraints:

- `project_progress.row_type` with allowed values `heading`, `group`, and `item`.
- `project_progress.unit` from a controlled unit-of-measure list.
- `project_progress.completion_date_source` with allowed values `auto` and `manual`.
- An explicit procurement currency, either per record or as a documented single-currency system setting.
- `status`, `attempts`, terminal-error information, next-attempt time, and an idempotency key for `rbac_outbox` and `file_cleanup_queue`.
- Only `item` rows may store Activity Weight, approved quantity, or progress, or be referenced by Submittals, Procurement, and Work Plans.
- Heading and Group totals are calculated from descendant items and are not stored as independent progress values.
- After the migration verification reports zero unmigrated memberships and the rollback window closes, `project_role_assignments` becomes the only authorization source and `project_members` is retired.

## 7. Progress Measurement

The portal supports quantity-based item progress and weighted project roll-up.

For a measurable BOQ item with an approved quantity greater than zero:

$$
\text{Item Progress} = \frac{\text{Maximum Cumulative Installed Quantity}}{\text{Approved BOQ Quantity}} \times 100
$$

The result is constrained to the range `0.00`–`100.00`. Items without an approved quantity fall back to an authorized manually entered percentage. Activity Weight never determines an item's own progress; it is used only to roll measurable item progress up to project level.

Overall progress is calculated as:

$$
\text{Overall Progress} = \frac{\sum(\text{Activity Weight} \times \text{Item Progress})}{\sum(\text{Activity Weight})}
$$

Quantity-based progress uses the maximum cumulative installed quantity across Work Plan stages. This avoids counting the same installed quantity more than once. The application rejects non-numeric or negative quantities and cumulative installed quantities above the approved BOQ quantity.

If the sum of Activity Weights is zero, Overall Progress is `0.00%` and the project is reported as **Not Started**. The calculation service must apply this rule centrally so dashboards, alerts, reports, and exports cannot produce division-by-zero errors or `NaN` values.

Item and overall progress are stored as `DECIMAL(5,2)`. Completion is evaluated using the stored value and occurs only at exactly `100.00`. A value below `100.00` must never become complete solely because of rounding.

Automated completion dates are cleared if calculated progress falls below `100.00`. The clearing rule applies only where `completion_date_source = 'auto'`; a manually entered completion date can be changed only by a user with BOQ write permission. A pending Work Plan activity is represented as `0.00%`, not 1%.

## 8. Work Plan Evidence

- One before-work photo is supported.
- Up to five after-work photos are supported.
- Accepted formats are decoded JPG, PNG, GIF, and WebP images.
- Spreadsheet, document, executable, macro, and formula-bearing uploads are rejected.
- SHA-256 checksums are stored and verified before and after moving evidence files.
- Evidence deletion occurs only after the related database deletion, audit entry, and BOQ recalculation transaction commits.
- Failed post-commit deletions are queued for recoverable cleanup.

### Hosted Evidence Storage

**Conversion requirement:** Hosted deployments must store evidence in persistent object storage, not on the application container's local filesystem. `workplan_photos` stores the object key and SHA-256 checksum. The existing format validation, checksum verification, transaction ordering, and recovery-safe deletion rules remain mandatory.

The cleanup worker must define its schedule, retry limit, idempotency key, backoff policy, terminal-failure state, and monitoring alert. Queue depth and terminal failures must be visible to administrators.

## 9. Project Roles

| Project Role | Intended Access |
|---|---|
| Project Manager | Full project permissions, including assignment management |
| Project Engineer | Engineering, BOQ, progress, submittal, procurement, Work Plan, evidence, and exports |
| MEP Engineer | Engineering access without procurement editing or assignment management |
| Coordinator | Submittal, procurement, Work Plan, evidence, and exports; BOQ is read-only |
| Viewer | Read-only project modules and exports |

Role changes require a reason, use optimistic concurrency, are audited, and invalidate the affected user's existing sessions by incrementing `auth_version`.

**Conversion requirement:** In token-based deployments, `auth_version` must be embedded in the token claims and compared with the current server-side value on every authenticated request. A short cache may reduce database reads, but the check cannot occur only at login. Short token lifetimes are defense in depth and do not replace server-side invalidation.

## 10. Assignment API

All assignment endpoints require authentication. Mutating endpoints also require a CSRF token. IDs must be positive integers, and assignment changes require a reason containing 10–500 characters.

| Method | Route | Success |
|---|---|---:|
| `POST` | `api/v1/projects/{id}/assignments` | 201 |
| `PUT` | `api/v1/assignments/{id}` | 200 |
| `DELETE` | `api/v1/assignments/{id}` | 204 |
| `GET` | `api/v1/users/{id}/projects` | 200 |
| `GET` | `api/v1/projects/{id}/assignments` | 200 |

`PUT` and `DELETE` requests must provide one standards-compliant quoted entity tag, for example `If-Match: "7"`. A missing header returns HTTP `428 Precondition Required`; a stale value returns HTTP `412 Precondition Failed`. Body-based version fields and unquoted values such as `v7` are not accepted.

## 11. Security Controls

- Project-scoped authorization and row filtering
- CSRF protection on protected write requests
- POST-only logout
- Password hashing and one-time password reset links
- Password-reset tokens stored only as SHA-256 hashes
- Session invalidation after account status, role, or password changes
- Server-side derivation of BOQ, discipline, MAS, and project-linked metadata
- Same-project validation for Procurement, Submittals, and Work Plans
- Safe HTTP/HTTPS validation for material submittal links
- HTML-safe JSON encoding for server-provided JavaScript objects
- Restricted access to internal application, route, include, tool, configuration, and SQL files
- Production-safe generic error responses with request IDs
- Detailed diagnostics available only in an explicitly enabled development environment
- Login throttling evaluated independently by username, IP address, and username-plus-IP, with documented thresholds, lockout duration, and reset behavior
- Per-request `auth_version` verification for token-based hosted deployments

### Date and Time Policy

- Project, BOQ, Submittal, Procurement, and Work Plan business dates are stored as SQL `DATE` values and are never timezone-converted.
- System-event timestamps, including audit history, workflow history, login attempts, queue processing, and token expiry, are stored in UTC.
- System timestamps are rendered using one configured display timezone; the default project deployment timezone is `Asia/Riyadh`.
- Material Submittal Actual Start uses the earliest qualifying submitted date for the linked measurable BOQ item.
- Automatic date derivation may populate an empty BOQ Actual Start but must not overwrite a manually entered value.
- If the triggering submittal is edited or deleted, the system recalculates from the remaining qualifying submittals; it clears the value only when its source is automatic and no qualifying date remains.
- Every automatic cross-module date change is recorded in `audit_logs` with its source record.

## 12. Installation

### New Installation

1. Copy `MEP_Projects_Version_007.4` to `C:\xampp\htdocs\MEP Projects`.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Import `database.sql`.
5. Configure `config.php` or the required environment values.
6. Open `setup_admin.php` and create the first administrator.
7. Sign in and test every module.

### Existing Database Upgrade

1. Export the existing database.
2. Copy the uploads directory to a backup location outside `htdocs`.
3. Put the portal in maintenance mode and stop application writes.
4. Import `database_upgrade.sql` once.
5. Confirm the final verification query reports zero unmigrated memberships.
6. Retain `project_members` only during the rollback window because Version 007.4 keeps dual-write compatibility.
7. Deploy the Version 007.4 code.
8. Clear the PHP opcode cache.
9. Run the acceptance checks.
10. After the rollback window closes and membership verification remains at zero, retire `project_members` and stop dual writes.

## 13. Environment Configuration

### Development

```text
MEP_APP_ENV=development
MEP_APP_DEBUG=true
```

Development diagnostics may display the exact exception, file, line, source excerpt, controller, action file, safe field names, likely cause, request ID, and stack trace. Sensitive values such as passwords, CSRF tokens, and submitted values are excluded.

### Production

```text
MEP_APP_ENV=production
MEP_APP_DEBUG=false
MEP_ALLOW_SELF_SIGNUP=false
```

Production also requires:

- A dedicated non-root database user through `MEP_DB_USER` and `MEP_DB_PASS`
- An HTTPS base URL through `MEP_APP_BASE_URL`
- SMTP settings through `MEP_SMTP_HOST`, `MEP_SMTP_PORT`, `MEP_SMTP_USER`, `MEP_SMTP_PASS`, and `MEP_SMTP_FROM`
- `MEP_ALLOW_INSECURE_LOCAL_DB` must never be enabled in production

## 14. Reports and Exports

- Project Progress report
- Work Plan report with photographic evidence
- Project Progress XLSX export
- Management XLSX export

The Work Plan report is opened through **View / Print Report** and saved with the browser's **Print / Save as PDF** function. No server-side PDF library is required.

## 15. Validation Tools

Run the following from an XAMPP command prompt with the same database environment used by Apache:

```bat
C:\xampp\php\php.exe tools\xampp_acceptance.php
C:\xampp\php\php.exe tools\test_pipeline.php
C:\xampp\php\php.exe tools\e2e_lifecycle_test.php
```

The pipeline and lifecycle test data are created inside transactions and rolled back.

**Conversion requirement:** Port these suites to the hosted stack's platform-neutral test runner while retaining transaction rollback isolation. Automate the acceptance checks for concurrency, cross-project authorization, CSRF protection, session invalidation, progress edge cases, evidence persistence, and queue processing.

## 16. Acceptance Checklist

- [ ] All validation commands pass.
- [ ] Protected internal and SQL paths return HTTP 403.
- [ ] Login, POST-only logout, project roles, and project access work.
- [ ] Project roles can be assigned, edited, and revoked.
- [ ] Viewer cannot write and Coordinator cannot edit BOQ data.
- [ ] Concurrent assignment updates correctly return HTTP 412 for the stale request.
- [ ] Missing `If-Match` on assignment update or deletion returns HTTP 428.
- [ ] Stale `If-Match` on assignment update or deletion returns HTTP 412.
- [ ] Role changes invalidate the target user's earlier sessions.
- [ ] Token-based requests recheck `auth_version` and reject a revoked session.
- [ ] Standard, Compact, and Full Project Progress views work.
- [ ] Heading, Group, and Measurable Item rows display correctly.
- [ ] Heading and Group rows cannot carry stored weight, quantity, or progress and cannot be linked by operational modules.
- [ ] A project with no weighted measurable items reports `0.00%` and Not Started without an error.
- [ ] Item progress uses cumulative installed quantity when an approved quantity exists.
- [ ] Progress below `100.00` never triggers completion through rounding.
- [ ] Recalculation does not clear a manually entered completion date.
- [ ] Material Submittal dates correctly establish BOQ Actual Start.
- [ ] Automatic BOQ Actual Start does not overwrite a manually entered date and recalculates safely after Submittal edit or deletion.
- [ ] Work Plan records can be added, edited, and deleted.
- [ ] One before photo and up to five after photos can be uploaded.
- [ ] Work Plan report renders all evidence photos.
- [ ] Hosted evidence survives application restart, deployment, and scale-out.
- [ ] Cleanup and RBAC outbox workers retry idempotently, expose queue depth, and alert on terminal failures.
- [ ] Project Progress and Work Plan reports print correctly.
- [ ] XLSX exports work.
- [ ] Audit History loads without a server error.
- [ ] Management alerts display Critical, High, then Medium severity.
- [ ] Procurement PO and delivery dates save and display.
- [ ] BOQ quantities display a controlled unit and Procurement monetary values display an explicit currency.
- [ ] Cross-project MAS and BOQ tampering is rejected.
- [ ] Editing a linked record from BOQ A to BOQ B recalculates both BOQs.
- [ ] Password-reset links expire, work once, and invalidate previous sessions.
- [ ] The interface displays Version 007.4.
- [ ] Missing or expired CSRF tokens are rejected.
- [ ] Production mode hides technical diagnostic details.
- [ ] Business dates do not shift across timezone boundaries; system timestamps are stored in UTC and displayed in `Asia/Riyadh` unless reconfigured.

## 17. Version 007.4 Highlights

- Consolidated clean installation and upgrade databases
- Project-scoped RBAC with audited, versioned role assignments
- Improved HTTP 500 diagnostics for development environments
- Stable measurable BOQ record IDs for Material Submittals
- Correct recalculation of old and new BOQ references after edits
- Correct cumulative quantity-based progress calculation
- Procurement PO and delivery milestone dates
- Safer Work Plan evidence storage, checksums, deletion, and recovery
- Stronger project/date/quantity/link validation
- Improved Standard, Compact, and Full progress-grid layouts
- Removal of obsolete PostgreSQL/UUID components
- Browser-based PDF reporting without TCPDF

## 18. Important Data-Safety Notes

> **Warning:** `database.sql` intentionally drops existing application tables. Never import it over a working database containing records that must be retained.

Before installation or upgrade, back up both the database and uploads outside the web project directory. Keep the backup until every acceptance check passes.

## 19. Package Reference Files

- `README.md` — release overview
- `INSTALL.txt` — installation, upgrade, and acceptance instructions
- `ARCHITECTURE.md` — MVC structure and assignment API
- `RELEASE_NOTES_007.4.md` — Version 007.4 release specification
- `VALIDATION_007.4.md` — validation record
- `.env.example` — environment configuration example
- `database.sql` — clean installer
- `database_upgrade.sql` — data-preserving upgrade
- `VERSION.txt` — visible application version
- `MEP Projects Portal — Conversion-Readiness Corrections (v007 → v008)` — source review incorporated into Sections 6–16 and the conversion register

### Documentation Formula Rendering

MathJax is optional documentation tooling and is not an application runtime requirement. If an HTML renderer needs to display the formulas in this specification, include:

```html
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
```

Use raw MathJax delimiters `\(...\)` for inline formulas and `\[...\]` for display formulas.

## 20. Conversion-Readiness Register

The following requirements must be implemented and verified before the portal is treated as conversion-ready for hosted infrastructure.

| ID | Requirement | Priority | Verification |
|---|---|:---:|---|
| CR-01 | Guard zero total Activity Weight | Critical | No division error or `NaN`; return `0.00%` and Not Started |
| CR-02 | Define quantity progress and weighted roll-up precedence | Critical | Item and project tests match Section 7 |
| CR-03 | Use persistent object storage for Work Plan evidence | Critical | Evidence survives restart, deployment, and scale-out |
| CR-04 | Revalidate `auth_version` on every token-authenticated request | Critical | Revoked sessions fail on the next request |
| CR-05 | Enforce two-decimal progress and non-rounding completion | High | Values below `100.00` do not auto-complete |
| CR-06 | Separate automatic and manual completion dates | High | Recalculation never clears a manual date |
| CR-07 | Define and monitor outbox and cleanup workers | High | Retries, idempotency, terminal state, and alerts verified |
| CR-08 | Model and enforce BOQ row types | High | Only measurable items participate in progress and links |
| CR-09 | Standardize assignment concurrency with quoted ETags | Medium | HTTP 428 and 412 behavior verified for PUT and DELETE |
| CR-10 | Define non-destructive Submittal-to-BOQ date derivation | Medium | Add, edit, delete, and manual-override tests pass |
| CR-11 | Apply business-date and UTC timestamp policy | Medium | Riyadh display and boundary tests pass |
| CR-12 | Add controlled BOQ units and explicit currency | Medium | UI, validation, reports, and exports show both |
| CR-13 | Retire compatibility membership dual writes | Medium | `project_role_assignments` is the sole authorization source |
| CR-14 | Port validation to a platform-neutral runner | Medium | Hosted CI runs rollback-isolated tests |
| CR-15 | Specify multi-key login throttling | Low | Username, IP, and combined thresholds are tested |

The existing layered architecture, project-scoped authorization, server-side metadata derivation, hashed one-time reset tokens, generic production errors, role matrix, evidence integrity controls, and browser-based PDF workflow carry over unchanged.

---

*Prepared from the latest saved MEP Projects Portal package, Version 007.4, and the uploaded conversion-readiness review.*
