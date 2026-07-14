# Multi-Platform Tracking Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the complete multi-platform attribution, conversion, and postback-log hierarchy behind the current pixel endpoint.

**Architecture:** Keep existing controllers and hooks. Add a platform catalog, config repository, tracking service, postback dispatcher, and three database tables for attribution/conversion/postback logs.

**Tech Stack:** Laravel 6, PHP 7.2-compatible code, Dcat admin storage JSON, plain JavaScript frontend runtime, PHPUnit/source tests, Node source tests.

---

### Task 1: Test The Required Hierarchy

**Files:**
- Modify: `tests/frontend/pixel-tracking-source.test.js`
- Create: `tests/Unit/Promotion/MultiPlatformTrackingSourceTest.php`

- [ ] Add assertions for all URL parameter keys from the pixel documentation and catalog.
- [ ] Add assertions that migration tables exist for attribution, conversion, and postback logs.
- [ ] Add assertions that `PromotionPixelEventService` delegates to a new tracking service.
- [ ] Add assertions that first deposit emits `firstDepositArrival`, `startTrial`, and `deposit`.
- [ ] Run the tests and verify they fail before implementation.

### Task 2: Add Database Tables

**Files:**
- Create: `database/migrations/2026_07_14_000002_create_multi_platform_tracking_tables.php`

- [ ] Add `promotion_tracking_attributions`.
- [ ] Add `promotion_tracking_conversions`.
- [ ] Add `promotion_tracking_postback_logs`.
- [ ] Include indexes for user, event, platform, status, event time, and dedupe keys.

### Task 3: Add Platform Catalog And Config Reader

**Files:**
- Create: `app/Services/Tracking/TrackingPlatformCatalog.php`
- Create: `app/Services/Tracking/TrackingConfigRepository.php`

- [ ] Centralize platform names, capture keys, click-id keys, and event mappings.
- [ ] Read `storage/app/tcg/pixel-config.json` safely.
- [ ] Normalize records/settings from the current TCG pixel admin page format.

### Task 4: Add Tracking Service And Dispatcher

**Files:**
- Create: `app/Services/Tracking/MultiPlatformTrackingService.php`
- Create: `app/Services/Tracking/TrackingPostbackDispatcher.php`
- Modify: `app/Services/PromotionPixelEventService.php`

- [ ] Normalize browser payloads into attribution rows.
- [ ] Bind attribution to user on `register`.
- [ ] Create conversion rows with stable `event_id`.
- [ ] Create postback logs for all mapped platforms.
- [ ] Record `skipped` reasons when required token, endpoint, or click ID is missing.
- [ ] Keep writing legacy `promotion_event_records` for current admin event screens.

### Task 5: Extend Frontend Capture

**Files:**
- Modify: `public/assets/pixel-tracking.js`
- Modify: `tests/frontend/pixel-tracking-source.test.js`

- [ ] Add every supported URL parameter to the capture allowlist.
- [ ] Generate and persist a browser/session identifier.
- [ ] Send `event_id`, `referrer`, `screen`, and stored params to the backend.
- [ ] Keep existing Facebook, TikTok, Gtag, and GTM browser event calls.

### Task 6: Verify Locally And On Server

**Files:**
- No source-only file requirement.

- [ ] Run Node frontend tests.
- [ ] Run PHPUnit/source tests for promotion tracking.
- [ ] PHP lint touched PHP files.
- [ ] Upload changed files to `/www/wwwroot/87713`.
- [ ] Run migrations/cache clear on server.
- [ ] Use Chrome to register a test user with multi-platform params.
- [ ] Confirm DB rows in attribution, conversion, legacy event, and postback log tables.
- [ ] Commit and push the branch.
