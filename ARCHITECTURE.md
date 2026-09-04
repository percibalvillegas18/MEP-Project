# Version 007.4 MVC Architecture

## Request lifecycle

1. A request enters `index.php` or a root endpoint wrapper.
2. `public/index.php` boots the application and loads `routes/web.php` and `routes/api.php`.
3. `App\Core\Router` selects the controller and executes assigned middleware.
4. The controller coordinates models and services and returns HTML, JSON, PDF, or XLSX.
5. Central error handling returns safe responses and writes technical details to the PHP error log.

## Layers

- `app/Core`: request, response, routing, rendering, and error handling.
- `app/Middleware`: authentication and CSRF middleware.
- `app/Controllers`: one controller per application module, plus APIs, reports, audit, and assignments.
- `app/Actions`: request use-cases and write orchestration extracted from templates.
- `app/Models`: database queries and persistence logic, including management alerts.
- `app/Services`: calculation, reporting, workflow history, and exports.
- `app/Views`: presentation templates.
- `routes`: centralized web and API routes.
- `public`: canonical front controller.

Version 007.4 uses dedicated controllers, action handlers, models, services, middleware, and views. Root endpoints are routing wrappers only.

The canonical database engine is **MySQL/MariaDB**, using the integer-keyed plural-table schema installed by `database.sql`. Obsolete PostgreSQL/UUID models and controllers are not part of the application.

## Public endpoint policy

Root PHP endpoints contain route-forwarding code only. They contain no SQL, form processing, or HTML.

## Route groups

- Authentication: login, administrator-issued one-time password reset, optional signup, administrator setup, and logout.
- Management: dashboard, projects, progress, submittals, procurement, work plans, suppliers, and users.
- Governance: project-scoped roles, permission catalogue, soft revocation, optimistic concurrency, audit history, and transactional outbox.
- API: BOQ, MAS, manufacturer, progress, and submittal lookups.
- Reporting: progress PDF, work-plan PDF, progress XLSX, and management XLSX.

## Project assignment API

All endpoints require an authenticated session. Mutating endpoints require `X-CSRF-Token` (JSON clients) or `csrf_token` (form clients). IDs are positive integers; role changes require a 10–500 character reason. Update and delete requests must send `If-Match: v{version}` or a numeric `version` field.

| Method | Route | Success | Main errors |
|---|---|---:|---|
| POST | `api/v1/projects/{id}/assignments` | 201 | 403, 409, 419, 422 |
| PUT | `api/v1/assignments/{id}` | 200 | 403, 404, 412, 419, 422 |
| DELETE | `api/v1/assignments/{id}` | 204 | 403, 404, 409, 412, 419 |
| GET | `api/v1/users/{id}/projects` | 200 | 401, 403 |
| GET | `api/v1/projects/{id}/assignments` | 200 | 401, 403 |

The assignment service writes `project_role_assignments`, the compatibility `project_members` table, immutable-style audit evidence, the RBAC outbox, and the target user's `auth_version` in one transaction.

## Role baseline

| Project role | Intended access |
|---|---|
| Project Manager | All project permissions, including assignments |
| Project Engineer | Engineering, BOQ, progress, submittal, procurement, work plan, evidence and exports |
| MEP Engineer | Engineering access without procurement editing or assignment management |
| Coordinator | Submittal, procurement, work plan, evidence and exports; BOQ is read-only |
| Viewer | Read-only project modules and exports |

## Development rules

1. Register every new endpoint in `routes/web.php` or `routes/api.php`.
2. Do not put SQL, business rules, or request mutations in a public wrapper or view.
3. Put database operations in models, request use-cases in actions, and multi-entity rules in services.
4. Apply authentication and authorization middleware to protected routes.
5. Return JSON through `Response::json()` and pages through `View::render()`.
