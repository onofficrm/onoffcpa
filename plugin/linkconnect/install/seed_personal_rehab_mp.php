<?php
/**
 * 개인회생 광고주 2곳(각각 별도) 다중 플랫폼 시딩.
 *
 * - CPA-BANKTUPT (banktupt 랜딩)
 * - CPA-00011   (dasibom 랜딩)
 * 각 광고주를 독립 advertiser_group 으로 등록하고,
 * hub(management)=ONOFFCPA, 멤버십=양쪽. 승인/취소는 멤버 모두.
 * 과금: 온오프CPA 승인=온오프CPA만, 링크커넥트 승인=양쪽. 잔액 온오프CPA≥링크커넥트.
 *
 * 브라우저: /plugin/linkconnect/install/seed_personal_rehab_mp.php?action=run
 * 서버 1회 스크립트도 동일 파일 사용.
 */
require_once dirname(__DIR__) . '/_common.php';

if (!defined('LC_MP_SEED_AS_LIB')) {
    define('LC_MP_SEED_AS_LIB', false);
}

$is_cli = (php_sapi_name() === 'cli');
$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';
$running_as_script = (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__))
    || ($is_cli && isset($_SERVER['argv'][0]) && realpath($_SERVER['argv'][0]) === realpath(__FILE__));

if (!$running_as_script && !LC_MP_SEED_AS_LIB) {
    // included as library — function defs only (below). Skip HTTP/CLI runner.
}

/** 양쪽 플랫폼이 공유하는 시크릿 (시딩 시 DB에만 저장, 코드에 평문 커밋 최소화) */
if (!function_exists('lc_mp_seed_shared_secrets')) {
    function lc_mp_seed_shared_secrets()
    {
        // 운영 시딩용 고정값 — 양쪽(온오프CPA·링크커넥트) 동일해야 함
        return array(
            'outbound' => 'mp_out_onoff_lc_2026_rehab_k9f3Qw8Zp2',
            'webhook'  => 'mp_wh_onoff_lc_2026_rehab_m4Hn7Vx1Rt',
        );
    }
}

