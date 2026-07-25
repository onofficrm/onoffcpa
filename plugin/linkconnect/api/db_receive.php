<?php
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('POST');

$endpoint = '/api/db_receive.php';
$raw_body = file_get_contents('php://input');
$body = lc_api_read_json_body();
if (!$body && $_POST) {
    $body = $_POST;
}

$api_key = '';
if (isset($_SERVER['HTTP_X_API_KEY'])) {
    $api_key = trim((string) $_SERVER['HTTP_X_API_KEY']);
} elseif (isset($body['api_key'])) {
    $api_key = trim((string) $body['api_key']);
} elseif (isset($body['apiKey'])) {
    $api_key = trim((string) $body['apiKey']);
}

$client_ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$auth = lc_api_client_validate_request($api_key, $client_ip);
$client = $auth['client'];
$client_name = is_array($client) ? (string) $client['ac_name'] : '외부연동';
$ac_id = is_array($client) ? (int) $client['ac_id'] : 0;

$log_base = array(
    'acId'         => $ac_id,
    'clientName'   => $client_name,
    'direction'    => '수신',
    'endpoint'     => $endpoint,
    'requestBody'  => $raw_body ?: json_encode($body, JSON_UNESCAPED_UNICODE),
);

if (!$auth['ok']) {
    $status = strpos($auth['message'], 'IP') !== false ? 'auth' : 'auth';
    $response = array('success' => false, 'error' => $auth['message'], 'code' => 401);
    lc_api_log_write(array_merge($log_base, array(
        'extId'        => (string) ($body['ext_id'] ?? $body['extId'] ?? ''),
        'statusCode'   => 401,
        'status'       => 'auth',
        'error'        => $auth['message'],
        'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
    )));
    lc_api_json($response, 401);
}

$lk_code = isset($body['lkCode']) ? trim((string) $body['lkCode']) : (isset($body['lk_code']) ? trim((string) $body['lk_code']) : '');
$campaign_code = isset($body['campaign_code']) ? trim((string) $body['campaign_code']) : (isset($body['campaignCode']) ? trim((string) $body['campaignCode']) : '');
$ext_id = isset($body['ext_id']) ? trim((string) $body['ext_id']) : (isset($body['extId']) ? trim((string) $body['extId']) : '');

$name = isset($body['name']) ? trim((string) $body['name']) : '';
$phone = isset($body['phone']) ? trim((string) $body['phone']) : '';
if ($phone === '') {
    $response = array('success' => false, 'error' => 'Missing required field: phone', 'code' => 400);
    lc_api_log_write(array_merge($log_base, array(
        'extId'        => $ext_id,
        'statusCode'   => 400,
        'status'       => 'validate',
        'error'        => 'Missing required field: phone',
        'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
    )));
    lc_api_json($response, 400);
}

if ($lk_code !== '') {
    $link = lc_link_get_with_campaign($lk_code);
    if (!$link || $link['lk_status'] !== 'active' || $link['cp_status'] !== LC_STATUS_ACTIVE) {
        $response = array('success' => false, 'error' => 'Invalid link code', 'code' => 404);
        lc_api_log_write(array_merge($log_base, array(
            'extId'        => $ext_id,
            'statusCode'   => 404,
            'status'       => 'failed',
            'error'        => 'Invalid link code',
            'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
        )));
        lc_api_json($response, 404);
    }

    $result = lc_conversion_create_from_link($link, array(
        'name'    => $name,
        'phone'   => $phone,
        'email'   => isset($body['email']) ? (string) $body['email'] : '',
        'region'  => isset($body['region']) ? (string) $body['region'] : '',
        'inquiry' => isset($body['inquiry']) ? (string) $body['inquiry'] : (isset($body['question']) ? (string) $body['question'] : ''),
    ));
} elseif ($campaign_code !== '') {
    $cp_table = lc_table('campaigns');
    $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}` WHERE cp_code = '" . lc_sql_escape($campaign_code) . "' AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' LIMIT 1 ");
    if (!$campaign) {
        $response = array('success' => false, 'error' => 'Invalid campaign code', 'code' => 404);
        lc_api_log_write(array_merge($log_base, array(
            'extId'        => $ext_id,
            'statusCode'   => 404,
            'status'       => 'failed',
            'error'        => 'Invalid campaign code',
            'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
        )));
        lc_api_json($response, 404);
    }

    $result = lc_conversion_create(array(
        'pt_id'   => isset($body['pt_id']) ? (int) $body['pt_id'] : 0,
        'cp_id'   => (int) $campaign['cp_id'],
        'name'    => $name,
        'phone'   => $phone,
        'email'   => isset($body['email']) ? (string) $body['email'] : '',
        'region'  => isset($body['region']) ? (string) $body['region'] : '',
        'inquiry' => isset($body['inquiry']) ? (string) $body['inquiry'] : (isset($body['question']) ? (string) $body['question'] : ''),
        'channel' => isset($body['channel']) ? (string) $body['channel'] : $client_name,
        'sub_id'  => isset($body['sub_id']) ? (string) $body['sub_id'] : (isset($body['subId']) ? (string) $body['subId'] : ''),
    ));
} else {
    $response = array('success' => false, 'error' => 'lkCode or campaign_code required', 'code' => 400);
    lc_api_log_write(array_merge($log_base, array(
        'extId'        => $ext_id,
        'statusCode'   => 400,
        'status'       => 'validate',
        'error'        => 'lkCode or campaign_code required',
        'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
    )));
    lc_api_json($response, 400);
}

if (!$result['ok']) {
    $is_duplicate = stripos($result['message'], '중복') !== false;
    $response = array('success' => false, 'error' => $result['message'], 'code' => $is_duplicate ? 409 : 400);
    lc_api_log_write(array_merge($log_base, array(
        'extId'        => $ext_id,
        'statusCode'   => $is_duplicate ? 409 : 400,
        'status'       => $is_duplicate ? 'duplicate' : 'failed',
        'error'        => $result['message'],
        'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
    )));
    lc_api_json($response, $is_duplicate ? 409 : 400);
}

$db_code = is_array($result['conversion']) ? (string) $result['conversion']['cv_code'] : '';
$response = array(
    'success' => true,
    'db_code' => $db_code,
    'message' => $result['message'],
);
lc_api_log_write(array_merge($log_base, array(
    'extId'        => $ext_id,
    'intCode'      => $db_code,
    'statusCode'   => 200,
    'status'       => 'success',
    'error'        => '',
    'responseBody' => json_encode($response, JSON_UNESCAPED_UNICODE),
)));
lc_api_json($response, 200);
