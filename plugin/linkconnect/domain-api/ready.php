<?php
/**
 * DOMAIN S2S — ensure Core referral SoT is ready.
 * POST /plugin/linkconnect/domain-api/ready.php
 */
require_once dirname(__DIR__) . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lc_api_error('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

$auth = lc_domain_s2s_require('domain.core.ready');
$ready = function_exists('lc_onoff_core_ensure_ready')
    ? lc_onoff_core_ensure_ready()
    : array('ok' => false, 'message' => 'bridge missing');

lc_api_success(array(
    'ready'   => $ready,
    'client'  => (string) ($auth['client']['name'] ?? ''),
));
