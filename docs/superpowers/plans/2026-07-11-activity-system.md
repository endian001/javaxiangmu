# TH2W Activity System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a database-driven, responsive promotion center with direct detail URLs, home popup behavior, and Dcat administration.

**Architecture:** Extend the existing activity tables and keep the old endpoints compatible. Add a focused promotion API controller and service, then load one shared frontend promotion module from both production home entry files.

**Tech Stack:** Laravel 6, Eloquent, Dcat Admin, MySQL, vanilla JavaScript, CSS, PHPUnit 8, Node test runner.

---

### Task 1: Promotion Contract Tests

**Files:**
- Create: `tests/Unit/Promotion/PromotionServiceTest.php`
- Create: `tests/Unit/Promotion/PromotionApiSourceTest.php`
- Create: `tests/Unit/Promotion/PromotionMigrationSourceTest.php`
- Create: `tests/Unit/Promotion/PromotionAdminSourceTest.php`
- Create: `tests/Unit/Promotion/PromotionFrontendSourceTest.php`
- Create: `tests/frontend/promotion-core.test.js`

- [ ] Write tests for visibility dates, channel state, sorting and popup selection.
- [ ] Write source tests for routes, migration fields, Dcat fields and frontend entry tags.
- [ ] Run PHPUnit and Node tests and confirm they fail because the implementation does not exist.

### Task 2: Database and Domain Service

**Files:**
- Create: `database/migrations/2026_07_11_000001_extend_activity_promotions.php`
- Create: `app/Services/PromotionService.php`
- Modify: `app/Models/Activity.php`
- Modify: `app/Models/ActivityType.php`

- [ ] Add nullable/defaulted promotion fields and exposure storage.
- [ ] Add `(activity_id, user_id)` uniqueness after removing duplicate rows.
- [ ] Implement channel visibility, date filtering, sorting and popup selection.
- [ ] Run focused PHPUnit tests until green.

### Task 3: Promotion API

**Files:**
- Create: `app/Http/Controllers/Api/PromotionController.php`
- Modify: `routes/api.php`

- [ ] Add category, list, detail, popup and exposure endpoints.
- [ ] Normalize Thai display fields and absolute image URLs.
- [ ] Keep legacy activity endpoints unchanged.
- [ ] Run API source and service tests until green.

### Task 4: Dcat Administration

**Files:**
- Modify: `app/Admin/Controllers/ActivityController.php`
- Modify: `app/Admin/Controllers/ActivityTypeController.php`

- [ ] Add publication, ordering, popup, image and action controls.
- [ ] Relabel `entitle`, `encontent` and `enmemo` as Thai fields.
- [ ] Include all new fields in operation audit snapshots.
- [ ] Run admin source tests until green.

### Task 5: Responsive Promotion Frontend

**Files:**
- Create: `public/assets/promotion-core.js`
- Create: `public/assets/promotion-system.js`
- Create: `public/assets/promotion-system.css`
- Modify: `public/index.html`
- Modify: `public/new-h5/index.html`

- [ ] Implement shared response parsing, path matching, popup frequency and detail URL helpers.
- [ ] Implement responsive category, card, detail and home popup UI.
- [ ] Add the shared assets to both production entry files.
- [ ] Run Node and frontend source tests until green.

### Task 6: Seed and Production Deployment

**Files:**
- Create: `database/migrations/2026_07_11_000002_seed_thai_activity_categories.php`

- [ ] Seed Thai categories without deleting existing activity records.
- [ ] Clean the two invalid activity titles into usable Thai titles.
- [ ] Enable one existing activity as the initial homepage popup only when no popup is configured.
- [ ] Upload files, run migrations, clear Laravel caches and verify API responses.

### Task 7: Browser Verification

- [ ] Verify desktop promotion center and direct detail URL.
- [ ] Verify mobile promotion center and home popup at 390×844.
- [ ] Verify popup close frequency and detail URL cleanup.
- [ ] Verify Thai-only visible text and scan for mojibake patterns.
- [ ] Verify login, wallet, deposit, withdraw and member navigation remain unchanged.
