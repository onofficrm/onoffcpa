#!/usr/bin/env php
<?php
/**
 * LinkConnect 데모 계정 CLI 시드
 *
 * Usage:
 *   php scripts/seed-linkconnect-demo.php
 *   php scripts/seed-linkconnect-demo.php --partner=lc_partner --advertiser=lc_advertiser
 */
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Project root not found.\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_REQUEST['action'] = 'run';

foreach ($argv as $arg) {
    if (strpos($arg, '--partner=') === 0) {
        $_REQUEST['partner_mb_id'] = substr($arg, 10);
    }
    if (strpos($arg, '--advertiser=') === 0) {
        $_REQUEST['advertiser_mb_id'] = substr($arg, 13);
    }
    if (strpos($arg, '--password=') === 0) {
        $_REQUEST['password'] = substr($arg, 11);
    }
}

chdir($root);
$_SERVER['SCRIPT_FILENAME'] = $root . '/plugin/linkconnect/install/seed_demo.php';
include $root . '/plugin/linkconnect/install/seed_demo.php';
