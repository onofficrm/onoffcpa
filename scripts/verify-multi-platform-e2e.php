<?php
/**
 * 다중 플랫폼 승인/반려 역전송 end-to-end 실측 (로컬 MariaDB).
 *
 * - 실제 lc_conversion_update_status / platform_sync / adapter 코드를 그대로 로드.
 * - g5 sql_* 레이어는 mysqli 로 얇게 구현.
 * - 외부 의존(지갑/알림/어뷰즈)은 스텁으로 대체하여 상태 변경 흐름만 검증.
 *
 * 사용법:
 *   MP_DB_HOST=127.0.0.1 MP_DB_USER=root MP_DB_PASS= MP_DB_NAME=mp_e2e_test \
 *   php scripts/verify-multi-platform-e2e.php
 */

error_reporting(E_ALL & ~E_DEPRECATED);

$H = getenv('MP_DB_HOST') ?: '127.0.0.1';
$U = getenv('MP_DB_USER') ?: 'root';
$P = getenv('MP_DB_PASS');
if ($P === false) { $P = ''; }
$N = getenv('MP_DB_NAME') ?: 'mp_e2e_test';

mysqli_report(MYSQLI_REPORT_OFF);
$boot = @mysqli_connect($H, $U, $P);
if (!$boot) {
    fwrite(STDERR, "DB connect failed: " . mysqli_connect_error() . "\n");
    exit(2);
}
mysqli_query($boot, "DROP DATABASE IF EXISTS `{$N}`");
mysqli_query($boot, "CREATE DATABASE `{$N}` DEFAULT CHARSET utf8mb4");
mysqli_select_db($boot, $N);
$GLOBALS['__lc_link'] = $boot;

/* ── 상수 ── */
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', 'g5_');
define('G5_DISPLAY_SQL_ERROR', false);
define('LC_MULTI_PLATFORM_ENABLED', true);
define('LC_PLATFORM_CODE', 'ONOFFCPA');
define('LC_PLATFORM_LINKCONNECT', 'LINKCONNECT');
define('LC_STATUS_PENDING', 'pending');
define('LC_STATUS_APPROVED', 'approved');
define('LC_STATUS_REJECTED', 'rejected');
define('LC_STATUS_SETTLED', 'settled');
define('LC_STATUS_ACTIVE', 'active');
define('LC_STATUS_DRAFT', 'draft');

/* ── g5 sql 레이어 (mysqli) ── */
function sql_query($sql, $error = false, $link = null) { $l = $link ?: $GLOBALS['__lc_link']; $r = mysqli_query($l, $sql); if ($r === false && $error) { fwrite(STDERR, "SQL ERR: " . mysqli_error($l) . "\n$sql\n"); } return $r; }
function sql_fetch($sql, $error = false, $link = null) { $r = sql_query($sql, $error, $link); if (!$r || $r === true) return array(); $row = mysqli_fetch_assoc($r); return is_array($row) ? $row : array(); }
function sql_fetch_array($result) { if (!$result || $result === true) return null; return mysqli_fetch_assoc($result); }
function sql_insert_id($link = null) { return mysqli_insert_id($link ?: $GLOBALS['__lc_link']); }
function sql_real_escape_string($s, $link = null) { return mysqli_real_escape_string($link ?: $GLOBALS['__lc_link'], (string) $s); }
function get_sql_affected_rows($link = null) { return mysqli_affected_rows($link ?: $GLOBALS['__lc_link']); }

/* ── lc DB 래퍼 (db.php 미로딩) ── */
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
function lc_db_column_exists($t, $c) { $t = lc_sql_escape($t); $c = lc_sql_escape($c); $row = lc_sql_fetch(" SHOW COLUMNS FROM `{$t}` LIKE '{$c}' "); return is_array($row) && count($row) > 0; }
function lc_conversion_generate_code() { static $n = 0; $n++; return 'CV-' . date('ymd') . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT); }

/* ── 외부 의존 스텁 (승인/반려 부수효과 무력화) ── */
function lc_wallet_deduct_for_conversion($mt, $cv, $price, $memo) { return array('ok' => true); }
function lc_partner_credit_for_conversion($c) {}
function lc_conversion_with_meta($cv) { return lc_conversion_get_by_id($cv); }
function lc_conversion_apply_quality_feedback($cv, $opts) {}
function lc_notification_emit_conversion($c, $ev) {}
function lc_event_on_conversion_approved($c) {}
function lc_abuse_check_cancel_spike($pt, $x) {}
function lc_abuse_refresh_partner_score($pt) {}
function lc_notification_create($a) {}
function lc_campaign_resolve_merchant_price($cp) { return (int) ($cp['cp_price'] ?? 0); }
function lc_campaign_resolve_partner_price($cp) { return 0; }

