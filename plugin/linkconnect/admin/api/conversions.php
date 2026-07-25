<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
lc_api_require_admin();

$filters = array(
    'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
);

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
} elseif (function_exists('lc_sample_admin_conversions')) {
    foreach (lc_sample_admin_conversions() as $row) {
        $items[] = array(
            'id'         => (string) $row['id'],
            'cvId'       => 0,
            'date'       => (string) $row['date'],
            'campaign'   => (string) $row['campaign'],
            'partner'    => (string) $row['partner'],
            'advertiser' => (string) $row['merchant'],
            'customer'   => (string) $row['customer'],
            'status'     => (string) $row['status'],
            'statusCode' => '',
            'price'      => 0,
        );
    }

    $summary = array(
        'todayReceived' => 248,
        'approved'      => 173,
        'rejected'      => 42,
        'pending'       => 18,
    );
}

lc_api_success(array(
    'items'   => $items,
    'summary' => $summary,
    'total'   => count($items),
    'dbReady' => lc_db_installed(),
));
