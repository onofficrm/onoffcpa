<?php
require_once dirname(__DIR__) . '/_common.php';
$lc_page_title = '정산 신청';
$lc_active_nav = 'partner';
$lc_sidebar_active = 'settlements';
lc_render_center_page(LC_CENTER_PARTNER, 'partner/settlements');