/* ── 플러그인 실제 코드 로드 ── */
$root = dirname(__DIR__) . '/plugin/linkconnect';
require_once $root . '/inc/platform.php';
require_once $root . '/inc/platform_db.php';
require_once $root . '/inc/platform_policy.php';
require_once $root . '/inc/platform_adapter.php';
require_once $root . '/inc/platform_sync.php';
require_once $root . '/inc/conversion.php';

/* ── 최소 lc 테이블 생성 ── */
$cp = lc_table('campaigns');
$cv = lc_table('conversions');
lc_sql_query("CREATE TABLE `{$cp}` (
  cp_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mt_id INT UNSIGNED NOT NULL DEFAULT 0,
  cp_code VARCHAR(40) NOT NULL DEFAULT '',
  cp_name VARCHAR(200) NOT NULL DEFAULT '',
  cp_type VARCHAR(20) NOT NULL DEFAULT 'cpa',
  cp_status VARCHAR(20) NOT NULL DEFAULT 'active',
  cp_price INT NOT NULL DEFAULT 0,
  cp_landing_url VARCHAR(500) NOT NULL DEFAULT '',
  cp_tracking_base_url VARCHAR(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);
lc_sql_query("CREATE TABLE `{$cv}` (
  cv_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cv_code VARCHAR(40) NOT NULL DEFAULT '',
  pt_id INT UNSIGNED NOT NULL DEFAULT 0,
  cp_id INT UNSIGNED NOT NULL DEFAULT 0,
  lk_id INT UNSIGNED NOT NULL DEFAULT 0,
  cv_name VARCHAR(100) NOT NULL DEFAULT '',
  cv_phone VARCHAR(40) NOT NULL DEFAULT '',
  cv_email VARCHAR(120) NOT NULL DEFAULT '',
  cv_region VARCHAR(80) NOT NULL DEFAULT '',
  cv_inquiry TEXT,
  cv_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  cv_price INT NOT NULL DEFAULT 0,
  cv_partner_price INT NOT NULL DEFAULT 0,
  cv_channel VARCHAR(80) NOT NULL DEFAULT '',
  cv_sub_id VARCHAR(120) NOT NULL DEFAULT '',
  cv_comment TEXT,
  cv_review_status VARCHAR(20) NOT NULL DEFAULT '',
  cv_reject_reason VARCHAR(120) NOT NULL DEFAULT '',
  cv_final_locked TINYINT NOT NULL DEFAULT 0,
  cv_created_at DATETIME NULL,
  cv_updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

/* ── mp_* 스키마 ── */
$schema = lc_mp_db_ensure_schema();

/* ── 검증 유틸 ── */
$RESULTS = array();
function check($name, $cond, $detail = '') {
    global $RESULTS;
    $RESULTS[] = array('name' => $name, 'ok' => (bool) $cond, 'detail' => $detail);
    printf("[%s] %s%s\n", $cond ? 'PASS' : 'FAIL', $name, $detail !== '' ? "  ($detail)" : '');
}

check('mp schema created', !empty($schema['ok']) && !empty($schema['created']), json_encode($schema));

/* ── 플랫폼 설정 (스키마 시더가 이미 두 플랫폼 행을 생성) ── */
$plat = lc_mp_db_table('platforms');
lc_sql_query("UPDATE `{$plat}` SET api_base_url='http://127.0.0.1:8791', outbound_token='OUT_TOKEN_LC', webhook_secret='WH_SECRET_LC'
  WHERE platform_code='LINKCONNECT'");

$onoff = lc_mp_get_platform_by_code('ONOFFCPA');
$lc = lc_mp_get_platform_by_code('LINKCONNECT');
check('platforms seeded', is_array($onoff) && is_array($lc));

/* 공동 입점 광고주 그룹 (관리 = 온오프CPA) */
$MT_LOCAL = 501; // 온오프CPA 로컬 광고주
$grp = lc_mp_create_group('희망법무법인(개인회생)', '123-45-67890');
$gid = (int) $grp['group_id'];
lc_mp_attach_membership($gid, 'ONOFFCPA', $MT_LOCAL, '', 'ONOFF-ADV-1');
lc_mp_attach_membership($gid, 'LINKCONNECT', 0, 'LC-MERCH-9', 'LC-ADV-9');
lc_mp_upsert_policy($gid, (int) $onoff['platform_id'], '공동 입점 — 온오프CPA 관리');
check('local is management for co-enrolled advertiser', lc_mp_local_is_management_for_mt($MT_LOCAL) === true);

/* 로컬 캠페인 (해당 광고주 소유) */
lc_sql_query("INSERT INTO `{$cp}` (mt_id, cp_code, cp_name, cp_type, cp_status, cp_price)
  VALUES ('{$MT_LOCAL}','CPA-BANKTUPT','개인회생 상담 DB','cpa','active','65000')");
$CP_LOCAL = (int) lc_sql_insert_id();

echo "\n=== 시나리오 A: 링크커넥트 → 온오프CPA 유입 후 승인 역전송 ===\n";

/* 1) 인바운드 lead (링크커넥트 원본 DB) */
$inbound_payload = array(
    'externalLeadId'   => 'LC-DB-1001',
    'status'           => 'pending',
    'name'             => '김개인',
    'phone'            => '010-1234-5678',
    'groupId'          => $gid,
    'localMtId'        => $MT_LOCAL,
    'localCampaignId'  => $CP_LOCAL,
);
$lead = lc_mp_upsert_lead_ref_from_inbound($lc, $inbound_payload);
check('inbound lead_ref created', !empty($lead['ok']) && !empty($lead['lead_ref_id']));

$leads_t = lc_mp_db_table('lead_refs');
$ref = sql_fetch(" SELECT * FROM `{$leads_t}` WHERE lead_ref_id = '" . (int) $lead['lead_ref_id'] . "' ");
$conv = lc_mp_ensure_local_conversion_for_lead($ref, $inbound_payload);
check('local conversion created & linked', !empty($conv['ok']) && !empty($conv['cvId']), json_encode($conv));
$CV_A = (int) $conv['cvId'];

$ref = sql_fetch(" SELECT * FROM `{$leads_t}` WHERE lead_ref_id = '" . (int) $lead['lead_ref_id'] . "' ");
check('lead_ref.local_cv_id set', (int) $ref['local_cv_id'] === $CV_A, "local_cv_id={$ref['local_cv_id']}");

$row = lc_conversion_get_by_id($CV_A);
check('local conversion is pending', $row['cv_status'] === LC_STATUS_PENDING);

/* 2) 광고주가 온오프CPA에서 승인 → 훅이 outbox 적재 */
$upd = lc_conversion_update_status($CV_A, $MT_LOCAL, LC_STATUS_APPROVED, '');
check('approve on OnOff CPA ok', !empty($upd['ok']), json_encode($upd));

$outbox_t = lc_mp_db_table('sync_outbox');
$ob = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE lead_ref_id = '" . (int) $lead['lead_ref_id'] . "' ORDER BY outbox_id DESC LIMIT 1 ");
check('outbox enqueued for LinkConnect', is_array($ob) && (int) $ob['target_platform_id'] === (int) $lc['platform_id'], is_array($ob) ? json_encode(json_decode($ob['payload_json'], true)) : 'none');
$ob_payload = is_array($ob) ? json_decode($ob['payload_json'], true) : array();
check('outbox payload targets external LC-DB-1001 / approved',
    ($ob_payload['external_lead_id'] ?? '') === 'LC-DB-1001' && ($ob_payload['status'] ?? '') === 'approved');

echo "\n=== 시나리오 B: outbox → 링크커넥트 HTTP 푸시 (mock 수신) ===\n";

/* mock 링크커넥트 수신 서버 기동 (모든 경로 {ok:true}) */
$stub_dir = sys_get_temp_dir() . '/mp_stub_' . getmypid();
@mkdir($stub_dir);
file_put_contents($stub_dir . '/router.php',
    "<?php header('Content-Type: application/json'); echo json_encode(['ok'=>true,'echo'=>json_decode(file_get_contents('php://input'),true)]);");
$srv = proc_open('php -S 127.0.0.1:8791 ' . escapeshellarg($stub_dir . '/router.php') . ' 2>/dev/null',
    array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
usleep(700000); // 서버 기동 대기

$proc = lc_mp_process_outbox_once(10);
check('outbox processed (pushed to LinkConnect)', !empty($proc['ok']) && (int) $proc['processed'] >= 1, json_encode($proc));
$ob2 = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE outbox_id = '" . (int) $ob['outbox_id'] . "' ");
check('outbox marked done', is_array($ob2) && $ob2['status'] === 'done', is_array($ob2) ? ('status=' . $ob2['status'] . ' err=' . $ob2['last_error']) : 'none');
$ref2 = sql_fetch(" SELECT * FROM `{$leads_t}` WHERE lead_ref_id = '" . (int) $lead['lead_ref_id'] . "' ");
check('lead_ref sync_status synced', is_array($ref2) && $ref2['sync_status'] === 'synced', 'sync=' . ($ref2['sync_status'] ?? '?'));

if (is_resource($srv)) { proc_terminate($srv); proc_close($srv); }

echo "\n=== 시나리오 C: 반려(취소) 역전송 ===\n";
$inbound2 = array('externalLeadId' => 'LC-DB-1002', 'status' => 'pending', 'name' => '박취소', 'phone' => '010-2222-3333', 'groupId' => $gid, 'localMtId' => $MT_LOCAL, 'localCampaignId' => $CP_LOCAL);
$lead2 = lc_mp_upsert_lead_ref_from_inbound($lc, $inbound2);
$ref_b = sql_fetch(" SELECT * FROM `{$leads_t}` WHERE lead_ref_id = '" . (int) $lead2['lead_ref_id'] . "' ");
$conv2 = lc_mp_ensure_local_conversion_for_lead($ref_b, $inbound2);
$CV_B = (int) $conv2['cvId'];
$upd2 = lc_conversion_update_status($CV_B, $MT_LOCAL, LC_STATUS_REJECTED, '연락불가');
check('reject on OnOff CPA ok', !empty($upd2['ok']));
$ob_b = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE lead_ref_id = '" . (int) $lead2['lead_ref_id'] . "' ORDER BY outbox_id DESC LIMIT 1 ");
$obp_b = is_array($ob_b) ? json_decode($ob_b['payload_json'], true) : array();
check('reject outbox enqueued (rejected)', ($obp_b['status'] ?? '') === 'rejected' && ($obp_b['external_lead_id'] ?? '') === 'LC-DB-1002');

echo "\n=== 시나리오 D: 수신측(링크커넥트) 원격 상태 반영 + 루프 방지 ===\n";
/* 링크커넥트 입장의 로컬 DB: 관리 플랫폼 = 링크커넥트가 아닌 온오프CPA 로 설정하여
   로컬(수신측)이 관리 플랫폼이 아님을 재현 → 게이트 우회 확인 */
$MT_OWNER = 777;
lc_sql_query("INSERT INTO `{$cp}` (mt_id, cp_code, cp_name, cp_type, cp_status, cp_price)
  VALUES ('{$MT_OWNER}','CPA-OWNER','수신측 캠페인','cpa','active','50000')");
$CP_OWNER = (int) lc_sql_insert_id();
lc_sql_query("INSERT INTO `{$cv}` (cv_code, cp_id, cv_name, cv_phone, cv_status, cv_price, cv_created_at, cv_updated_at)
  VALUES ('LC-DB-1001','{$CP_OWNER}','김개인','010-1234-5678','pending','50000',NOW(),NOW())");
$CV_OWNER = (int) lc_sql_insert_id();
/* 수신측 그룹: 관리 = 원격(LINKCONNECT행을 관리자로) → 로컬 관리 아님 */
$grp2 = lc_mp_create_group('수신측광고주');
$gid2 = (int) $grp2['group_id'];
lc_mp_attach_membership($gid2, 'ONOFFCPA', $MT_OWNER, '', 'OWN');
lc_mp_attach_membership($gid2, 'LINKCONNECT', 0, 'LC-OWN', 'LC-OWN');
lc_mp_upsert_policy($gid2, (int) $lc['platform_id'], '수신측: 원격이 관리');
check('receiver local is NOT management', lc_mp_local_is_management_for_mt($MT_OWNER) === false);

$outbox_before = (int) (sql_fetch(" SELECT COUNT(*) c FROM `{$outbox_t}` ")['c'] ?? 0);
$apply = lc_mp_apply_remote_status('LC-DB-1001', 'approved', '관리플랫폼 승인');
check('remote status applied (gate bypassed)', !empty($apply['ok']) && !empty($apply['applied']), json_encode($apply));
$owner_row = lc_conversion_get_by_id($CV_OWNER);
check('receiver conversion now approved', $owner_row['cv_status'] === LC_STATUS_APPROVED);
$outbox_after = (int) (sql_fetch(" SELECT COUNT(*) c FROM `{$outbox_t}` ")['c'] ?? 0);
check('no loopback outbox created', $outbox_after === $outbox_before, "before={$outbox_before} after={$outbox_after}");

/* 멱등 재수신 */
$apply2 = lc_mp_apply_remote_status('LC-DB-1001', 'approved', '재수신');
check('remote status idempotent (already processed)', !empty($apply2['ok']) && empty($apply2['applied']), json_encode($apply2));

echo "\n=== 시나리오 E: 로컬 전용(미등록) 광고주 무영향 ===\n";
$MT_PURE = 999;
lc_sql_query("INSERT INTO `{$cp}` (mt_id, cp_code, cp_name, cp_type, cp_status, cp_price)
  VALUES ('{$MT_PURE}','CPA-PURE','로컬전용','cpa','active','30000')");
$CP_PURE = (int) lc_sql_insert_id();
lc_sql_query("INSERT INTO `{$cv}` (cv_code, cp_id, cv_name, cv_phone, cv_status, cv_price, cv_created_at, cv_updated_at)
  VALUES ('PURE-1','{$CP_PURE}','로컬','010-0000-0000','pending','30000',NOW(),NOW())");
$CV_PURE = (int) lc_sql_insert_id();
$ob_before = (int) (sql_fetch(" SELECT COUNT(*) c FROM `{$outbox_t}` ")['c'] ?? 0);
check('pure-local mgmt passthrough true', lc_mp_local_is_management_for_mt($MT_PURE) === true);
$updp = lc_conversion_update_status($CV_PURE, $MT_PURE, LC_STATUS_APPROVED, '');
check('pure-local approve ok', !empty($updp['ok']));
$ob_after = (int) (sql_fetch(" SELECT COUNT(*) c FROM `{$outbox_t}` ")['c'] ?? 0);
check('pure-local creates NO outbox', $ob_after === $ob_before, "before={$ob_before} after={$ob_after}");

echo "\n=== 시나리오 F: outbox 워커 — inbound_lead 분기 / 재시도 / dead 상한 ===\n";
$LC_PID = (int) $lc['platform_id'];

/* F1. inbound_lead 커맨드가 status 어댑터가 아닌 inbound_lead 어댑터로 나가는지 (mock 수신 기동) */
/* exec 접두어: proc_terminate 가 sh 래퍼가 아닌 php 프로세스를 직접 종료하도록 */
$srv2 = proc_open('exec php -S 127.0.0.1:8791 ' . escapeshellarg($stub_dir . '/router.php') . ' 2>/dev/null',
    array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes2);
usleep(700000);

$lead_payload = array(
    'sourcePlatform' => 'ONOFFCPA',
    'eventType' => 'lead.upsert',
    'externalLeadId' => 'CV-MP-F1',
    'externalCampaignId' => 'CPA-OWNER',
    'status' => 'pending',
    'name' => '유입테스트',
    'phone' => '010-7777-8888',
    'idempotencyKey' => 'ONOFFCPA:CV-MP-F1:1',
);
$enq = lc_mp_outbox_enqueue_inbound_lead($LC_PID, $lead_payload);
check('inbound_lead outbox enqueued', !empty($enq['ok']) && (int) $enq['outbox_id'] > 0, json_encode($enq));

/* 즉시 처리되도록 백오프 해제 */
lc_sql_query("UPDATE `{$outbox_t}` SET next_attempt_at = NOW() WHERE outbox_id = '" . (int) $enq['outbox_id'] . "'");
$procF = lc_mp_process_outbox_once(10);
$obF = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE outbox_id = '" . (int) $enq['outbox_id'] . "' ");
check('inbound_lead pushed via inbound adapter (done)',
    is_array($obF) && $obF['status'] === 'done',
    'status=' . ($obF['status'] ?? '?') . ' err=' . ($obF['last_error'] ?? ''));
check('inbound_lead row command preserved', is_array($obF) && $obF['command'] === 'inbound_lead');

/* F2. 동일 idempotencyKey 재적재 차단 */
$enq_dup = lc_mp_outbox_enqueue_inbound_lead($LC_PID, $lead_payload);
check('inbound_lead enqueue idempotent',
    !empty($enq_dup['ok']) && $enq_dup['message'] === 'already queued'
        && (int) $enq_dup['outbox_id'] === (int) $enq['outbox_id'], json_encode($enq_dup));

if (is_resource($srv2)) { proc_terminate($srv2); proc_close($srv2); }

/* F3. 수신측 도달 불가 → 실패 시 backoff 예약 (무한 즉시 재시도 금지)
   mock 서버 종료에 의존하지 않고, 아무도 듣지 않는 포트로 대상 주소를 바꿔 확실히 실패시킨다. */
lc_sql_query("UPDATE `{$plat}` SET api_base_url='http://127.0.0.1:8799' WHERE platform_id = '{$LC_PID}'");
$lead_payload2 = $lead_payload;
$lead_payload2['externalLeadId'] = 'CV-MP-F3';
$lead_payload2['idempotencyKey'] = 'ONOFFCPA:CV-MP-F3:1';
$enq2 = lc_mp_outbox_enqueue_inbound_lead($LC_PID, $lead_payload2);
lc_sql_query("UPDATE `{$outbox_t}` SET next_attempt_at = NOW() WHERE outbox_id = '" . (int) $enq2['outbox_id'] . "'");
lc_mp_process_outbox_once(10);
$obF3 = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE outbox_id = '" . (int) $enq2['outbox_id'] . "' ");
check('push failure marks failed with backoff',
    is_array($obF3) && $obF3['status'] === 'failed' && (int) $obF3['attempts'] === 1 && !empty($obF3['next_attempt_at']),
    'status=' . ($obF3['status'] ?? '?') . ' attempts=' . ($obF3['attempts'] ?? '?') . ' next=' . ($obF3['next_attempt_at'] ?? 'NULL'));

/* F4. 재시도 상한 도달 → dead 로 내려가고 이후 재선택 대상에서 제외 */
lc_sql_query("UPDATE `{$outbox_t}` SET attempts = '" . (LC_MP_OUTBOX_MAX_ATTEMPTS - 1) . "', next_attempt_at = NOW()
    WHERE outbox_id = '" . (int) $enq2['outbox_id'] . "'");
$procF4 = lc_mp_process_outbox_once(10);
$obF4 = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE outbox_id = '" . (int) $enq2['outbox_id'] . "' ");
check('retry cap reached → dead', is_array($obF4) && $obF4['status'] === 'dead',
    'status=' . ($obF4['status'] ?? '?') . ' attempts=' . ($obF4['attempts'] ?? '?'));
check('worker reports dead count', (int) ($procF4['dead'] ?? 0) >= 1, json_encode($procF4));

$procF5 = lc_mp_process_outbox_once(10);
$obF5 = sql_fetch(" SELECT attempts FROM `{$outbox_t}` WHERE outbox_id = '" . (int) $enq2['outbox_id'] . "' ");
check('dead row not retried again',
    (int) ($obF5['attempts'] ?? 0) === (int) ($obF4['attempts'] ?? -1),
    'attempts stayed ' . ($obF5['attempts'] ?? '?'));

lc_sql_query("UPDATE `{$plat}` SET api_base_url='http://127.0.0.1:8791' WHERE platform_id = '{$LC_PID}'");

/* F5. target 플랫폼이 사라진 행도 backoff 를 받아 즉시 루프하지 않음 */
lc_sql_query("INSERT INTO `{$outbox_t}`
    (`target_platform_id`, `lead_ref_id`, `command`, `idempotency_key`, `payload_json`, `status`, `next_attempt_at`)
    VALUES ('99999','0','status_change','orphan:1','{}','pending', NOW())");
$orphan_id = (int) lc_sql_insert_id();
lc_mp_process_outbox_once(10);
$obF6 = sql_fetch(" SELECT * FROM `{$outbox_t}` WHERE outbox_id = '{$orphan_id}' ");
check('missing platform row gets backoff (no tight loop)',
    is_array($obF6) && !empty($obF6['next_attempt_at']) && (int) $obF6['attempts'] === 1,
    'attempts=' . ($obF6['attempts'] ?? '?') . ' next=' . ($obF6['next_attempt_at'] ?? 'NULL'));

/* ── 요약 ── */
$failed = array_filter($RESULTS, function ($r) { return !$r['ok']; });
echo "\n========================================\n";
printf("TOTAL %d / PASS %d / FAIL %d\n", count($RESULTS), count($RESULTS) - count($failed), count($failed));
mysqli_query($boot, "DROP DATABASE IF EXISTS `{$N}`");
exit(count($failed) === 0 ? 0 : 1);
