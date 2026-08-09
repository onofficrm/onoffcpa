<?php
require_once __DIR__ . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_admin();

    $filters = array(
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
        'source' => isset($_GET['source']) ? (string) $_GET['source'] : '',
    );

    $format = isset($_GET['format']) ? strtolower(trim((string) $_GET['format'])) : '';
    if ($format === 'csv') {
        if (!lc_db_installed()) {
            lc_api_error('DB가 설치되지 않았습니다.', 'DB_NOT_READY', 400);
        }
        $csv = function_exists('lc_admin_conversions_export_csv')
            ? lc_admin_conversions_export_csv($filters, 5000)
            : '';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="conversions_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-store');
        echo "\xEF\xBB\xBF"; // Excel UTF-8 BOM
        echo $csv;
        exit;
    }

    $items = array();
    $summary = array(
        'todayReceived' => 0,
        'approved'      => 0,
        'rejected'      => 0,
        'pending'       => 0,
    );

    if (lc_db_installed()) {
        $items = array_map('lc_admin_conversion_to_api', lc_admin_list_conversions($filters, 100));

        $cv_table = lc_table('conversions');
        $today = date('Y-m-d');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN DATE(cv_created_at) = '{$today}' THEN 1 ELSE 0 END) AS today_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' THEN 1 ELSE 0 END) AS pending_cnt
            FROM `{$cv_table}` ");

        $summary = array(
            'todayReceived' => (int) ($row['today_cnt'] ?? 0),
            'approved'      => (int) ($row['approved_cnt'] ?? 0),
            'rejected'      => (int) ($row['rejected_cnt'] ?? 0),
            'pending'       => (int) ($row['pending_cnt'] ?? 0),
        );
    }

    lc_api_success(array(
        'items'   => $items,
        'summary' => $summary,
        'total'   => count($items),
        'dbReady' => lc_db_installed(),
    ));
}

if ($method === 'POST') {
    lc_api_require_admin();
    lc_api_require_method('POST');

    if (!lc_is_super_admin()) {
        lc_api_error('최고관리자만 전체 디비를 초기화할 수 있습니다.', 'FORBIDDEN', 403);
    }

    if (!lc_db_installed()) {
        lc_api_error('DB가 설치되지 않았습니다.', 'DB_NOT_READY', 400);
    }

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : '';

    if ($action !== 'reset_all') {
        lc_api_error('유효하지 않은 action입니다.', 'INVALID_ACTION', 400);
    }

    $confirm = isset($body['confirm']) ? trim((string) $body['confirm']) : '';
    if ($confirm !== '초기화') {
        lc_api_error('초기화를 확인하려면 confirm에 "초기화"를 입력해주세요.', 'CONFIRM_REQUIRED', 400);
    }

    $cv_table = lc_table('conversions');
    $before = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` ");
    $before_cnt = is_array($before) ? (int) ($before['cnt'] ?? 0) : 0;

    lc_sql_query(" DELETE FROM `{$cv_table}` ", false);

    $after = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` ");
    $after_cnt = is_array($after) ? (int) ($after['cnt'] ?? 0) : 0;

    if (function_exists('lc_admin_log_write')) {
        lc_admin_log_write('conversions_reset', 'conversion', 0, '전체 디비 목록 초기화', array(
            'deleted' => $before_cnt - $after_cnt,
        ));
    }

    lc_api_success(array(
        'message' => '전체 디비 목록을 초기화했습니다. (' . number_format($before_cnt - $after_cnt) . '건 삭제)',
        'deleted' => $before_cnt - $after_cnt,
        'remaining' => $after_cnt,
    ));
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
