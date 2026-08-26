<?php
/**
 * Content Platform S2S API — shared bootstrap.
 *
 * Auth: X-LC-Content-Key / Timestamp / Nonce / Signature (HMAC-SHA256).
 * Partner identity comes from machine credential mapping only.
 */
require_once dirname(__DIR__) . '/_common.php';

if (!function_exists('lc_content_s2s_require')) {
    lc_api_error('Content S2S bridge is not available.', 'NOT_AVAILABLE', 500);
}
