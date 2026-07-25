<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
$partner = lc_api_require_active_partner();
$pt_id = (int) $partner['pt_id'];

$filters = array(
    'period'     => isset($_GET['period']) ? $_GET['period'] : 7,
    'dateFrom'   => isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '',
    'dateTo'     => isset($_GET['dateTo']) ? $_GET['dateTo'] : '',
    'linkId'     => isset($_GET['linkId']) ? $_GET['linkId'] : 0,
    'lpmId'      => isset($_GET['lpmId']) ? $_GET['lpmId'] : 0,
    'channel'    => isset($_GET['channel']) ? $_GET['channel'] : '',
    'linkName'   => isset($_GET['linkName']) ? $_GET['linkName'] : '',
    'source'     => isset($_GET['source']) ? $_GET['source'] : 'cpa',
    'compareIds' => isset($_GET['compareIds']) ? $_GET['compareIds'] : '',
    'compareLpmIds' => isset($_GET['compareLpmIds']) ? $_GET['compareLpmIds'] : '',
);

if (function_exists('lc_partner_analytics_for_api')) {
    $data = lc_partner_analytics_for_api($pt_id, $filters);
    $data['chart7d'] = $data['chart'] ?? array();
    lc_api_success(array_merge($data, array('dbReady' => lc_db_installed())));
}

$data = lc_conversion_partner_analytics_for_api($pt_id);
$data['chart7d'] = $data['chart7d'] ?? array();
$data['chart'] = $data['chart7d'];
lc_api_success(array_merge($data, array(
    'dbReady'       => lc_db_installed(),
    'range'         => array('dateFrom' => '', 'dateTo' => '', 'period' => 7),
    'funnel'        => array(
        'clicks'    => (int) ($data['summary']['totalClicks'] ?? 0),
        'received'  => (int) ($data['summary']['totalDb'] ?? 0),
        'approved'  => (int) ($data['summary']['approvedDb'] ?? 0),
        'confirmed' => (int) ($data['summary']['approvedDb'] ?? 0),
    ),
    'linkNames'     => array(),
    'links'         => array(),
    'compareLinks'  => array(),
    'referrers'     => array(),
    'devices'       => array(),
    'filterOptions' => array('links' => array(), 'channels' => array(), 'linkNames' => array()),
    'summary'       => array_merge(array(
        'uniqueVisitors' => 0,
        'confRevenue'    => 0,
        'epc'            => 0,
    ), $data['summary'] ?? array()),
)));
