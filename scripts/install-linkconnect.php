#!/usr/bin/env php
<?php
/**
 * LinkConnect DB CLI 설치
 *
 * Usage:
 *   php scripts/install-linkconnect.php
 *   php scripts/install-linkconnect.php --activate=admin
 */
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Project root not found.\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_REQUEST['action'] = 'run';

foreach ($argv as $arg) {
    if (strpos($arg, '--activate=') === 0) {
        $_REQUEST['activate_mb_id'] = substr($arg, 11);
    }
    if (strpos($arg, '--activate-merchant=') === 0) {
        $_REQUEST['activate_merchant_mb_id'] = substr($arg, 20);
    }
}

chdir($root);
$_SERVER['SCRIPT_FILENAME'] = $root . '/plugin/linkconnect/install/install.php';
include $root . '/plugin/linkconnect/install/install.php';
