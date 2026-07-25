<?php
/**
 * LinkConnect SPA 경로 스텁(partner/, admin/ 등) 공통 부트스트랩
 */
if (!defined('_GNUBOARD_')) {
    $lc_g5_root = realpath(dirname(__FILE__) . '/..');
    if ($lc_g5_root === false || !is_file($lc_g5_root . '/common.php')) {
        header('HTTP/1.1 500 Internal Server Error');
        exit('LinkConnect SPA bootstrap: common.php not found');
    }
    include_once $lc_g5_root . '/common.php';
}

if (!defined('_GNUBOARD_')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('LinkConnect SPA bootstrap failed');
}

if (is_file(G5_PATH . '/_site.config.php')) {
    include_once G5_PATH . '/_site.config.php';
}

if (!defined('_INDEX_')) {
    define('_INDEX_', true);
}

if (is_file(G5_PLUGIN_PATH . '/onoff-builder-bridge/bootstrap.php')) {
    include_once G5_PLUGIN_PATH . '/onoff-builder-bridge/bootstrap.php';
    if (function_exists('onoff_builder_maybe_render_home') && onoff_builder_maybe_render_home()) {
        return;
    }

    // SPA 스텁 디렉터리(about/, partner/ 등) — 경로 prefix 목록과 무관하게 직접 렌더
    if (
        function_exists('onoff_builder_home_enabled') && onoff_builder_home_enabled()
        && function_exists('onoff_builder_get_home_bridge_id')
        && function_exists('onoff_builder_render_import_page')
    ) {
        $id = onoff_builder_get_home_bridge_id();
        if ($id !== '') {
            onoff_builder_render_import_page($id);
            return;
        }
    }
}

header('HTTP/1.1 503 Service Unavailable');
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><title>LinkConnect</title></head>';
echo '<body><p>LinkConnect SPA를 불러올 수 없습니다. 관리자에게 문의하세요.</p></body></html>';
exit;
