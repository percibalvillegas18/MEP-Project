# Version 007.4 Implementation Status

## Stage 4 status

All six Stage 4 user-experience and miscellaneous requirements are implemented and enforced by `tools/validate_release.py`.

## Stage 3 status

All six Stage 3 workflow and calculation-accuracy requirements are implemented and enforced by `tools/validate_release.py`.

## Implemented in this package

- Deterministic two-decimal quantity progress and zero-weight protection
- Weighted project roll-up separated from item progress
- Manual versus automatic completion-date protection
- Quoted `If-Match` assignment concurrency with HTTP 428/412 responses
- Explicit procurement currency with configurable SAR default
- Controlled BOQ item types and units
- Riyadh display timezone and UTC database connection policy
- Username, IP, and combined login throttling
- Queue lifecycle, retry, idempotency, monitoring fields, and CLI processor
- Expanded static validation and acceptance requirements

## Deployment-dependent requirements

- XAMPP installations continue to use local Work Plan evidence storage.
- Hosted deployments must connect `MEP_EVIDENCE_STORAGE_DRIVER` to persistent object storage before accepting evidence uploads. The provider account, bucket/container, credentials, signed-URL policy, and scheduler are external deployment resources and are not embedded in this ZIP.
- Schedule `php tools/process_queues.php` using Task Scheduler, cron, or the hosting platform's job scheduler.
- Token-based frontends must compare the token's `auth_version` claim with `users.auth_version` on every authenticated request. The bundled PHP session flow already performs this comparison on every request.
- Retire `project_members` only after the rollback window closes and the final migration verification reports zero unmigrated memberships.

## Required deployment verification

Run `python tools/validate_release.py`, import the appropriate SQL file in a test database, run the PHP acceptance scripts, and complete the checklist in `MEP_Project_Portal_Version_007.4.md` before production release.
