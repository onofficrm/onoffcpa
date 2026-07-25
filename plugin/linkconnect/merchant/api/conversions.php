<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
        $merchant = lc_api_require_active_merchant();
    } else {
        lc_api_require_login();
        $merchant = lc_get_current_merchant();
    }

    $mt_id = is_array($merchant) ? (int) $merchant['mt_id'] : 0;
    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $needs_action = isset($_GET['needs_action']) && $_GET['needs_action'] === '1';

    $filters = array();
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if ($q !== '') {
        $filters['q'] = $q;
    }
    if ($needs_action) {
        $filters['needs_action'] = true;
    }

    $items = lc_conversion_list_for_api($mt_id, $filters);
    $summary = $mt_id > 0 ? lc_conversion_merchant_summary($mt_id) : array(
        'pending' => 9, 'approved' => 21, 'rejected' => 4, 'needsAction' => 9,
        'todayReceived' => 17, 'todaySpend' => 300000,
    );

    lc_api_success(array(
        'items'   => $items,
        'summary' => $summary,
        'total'   => count($items),
    ));
}

if ($method === 'POST') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    $body = lc_api_read_json_body();

    $action = isset($body['action']) ? (string) $body['action'] : '';
    $cv_id = isset($body['cvId']) ? (int) $body['cvId'] : 0;
    $comment = isset($body['comment']) ? trim((string) $body['comment']) : '';

    if ($cv_id <= 0) {
        lc_api_error('디비 ID가 필요합니다.', 'INVALID_ID', 400);
    }

    if ($action === 'approve') {
        $opts = array();
        if (isset($body['qualityScore'])) {
            $opts['qualityScore'] = (int) $body['qualityScore'];
        }
        if (isset($body['qualityTags']) && is_array($body['qualityTags'])) {
            $opts['qualityTags'] = $body['qualityTags'];
        }
        if (isset($body['partnerVisible'])) {
            $opts['partnerVisible'] = !empty($body['partnerVisible']);
        }
        $result = lc_conversion_update_status($cv_id, $mt_id, LC_STATUS_APPROVED, $comment, $opts);
    } elseif ($action === 'reject') {
        $reason = isset($body['reason']) ? trim((string) $body['reason']) : '';
        $full_comment = $reason;
        if ($comment !== '') {
            $full_comment = $reason !== '' ? $reason . ' - ' . $comment : $comment;
        }
        $opts = array();
        if (isset($body['partnerVisible'])) {
            $opts['partnerVisible'] = !empty($body['partnerVisible']);
        }
        if (isset($body['qualityTags']) && is_array($body['qualityTags'])) {
            $opts['qualityTags'] = $body['qualityTags'];
        }
        $result = lc_conversion_update_status($cv_id, $mt_id, LC_STATUS_REJECTED, $full_comment, $opts);
    } elseif ($action === 'report_channel') {
        $result = lc_channel_report_create(array(
            'cvId'    => $cv_id,
            'mtId'    => $mt_id,
            'channel' => isset($body['channel']) ? (string) $body['channel'] : '',
            'reason'  => isset($body['reason']) ? (string) $body['reason'] : '',
        ));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'REPORT_FAILED', 400);
        }
        lc_api_success(array('message' => $result['message']));
    } else {
        lc_api_error('유효하지 않은 action 입니다.', 'INVALID_ACTION', 400);
    }

    if (!$result['ok']) {
        lc_api_error($result['message'], 'UPDATE_FAILED', 400);
    }

    lc_api_success(array(
        'message'    => $result['message'],
        'conversion' => isset($result['conversion']) && is_array($result['conversion'])
            ? lc_conversion_to_api_merchant($result['conversion'], false)
            : null,
        'merchant'   => lc_merchant_to_api(lc_get_merchant_by_id($mt_id)),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
