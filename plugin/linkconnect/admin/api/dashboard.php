<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
lc_api_require_admin();

if (lc_db_installed()) {
    $data = lc_admin_dashboard_data();
    if ($data === null) {
        $data = array(
            'summary'   => array(),
            'chart7d'   => array(),
            'recent'    => array(),
            'partners'  => array('total' => 0, 'active' => 0, 'pending' => 0),
            'merchants' => array('total' => 0, 'active' => 0, 'pending' => 0, 'lowBalance' => 0),
        );
    }
} else {
    $chart = function_exists('lc_sample_admin_chart_7d') ? lc_sample_admin_chart_7d() : array();
    $chart7d = array();
    foreach ($chart as $row) {
        $chart7d[] = array(
            'date'     => (string) $row['date'],
            'received' => (int) $row['received'],
            'approved' => (int) $row['approved'],
            'rejected' => (int) ($row['canceled'] ?? 0),
            'revenue'  => (int) ($row['revenue'] ?? 0) * 10000,
        );
    }

    $data = array(
        'summary' => array(
            'todayReceived'    => 248,
            'todayApproved'    => 173,
            'todayRejected'    => 42,
            'todayRate'        => 69.7,
            'todayRevenue'     => 8650000,
            'pendingDb'        => 18,
            'pendingCharge'    => 2,
            'pendingPartners'  => 1,
            'pendingMerchants' => 1,
        ),
        'chart7d'   => $chart7d,
        'recent'    => array(),
        'partners'  => array('total' => 5, 'active' => 4, 'pending' => 1),
        'merchants' => array('total' => 5, 'active' => 4, 'pending' => 0, 'lowBalance' => 1),
    );
}

lc_api_success($data);
