# Game Management Wave 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all twelve game-management placeholder pages with database-backed pages that support page-specific filters, CRUD or configuration updates, status changes, bulk actions, CSV import/export, permissions, and audit logging.

**Architecture:** Add one game-management domain controller and service with an explicit contract for each page code. Reuse `game_lists` for third-party games and platform hot games; create dedicated lottery, ranking, monitoring, interference, and free-spin tables for functionality that has no legacy storage. Render through a new Dcat-compatible Blade view and keep every route explicit before `/tcg/{code}`.

**Tech Stack:** PHP 7.3, Laravel 6.20, Dcat Admin, MySQL, PHPUnit, Blade, jQuery.

---

### Task 1: Define the page contracts

**Files:**
- Create: `tests/Unit/Admin/GameManagementCatalogTest.php`
- Create: `app/Admin/Services/GameManagementService.php`

- [ ] Write a failing catalog test for all twelve codes, titles, storage tables, filters, columns, actions, and editable fields.
- [ ] Run the test on the production-compatible PHP 7.3 environment and verify it fails because the service is missing.
- [ ] Add explicit page contracts for `31202`, `31000`, `70037`, `20401`, `5000`, `5500`, `5754`, `6400`, `5749`, `5700`, `5600`, and `260025`.
- [ ] Run the catalog test and verify it passes.

### Task 2: Add dedicated storage

**Files:**
- Create: `tests/Unit/Admin/GameManagementMigrationTest.php`
- Create: `database/migrations/2026_07_10_000007_create_game_management_tables.php`

- [ ] Write a failing migration test for `game_winner_rankings`, `lottery_branches`, `lottery_draw_records`, `lottery_group_settings`, `lottery_types`, `lottery_play_settings`, `lottery_sales_controls`, `lottery_bet_interferences`, and `free_spin_records`.
- [ ] Run the test and verify the migration file is missing.
- [ ] Add typed columns, status fields, indexes, administrator IDs, and timestamps.
- [ ] Run the migration test and verify all expected columns exist.

### Task 3: Implement real table access

**Files:**
- Create: `tests/Unit/Admin/GameManagementServiceTest.php`
- Modify: `app/Admin/Services/GameManagementService.php`

- [ ] Write failing tests that read/filter `game_lists`, restrict the hot page to `is_hot=1`, update real hot/sort flags, and CRUD dedicated lottery tables.
- [ ] Run the tests and verify they fail on missing methods.
- [ ] Implement page-specific queries, input whitelisting, required-field validation, create/update, status changes, delete, bulk delete, and normalized display rows.
- [ ] Run the service tests and verify legacy and new-table operations pass.

### Task 4: Add controller, permissions, audit, and routes

**Files:**
- Create: `tests/Unit/Admin/GameManagementRoutesTest.php`
- Create: `tests/Unit/Admin/GameManagementControllerBehaviorTest.php`
- Create: `app/Admin/Controllers/GameManagementController.php`
- Modify: `app/Admin/Support/OperationPermission.php`
- Modify: `app/Admin/routes.php`

- [ ] Write failing tests for twelve explicit routes before the generic route and for save/status/delete/export behavior.
- [ ] Run the tests and verify the controller and routes are absent.
- [ ] Add read/write/delete/export abilities and enforce them on every operation.
- [ ] Add explicit page methods, CRUD endpoints, bulk delete, status, CSV import, CSV export, and `admin_audit_logs` entries containing before/after context.
- [ ] Run controller and route tests and verify all behavior passes.

### Task 5: Build the Dcat page

**Files:**
- Create: `tests/Unit/Admin/GameManagementPageSourceTest.php`
- Create: `resources/views/admin/game-management.blade.php`

- [ ] Write a failing source test for page-specific filters, pagination, dynamic record fields, edit/delete/status controls, selection, import, export, and modal validation.
- [ ] Run the test and verify the view is missing.
- [ ] Build a responsive Dcat-styled page that renders the current page contract and real paginator records.
- [ ] Wire all controls to the dedicated endpoints and show a genuine empty state when the selected business table has no records.
- [ ] Run the source test and verify no placeholder actions remain.

### Task 6: Deploy and verify

**Files:**
- Deploy only the files changed by Tasks 1-5.

- [ ] Record hashes for both protected pixel views before deployment.
- [ ] Back up each overwritten production file and the database tables affected by migration `000007`.
- [ ] Deploy files, run only `php artisan migrate --path=database/migrations/2026_07_10_000007_create_game_management_tables.php --force`, clear caches, and reload PHP 7.3.
- [ ] Run the game-management test suite plus the existing admin suite.
- [ ] Use Chrome to visit all twelve pages, exercise create/edit/status/export on representative pages, and verify responsive layout and browser console errors.
- [ ] Delete acceptance-test records and confirm protected pixel hashes are unchanged.
