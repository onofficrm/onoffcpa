<?php
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $partner = lc_api_require_active_partner();
    $pt_id = (int) $partner['pt_id'];

    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $source = isset($_GET['source']) ? trim((string) $_GET['source']) : '';
    $rejected = isset($_GET['rejected']) && $_GET['rejected'] === '1';
    $format = isset($_GET['format']) ? strtolower(trim((string) $_GET['format'])) : '';

    $filters = array();
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if ($q !== '') {
        $filters['q'] = $q;
    }
    if ($source !== '') {
        $filters['source'] = $source;
    }
    if ($rejected) {
        $filters['rejected_only'] = true;
    }

    if ($format === 'csv') {
        $filters['limit'] = 5000;
        $csv = function_exists('lc_conversion_partner_export_csv')
            ? lc_conversion_partner_export_csv($pt_id, $filters)
            : '';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="partner_conversions_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-store');
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
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
