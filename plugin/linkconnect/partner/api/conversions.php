<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $rejected = isset($_GET['rejected']) && $_GET['rejected'] === '1';

    $filters = array();
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if ($q !== '') {
        $filters['q'] = $q;
    }
    if ($rejected) {
        $filters['rejected_only'] = true;
    }

    $response = array(
        'items'   => lc_conversion_list_for_partner_api($pt_id, $filters),
        'summary' => lc_conversion_partner_summary($pt_id),
        'total'   => 0,
    );

    if ($rejected) {
        $response['cancelSummary'] = lc_conversion_partner_cancel_summary($pt_id);
    }

    $response['total'] = count($response['items']);

    lc_api_success($response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];
    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action === 'appeal') {
        $cv_id = isset($body['cvId']) ? (int) $body['cvId'] : 0;
        $appeal = isset($body['appeal']) ? (string) $body['appeal'] : '';
        $result = lc_conversion_partner_appeal($pt_id, $cv_id, $appeal);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'APPEAL_FAILED', 400);
        }
        lc_api_success(array(
            'message'    => $result['message'],
            'conversion' => $result['conversion'],
        ));
    }

    lc_api_error('유효하지 않은 요청입니다.', 'INVALID_ACTION', 400);
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
