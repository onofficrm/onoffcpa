<?php
/**
 * 광고주 외부 플랫폼 — 디비 조회·승인·취소 API
 *
 * GET  /plugin/linkconnect/api/v1/merchant/conversions.php
 * GET  /plugin/linkconnect/api/v1/merchant/conversions.php?code=CV-xxx
 * POST /plugin/linkconnect/api/v1/merchant/conversions.php
 *   { "action": "approve"|"reject", "code": "CV-xxx", ... }
 *
 * 인증: Authorization: Bearer sk_mt_...  또는  X-API-KEY: sk_mt_...
 */
require_once dirname(__DIR__, 3) . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
$endpoint = '/api/v1/merchant/conversions.php';
$raw_body = file_get_contents('php://input');
$body = ($method === 'POST') ? lc_api_read_json_body() : array();
if (!$body && $method === 'POST' && $_POST) {
    $body = $_POST;
}

$client = lc_api_require_merchant_client($body);
$mt_id = (int) $client['_mt_id'];
$ac_id = (int) ($client['ac_id'] ?? 0);
$client_name = (string) ($client['ac_name'] ?? 'merchant');

$log_base = array(
    'acId'        => $ac_id,
    'clientName'  => $client_name,
    'direction'   => 'merchant_v1',
    'endpoint'    => $endpoint,
    'requestBody' => $raw_body !== '' ? $raw_body : json_encode(array_merge($_GET, $body), JSON_UNESCAPED_UNICODE),
);

function lc_v1_merchant_log_and_success(array $log_base, array $data, $message = '')
{
    $payload = array('ok' => true, 'data' => $data);
    if ($message !== '') {
        $payload['message'] = $message;
    }
    if (function_exists('lc_api_log_write')) {
        lc_api_log_write(array_merge($log_base, array(
            'extId'        => (string) ($data['conversion']['code'] ?? $data['code'] ?? ''),
            'intCode'      => (string) ($data['conversion']['code'] ?? ''),
            'statusCode'   => 200,
            'status'       => 'success',
            'error'        => '',
            'responseBody' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        )));
    }
    lc_api_success($data, $message !== '' ? array('message' => $message) : array());
}

function lc_v1_merchant_log_and_error(array $log_base, $message, $code, $status, array $extra = array())
{
    $response = array_merge(array(
        'ok'      => false,
        'error'   => $message,
        'code'    => $code,
    ), $extra);
    if (function_exists('lc_api_log_write')) {
        lc_api_log_write(array_merge($log_base, array(
            'extId'        => (string) ($extra['code'] ?? ''),
            'statusCode'   => (int) $status,
            'status'       => $status >= 500 ? 'failed' : ($status === 401 || $status === 403 ? 'auth' : 'validate'),
            'error'        => $message,
            'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
        )));
    }
    lc_api_error($message, $code, $status, $extra);
}

function lc_v1_resolve_conversion_for_merchant($mt_id, $code)
{
    $code = trim((string) $code);
    if ($code === '') {
        return array('ok' => false, 'message' => '디비 코드(code)가 필요합니다.', 'errorCode' => 'INVALID_CODE', 'http' => 400);
    }

    $row = lc_conversion_get_by_code($code);
    if (!$row) {
        return array('ok' => false, 'message' => '디비를 찾을 수 없습니다.', 'errorCode' => 'NOT_FOUND', 'http' => 404);
    }

    if (!lc_conversion_belongs_to_merchant($row, $mt_id)) {
        return array('ok' => false, 'message' => '접근 권한이 없습니다.', 'errorCode' => 'FORBIDDEN', 'http' => 403);
    }

    $meta = lc_conversion_with_meta((int) $row['cv_id']);
    if (!$meta) {
        $meta = $row;
    }

    return array('ok' => true, 'row' => $meta);
}

if ($method === 'GET') {
    $code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';

    if ($code !== '') {
        $resolved = lc_v1_resolve_conversion_for_merchant($mt_id, $code);
        if (!$resolved['ok']) {
            lc_v1_merchant_log_and_error($log_base, $resolved['message'], $resolved['errorCode'], $resolved['http']);
        }
        $item = lc_conversion_to_api_v1($resolved['row']);
        if (function_exists('lc_api_log_write')) {
            lc_api_log_write(array_merge($log_base, array(
                'extId'        => $code,
                'intCode'      => $code,
                'statusCode'   => 200,
                'status'       => 'success',
                'error'        => '',
                'responseBody' => json_encode(array('ok' => true, 'data' => array('conversion' => $item)), JSON_UNESCAPED_UNICODE),
            )));
        }
        lc_api_success(array(
            'conversion'    => $item,
            'rejectReasons' => lc_conversion_reject_reasons(),
        ));
    }

    $filters = array();
    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if (isset($_GET['needs_action']) && $_GET['needs_action'] === '1') {
        $filters['needs_action'] = true;
    }
    if (isset($_GET['q']) && trim((string) $_GET['q']) !== '') {
        $filters['q'] = trim((string) $_GET['q']);
    }

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $limit = max(1, min(200, $limit));

    $rows = lc_conversion_list_for_merchant($mt_id, $filters);
    $items = array();
    foreach ($rows as $row) {
        $items[] = lc_conversion_to_api_v1($row);
        if (count($items) >= $limit) {
            break;
        }
    }

    $summary = function_exists('lc_conversion_merchant_summary')
        ? lc_conversion_merchant_summary($mt_id)
        : array();

    if (function_exists('lc_api_log_write')) {
        lc_api_log_write(array_merge($log_base, array(
            'statusCode'   => 200,
            'status'       => 'success',
            'error'        => '',
            'responseBody' => json_encode(array('ok' => true, 'count' => count($items)), JSON_UNESCAPED_UNICODE),
        )));
    }

    lc_api_success(array(
        'items'         => $items,
        'total'         => count($items),
        'summary'       => $summary,
        'rejectReasons' => lc_conversion_reject_reasons(),
    ));
}

