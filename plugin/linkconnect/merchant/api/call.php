<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

/**
 * 광고주 콜디비 설정 API
 * - 광고주는 캠페인별 콜디비 수신 on/off, 착신번호1/2, 상품 별칭만 편집
 * - 녹음방식·업무시간·휴무·단가 등은 관리자 전용
 */

function lc_merchant_call_current_mt()
{
    if (function_exists('lc_merchant_api_use_strict_guard') && lc_merchant_api_use_strict_guard()) {
        $merchant = lc_api_require_active_merchant();
    } else {
        lc_api_require_login();
        $merchant = lc_get_current_merchant();
    }

    return is_array($merchant) ? (int) $merchant['mt_id'] : 0;
}

function lc_merchant_call_owns_campaign($mt_id, $cp_id)
{
    if ($mt_id <= 0 || $cp_id <= 0) {
        return false;
    }
    $cp_table = lc_table('campaigns');
    $row = lc_sql_fetch(" SELECT cp_id FROM `{$cp_table}` WHERE cp_id = '" . (int) $cp_id . "' AND mt_id = '" . (int) $mt_id . "' LIMIT 1 ");

    return (bool) $row;
}

if ($method === 'GET') {
    $mt_id = lc_merchant_call_current_mt();
    $view = isset($_GET['view']) ? (string) $_GET['view'] : 'campaigns';

    if ($view === 'campaigns') {
        $items = array();
        foreach (lc_campaign_list_for_merchant($mt_id) as $c) {
            $settings = lc_call_settings_get((int) $c['cp_id'], $mt_id);
            $items[] = array(
                'cpId'         => (int) $c['cp_id'],
                'campaign'     => (string) $c['cp_name'],
                'code'         => (string) $c['cp_code'],
                'enabled'      => (int) ($settings['cs_enabled'] ?? 0) === 1,
                'adminEnabled' => (int) ($settings['cs_admin_enabled'] ?? 1) === 1,
                'alias'        => (string) ($settings['cs_alias'] ?? ''),
                'forward1'     => (string) ($settings['cs_forward1'] ?? ''),
                'forward2'     => (string) ($settings['cs_forward2'] ?? ''),
                'recordingMode' => (string) ($settings['cs_recording_mode'] ?? 'normal'),
            );
        }
        lc_api_success(array('items' => $items, 'dbReady' => lc_db_installed()));
    }

    if ($view === 'settings') {
        $cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : 0;
        if (!lc_merchant_call_owns_campaign($mt_id, $cp_id)) {
            lc_api_error('권한이 없습니다.', 'FORBIDDEN', 403);
        }
        $s = lc_call_settings_get($cp_id, $mt_id);
        lc_api_success(array('settings' => array(
            'cpId'     => (int) $cp_id,
            'enabled'  => (int) ($s['cs_enabled'] ?? 0) === 1,
            'alias'    => (string) ($s['cs_alias'] ?? ''),
            'forward1' => (string) ($s['cs_forward1'] ?? ''),
            'forward2' => (string) ($s['cs_forward2'] ?? ''),
            'adminEnabled'  => (int) ($s['cs_admin_enabled'] ?? 1) === 1,
            'recordingMode' => (string) ($s['cs_recording_mode'] ?? 'normal'),
        )));
    }

    if ($view === 'logs') {
        $filters = array('mt_id' => $mt_id, 'limit' => 200);
        if (isset($_GET['cpId']) && $_GET['cpId'] !== '') {
            $cp_id = (int) $_GET['cpId'];
            if (!lc_merchant_call_owns_campaign($mt_id, $cp_id)) {
                lc_api_error('권한이 없습니다.', 'FORBIDDEN', 403);
            }
            $filters['cp_id'] = $cp_id;
        }
        if (isset($_GET['virtualNumber']) && $_GET['virtualNumber'] !== '') {
            $filters['virtual_number'] = (string) $_GET['virtualNumber'];
        }
        $rows = array();
        foreach (lc_call_logs_list($filters) as $row) {
            $api = lc_call_log_to_api($row, false, false);
            if (function_exists('lc_call_recording_request_meta_for_log')) {
                $api['recordingRequest'] = lc_call_recording_request_meta_for_log((int) $row['clog_id'], 'merchant', $mt_id);
            }
            $rows[] = $api;
        }
        lc_api_success(array('items' => $rows, 'dbReady' => lc_db_installed()));
    }

    lc_api_error('유효하지 않은 view입니다.', 'INVALID_VIEW', 400);
}

if ($method === 'POST') {
    $mt_id = lc_merchant_call_current_mt();
    lc_api_require_method('POST');

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    $cp_id = isset($body['cpId']) ? (int) $body['cpId'] : 0;

    if (!lc_merchant_call_owns_campaign($mt_id, $cp_id)) {
        lc_api_error('권한이 없습니다.', 'FORBIDDEN', 403);
    }

    if ($action === 'save_settings') {
        $result = lc_call_settings_save($cp_id, array(
            'enabled'  => !empty($body['enabled']),
            'alias'    => $body['alias'] ?? '',
            'forward1' => $body['forward1'] ?? '',
            'forward2' => $body['forward2'] ?? '',
        ), 'merchant');
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    if ($action === 'toggle') {
        $result = lc_call_settings_save($cp_id, array('enabled' => !empty($body['enabled'])), 'merchant');
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    if ($action === 'request_recording') {
        $result = lc_call_recording_request_create((int) ($body['clogId'] ?? 0), 'merchant', $mt_id, (string) ($body['memo'] ?? ''));
        $result['ok'] ? lc_api_success($result) : lc_api_error($result['message'], 'REQUEST_FAILED', 400);
    }

    lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
