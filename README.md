# MEP Projects Portal — Version 007.4

Version 007.4 is the current conversion-ready baseline. It retains the earlier security and calculation controls, adds deterministic two-decimal progress, standard ETag concurrency, explicit currency, queue workers, evidence-storage configuration, verified evidence checksums and recovery-safe cleanup, and stable measurable BOQ record links.

Version 007.4 formally incorporates Stage 3 workflow and calculation accuracy:
chronological and non-negative Work Plan validation, stronger Project/Submittal
date validation, cumulative installed-quantity precedence across stages, old/new
BOQ recalculation, complete Procurement delivery dates, and consistent `0.00%`
Work Pending behavior.

It also incorporates Stage 4 user-experience and miscellaneous fixes: safe
JavaScript data rendering, POST-only logout, image-only upload controls,
transaction-safe evidence cleanup, checksum verification, and clear duplicate or
stale BOQ handling in both the interface and backend.

## Canonical database engine

The canonical database engine is **MySQL 8.0+ or MariaDB 10.4+** using integer keys and plural table names. Obsolete PostgreSQL/UUID classes have been removed.

## Debugging HTTP 500 errors

For development or testing, configure these environment values:

```text
MEP_APP_ENV=development
MEP_APP_DEBUG=true
```

The error page then displays the exact exception and message, file and line, source syntax excerpt, controller/class, MVC action file, form action, safe submitted field names, likely cause, request ID and stack trace. Passwords, CSRF tokens and submitted values are not displayed.

For production, always use:

```text
MEP_APP_ENV=production
MEP_APP_DEBUG=false
```

Production continues to show a generic error while recording the request ID and full exception in the PHP error log.

## Main modules

- Projects and project assignments
- Weighted BOQ progress tracking
- Material submittals and procurement
- Work plans with one before photo and up to five after photos
- Suppliers and user administration
- Audit and workflow history
- XLSX management exports
- Print-ready Project Progress and Work Plan reports

## Work Plan report

Select **View / Print Report**, review the A4 preview, then select **Print / Save as PDF**. Chrome or Microsoft Edge can save the report as a PDF without a server-side PDF library.

## Database files

- `database.sql`: destructive Version 007.4 clean installer containing the complete current schema and four default disciplines only.
- `database_upgrade.sql`: the single additive, data-preserving upgrade for any existing Version 001–005 database. It combines all required migrations.

Never import `database.sql` over a working database. It intentionally drops the application tables and contains no users, projects, logs, or operational records. After a clean import, use `setup_admin.php` to create the first administrator.

Production requires non-root database credentials, HTTPS, disabled self-signup, and SMTP/base-URL environment values from `.env.example`. The `tools/`, `app/`, `routes/`, and `includes/` directories and both SQL files are blocked from direct Apache access.

Uploads are restricted to server-decoded JPG, PNG, GIF, and WebP images. Spreadsheet, document, executable, macro, and formula-bearing files are not accepted by any upload endpoint.

## Persistent photo storage

Hosted deployments must set `MEP_EVIDENCE_STORAGE_DRIVER=s3` and configure the
S3-compatible endpoint, region, private bucket, credentials, and object prefix in
the environment. Uploads are signed with AWS Signature Version 4, verified by
SHA-256 metadata after storage, and displayed through short-lived signed URLs.
Deletion and retry-queue processing also operate on object storage, so evidence
survives application container replacement or restart. Local disk remains available
only for XAMPP/development; migration steps are in `INSTALL.txt`.

See `MEP_Project_Portal_Version_007.4.md`, `IMPLEMENTATION_STATUS_007.4.md`, `INSTALL.txt`, `RELEASE_NOTES_007.4.md` and `ARCHITECTURE.md`.
