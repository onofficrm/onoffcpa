<?php
/**
 * 루트 크론 엔트리 — 플러그인 크론으로 위임
 * php cron/linkprice_sync_merchants.php [--scope=apr|all] [--detail=1] [--test]
 */
require dirname(__DIR__) . '/plugin/linkconnect/cron/linkprice_sync_merchants.php';
