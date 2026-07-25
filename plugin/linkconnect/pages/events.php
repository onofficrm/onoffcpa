<?php
require_once dirname(__DIR__) . '/_common.php';

$lc_page_title = '이벤트/프로모션';
$lc_active_nav = 'events';
$lc_body_class = 'lc-app lc-app--public';
$lc_main_class = 'lc-main lc-main--flush';

lc_render_public_page('public/events');
