<?php
/**
 * Platform S2S — ensure Core referral SoT is ready.
 * POST /plugin/linkconnect/platform-api/ready.php
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_platform_s2s_require('platform.core.ready');
$ready = function_exists('lc_onoff_core_ensure_ready')
    ? lc_onoff_core_ensure_ready()
    : array('ok' => false, 'message' => 'bridge missing');

lc_api_success(array(
    'ready'  => $ready,
    'client' => (string) ($auth['client']['name'] ?? ''),
));
