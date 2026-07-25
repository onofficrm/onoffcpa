<?php
require_once __DIR__ . '/_common.php';

lc_api_require_method('GET');
$merchant = lc_api_require_active_merchant();
$mt_id = (int) $merchant['mt_id'];

$filters = array(
    'period'   => isset($_GET['period']) ? $_GET['period'] : 30,
    'dateFrom' => isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '',
    'dateTo'   => isset($_GET['dateTo']) ? $_GET['dateTo'] : '',
    'cpId'     => isset($_GET['cpId']) ? $_GET['cpId'] : 0,
);

if (!function_exists('lc_merchant_marketing_for_api')) {
    lc_api_error('마케팅 분석 모듈을 사용할 수 없습니다.', 'NOT_AVAILABLE', 503);
}

lc_api_success(array_merge(
    lc_merchant_marketing_for_api($mt_id, $filters),
    array('dbReady' => lc_db_installed())
));
