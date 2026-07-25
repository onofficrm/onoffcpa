<?php
/**
 * 다중 플랫폼 — 헬스/상태 (시크릿 없이 최소 정보만)
 * 플래그 OFF 시 404.
 */
require_once dirname(__DIR__, 2) . '/_common.php';

lc_mp_require_enabled();

lc_api_success(array(
    'enabled'        => true,
    'localPlatform'  => lc_mp_local_platform_code(),
    'schemaReady'    => function_exists('lc_db_table_exists') && lc_db_table_exists(lc_mp_db_table('platforms')),
));
