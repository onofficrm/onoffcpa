<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    $iq_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($iq_id > 0) {
        $row = lc_inquiry_get_by_id($iq_id);
        if (!$row || (int) $row['mt_id'] !== $mt_id) {
            lc_api_error('문의를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $row['mt_code'] = $merchant['mt_code'] ?? '';
        $row['mt_company'] = $merchant['mt_company'] ?? '';
        lc_api_success(array(
            'item' => lc_inquiry_to_api($row, true),
        ));
    }

    lc_api_success(array(
        'summary' => lc_inquiry_summary(array('mt_id' => $mt_id)),
        'items'   => array_map(function ($row) {
            return lc_inquiry_to_api($row, false);
        }, lc_inquiry_list(array('mt_id' => $mt_id))),
        'dbReady' => lc_db_installed(),
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchant = lc_api_require_active_merchant();
    $mt_id = (int) $merchant['mt_id'];
    $body = lc_api_read_json_body();

    $result = lc_inquiry_create(array(
        'center'   => 'merchant',
        'mt_id'    => $mt_id,
        'mb_id'    => isset($GLOBALS['member']['mb_id']) ? (string) $GLOBALS['member']['mb_id'] : '',
        'category' => $body['category'] ?? '',
        'subject'  => $body['subject'] ?? '',
        'body'     => $body['body'] ?? '',
        'campaign' => $body['campaign'] ?? '',
        'cvCode'   => $body['cvCode'] ?? '',
    ));

    if (!$result['ok']) {
        lc_api_error($result['message'], 'INQUIRY_FAILED', 400);
    }

    $inquiry = $result['inquiry'];
    $inquiry['mt_code'] = $merchant['mt_code'] ?? '';
    $inquiry['mt_company'] = $merchant['mt_company'] ?? '';

    lc_api_success(array(
        'message' => $result['message'],
        'item'    => lc_inquiry_to_api($inquiry, false),
        'summary' => lc_inquiry_summary(array('mt_id' => $mt_id)),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
