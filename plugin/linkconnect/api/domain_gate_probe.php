<?php
/**
 * 독립 도메인 게이트 점검 (임시)
 * /plugin/linkconnect/api/domain_gate_probe.php?key=lc-domain-gate-2026
 */
require_once dirname(__DIR__) . '/_common.php';

$key = isset($_GET['key']) ? (string) $_GET['key'] : '';
if ($key !== 'lc-domain-gate-2026') {
    lc_api_error('forbidden', 'FORBIDDEN', 403);
}

$host = function_exists('lc_link_request_host') ? lc_link_request_host() : (string) ($_SERVER['HTTP_HOST'] ?? '');
$file = function_exists('linkconnect_tracking_home_landing_file')
    ? linkconnect_tracking_home_landing_file($host)
    : '';
$path = function_exists('linkconnect_tracking_home_landing_path')
    ? linkconnect_tracking_home_landing_path($host)
    : '';

lc_api_success(array(
    'httpHost'       => (string) ($_SERVER['HTTP_HOST'] ?? ''),
    'xForwardedHost' => (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''),
    'resolvedHost'   => $host,
    'landingFile'    => $file,
    'landingPath'    => $path,
    'hasLandingFn'   => function_exists('linkconnect_tracking_home_landing_file'),
    'hasPathFn'      => function_exists('linkconnect_tracking_home_landing_path'),
    'gateBuild'      => '2026-08-02-iloves-v3',
));
