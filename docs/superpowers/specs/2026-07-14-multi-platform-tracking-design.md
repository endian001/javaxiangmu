# Multi-Platform Tracking Design

## Goal

Build one complete attribution and conversion postback chain for the gaming site. The chain must capture traffic parameters on landing pages, bind them to a registered user, emit normalized business events, and create platform-specific browser pixel events and server-side postback logs for operations.

## Scope

This phase keeps the existing frontend pages, register/login APIs, recharge callbacks, and admin pixel page entry. It adds the missing backend hierarchy behind the current `/api/pixel/event` endpoint and recharge-arrival hooks.

Payment provider integration remains separate. Missing advertising tokens or postback URLs must not break registration or recharge; they are recorded as skipped postbacks with a clear reason.

## Architecture

The existing `PromotionPixelEventService` stays as the compatibility entry point. It delegates to a new tracking service that owns attribution records, conversion events, event deduplication, first-deposit detection, and postback log creation.

The platform catalog is code-driven so all supported URL keys and event mappings live in one place. Admin configuration is still read from `storage/app/tcg/pixel-config.json`, because the current TCG pixel page already saves platform records and settings there.

## Data Flow

1. Frontend `pixel-tracking.js` captures URL parameters from query string and hash.
2. The browser persists the parameters and sends each event to `/api/pixel/event`.
3. Backend normalizes parameters into an attribution record.
4. On successful registration, the attribution is bound to the user.
5. On recharge arrival, the service finds the user's attribution and detects whether this is first deposit or redeposit.
6. Each internal event gets a stable `event_id`.
7. The postback dispatcher creates one log row per supported platform.
8. If credentials and endpoint are present, the dispatcher can send immediately; otherwise it records `skipped` with `missing_credentials` or `missing_click_id`.
9. Operations can inspect conversion records and postback logs from the database/admin event area.

## Supported Inputs

Core pixel and ad IDs include `fbPixelId`, `tiktokPixelId`, `kwai_pixel_id`, `kwaiPixelBaseCode`, `gtagId`, `gtmId`, `bigoPixelId`, `oks_pixel_id`, `pixel_click_id`, `fbclid`, `ttclid`, and `gclid`.

Click/postback IDs include `cid`, `tfTracker`, `visitor_id`, `rtCid`, `obclid`, `kadam_id`, `phxCid`, `mgsClickId`, `devilsClickId`, `macanClickId`, `rbclickid`, `egwId`, `clickId`, `keitaroClickId`, `clickid`, `revosurge`, and `rmClickId`.

App attribution IDs include `af_app_id`, `appsflyer_id`, `advertising_id`, `oaid`, `idfa`, `idfv`, `ad_app_token`, `gps_adid`, and `adid`.

## Event Rules

Browser events remain `firstOpen`, `registerSubmit`, `register`, `login`, `depositSubmit`, and `withdraw`.

Server-side recharge arrival emits:

- First successful recharge: `firstDepositArrival`, `startTrial`, and `deposit`.
- Later successful recharge: `redeposit` and `deposit`.

Every conversion event gets an `event_id` for platform deduplication.

## Admin/Operations

Existing admin pixel settings continue to save platform records and settings. New database logs expose whether each platform was sent, skipped, pending retry, or failed. This avoids silent failure when a token or URL is missing.

## Verification

Source tests must cover platform key coverage, migration tables, service wiring, event mapping, first-deposit behavior markers, and postback log status markers. Runtime validation then checks registration and recharge flows create attribution/conversion/postback rows without breaking existing event records.
