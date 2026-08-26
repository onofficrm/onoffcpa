<?php
/**
 * GET /plugin/linkconnect/content-api/analytics.php
 *
 * Scope: content.analytics.read
 * Read-only: dashboard | analytics | conversions (query view=)
 *
 * Reuses existing partner domain helpers; no settlement/status mutation.
 */
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');

$auth = lc_content_s2s_require('content.analytics.read');
$pt_id = (int) $auth['pt_id'];

$override = lc_content_s2s_reject_client_pt_override($_GET, $pt_id);
if (empty($override['ok'])) {
    lc_api_error($override['message'], $override['code'], 403);
}

$view = isset($_GET['view']) ? strtolower(trim((string) $_GET['view'])) : 'dashboard';
$allowed_views = array('dashboard', 'analytics', 'conversions', 'summary');
if (!in_array($view, $allowed_views, true)) {
    lc_api_error('Invalid view. Use dashboard|analytics|conversions|summary.', 'INVALID_VIEW', 400);
}

if ($view === 'dashboard' || $view === 'summary') {
    lc_api_success(array_merge(
        lc_partner_dashboard_for_api($pt_id),
        array('partnerId' => $pt_id, 'view' => $view)
    ));
}

if ($view === 'analytics') {
    $filters = array(
        'period'   => isset($_GET['period']) ? $_GET['period'] : 7,
        'dateFrom' => isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '',
        'dateTo'   => isset($_GET['dateTo']) ? $_GET['dateTo'] : '',
        'linkId'   => isset($_GET['linkId']) ? $_GET['linkId'] : 0,
        'channel'  => isset($_GET['channel']) ? $_GET['channel'] : '',
        'linkName' => isset($_GET['linkName']) ? $_GET['linkName'] : '',
        'source'   => isset($_GET['source']) ? $_GET['source'] : 'cpa',
    );

    if (function_exists('lc_partner_analytics_for_api')) {
        $data = lc_partner_analytics_for_api($pt_id, $filters);
        lc_api_success(array_merge($data, array(
            'dbReady'   => lc_db_installed(),
            'partnerId' => $pt_id,
            'view'      => 'analytics',
        )));
    }

    $data = lc_conversion_partner_analytics_for_api($pt_id);
    lc_api_success(array_merge($data, array(
        'dbReady'   => lc_db_installed(),
        'partnerId' => $pt_id,
        'view'      => 'analytics',
    )));
}

// conversions (read-only list)
$status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$source = isset($_GET['source']) ? trim((string) $_GET['source']) : '';
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

lc_api_success(array(
    'items'     => lc_conversion_list_for_partner_api($pt_id, $filters),
    'summary'   => lc_conversion_partner_summary($pt_id),
    'partnerId' => $pt_id,
    'view'      => 'conversions',
));
