<?php
require_once dirname(__DIR__) . '/_common.php';

lc_api_require_method('GET');

$params = array_merge($_GET, array());
$ctx = lc_landing_context_for_api($params);

lc_api_success($ctx);
