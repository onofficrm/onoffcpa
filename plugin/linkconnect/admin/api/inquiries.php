<?php
require_once __DIR__ . '/_common.php';

lc_api_require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $iq_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $center = isset($_GET['center']) ? trim((string) $_GET['center']) : '';
    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';

    if ($iq_id > 0 && $action === 'download_attachment') {
        lc_inquiry_ensure_schema();
        $row = lc_inquiry_get_by_id($iq_id);
        if (!$row) {
            lc_api_error('문의를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        $rel = trim((string) ($row['iq_attachment_path'] ?? ''));
        $name = trim((string) ($row['iq_attachment_name'] ?? 'attachment'));
        $mime = trim((string) ($row['iq_attachment_mime'] ?? 'application/octet-stream'));
        $full = function_exists('lc_inquiry_attachment_full_path') ? lc_inquiry_attachment_full_path($rel) : '';
        if ($rel === '' || $full === '' || !is_file($full)) {
            lc_api_error('첨부파일이 없습니다.', 'NOT_FOUND', 404);
        }
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($full));
        header('Content-Disposition: attachment; filename="' . str_replace(array('"', "\r", "\n"), '', $name) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($full);
        exit;
    }

    if ($iq_id > 0) {
        $row = lc_inquiry_get_by_id($iq_id);
        if (!$row) {
            lc_api_error('문의를 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        // JOIN으로 파트너/광고주명 보강 (첨부 컬럼은 iq.*에 포함)
        $rows = lc_inquiry_list(array('q' => (string) ($row['iq_code'] ?? '')), 5);
        $detail = $row;
        foreach ((is_array($rows) ? $rows : array()) as $candidate) {
            if ((int) ($candidate['iq_id'] ?? 0) === $iq_id) {
                $detail = $candidate;
                break;
            }
        }
        lc_api_success(array(
            'item' => lc_inquiry_to_api($detail, true),
        ));
    }

    $filters = array();
    if ($center !== '') {
        $filters['center'] = $center;
    }
    if ($status !== '') {
        $filters['status'] = $status;
    }
    if ($q !== '') {
        $filters['q'] = $q;
    }

    lc_api_success(array(
        'summary' => lc_inquiry_summary($filters),
        'items'   => array_map(function ($row) {
            return lc_inquiry_to_api($row, false);
        }, lc_inquiry_list($filters)),
        'dbReady' => lc_db_installed(),
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = lc_api_read_json_body();
    $iq_id = isset($body['iqId']) ? (int) $body['iqId'] : 0;
    if ($iq_id <= 0) {
        lc_api_error('문의 ID가 필요합니다.', 'INVALID_ID', 400);
    }

    $result = lc_inquiry_admin_reply($iq_id, $body);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'INQUIRY_UPDATE_FAILED', 400);
    }

    $rows = lc_inquiry_list(array('q' => (string) ($result['inquiry']['iq_code'] ?? '')), 1);
    $detail = $rows ? $rows[0] : $result['inquiry'];

    lc_api_success(array(
        'message' => $result['message'],
        'item'    => lc_inquiry_to_api($detail, true),
        'summary' => lc_inquiry_summary(),
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
