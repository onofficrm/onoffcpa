<?php
require_once __DIR__ . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_admin();
    $view = isset($_GET['view']) ? (string) $_GET['view'] : '';

    if ($view === 'review_queue') {
        lc_api_success(array('items' => lc_admin_review_queue_for_api(), 'dbReady' => lc_db_installed()));
    }

    if ($view === 'impersonate_history') {
        lc_api_success(array(
            'items' => lc_impersonate_history_for_api(isset($_GET['limit']) ? (int) $_GET['limit'] : 10),
            'dbReady' => lc_db_installed(),
        ));
    }

    lc_api_error('유효하지 않은 view입니다.', 'INVALID_VIEW', 400);
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'bulk_partner') {
        $ids = isset($body['ids']) && is_array($body['ids']) ? array_map('intval', $body['ids']) : array();
        $sub = isset($body['subAction']) ? (string) $body['subAction'] : 'activate';
        $result = lc_admin_bulk_partner_status($ids, $sub);
        lc_api_success($result);
    }

    if ($action === 'bulk_merchant') {
        $ids = isset($body['ids']) && is_array($body['ids']) ? array_map('intval', $body['ids']) : array();
        $sub = isset($body['subAction']) ? (string) $body['subAction'] : 'activate';
        $result = lc_admin_bulk_merchant_status($ids, $sub);
        lc_api_success($result);
    }

    if ($action === 'bulk_reward_pay') {
        $ids = isset($body['ids']) && is_array($body['ids']) ? array_map('intval', $body['ids']) : array();
        $result = lc_admin_bulk_reward_pay($ids);
        lc_api_success($result);
    }

    if ($action === 'bulk_notify') {
        $result = lc_admin_bulk_notify($body);
        lc_api_success($result);
    }

    if ($action === 'save_meta') {
        $type = isset($body['entityType']) ? (string) $body['entityType'] : '';
        $id = isset($body['entityId']) ? (int) $body['entityId'] : 0;
        $result = lc_admin_save_entity_meta($type, $id, array(
            'adminMemo'  => $body['adminMemo'] ?? null,
            'tags'       => $body['tags'] ?? null,
            'assignedTo' => $body['assignedTo'] ?? null,
        ));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'SAVE_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'refresh_tiers') {
        $count = lc_partner_tier_refresh(isset($body['ptId']) ? (int) $body['ptId'] : 0);
        lc_api_success(array('message' => $count . '건 등급 갱신', 'count' => $count));
    }

    if ($action === 'apply_banktupt_campaign') {
        if (!function_exists('lc_campaign_apply_banktupt_only')) {
            lc_api_error('banktupt 캠페인 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $result = lc_campaign_apply_banktupt_only(array(
            'advertiser_mb_id' => isset($body['advertiserMbId']) ? trim((string) $body['advertiserMbId']) : 'lc_advertiser',
        ));
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_hasugu_cpa_campaign') {
        if (!function_exists('lc_campaign_ensure_hasugu_cpa')) {
            lc_api_error('hasugu_cpa 캠페인 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $opts = array();
        if (isset($body['advertiserMbId']) && trim((string) $body['advertiserMbId']) !== '') {
            $opts['advertiser_mb_id'] = trim((string) $body['advertiserMbId']);
        }
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        if (!empty($body['activate'])) {
            $opts['activate'] = true;
        }
        $result = lc_campaign_ensure_hasugu_cpa($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
