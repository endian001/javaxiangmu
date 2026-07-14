# Pixel Operator Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the admin pixel tracking pages readable for operators by translating technical events, postback statuses, and failure reasons into plain operational language.

**Architecture:** Keep the existing Laravel 6 / Dcat Admin routes and tracking dispatch logic unchanged. Add display-only helpers in the promotion channel controller/view and a compact operator guide in the pixel tools view.

**Tech Stack:** Laravel 6, Blade, Dcat Admin, existing Node source tests.

---

### Task 1: Source Tests

**Files:**
- Modify: `tests/frontend/pixel-tracking-source.test.js`

- [ ] Add a test that asserts the admin promotion event page contains operator-facing labels: `运营总览`, `已回传成功`, `缺少平台 ID 或 Token`, `首存到账`, `点击ID`, and does not expose raw status labels as the primary status badge.
- [ ] Run `node tests/frontend/pixel-tracking-source.test.js` and verify the new test fails before implementation.

### Task 2: Event And Postback Display Helpers

**Files:**
- Modify: `app/Admin/Controllers/PromotionChannelController.php`
- Modify: `resources/views/admin/promotion-channel.blade.php`

- [ ] Add controller summary counts for today's page views, registrations, first deposits, successful callbacks, failed callbacks, skipped callbacks, and pending callbacks.
- [ ] Add Blade label maps for event names, postback statuses, skip reasons, and platform names.
- [ ] Replace raw log columns with operator columns: time, platform, event, user, amount, click ID, status, reason, retry.
- [ ] Keep raw JSON accessible in a collapsed detail block instead of putting it directly in the main table.

### Task 3: Pixel Setup Operator Guide

**Files:**
- Modify: `resources/views/admin/tcg-pixel-tools.blade.php`

- [ ] Add a compact “运营测试步骤” panel at the top of the pixel tool page.
- [ ] Add platform readiness cards for Facebook, Google, TikTok, Kwai, Bigo, OKSpin, Voluum, and Red Track.
- [ ] Use plain Chinese labels for required IDs, tokens, and the meaning of registration / first deposit callbacks.

### Task 4: Verification And Deployment

**Files:**
- Test: `tests/frontend/pixel-tracking-source.test.js`

- [ ] Run `node tests/frontend/pixel-tracking-source.test.js`.
- [ ] Run `git diff --check`.
- [ ] Scan changed user-facing files for mojibake patterns.
- [ ] Upload changed files to the server, clear Laravel caches, and run server-side `php -l` for changed PHP files.
- [ ] Use Chrome to verify `/game/tcg/12535` and `/game/tcg/280012?tab=postback-logs` show operator labels.
- [ ] Commit and push the changes.
