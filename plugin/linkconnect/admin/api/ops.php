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
        $opts = array('activate' => true);
        if (isset($body['advertiserMbId']) && trim((string) $body['advertiserMbId']) !== '') {
            $opts['advertiser_mb_id'] = trim((string) $body['advertiserMbId']);
        } else {
            $opts['advertiser_mb_id'] = 'drainpolice';
        }
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        if (array_key_exists('activate', $body) && empty($body['activate'])) {
            unset($opts['activate']);
        }
        $result = lc_campaign_ensure_hasugu_cpa($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_modemo_campaign') {
        if (!function_exists('lc_campaign_ensure_modemo')) {
            lc_api_error('modemo 캠페인 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
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
        $result = lc_campaign_ensure_modemo($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_domain_cps_campaign') {
        if (!function_exists('lc_campaign_ensure_domain_cps')) {
            lc_api_error('낙장도메인 CPS 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $opts = array('activate' => true);
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        $result = lc_campaign_ensure_domain_cps($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_seo_geo_cps_campaign') {
        if (!function_exists('lc_campaign_ensure_seo_geo_cps')) {
            lc_api_error('SEO GEO CPS 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $opts = array('activate' => true);
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        $result = lc_campaign_ensure_seo_geo_cps($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_traffic_cps_campaign') {
        if (!function_exists('lc_campaign_ensure_traffic_cps')) {
            lc_api_error('트래픽 CPS 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $opts = array('activate' => true);
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        $result = lc_campaign_ensure_traffic_cps($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    if ($action === 'apply_backlink_cps_campaign') {
        if (!function_exists('lc_campaign_ensure_backlink_cps')) {
            lc_api_error('백링크 CPS 모듈을 찾을 수 없습니다.', 'NOT_FOUND', 500);
        }
        $opts = array('activate' => true);
        if (isset($body['mtId']) && (int) $body['mtId'] > 0) {
            $opts['mt_id'] = (int) $body['mtId'];
        }
        $result = lc_campaign_ensure_backlink_cps($opts);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPLY_FAILED', 400);
        }
        lc_api_success($result);
    }

    // Kakao/manual SEO GEO enrollment → Core fixed ₩300,000 commission
    if ($action === 'report_seo_geo_payment') {
        if (!function_exists('lc_onoff_core_bind_customer') || !function_exists('lc_onoff_core_record_purchased_point_payment')) {
            lc_api_error('Core bridge unavailable', 'NOT_READY', 500);
        }
        $customer = trim((string) ($body['customerMbId'] ?? $body['customer_mb_id'] ?? ''));
        $payment_id = trim((string) ($body['paymentId'] ?? $body['payment_id'] ?? ''));
        $referral_code = strtoupper(trim((string) ($body['referralCode'] ?? $body['referral_code'] ?? '')));
        if ($customer === '' || $payment_id === '') {
            lc_api_error('customerMbId and paymentId required', 'INVALID_ARGS', 400);
        }
        $bind = null;
        if ($referral_code !== '') {
            $bind = lc_onoff_core_bind_customer($customer, $referral_code, array(
                'source_service'   => 'CONTENT',
                'source_reference' => 'admin.ops.report_seo_geo_payment',
            ));
        }
        $pay = lc_onoff_core_record_purchased_point_payment(array(
            'payment_id'     => $payment_id,
            'customer_mb_id' => $customer,
            'amount'         => 300000,
            'source_service' => 'CONTENT',
            'approved_at'    => date('Y-m-d H:i:s'),
        ));
        if (empty($pay['ok'])) {
            lc_api_error((string) ($pay['message'] ?? 'payment_failed'), 'PAYMENT_REPORT_FAILED', 400);
        }
        lc_api_success(array(
            'bind'    => $bind,
            'payment' => $pay,
            'amount'  => 300000,
        ));
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
