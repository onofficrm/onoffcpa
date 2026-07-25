<?php
/**
 * LinkConnect 플러그인 진입점
 */
require_once __DIR__ . '/_common.php';

header('Location: ' . lc_public_home_url(), true, 302);
exit;