if (!function_exists('lc_mp_seed_configure_peer_platform')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_mp_seed_configure_peer_platform()
    {
        $local = lc_mp_local_platform_code();
        $secrets = lc_mp_seed_shared_secrets();
        $table = lc_mp_db_table('platforms');
        if (!lc_db_table_exists($table)) {
            return array('ok' => false, 'message' => 'platforms missing — enable flag & ensure schema first');
        }

        // 스키마 시더가 만든 행을 갱신
        if ($local === 'ONOFFCPA') {
            $peer = 'LINKCONNECT';
            $base = 'https://linkconnect.co.kr';
        } else {
            $peer = 'ONOFFCPA';
            $base = function_exists('lc_mp_resolve_onoffcpa_base_url')
                ? lc_mp_resolve_onoffcpa_base_url()
                : (defined('LC_ONOFFCPA_PUBLIC_URL') ? LC_ONOFFCPA_PUBLIC_URL : 'https://onoffcpa.icrm.co.kr');
        }

        $peer_esc = lc_sql_escape($peer);
        $base_esc = lc_sql_escape($base);
        $out_esc = lc_sql_escape($secrets['outbound']);
        $wh_esc = lc_sql_escape($secrets['webhook']);
        lc_sql_query(" UPDATE `{$table}` SET
            api_base_url = '{$base_esc}',
            outbound_token = '{$out_esc}',
            webhook_secret = '{$wh_esc}',
            status = 'active',
            updated_at = NOW()
            WHERE platform_code = '{$peer_esc}' ", false);

        // 로컬 플랫폼도 webhook/outbound 을 동일하게 맞춰 수신측 검증에 사용
        $local_esc = lc_sql_escape($local);
        lc_sql_query(" UPDATE `{$table}` SET
            outbound_token = '{$out_esc}',
            webhook_secret = '{$wh_esc}',
            status = 'active',
            updated_at = NOW()
            WHERE platform_code = '{$local_esc}' ", false);

        return array('ok' => true, 'message' => "peer {$peer} configured → {$base}");
    }
}

if (!function_exists('lc_mp_seed_one_rehab_advertiser')) {
    /**
     * @return array{ok:bool,message:string,groupId?:int,mtId?:int,cpId?:int,code?:string}
     */
    function lc_mp_seed_one_rehab_advertiser($group_code, $display_name, $cp_code, $landing_like = '')
    {
        $cp_table = lc_table('campaigns');
        $code_esc = lc_sql_escape($cp_code);
        $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
        if (!$campaign && $landing_like !== '') {
            $like = lc_sql_escape($landing_like);
            $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}`
                WHERE cp_landing_url LIKE '%{$like}%' ORDER BY cp_id ASC LIMIT 1 ");
        }
        if (!$campaign) {
            return array('ok' => false, 'message' => "campaign not found: {$cp_code}");
        }

        $mt_id = (int) ($campaign['mt_id'] ?? 0);
        $cp_id = (int) ($campaign['cp_id'] ?? 0);
        if ($mt_id <= 0) {
            return array('ok' => false, 'message' => "campaign {$cp_code} has no mt_id", 'cpId' => $cp_id);
        }

        // 그룹 upsert by code
        $grp_table = lc_mp_db_table('advertiser_groups');
        $gcode_esc = lc_sql_escape($group_code);
        $existing = sql_fetch(" SELECT * FROM `{$grp_table}` WHERE group_code = '{$gcode_esc}' LIMIT 1 ");
        if (is_array($existing) && !empty($existing['group_id'])) {
            $group_id = (int) $existing['group_id'];
            lc_sql_query(" UPDATE `{$grp_table}` SET
                display_name = '" . lc_sql_escape($display_name) . "',
                status = 'active', updated_at = NOW()
                WHERE group_id = '{$group_id}' ", false);
        } else {
            $created = lc_mp_create_group($display_name, '', $group_code);
            if (empty($created['ok'])) {
                return array('ok' => false, 'message' => $created['message']);
            }
            $group_id = (int) $created['group_id'];
        }

        $local_code = lc_mp_local_platform_code();
        // 로컬 멤버십
        $m1 = lc_mp_attach_membership($group_id, $local_code, $mt_id, 'local:' . $mt_id, $cp_code);
        // 원격 멤버십 — external key = 캠페인 코드 (양쪽 공통 키)
        $peer = ($local_code === 'ONOFFCPA') ? 'LINKCONNECT' : 'ONOFFCPA';
        $m2 = lc_mp_attach_membership($group_id, $peer, 0, 'campaign:' . $cp_code, $cp_code);

        // 관리 플랫폼 = 온오프CPA (어느 쪽에서 시딩해도 동일)
        $onoff = lc_mp_get_platform_by_code('ONOFFCPA');
        if (!$onoff) {
            return array('ok' => false, 'message' => 'ONOFFCPA platform missing');
        }
        $pol = lc_mp_upsert_policy(
            $group_id,
            (int) $onoff['platform_id'],
            '개인회생 공동입점 — 멤버 플랫폼(링크커넥트·온오프CPA) 모두 승인/취소 가능, 과금은 승인자만·상대 ACK'
        );

        return array(
            'ok'       => !empty($m1['ok']) && !empty($m2['ok']) && !empty($pol['ok']),
            'message'  => "seeded {$cp_code} mt={$mt_id}",
            'groupId'  => $group_id,
            'groupCode'=> $group_code,
            'mtId'     => $mt_id,
            'cpId'     => $cp_id,
            'code'     => $cp_code,
            'memberships' => array($m1, $m2),
            'policy'   => $pol,
            'isManagementLocal' => lc_mp_local_is_management_for_mt($mt_id),
        );
    }
}

if (!function_exists('lc_mp_seed_personal_rehab_pair')) {
    /**
     * @return array{ok:bool,message:string,items?:array,peer?:array,schema?:array}
     */
    function lc_mp_seed_personal_rehab_pair()
    {
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'LC_MULTI_PLATFORM_ENABLED is false');
        }
        $schema = lc_mp_db_ensure_schema();
        if (empty($schema['ok'])) {
            return array('ok' => false, 'message' => 'schema failed', 'schema' => $schema);
        }
        $peer = lc_mp_seed_configure_peer_platform();
        $items = array(
            lc_mp_seed_one_rehab_advertiser('AG-BANKTUPT', '개인회생 상담 DB (banktupt)', 'CPA-BANKTUPT', '/merchant/banktupt/'),
            lc_mp_seed_one_rehab_advertiser('AG-DASIBOM', '개인회생 개인파산 (dasibom)', 'CPA-00011', '/merchant/dasibom/'),
        );
        $ok = !empty($peer['ok']);
        foreach ($items as $it) {
            if (empty($it['ok'])) {
                $ok = false;
            }
        }

        return array(
            'ok'       => $ok,
            'message'  => $ok ? '개인회생 광고주 2곳 시딩 완료' : '일부 실패',
            'local'    => lc_mp_local_platform_code(),
            'peer'     => $peer,
            'schema'   => $schema,
            'items'    => $items,
        );
    }
}

if ($running_as_script) {
if (!$is_cli && $action === 'run' && !lc_is_super_admin()) {
    // 토큰 허용
    $token_ok = false;
    if (function_exists('g5site_cfg')) {
        $expected = g5site_cfg('linkconnect_seed_token', '');
        if ($expected === '') {
            $expected = g5site_cfg('linkconnect_install_token', '');
        }
        $given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
        $token_ok = ($expected !== '' && $given !== '' && hash_equals($expected, $given));
    }
    if (!$token_ok) {
        alert('최고관리자만 실행할 수 있습니다.', G5_URL);
    }
}

if ($action === 'run' || $is_cli) {
    $result = lc_mp_seed_personal_rehab_pair();
    if ($is_cli) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit(!empty($result['ok']) ? 0 : 1);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko"><head><meta charset="UTF-8"><title>개인회생 MP 시딩</title></head>
<body style="font-family:sans-serif;max-width:640px;margin:2rem auto;">
<h1>개인회생 광고주 2곳 다중 플랫폼 시딩</h1>
<p>banktupt / dasibom 각각 별도 그룹. 멤버 양쪽 승인 가능(과금=승인자, 상대 ACK). hub=온오프CPA.</p>
<p><a href="?action=run">실행</a></p>
</body></html>
<?php
} // $running_as_script
