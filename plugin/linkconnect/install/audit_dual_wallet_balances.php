<?php
/**
 * 공동 입점 광고주 잔액 불변식(온오프CPA ≥ 링크커넥트) 감사/보정.
 *
 * CLI: php plugin/linkconnect/install/audit_dual_wallet_balances.php [--fix]
 * HTTP: ?action=run&mode=report|fix&token=...
 */
require_once dirname(__DIR__) . '/_common.php';

$is_cli = (php_sapi_name() === 'cli');
$running_as_script = (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__))
    || ($is_cli && isset($_SERVER['argv'][0]) && realpath($_SERVER['argv'][0]) === realpath(__FILE__));

if (!function_exists('lc_mp_audit_dual_wallet_balances')) {
    /**
     * @param array{fix?:bool} $opts
     * @return array{ok:bool,message:string,local?:string,items?:array,violations?:int,fixed?:int}
     */
    function lc_mp_audit_dual_wallet_balances(array $opts = array())
    {
        $do_fix = !empty($opts['fix']);
        if (!lc_mp_enabled()) {
            return array('ok' => false, 'message' => 'multi-platform disabled');
        }

        $groups_t = lc_mp_db_table('advertiser_groups');
        $mem_t = lc_mp_db_table('advertiser_memberships');
        if (!lc_db_table_exists($groups_t) || !lc_db_table_exists($mem_t)) {
            return array('ok' => false, 'message' => 'schema missing');
        }

        $local = lc_mp_local_platform_code();
        $items = array();
        $violations = 0;
        $fixed = 0;

        $r = sql_query(" SELECT group_id, group_code, display_name FROM `{$groups_t}`
            WHERE status = 'active' ORDER BY group_id ASC ", false);
        if (!$r) {
            return array('ok' => false, 'message' => 'group query failed');
        }

        while ($g = sql_fetch_array($r)) {
            $gid = (int) $g['group_id'];
            $members = lc_mp_list_memberships($gid);
            if (!is_array($members) || count($members) < 2) {
                continue;
            }

            $local_mt = 0;
            foreach ($members as $m) {
                if ((int) ($m['local_mt_id'] ?? 0) > 0) {
                    $local_mt = (int) $m['local_mt_id'];
                    break;
                }
            }
            if ($local_mt <= 0) {
                continue;
            }

            $primary = function_exists('lc_mp_fetch_primary_peer_balance')
                ? lc_mp_fetch_primary_peer_balance($local_mt)
                : array('ok' => false, 'message' => 'helper missing');
            $secondary = function_exists('lc_mp_fetch_secondary_peer_balance')
                ? lc_mp_fetch_secondary_peer_balance($local_mt)
                : array('ok' => true, 'skipped' => true, 'balance' => 0);

            // 로컬이 비-primary(링크커넥트)면 secondary=로컬, primary=피어
            $local_bal = function_exists('lc_wallet_get_balance') ? (int) lc_wallet_get_balance($local_mt) : 0;
            if (function_exists('lc_mp_local_is_primary_wallet_platform')
                && !lc_mp_local_is_primary_wallet_platform()) {
                $onoff_bal = !empty($primary['ok']) ? (int) ($primary['balance'] ?? 0) : null;
                $lc_bal = $local_bal;
                $primary_ok = !empty($primary['ok']) && empty($primary['skipped']);
            } else {
                $onoff_bal = $local_bal;
                $lc_bal = (!empty($secondary['ok']) && empty($secondary['skipped']))
                    ? (int) ($secondary['balance'] ?? 0)
                    : null;
                $primary_ok = true;
            }

            $row = array(
                'groupId'     => $gid,
                'groupCode'   => (string) ($g['group_code'] ?? ''),
                'displayName' => (string) ($g['display_name'] ?? ''),
                'localMtId'   => $local_mt,
                'localPlatform' => $local,
                'onoffcpaBalance' => $onoff_bal,
                'linkconnectBalance' => $lc_bal,
                'invariantOk' => null,
                'fix'       => null,
            );

            if ($onoff_bal === null || $lc_bal === null || !$primary_ok) {
                $row['invariantOk'] = null;
                $row['message'] = 'peer balance unavailable';
                $items[] = $row;
                continue;
            }

            $ok_inv = $onoff_bal >= $lc_bal;
            $row['invariantOk'] = $ok_inv;
            if ($ok_inv) {
                $items[] = $row;
                continue;
            }

            $violations++;
            $excess = $lc_bal - $onoff_bal;
            $row['excess'] = $excess;

            if ($do_fix
                && function_exists('lc_mp_local_is_primary_wallet_platform')
                && !lc_mp_local_is_primary_wallet_platform()
                && function_exists('lc_wallet_admin_adjust')
                && $excess > 0) {
                $adj = lc_wallet_admin_adjust(
                    $local_mt,
                    'deduct',
                    $excess,
                    'dual-wallet invariant clamp (LC ≤ ONOFFCPA)'
                );
                $row['fix'] = $adj;
                if (!empty($adj['ok'])) {
                    $fixed++;
                    $row['linkconnectBalance'] = $onoff_bal;
                    $row['invariantOk'] = true;
                }
            } else {
                $row['message'] = $do_fix
                    ? 'fix only runs on LINKCONNECT local (deduct excess)'
                    : 'violation — run with mode=fix on LinkConnect to clamp';
            }

            $items[] = $row;
        }

        return array(
            'ok'         => true,
            'message'    => $violations === 0
                ? 'all dual advertisers satisfy ONOFFCPA ≥ LINKCONNECT'
                : ($fixed > 0
                    ? "fixed {$fixed}/{$violations} violations"
                    : "{$violations} violation(s) found"),
            'local'      => $local,
            'violations' => $violations,
            'fixed'      => $fixed,
            'items'      => $items,
        );
    }
}

if (!$running_as_script) {
    return;
}

if (!$is_cli) {
    $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : 'form';
    if ($action === 'run' && !lc_is_super_admin()) {
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
    if ($action !== 'run') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="ko"><body style="font-family:sans-serif;max-width:640px;margin:2rem auto;">';
        echo '<h1>이중 지갑 잔액 감사</h1><p>온오프CPA ≥ 링크커넥트</p>';
        echo '<p><a href="?action=run&mode=report">report</a> · <a href="?action=run&mode=fix">fix (LC only)</a></p>';
        echo '</body></html>';
        exit;
    }
}

$fix = false;
if ($is_cli) {
    $fix = in_array('--fix', $argv ?? array(), true);
} else {
    $fix = (isset($_REQUEST['mode']) && (string) $_REQUEST['mode'] === 'fix');
}

$result = lc_mp_audit_dual_wallet_balances(array('fix' => $fix));
if ($is_cli) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($result['ok']) && (int) ($result['violations'] ?? 0) === 0 ? 0 : 1);
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
