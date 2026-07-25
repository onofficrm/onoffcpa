<?php
/**
 * 개인회생 광고주 전 구간 라이프사이클 검증 (유입→목록→승인/취소→정산).
 * 로컬 MariaDB 시뮬. 실제 플러그인 함수 사용.
 *
 *   MP_DB_HOST=localhost MP_DB_USER=$USER MP_DB_NAME=mp_lifecycle_test \
 *   php scripts/verify-multi-platform-lifecycle.php
 */
error_reporting(E_ALL & ~E_DEPRECATED);

$H = getenv('MP_DB_HOST') ?: 'localhost';
$U = getenv('MP_DB_USER') ?: get_current_user();
$P = getenv('MP_DB_PASS'); if ($P === false) $P = '';
$N = getenv('MP_DB_NAME') ?: 'mp_lifecycle_test';

mysqli_report(MYSQLI_REPORT_OFF);
$boot = @mysqli_connect($H, $U, $P);
if (!$boot) { fwrite(STDERR, "DB connect failed\n"); exit(2); }
mysqli_query($boot, "DROP DATABASE IF EXISTS `{$N}`");
mysqli_query($boot, "CREATE DATABASE `{$N}` DEFAULT CHARSET utf8mb4");
mysqli_select_db($boot, $N);
$GLOBALS['__lc_link'] = $boot;

define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
define('G5_DISPLAY_SQL_ERROR', false);
define('LC_MULTI_PLATFORM_ENABLED', true);
define('LC_PLATFORM_CODE', 'ONOFFCPA');
define('LC_PLATFORM_LINKCONNECT', 'LINKCONNECT');
define('LC_PLATFORM_ONOFFCPA', 'ONOFFCPA');
define('LC_STATUS_PENDING', 'pending');
define('LC_STATUS_APPROVED', 'approved');
define('LC_STATUS_REJECTED', 'rejected');
define('LC_STATUS_SETTLED', 'settled');
define('LC_STATUS_ACTIVE', 'active');

function sql_query($sql, $error = false, $link = null) { $l = $link ?: $GLOBALS['__lc_link']; return mysqli_query($l, $sql); }
function sql_fetch($sql, $error = false, $link = null) { $r = sql_query($sql, $error, $link); if (!$r || $r === true) return array(); $row = mysqli_fetch_assoc($r); return is_array($row) ? $row : array(); }
function sql_fetch_array($result) { if (!$result || $result === true) return null; return mysqli_fetch_assoc($result); }
function sql_insert_id($link = null) { return mysqli_insert_id($link ?: $GLOBALS['__lc_link']); }
function sql_real_escape_string($s, $link = null) { return mysqli_real_escape_string($link ?: $GLOBALS['__lc_link'], (string) $s); }
function get_sql_affected_rows($link = null) { return mysqli_affected_rows($link ?: $GLOBALS['__lc_link']); }