if ($method === 'POST') {
    $action = isset($body['action']) ? trim((string) $body['action']) : '';
    $code = isset($body['code']) ? trim((string) $body['code']) : (isset($body['cvCode']) ? trim((string) $body['cvCode']) : '');

    if ($action === 'meta' || $action === 'reasons') {
        lc_api_success(array(
            'rejectReasons' => lc_conversion_reject_reasons(),
            'statuses'      => array(LC_STATUS_PENDING, LC_STATUS_APPROVED, LC_STATUS_REJECTED),
        ));
    }

    $resolved = lc_v1_resolve_conversion_for_merchant($mt_id, $code);
    if (!$resolved['ok']) {
        lc_v1_merchant_log_and_error($log_base, $resolved['message'], $resolved['errorCode'], $resolved['http']);
    }

    $row = $resolved['row'];
    $cv_id = (int) $row['cv_id'];

    // 멱등: 이미 처리된 건은 현재 상태 반환
    if ((string) ($row['cv_status'] ?? '') !== LC_STATUS_PENDING) {
        $item = lc_conversion_to_api_v1($row);
        if (function_exists('lc_api_log_write')) {
            lc_api_log_write(array_merge($log_base, array(
                'extId'        => $code,
                'intCode'      => $code,
                'statusCode'   => 200,
                'status'       => 'success',
                'error'        => 'already_processed',
                'responseBody' => json_encode(array('ok' => true, 'duplicate' => true), JSON_UNESCAPED_UNICODE),
            )));
        }
        lc_api_success(array(
            'message'     => '이미 처리된 디비입니다.',
            'alreadyProcessed' => true,
            'conversion'  => $item,
        ));
    }

    if ($action === 'approve') {
        $comment = isset($body['comment']) ? trim((string) $body['comment']) : '';
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
        if (!$result['ok']) {
            $err = (string) $result['message'];
            $code_key = (stripos($err, '잔액') !== false || stripos($err, '광고비') !== false)
                ? 'INSUFFICIENT_BALANCE'
                : 'UPDATE_FAILED';
            lc_v1_merchant_log_and_error($log_base, $err, $code_key, 400, array('code' => $code));
        }

        $updated = isset($result['conversion']) && is_array($result['conversion'])
            ? $result['conversion']
            : lc_conversion_with_meta($cv_id);
        $item = lc_conversion_to_api_v1(is_array($updated) ? $updated : $row);

        if (function_exists('lc_api_log_write')) {
            lc_api_log_write(array_merge($log_base, array(
                'extId'        => $code,
                'intCode'      => $code,
                'statusCode'   => 200,
                'status'       => 'success',
                'error'        => '',
                'responseBody' => json_encode(array('ok' => true, 'action' => 'approve'), JSON_UNESCAPED_UNICODE),
            )));
        }

        lc_api_success(array(
            'message'    => $result['message'],
            'conversion' => $item,
        ));
    }

    if ($action === 'reject') {
        $reason = isset($body['reason']) ? trim((string) $body['reason']) : '';
        $comment = isset($body['comment']) ? trim((string) $body['comment']) : '';
        if ($reason === '') {
            lc_v1_merchant_log_and_error($log_base, '취소 사유(reason)가 필요합니다.', 'INVALID_REASON', 400, array(
                'rejectReasons' => lc_conversion_reject_reasons(),
                'code'          => $code,
            ));
        }

        $allowed = lc_conversion_reject_reasons();
        if (!in_array($reason, $allowed, true)) {
            lc_v1_merchant_log_and_error($log_base, '유효하지 않은 취소 사유입니다.', 'INVALID_REASON', 400, array(
                'rejectReasons' => $allowed,
                'code'          => $code,
            ));
        }

        $full_comment = $reason;
        if ($comment !== '') {
            $full_comment = $reason . ' - ' . $comment;
        }

        $opts = array();
        if (isset($body['partnerVisible'])) {
            $opts['partnerVisible'] = !empty($body['partnerVisible']);
        }
        if (isset($body['qualityTags']) && is_array($body['qualityTags'])) {
            $opts['qualityTags'] = $body['qualityTags'];
        }

        $result = lc_conversion_update_status($cv_id, $mt_id, LC_STATUS_REJECTED, $full_comment, $opts);
        if (!$result['ok']) {
            lc_v1_merchant_log_and_error($log_base, (string) $result['message'], 'UPDATE_FAILED', 400, array('code' => $code));
        }

        $updated = isset($result['conversion']) && is_array($result['conversion'])
            ? $result['conversion']
            : lc_conversion_with_meta($cv_id);
        $item = lc_conversion_to_api_v1(is_array($updated) ? $updated : $row);

        if (function_exists('lc_api_log_write')) {
            lc_api_log_write(array_merge($log_base, array(
                'extId'        => $code,
                'intCode'      => $code,
                'statusCode'   => 200,
                'status'       => 'success',
                'error'        => '',
                'responseBody' => json_encode(array('ok' => true, 'action' => 'reject'), JSON_UNESCAPED_UNICODE),
            )));
        }

        lc_api_success(array(
            'message'    => $result['message'],
            'conversion' => $item,
        ));
    }

    lc_v1_merchant_log_and_error($log_base, '유효하지 않은 action입니다. (approve|reject)', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
