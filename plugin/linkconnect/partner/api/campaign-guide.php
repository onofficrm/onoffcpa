<?php
require_once __DIR__ . '/_common.php';

lc_campaign_promo_guide_db_ensure_schema();

if (LC_PARTNER_GUARD_ENABLED && lc_db_installed() && !lc_is_super_admin()) {
    $partner = lc_api_require_active_partner();
} else {
    lc_api_require_login();
    $partner = lc_get_current_partner();
}

if (!is_array($partner)) {
    lc_api_error('파트너 등록이 필요합니다.', 'NOT_PARTNER', 403);
}

$pt_id = (int) $partner['pt_id'];
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_method('GET');

    $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : (isset($_GET['campaignId']) ? (int) $_GET['campaignId'] : 0);
    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
    }

    $view = lc_campaign_promo_guide_partner_detail_for_api($pt_id, $cp_id);
    if (empty($view['ok'])) {
        lc_api_error($view['message'], 'NOT_FOUND', 404);
    }

    lc_api_success($view['data']);
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'confirm';
    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : (isset($body['campaignId']) ? (int) $body['campaignId'] : 0);

    if ($cp_id <= 0) {
        lc_api_error('광고상품 ID가 필요합니다.', 'INVALID_CAMPAIGN', 400);
    }

    if ($action === 'confirm') {
        $result = lc_campaign_promo_guide_partner_confirm($pt_id, $cp_id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'CONFIRM_FAILED', 400);
        }

        lc_api_success(array(
            'message'      => $result['message'],
            'confirmation' => $result['confirmation'],
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