function lc_sql_link() { return $GLOBALS['__lc_link']; }
function lc_table_prefix() { return G5_TABLE_PREFIX; }
function lc_table($name) { return lc_table_prefix() . 'lc_' . ltrim((string) $name, '_'); }
function lc_sql_query($sql, $error = false) { return sql_query($sql, $error, lc_sql_link()); }
function lc_sql_fetch($sql, $error = false) { return sql_fetch($sql, $error, lc_sql_link()); }
function lc_sql_insert_id() { return sql_insert_id(lc_sql_link()); }
function lc_sql_escape($v) { return sql_real_escape_string((string) $v, lc_sql_link()); }
function lc_sql_affected_rows() { return (int) get_sql_affected_rows(lc_sql_link()); }
function lc_db_installed() { return true; }
function lc_db_table_exists($t) { $t = lc_sql_escape($t); $row = lc_sql_fetch(" SHOW TABLES LIKE '{$t}' "); return is_array($row) && count($row) > 0; }
function lc_conversion_generate_code() { static $n=0; $n++; return 'CV-L-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT); }
function lc_conversion_belongs_to_merchant(array $c, $mt_id) {
    $cp = lc_sql_fetch(" SELECT mt_id FROM `" . lc_table('campaigns') . "` WHERE cp_id='" . (int)$c['cp_id'] . "' ");
    return is_array($cp) && (int)$cp['mt_id'] === (int)$mt_id;
}
function lc_wallet_deduct_for_conversion($mt, $cv, $price, $memo) {
    $w = lc_table('wallets');
    $row = lc_sql_fetch(" SELECT balance FROM `{$w}` WHERE mt_id='" . (int)$mt . "' ");
    $bal = is_array($row) ? (int)$row['balance'] : 0;
    if ($bal < (int)$price) return array('ok'=>false,'message'=>'잔액 부족');
    lc_sql_query(" UPDATE `{$w}` SET balance = balance - '" . (int)$price . "' WHERE mt_id='" . (int)$mt . "' ");
    $GLOBALS['__wallet_deducts'][] = array('mt'=>(int)$mt,'cv'=>(int)$cv,'price'=>(int)$price,'memo'=>$memo);
    return array('ok'=>true);
}
function lc_partner_credit_for_conversion($c) {
    $pt = (int)($c['pt_id'] ?? 0);
    if ($pt <= 0) return;
    $amt = (int)($c['cv_partner_price'] ?? 0);
    lc_sql_query(" UPDATE `" . lc_table('partners') . "` SET pt_balance = pt_balance + '{$amt}' WHERE pt_id='{$pt}' ");
    $GLOBALS['__partner_credits'][] = array('pt'=>$pt,'amt'=>$amt,'cv'=>(int)$c['cv_id']);
}
function lc_conversion_with_meta($cv) { return lc_conversion_get_by_id($cv); }
function lc_conversion_apply_quality_feedback($cv, $opts) {}
function lc_notification_emit_conversion($c, $ev) { $GLOBALS['__notifies'][] = $ev; }
function lc_event_on_conversion_approved($c) {}
function lc_abuse_check_cancel_spike($pt, $x) {}
function lc_abuse_refresh_partner_score($pt) {}
function lc_notification_create($a) {}
function lc_campaign_resolve_merchant_price($cp) { return (int)($cp['cp_price'] ?? 0); }
function lc_campaign_resolve_partner_price($cp) { return (int)($cp['cp_partner_price'] ?? 30000); }
function lc_get_partner_by_id($id) { return lc_sql_fetch(" SELECT * FROM `" . lc_table('partners') . "` WHERE pt_id='" . (int)$id . "' "); }

$GLOBALS['__wallet_deducts'] = array();
$GLOBALS['__partner_credits'] = array();
$GLOBALS['__notifies'] = array();

$root = dirname(__DIR__) . '/plugin/linkconnect';
require_once $root . '/inc/platform.php';
require_once $root . '/inc/platform_db.php';
require_once $root . '/inc/platform_policy.php';
require_once $root . '/inc/platform_adapter.php';
require_once $root . '/inc/platform_sync.php';
require_once $root . '/inc/conversion.php';

/* schema */
foreach (array(
    "CREATE TABLE `" . lc_table('campaigns') . "` (
      cp_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mt_id INT UNSIGNED NOT NULL DEFAULT 0,
      cp_code VARCHAR(40) NOT NULL DEFAULT '', cp_name VARCHAR(200) NOT NULL DEFAULT '',
      cp_type VARCHAR(20) NOT NULL DEFAULT 'cpa', cp_status VARCHAR(20) NOT NULL DEFAULT 'active',
      cp_price INT NOT NULL DEFAULT 0, cp_partner_price INT NOT NULL DEFAULT 30000,
      cp_landing_url VARCHAR(500) NOT NULL DEFAULT '', cp_tracking_base_url VARCHAR(500) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('conversions') . "` (
      cv_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, cv_code VARCHAR(40) NOT NULL DEFAULT '',
      pt_id INT UNSIGNED NOT NULL DEFAULT 0, cp_id INT UNSIGNED NOT NULL DEFAULT 0, lk_id INT UNSIGNED NOT NULL DEFAULT 0,
      cv_name VARCHAR(100) NOT NULL DEFAULT '', cv_phone VARCHAR(40) NOT NULL DEFAULT '',
      cv_email VARCHAR(120) NOT NULL DEFAULT '', cv_region VARCHAR(80) NOT NULL DEFAULT '', cv_inquiry TEXT,
      cv_status VARCHAR(20) NOT NULL DEFAULT 'pending', cv_price INT NOT NULL DEFAULT 0, cv_partner_price INT NOT NULL DEFAULT 0,
      cv_channel VARCHAR(80) NOT NULL DEFAULT '', cv_sub_id VARCHAR(120) NOT NULL DEFAULT '', cv_comment TEXT,
      cv_review_status VARCHAR(20) NOT NULL DEFAULT '', cv_reject_reason VARCHAR(120) NOT NULL DEFAULT '',
      cv_final_locked TINYINT NOT NULL DEFAULT 0, cv_created_at DATETIME NULL, cv_updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('partners') . "` (
      pt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, pt_code VARCHAR(40) NOT NULL DEFAULT '',
      pt_name VARCHAR(100) NOT NULL DEFAULT '', pt_balance INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('wallets') . "` (
      mt_id INT UNSIGNED PRIMARY KEY, balance INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('links') . "` (
      lk_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lk_code VARCHAR(40) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('clicks') . "` (
      cl_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, lk_id INT UNSIGNED NOT NULL DEFAULT 0, cl_referer VARCHAR(500) NOT NULL DEFAULT '', cl_created_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE `" . lc_table('merchants') . "` (
      mt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mt_company VARCHAR(200) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
) as $sql) { lc_sql_query($sql, true); }

lc_mp_db_ensure_schema();
$plat = lc_mp_db_table('platforms');
lc_sql_query("UPDATE `{$plat}` SET api_base_url='http://127.0.0.1:8792', outbound_token='TOK', webhook_secret='WH'
  WHERE platform_code='LINKCONNECT'");

$RESULTS = array();
function check($name, $cond, $detail = '') {
    global $RESULTS;
    $RESULTS[] = array('name'=>$name,'ok'=>(bool)$cond,'detail'=>$detail);
    printf("[%s] %s%s\n", $cond?'PASS':'FAIL', $name, $detail!==''?"  ($detail)":'');
}

$onoff = lc_mp_get_platform_by_code('ONOFFCPA');
$lc = lc_mp_get_platform_by_code('LINKCONNECT');

/* 두 광고주 각각 */
$advertisers = array(
    array('group'=>'AG-BANKTUPT','name'=>'banktupt','mt'=>2,'code'=>'CPA-BANKTUPT','price'=>65000),
    array('group'=>'AG-DASIBOM','name'=>'dasibom','mt'=>6,'code'=>'CPA-00011','price'=>65000),
);

foreach ($advertisers as $adv) {
    echo "\n======== {$adv['name']} ({$adv['code']}) ========\n";
    $MT = $adv['mt'];
    lc_sql_query("INSERT INTO `" . lc_table('wallets') . "` (mt_id, balance) VALUES ('{$MT}','500000')
      ON DUPLICATE KEY UPDATE balance=500000");
    lc_sql_query("INSERT INTO `" . lc_table('merchants') . "` (mt_id, mt_company) VALUES ('{$MT}','{$adv['name']}')
      ON DUPLICATE KEY UPDATE mt_company='{$adv['name']}'");
    lc_sql_query("INSERT INTO `" . lc_table('campaigns') . "` (mt_id, cp_code, cp_name, cp_status, cp_price, cp_partner_price)
      VALUES ('{$MT}','" . lc_sql_escape($adv['code']) . "','{$adv['name']}','active','{$adv['price']}','30000')");
    $CP = (int) lc_sql_insert_id();

    $grp = lc_mp_create_group($adv['name'], '', $adv['group']);
    $gid = (int)$grp['group_id'];
    lc_mp_attach_membership($gid, 'ONOFFCPA', $MT, 'local:'.$MT, $adv['code']);
    lc_mp_attach_membership($gid, 'LINKCONNECT', 0, 'campaign:'.$adv['code'], $adv['code']);
    lc_mp_upsert_policy($gid, (int)$onoff['platform_id'], '온오프CPA 관리');
    check("{$adv['name']} management=local", lc_mp_local_is_management_for_mt($MT) === true);

    /* 1) 유입 */
    $ext = 'LC-' . strtoupper($adv['name']) . '-001';
    $payload = array(
        'externalLeadId' => $ext,
        'externalCampaignId' => $adv['code'],
        'localCampaignCode' => $adv['code'],
        'groupCode' => $adv['group'],
        'status' => 'pending',
        'name' => $adv['name'].'고객',
        'phone' => '010-1111-2222',
    );
    $lead = lc_mp_upsert_lead_ref_from_inbound($lc, $payload);
    $ref = sql_fetch(" SELECT * FROM `" . lc_mp_db_table('lead_refs') . "` WHERE lead_ref_id='" . (int)$lead['lead_ref_id'] . "' ");
    $conv = lc_mp_ensure_local_conversion_for_lead($ref, $payload);
    check("{$adv['name']} inflow→local CV", !empty($conv['ok']) && !empty($conv['cvId']), json_encode($conv));
    $CVID = (int)$conv['cvId'];
    $row = lc_conversion_get_by_id($CVID);
    check("{$adv['name']} channel external:linkconnect", ($row['cv_channel'] ?? '') === 'external:linkconnect');
    check("{$adv['name']} pt_id=0 (mirror)", (int)$row['pt_id'] === 0);
    check("{$adv['name']} pending", $row['cv_status'] === 'pending');

    /* 2) 목록 */
    $list = lc_conversion_list_for_merchant($MT);
    $found = false;
    foreach ($list as $it) { if ((int)$it['cv_id'] === $CVID) { $found = true; break; } }
    check("{$adv['name']} appears in merchant DB list", $found, 'list_n='.count($list));

    /* 3a) 승인 */
    $bal_before = (int) lc_sql_fetch(" SELECT balance FROM `" . lc_table('wallets') . "` WHERE mt_id='{$MT}' ")['balance'];
    $upd = lc_conversion_update_status($CVID, $MT, LC_STATUS_APPROVED, '');
    check("{$adv['name']} approve ok", !empty($upd['ok']), json_encode($upd));
    $bal_after = (int) lc_sql_fetch(" SELECT balance FROM `" . lc_table('wallets') . "` WHERE mt_id='{$MT}' ")['balance'];
    check("{$adv['name']} wallet deducted {$adv['price']}", $bal_before - $bal_after === (int)$adv['price'], "{$bal_before}->{$bal_after}");
    $ob = sql_fetch(" SELECT * FROM `" . lc_mp_db_table('sync_outbox') . "` WHERE lead_ref_id='" . (int)$lead['lead_ref_id'] . "' ORDER BY outbox_id DESC LIMIT 1 ");
    check("{$adv['name']} outbox approved", is_array($ob) && strpos($ob['payload_json'],'approved') !== false);

    /* 3b) 원본(링크커넥트) 쪽 원격 ACK: 지갑 미차감 + 파트너 적립 */
    // 원본 conversion 시뮬레이션 (별도 mt/pt)
    $MT_SRC = 9000 + $MT;
    lc_sql_query("INSERT INTO `" . lc_table('wallets') . "` (mt_id, balance) VALUES ('{$MT_SRC}','999999')");
    lc_sql_query("INSERT INTO `" . lc_table('partners') . "` (pt_code, pt_name, pt_balance) VALUES ('PT-{$adv['name']}','파트너{$adv['name']}','0')");
    $PT = (int) lc_sql_insert_id();
    lc_sql_query("INSERT INTO `" . lc_table('campaigns') . "` (mt_id, cp_code, cp_name, cp_status, cp_price, cp_partner_price)
      VALUES ('{$MT_SRC}','SRC-{$adv['code']}','src','active','{$adv['price']}','30000')");
    $CP_SRC = (int) lc_sql_insert_id();
    lc_sql_query("INSERT INTO `" . lc_table('conversions') . "`
      (cv_code, pt_id, cp_id, cv_name, cv_phone, cv_status, cv_price, cv_partner_price, cv_created_at, cv_updated_at)
      VALUES ('{$ext}','{$PT}','{$CP_SRC}','원본','010','pending','{$adv['price']}','30000',NOW(),NOW())");
    $CV_SRC = (int) lc_sql_insert_id();
    // 원본 광고주는 관리가 아님
    $grp2 = lc_mp_create_group($adv['name'].'-src');
    lc_mp_attach_membership((int)$grp2['group_id'], 'ONOFFCPA', $MT_SRC, 'x', 'x');
    lc_mp_attach_membership((int)$grp2['group_id'], 'LINKCONNECT', 0, 'y', 'y');
    lc_mp_upsert_policy((int)$grp2['group_id'], (int)$lc['platform_id'], '원본측: 원격관리');
    // 정책이 LINKCONNECT 관리 → 로컬(ONOFFCPA 시뮬) is_management false — 하지만 apply_remote_status는 게이트 우회
    $src_bal_before = (int) lc_sql_fetch(" SELECT balance FROM `" . lc_table('wallets') . "` WHERE mt_id='{$MT_SRC}' ")['balance'];
    $pt_before = (int) lc_sql_fetch(" SELECT pt_balance FROM `" . lc_table('partners') . "` WHERE pt_id='{$PT}' ")['pt_balance'];
    $apply = lc_mp_apply_remote_status($ext, 'approved', '');
    check("{$adv['name']} remote ACK apply", !empty($apply['ok']) && !empty($apply['applied']), json_encode($apply));
    $src_bal_after = (int) lc_sql_fetch(" SELECT balance FROM `" . lc_table('wallets') . "` WHERE mt_id='{$MT_SRC}' ")['balance'];
    $pt_after = (int) lc_sql_fetch(" SELECT pt_balance FROM `" . lc_table('partners') . "` WHERE pt_id='{$PT}' ")['pt_balance'];
    check("{$adv['name']} source wallet NOT double-charged", $src_bal_before === $src_bal_after, "{$src_bal_before}->{$src_bal_after}");
    check("{$adv['name']} partner credited 30000 (settlement pool)", $pt_after - $pt_before === 30000, "{$pt_before}->{$pt_after}");

    /* 4) 반려 경로 (두번째 DB) */
    $ext2 = 'LC-' . strtoupper($adv['name']) . '-002';
    $payload2 = array('externalLeadId'=>$ext2,'localCampaignCode'=>$adv['code'],'groupCode'=>$adv['group'],'status'=>'pending','name'=>'반려고객','phone'=>'010-3333-4444');
    $lead2 = lc_mp_upsert_lead_ref_from_inbound($lc, $payload2);
    $ref2 = sql_fetch(" SELECT * FROM `" . lc_mp_db_table('lead_refs') . "` WHERE lead_ref_id='" . (int)$lead2['lead_ref_id'] . "' ");
    $conv2 = lc_mp_ensure_local_conversion_for_lead($ref2, $payload2);
    $CVID2 = (int)$conv2['cvId'];
    $rej = lc_conversion_update_status($CVID2, $MT, LC_STATUS_REJECTED, '연락불가');
    check("{$adv['name']} reject ok", !empty($rej['ok']));
    $row2 = lc_conversion_get_by_id($CVID2);
    check("{$adv['name']} reject status+reason", $row2['cv_status']==='rejected' && $row2['cv_reject_reason']==='연락불가');
    $insp = lc_conversion_list_for_inspection();
    $insp_found = false;
    foreach ($insp as $it) { if ((int)$it['cv_id'] === $CVID2) { $insp_found = true; break; } }
    check("{$adv['name']} rejected mirror visible in inspection (LEFT JOIN)", $insp_found);
}

$failed = array_filter($RESULTS, function($r){ return !$r['ok']; });
echo "\n========================================\n";
printf("TOTAL %d / PASS %d / FAIL %d\n", count($RESULTS), count($RESULTS)-count($failed), count($failed));
mysqli_query($boot, "DROP DATABASE IF EXISTS `{$N}`");
exit(count($failed)===0 ? 0 : 1);
