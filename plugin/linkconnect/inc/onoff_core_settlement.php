<?php
/**
 * ONOFF Core CPS settlement — reserve commissions + submit/pay against Core SoT.
 *
 * Flag: LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED
 *  - false: draft validation only (no settlement row)
 *  - true: partner can submit Core settlement; admin pay marks commissions PAID
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

/** @var array<string,bool> commission_id → settled (in-memory test registry) */
$GLOBALS['lc_onoff_core_settlement_memory'] = array();

if (!defined('LC_ONOFF_CORE_SETTLEMENT_MEMO_PREFIX')) {
    define('LC_ONOFF_CORE_SETTLEMENT_MEMO_PREFIX', '[CORE_CPS]');
}

if (!function_exists('lc_onoff_core_settlement_payout_enabled')) {
    function lc_onoff_core_settlement_payout_enabled()
    {
        return defined('LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED')
            && LC_ONOFF_CORE_SETTLEMENT_PAYOUT_ENABLED;
    }
}

if (!function_exists('lc_onoff_core_settlement_table')) {
    function lc_onoff_core_settlement_table()
    {
        return lc_table('core_settlement_commissions');
    }
}

if (!function_exists('lc_onoff_core_settlement_is_core_row')) {
    function lc_onoff_core_settlement_is_core_row(array $row)
    {
        $memo = (string) ($row['st_memo'] ?? '');
        return strpos($memo, LC_ONOFF_CORE_SETTLEMENT_MEMO_PREFIX) === 0;
    }
}

