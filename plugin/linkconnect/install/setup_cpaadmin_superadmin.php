<?php
/**
 * One-shot: ensure superadmin member cpaadmin with given password and cf_admin.
 * Deploy temporarily, call ?action=run, then delete from server.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'run') {
    echo json_encode(array('ok' => false, 'error' => 'usage: ?action=run'), JSON_UNESCAPED_UNICODE);
    exit;
}

define('_GNUBOARD_', true);
include_once __DIR__ . '/common.php';

$out = array('ok' => false);
$admin_id = 'cpaadmin';
$plain = 'a123456@';

if (!function_exists('get_encrypt_string') || !function_exists('sql_query')) {
    $out['error'] = 'gnuboard helpers missing';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$hash = get_encrypt_string($plain);
$now = G5_TIME_YMDHIS;
$member_table = $g5['member_table'];
$config_table = $g5['config_table'];

$mb = get_member($admin_id);
$created = false;
if (!$mb || empty($mb['mb_id'])) {
    $nick = 'CPA관리자';
    $sql = " INSERT INTO `{$member_table}` SET
        mb_id = '" . sql_escape_string($admin_id) . "',
        mb_password = '" . sql_escape_string($hash) . "',
        mb_name = '" . sql_escape_string($nick) . "',
        mb_nick = '" . sql_escape_string($nick) . "',
        mb_nick_date = '" . sql_escape_string(G5_TIME_YMD) . "',
        mb_email = 'cpaadmin@onoffcpa.icrm.co.kr',
        mb_homepage = '',
        mb_level = 10,
        mb_sex = '',
        mb_signature = '',
        mb_memo = '',
        mb_lost_certify = '',
        mb_mailling = 0,
        mb_sms = 0,
        mb_open = 0,
        mb_open_date = '" . sql_escape_string(G5_TIME_YMD) . "',
        mb_profile = '',
        mb_today_login = '" . sql_escape_string($now) . "',
        mb_datetime = '" . sql_escape_string($now) . "',
        mb_ip = '" . sql_escape_string($_SERVER['REMOTE_ADDR'] ?? '') . "',
        mb_leave_date = '',
        mb_intercept_date = '',
        mb_email_certify = '" . sql_escape_string($now) . "',
        mb_memo_call = '',
        mb_1 = '', mb_2 = '', mb_3 = '', mb_4 = '', mb_5 = '',
        mb_6 = '', mb_7 = '', mb_8 = '', mb_9 = '', mb_10 = '' ";
    sql_query($sql);
    $created = true;
    $mb = get_member($admin_id);
} else {
    $sql = " UPDATE `{$member_table}` SET
        mb_password = '" . sql_escape_string($hash) . "',
        mb_level = 10,
        mb_intercept_date = '',
        mb_leave_date = '',
        mb_email_certify = IF(mb_email_certify = '' OR mb_email_certify = '0', '" . sql_escape_string($now) . "', mb_email_certify)
      WHERE mb_id = '" . sql_escape_string($admin_id) . "' ";
    sql_query($sql);
    $mb = get_member($admin_id);
}

// Point cf_admin to cpaadmin (superadmin identity)
sql_query(" UPDATE `{$config_table}` SET cf_admin = '" . sql_escape_string($admin_id) . "' ");

// Refresh config in-process
$config['cf_admin'] = $admin_id;

$mb2 = get_member($admin_id);
$check = function_exists('login_password_check')
    ? login_password_check($mb2, $plain, $mb2['mb_password'])
    : false;

$cfg = sql_fetch(" SELECT cf_admin FROM `{$config_table}` LIMIT 1 ");

$out['ok'] = (bool) $check && ((string) ($cfg['cf_admin'] ?? '') === $admin_id);
$out['created'] = $created;
$out['cfAdmin'] = (string) ($cfg['cf_admin'] ?? '');
$out['mbId'] = (string) ($mb2['mb_id'] ?? '');
$out['mbLevel'] = (int) ($mb2['mb_level'] ?? 0);
$out['passwordCheck'] = (bool) $check;
$out['pwPrefix'] = substr((string) ($mb2['mb_password'] ?? ''), 0, 4);
$out['message'] = $out['ok'] ? 'cpaadmin superadmin ready' : 'setup incomplete';

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
