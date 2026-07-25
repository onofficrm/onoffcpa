<?php
include_once('./_common.php');

define('_INDEX_', true);
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (is_file(G5_PLUGIN_PATH . '/onoff-builder-bridge/bootstrap.php')) {
    include_once G5_PLUGIN_PATH . '/onoff-builder-bridge/bootstrap.php';
    if (function_exists('onoff_builder_maybe_render_home') && onoff_builder_maybe_render_home()) {
        return;
    }
}

include_once(G5_PATH.'/inc/onoff-builder-home.php');