if (!function_exists('lc_onoff_core_settlement_ensure_schema')) {
    function lc_onoff_core_settlement_ensure_schema()
    {
        if (!lc_db_installed() || !function_exists('lc_db_table_exists')) {
            return false;
        }

        $table = lc_onoff_core_settlement_table();
        if (lc_db_table_exists($table)) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `csc_id` int unsigned NOT NULL AUTO_INCREMENT,
            `st_id` int unsigned NOT NULL DEFAULT 0,
            `pt_id` int unsigned NOT NULL DEFAULT 0,
            `commission_id` varchar(64) NOT NULL,
            `amount` int NOT NULL DEFAULT 0,
            `status` varchar(20) NOT NULL DEFAULT 'reserved',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`csc_id`),
            UNIQUE KEY `uk_commission_id` (`commission_id`),
            KEY `idx_st_id` (`st_id`),
            KEY `idx_pt_id` (`pt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        return (bool) lc_sql_query($sql, false);
    }
}

if (!function_exists('lc_onoff_core_settlement_commission_taken')) {
    function lc_onoff_core_settlement_commission_taken($commission_id)
    {
        $commission_id = trim((string) $commission_id);
        if ($commission_id === '') {
            return false;
        }

        if (!empty($GLOBALS['lc_onoff_core_settlement_memory'][$commission_id])) {
            return true;
        }

        if (!lc_db_installed()) {
            return false;
        }

        lc_onoff_core_settlement_ensure_schema();
        $table = lc_onoff_core_settlement_table();
        if (!lc_db_table_exists($table)) {
            return false;
        }

        $esc = lc_sql_escape($commission_id);
        $row = lc_sql_fetch(" SELECT csc_id FROM `{$table}` WHERE commission_id = '{$esc}' AND status IN ('reserved','paid') LIMIT 1 ");

        return is_array($row) && !empty($row['csc_id']);
    }
}

if (!function_exists('lc_onoff_core_settlement_pick_commissions')) {
    /**
     * FIFO pick of unsettled PENDING/APPROVED/AVAILABLE commissions covering $amount.
     *
     * @return array{ok:bool,message:string,items?:array<int,array{commission_id:string,amount:int}>}
     */
    function lc_onoff_core_settlement_pick_commissions($pt_id, $amount, array $commission_ids = array())
    {
        $pt_id = (int) $pt_id;
        $amount = (int) $amount;
        $earnings = lc_onoff_core_partner_earnings($pt_id);
        if (!is_array($earnings)) {
            return array('ok' => false, 'message' => 'core earnings unavailable');
        }

        $settleable_statuses = array('AVAILABLE', 'APPROVED', 'PENDING');
        $by_id = array();
        $ordered = array();
        foreach ($earnings['commission_history'] ?? array() as $c) {
            if (!is_array($c) || empty($c['commission_id'])) {
                continue;
            }
            $st = strtoupper((string) ($c['status'] ?? ''));
            if (!in_array($st, $settleable_statuses, true)) {
                continue;
            }
            $cid = (string) $c['commission_id'];
            if (lc_onoff_core_settlement_commission_taken($cid)) {
                continue;
            }
            $by_id[$cid] = $c;
            $ordered[] = $c;
        }

        $items = array();
        if (!empty($commission_ids)) {
            foreach ($commission_ids as $cid) {
                $cid = trim((string) $cid);
                if ($cid === '' || !isset($by_id[$cid])) {
                    return array('ok' => false, 'message' => 'invalid commission id: ' . $cid);
                }
                $items[] = array(
                    'commission_id' => $cid,
                    'amount'        => (int) ($by_id[$cid]['commission_amount'] ?? 0),
                );
            }
        } else {
            // 1) Prefer exact single commission match
            foreach ($ordered as $c) {
                $amt = (int) ($c['commission_amount'] ?? 0);
                if ($amt === $amount) {
                    $items[] = array(
                        'commission_id' => (string) $c['commission_id'],
                        'amount'        => $amt,
                    );
                    break;
                }
            }

            // 2) Otherwise exact subset-sum (n is small)
            if (!$items) {
                $n = count($ordered);
                $found = null;
                $limit = 1 << $n;
                for ($mask = 1; $mask < $limit; $mask++) {
                    $sum = 0;
                    $pick = array();
                    for ($i = 0; $i < $n; $i++) {
                        if (($mask & (1 << $i)) === 0) {
                            continue;
                        }
                        $amt = (int) ($ordered[$i]['commission_amount'] ?? 0);
                        if ($amt <= 0) {
                            continue 2;
                        }
                        $sum += $amt;
                        if ($sum > $amount) {
                            continue 2;
                        }
                        $pick[] = array(
                            'commission_id' => (string) $ordered[$i]['commission_id'],
                            'amount'        => $amt,
                        );
                    }
                    if ($sum === $amount) {
                        $found = $pick;
                        break;
                    }
                }
                if ($found) {
                    $items = $found;
                }
            }

            if (!$items) {
                return array(
                    'ok'      => false,
                    'message' => '금액에 맞는 Core 수수료 조합이 없습니다. 수수료 단위 합계로 신청해주세요.',
                );
            }
        }

        $sum = 0;
        foreach ($items as $it) {
            $sum += (int) $it['amount'];
        }
        if ($sum !== $amount) {
            return array('ok' => false, 'message' => 'selected commissions total must equal request amount');
        }

        return array('ok' => true, 'message' => 'ok', 'items' => $items, 'selectedTotal' => $sum);
    }
}

if (!function_exists('lc_onoff_core_settlement_reserve_commissions')) {
    /**
     * @param array<int,array{commission_id:string,amount:int}> $items
     * @return array{ok:bool,message:string,reserved?:int}
     */
    function lc_onoff_core_settlement_reserve_commissions($pt_id, $st_id, array $items)
    {
        $pt_id = (int) $pt_id;
        $st_id = (int) $st_id;
        if ($pt_id <= 0 || empty($items)) {
            return array('ok' => false, 'message' => 'invalid settlement commission payload');
        }

        $reserved = 0;
        foreach ($items as $item) {
            $cid = trim((string) ($item['commission_id'] ?? ''));
            $amt = (int) ($item['amount'] ?? 0);
            if ($cid === '') {
                return array('ok' => false, 'message' => 'empty commission id');
            }
            if (lc_onoff_core_settlement_commission_taken($cid)) {
                return array('ok' => false, 'message' => 'duplicate settlement commission: ' . $cid);
            }

            if (lc_onoff_core_test_store() instanceof OnoffCore_MemoryReferralStore) {
                $GLOBALS['lc_onoff_core_settlement_memory'][$cid] = true;
                $reserved++;
                continue;
            }

            if (lc_db_installed()) {
                lc_onoff_core_settlement_ensure_schema();
                $table = lc_onoff_core_settlement_table();
                $ok = lc_sql_query(
                    " INSERT INTO `{$table}` SET
                        st_id = '{$st_id}',
                        pt_id = '{$pt_id}',
                        commission_id = '" . lc_sql_escape($cid) . "',
                        amount = '{$amt}',
                        status = 'reserved',
                        created_at = NOW() ",
                    false
                );
                if (!$ok) {
                    return array('ok' => false, 'message' => 'reserve insert failed: ' . $cid);
                }
                $reserved++;
            }
        }

        return array('ok' => true, 'message' => 'commissions reserved', 'reserved' => $reserved);
    }
}

if (!function_exists('lc_onoff_core_settlement_prepare_request')) {
    /**
     * Validate Core settlement amount. Does not create rows.
     *
     * @return array{ok:bool,message:string,draft?:array}
     */
    function lc_onoff_core_settlement_prepare_request($pt_id, $amount, array $commission_ids = array())
    {
        if (!lc_onoff_core_integration_enabled()) {
            return array('ok' => false, 'message' => 'core integration disabled');
        }

        $pt_id = (int) $pt_id;
        $amount = (int) $amount;
        $earnings = lc_onoff_core_partner_earnings($pt_id);
        if (!is_array($earnings)) {
            return array('ok' => false, 'message' => 'core earnings unavailable');
        }

        $available = (int) ($earnings['available_earnings'] ?? 0);
        $pending = (int) ($earnings['pending_earnings'] ?? 0);
        $settleable = $available + $pending;
        if ($amount <= 0 || $amount > $settleable) {
            return array('ok' => false, 'message' => 'amount exceeds core settleable earnings');
        }

        $pick = lc_onoff_core_settlement_pick_commissions($pt_id, $amount, $commission_ids);
        if (empty($pick['ok'])) {
            return array('ok' => false, 'message' => (string) ($pick['message'] ?? 'commission pick failed'));
        }

        $payout_on = lc_onoff_core_settlement_payout_enabled();

        return array(
            'ok'      => true,
            'message' => $payout_on
                ? 'settlement draft ready for submit'
                : 'settlement draft prepared (payout blocked)',
            'draft'   => array(
                'ptId'               => $pt_id,
                'amount'             => $amount,
                'availableEarnings'  => $available,
                'pendingEarnings'    => $pending,
                'settleableEarnings' => $settleable,
                'commissionItems'    => $pick['items'],
                'selectedTotal'      => (int) ($pick['selectedTotal'] ?? 0),
                'payoutBlocked'      => !$payout_on,
                'payoutEnabled'      => $payout_on,
                'status'             => 'draft',
            ),
        );
    }
}

if (!function_exists('lc_onoff_core_settlement_submit_request')) {
    /**
     * Create LC settlement row + reserve Core commissions (requires payout flag).
     *
     * @return array{ok:bool,message:string,settlement?:array|null}
     */
    function lc_onoff_core_settlement_submit_request($pt_id, $amount, array $bank = array(), $memo = '', array $commission_ids = array())
    {
        if (!lc_onoff_core_settlement_payout_enabled()) {
            return array('ok' => false, 'message' => 'core settlement payout disabled');
        }
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $pt_id = (int) $pt_id;
        $amount = (int) $amount;
        if ($amount < 50000) {
            return array('ok' => false, 'message' => '최소 정산 금액은 50,000원입니다.');
        }

        $prep = lc_onoff_core_settlement_prepare_request($pt_id, $amount, $commission_ids);
        if (empty($prep['ok'])) {
            return array('ok' => false, 'message' => (string) ($prep['message'] ?? 'prepare failed'));
        }
        $items = $prep['draft']['commissionItems'] ?? array();
        if (empty($items)) {
            return array('ok' => false, 'message' => 'no commissions to settle');
        }

        $partner = lc_get_partner_by_id($pt_id);
        if (!$partner) {
            return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
        }

        $bank_name = isset($bank['bankName']) ? trim((string) $bank['bankName']) : (string) $partner['pt_bank_name'];
        $bank_account = isset($bank['bankAccount']) ? trim((string) $bank['bankAccount']) : (string) $partner['pt_bank_account'];
        $bank_holder = isset($bank['bankHolder']) ? trim((string) $bank['bankHolder']) : (string) $partner['pt_bank_holder'];
        if ($bank_name === '' || $bank_account === '' || $bank_holder === '') {
            return array('ok' => false, 'message' => '계좌 정보를 입력해주세요.');
        }

        $memo = trim((string) $memo);
        if (strpos($memo, LC_ONOFF_CORE_SETTLEMENT_MEMO_PREFIX) !== 0) {
            $memo = LC_ONOFF_CORE_SETTLEMENT_MEMO_PREFIX . ($memo !== '' ? ' ' . $memo : ' Core CPS 정산');
        }

        $table = lc_table('settlements');
        $code = lc_settlement_generate_code();
        lc_sql_query(" INSERT INTO `{$table}` SET
            st_code = '" . lc_sql_escape($code) . "',
            pt_id = '{$pt_id}',
            st_amount = '{$amount}',
            st_approved_amount = 0,
            st_status = '" . lc_sql_escape(LC_SETTLEMENT_PENDING) . "',
            st_bank_name = '" . lc_sql_escape($bank_name) . "',
            st_bank_account = '" . lc_sql_escape($bank_account) . "',
            st_bank_holder = '" . lc_sql_escape($bank_holder) . "',
            st_memo = '" . lc_sql_escape($memo) . "',
            st_requested_at = NOW() ", false);

        $st_id = (int) lc_sql_insert_id();
        if ($st_id <= 0) {
            return array('ok' => false, 'message' => 'settlement insert failed');
        }

        $reserve = lc_onoff_core_settlement_reserve_commissions($pt_id, $st_id, $items);
        if (empty($reserve['ok'])) {
            lc_sql_query(" DELETE FROM `{$table}` WHERE st_id = '{$st_id}' LIMIT 1 ", false);
            return array('ok' => false, 'message' => (string) ($reserve['message'] ?? 'reserve failed'));
        }

        $row = lc_settlement_get_by_id($st_id);
        return array(
            'ok'         => true,
            'message'    => 'Core CPS 정산 신청이 접수되었습니다.',
            'settlement' => is_array($row) ? lc_settlement_to_partner_api($row) : null,
            'reserved'   => (int) ($reserve['reserved'] ?? 0),
        );
    }
}

if (!function_exists('lc_onoff_core_mark_commissions_status')) {
    /**
     * @param list<string> $commission_ids
     */
    function lc_onoff_core_mark_commissions_status(array $commission_ids, $status)
    {
        $status = strtoupper(trim((string) $status));
        if (!in_array($status, array('PAID', 'AVAILABLE', 'PENDING', 'CANCELLED'), true)) {
            return array('ok' => false, 'message' => 'invalid status');
        }
        if (!lc_onoff_core_bootstrap() || !class_exists('OnoffCore_ReferralSchema', false)) {
            return array('ok' => false, 'message' => 'core unavailable');
        }
        if (!function_exists('sql_query')) {
            return array('ok' => false, 'message' => 'sql unavailable');
        }

        $tbl = OnoffCore_ReferralSchema::tableCommission();
        $now = date('Y-m-d H:i:s');
        $n = 0;
        foreach ($commission_ids as $cid) {
            $cid = trim((string) $cid);
            if ($cid === '') {
                continue;
            }
            $esc = sql_escape_string($cid);
            $extra = '';
            if ($status === 'PAID') {
                $extra = ", paid_at = '" . sql_escape_string($now) . "'";
            } elseif ($status === 'AVAILABLE') {
                $extra = ", available_at = '" . sql_escape_string($now) . "'";
            }
            $ok = sql_query(
                "UPDATE `{$tbl}` SET status = '" . sql_escape_string($status) . "'{$extra}
                 WHERE commission_id = '{$esc}' LIMIT 1",
                false
            );
            if ($ok) {
                $n++;
            }
        }
        return array('ok' => true, 'updated' => $n);
    }
}

if (!function_exists('lc_onoff_core_settlement_list_reserved')) {
    /**
     * @return list<array{commission_id:string,amount:int,status:string}>
     */
    function lc_onoff_core_settlement_list_reserved($st_id)
    {
        $st_id = (int) $st_id;
        if ($st_id <= 0 || !lc_db_installed()) {
            return array();
        }
        lc_onoff_core_settlement_ensure_schema();
        $table = lc_onoff_core_settlement_table();
        $rows = array();
        $res = lc_sql_query(" SELECT commission_id, amount, status FROM `{$table}` WHERE st_id = '{$st_id}' ", false);
        if ($res) {
            while ($r = sql_fetch_array($res)) {
                $rows[] = array(
                    'commission_id' => (string) ($r['commission_id'] ?? ''),
                    'amount'        => (int) ($r['amount'] ?? 0),
                    'status'        => (string) ($r['status'] ?? ''),
                );
            }
        }
        return $rows;
    }
}

if (!function_exists('lc_onoff_core_settlement_on_admin_pay')) {
    function lc_onoff_core_settlement_on_admin_pay($st_id)
    {
        $st_id = (int) $st_id;
        $reserved = lc_onoff_core_settlement_list_reserved($st_id);
        $ids = array();
        foreach ($reserved as $r) {
            if (($r['status'] ?? '') === 'reserved' || ($r['status'] ?? '') === 'paid') {
                $ids[] = $r['commission_id'];
            }
        }
        if (!$ids) {
            return array('ok' => true, 'message' => 'no reserved commissions', 'updated' => 0);
        }
        $mark = lc_onoff_core_mark_commissions_status($ids, 'PAID');
        if (lc_db_installed()) {
            $table = lc_onoff_core_settlement_table();
            lc_sql_query(" UPDATE `{$table}` SET status = 'paid' WHERE st_id = '{$st_id}' AND status = 'reserved' ", false);
        }
        return $mark;
    }
}

if (!function_exists('lc_onoff_core_settlement_on_admin_reject')) {
    function lc_onoff_core_settlement_on_admin_reject($st_id)
    {
        $st_id = (int) $st_id;
        if (!lc_db_installed()) {
            return array('ok' => true);
        }
        lc_onoff_core_settlement_ensure_schema();
        $table = lc_onoff_core_settlement_table();
        lc_sql_query(" UPDATE `{$table}` SET status = 'released' WHERE st_id = '{$st_id}' AND status = 'reserved' ", false);
        return array('ok' => true);
    }
}

if (!function_exists('lc_onoff_core_settlement_reset_test_memory')) {
    function lc_onoff_core_settlement_reset_test_memory()
    {
        $GLOBALS['lc_onoff_core_settlement_memory'] = array();
    }
}
