<?php
require_once __DIR__ . '/_common.php';

lc_api_require_admin();
lc_merchant_ad_apply_db_ensure_schema();

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    $maa_id = isset($_GET['maaId']) ? (int) $_GET['maaId'] : 0;
    if ($maa_id > 0) {
        $row = lc_merchant_ad_apply_get($maa_id);
        if (!is_array($row)) {
            lc_api_error('신청서를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $api = lc_merchant_ad_apply_to_api($row, true);
        $mt_id = (int) ($row['maa_mt_id'] ?? 0);
        $merchant = $mt_id > 0 ? lc_get_merchant_by_id($mt_id) : null;
        $api['merchantCode'] = is_array($merchant) ? (string) ($merchant['mt_code'] ?? '') : '';
        $api['merchantCompany'] = is_array($merchant) ? (string) ($merchant['mt_company'] ?? '') : '';
        lc_api_success(array('application' => $api));
    }

    $data = lc_merchant_ad_apply_admin_list(
        isset($_GET['page']) ? (int) $_GET['page'] : 1,
        isset($_GET['limit']) ? (int) $_GET['limit'] : 30,
        isset($_GET['status']) ? (string) $_GET['status'] : ''
    );
    lc_api_success($data);
}

if ($method === 'POST') {
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';
    $maa_id = isset($body['maaId']) ? (int) $body['maaId'] : 0;
    if ($maa_id <= 0) {
        lc_api_error('신청서 ID가 필요합니다.', 'INVALID_REQUEST', 400);
    }

    if ($action === 'set_status') {
        $result = lc_merchant_ad_apply_admin_set_status(
            $maa_id,
            isset($body['status']) ? (string) $body['status'] : '',
            isset($body['note']) ? (string) $body['note'] : ''
        );
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'UPDATE_FAILED', 400);
        }
        lc_api_success(array(
            'message'     => $result['message'],
            'application' => is_array($result['row']) ? lc_merchant_ad_apply_to_api($result['row'], true) : null,
        ));
    }

    lc_api_error('알 수 없는 액션입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
