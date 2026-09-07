# LIVE-5C.1B — Content S2S Bootstrap Hotfix Report

**Marker:** `ONOFFCPA_CONTENT_S2S_BOOTSTRAP_READY`

**Date:** 2026-08-26

---

## A. Patch

| Field | Value |
|-------|-------|
| **File** | `plugin/linkconnect/_common.php` |
| **Lines added** | 3 (after `inc/api.php` loader) |
| **Side effects** | None at include time — conditional `is_file` + `require_once` only |

```php
if (is_file(LC_PLUGIN_PATH . '/inc/content_s2s.php')) {
    require_once LC_PLUGIN_PATH . '/inc/content_s2s.php';
}
```

---

## B. PHP Lint

**PASS** — `_common.php`, `content_s2s.php`, all `content-api/*.php`

## C. S2S Tests

**13/13 PASS**

## D. Regression

| Check | Result |
|-------|--------|
| Partner API | **PASS** — `UNAUTHORIZED` (session required) |
| Homepage | **PASS** — HTTP 200 |
| Tracking `/r/` | **PASS** — 404 for invalid code (expected) |

---

## E. Git

| Item | Value |
|------|-------|
| **Branch** | `feat/content-s2s-bootstrap` |
| **Commit** | `3921d5e` — `fix: bootstrap Content S2S bridge` |
| **Merge** | `913faa3` on `main` |

Also updated: `.github/workflows/deploy-content-api-hotfix.yml` — added `_common.php` to paths + bundle; post-deploy fails on HTTP 500.

---

## F. Deploy

| Item | Value |
|------|-------|
| **Workflow** | Deploy content API hotfix #3 |
| **Uploaded files** | `_common.php` + existing content-api bundle |

---

## G. Endpoint

| | HTTP | Body |
|---|------|------|
| **Before** | 500 | `NOT_AVAILABLE` |
| **After** | **401** | `Missing Content S2S auth headers` / `UNAUTHORIZED` |

`links.php`, `analytics.php` — same fail-closed **401**.

---

## H–K. Unchanged (per scope)

| Item | Status |
|------|--------|
| `clients.local.php` | Not created |
| BFF `ONOFFCPA_S2S_ENABLED` | false |
| Link mint | 0 |
| DB changes | 0 |
