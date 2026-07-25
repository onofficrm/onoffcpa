<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    lc_api_success(array(
        'prefs'   => lc_email_notify_get_prefs('partner', $pt_id),
        'meta'    => lc_email_notify_prefs_meta('partner'),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $prefs = isset($body['prefs']) && is_array($body['prefs']) ? $body['prefs'] : $body;
    $result = lc_email_notify_save_prefs('partner', $pt_id, is_array($prefs) ? $prefs : array());
    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }
    lc_api_success(array(
        'message' => $result['message'],
        'prefs'   => $result['prefs'],
        'meta'    => lc_email_notify_prefs_meta('partner'),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
