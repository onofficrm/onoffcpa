<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_wallet_get_balance')) {
    function lc_wallet_get_balance($mt_id)
    {
        $merchant = lc_get_merchant_by_id($mt_id);

        return is_array($merchant) ? (int) $merchant['mt_balance'] : 0;
    }
}

if (!function_exists('lc_wallet_record')) {
    /**
     * @return array{ok:bool,message:string,balance?:int}
     */
    function lc_wallet_record($mt_id, $type, $amount, $memo = '', $ref_type = '', $ref_id = 0, $status = 'completed')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $mt_id = (int) $mt_id;
        $amount = (int) $amount;
        $merchant = lc_get_merchant_by_id($mt_id);
        if (!$merchant) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.');
        }

        $balance = (int) $merchant['mt_balance'];
        $new_balance = $balance + $amount;
        if ($status === 'completed' && $new_balance < 0) {
            return array('ok' => false, 'message' => '광고비 잔액이 부족합니다.');
        }

        $merchant_table = lc_table('merchants');
        $wallet_table = lc_table('wallet_transactions');

        if ($status === 'completed') {
            lc_sql_query(" UPDATE `{$merchant_table}` SET mt_balance = '{$new_balance}', mt_updated_at = NOW() WHERE mt_id = '{$mt_id}' ", false);
        }

        lc_sql_query(" INSERT INTO `{$wallet_table}` SET
            mt_id = '{$mt_id}',
            wt_type = '" . lc_sql_escape($type) . "',
            wt_amount = '{$amount}',
            wt_balance_after = '" . ($status === 'completed' ? $new_balance : $balance) . "',
            wt_status = '" . lc_sql_escape($status) . "',
            wt_memo = '" . lc_sql_escape($memo) . "',
            wt_ref_type = '" . lc_sql_escape($ref_type) . "',
            wt_ref_id = '" . (int) $ref_id . "',
            wt_created_at = NOW() ", false);

        if ($status === 'completed' && function_exists('lc_wallet_check_low_balance')) {
            lc_wallet_check_low_balance($mt_id, $new_balance);
        }

        return array('ok' => true, 'message' => '거래가 기록되었습니다.', 'balance' => $status === 'completed' ? $new_balance : $balance);
    }
}

if (!function_exists('lc_wallet_deduct_for_conversion')) {
    function lc_wallet_deduct_for_conversion($mt_id, $cv_id, $amount, $memo = '')
    {
        return lc_wallet_record($mt_id, 'deduct', -1 * abs((int) $amount), $memo, 'conversion', (int) $cv_id);
    }
}

if (!function_exists('lc_wallet_list_for_merchant')) {
    function lc_wallet_list_for_merchant($mt_id, $limit = 20)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $limit = max(1, min(100, (int) $limit));
        $table = lc_table('wallet_transactions');
        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE mt_id = '{$mt_id}' ORDER BY wt_id DESC LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_wallet_transaction_to_api')) {
    function lc_wallet_transaction_to_api(array $row)
    {
        $type_labels = array(
            'charge' => '충전',
            'deduct' => '차감',
            'refund' => '환급',
        );

        return array(
            'id'        => (int) $row['wt_id'],
            'date'      => (string) $row['wt_created_at'],
            'type'      => isset($type_labels[$row['wt_type']]) ? $type_labels[$row['wt_type']] : (string) $row['wt_type'],
            'typeCode'  => (string) $row['wt_type'],
            'amount'    => (int) $row['wt_amount'],
            'balance'   => (int) $row['wt_balance_after'],
            'status'    => (string) $row['wt_status'],
            'memo'      => (string) $row['wt_memo'],
        );
    }
}

if (!function_exists('lc_wallet_request_charge')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_wallet_request_charge($mt_id, $amount, $memo = '')
    {
        if ($amount <= 0) {
            return array('ok' => false, 'message' => '충전 금액이 올바르지 않습니다.');
        }

        $result = lc_wallet_record($mt_id, 'charge', (int) $amount, $memo !== '' ? $memo : '광고비 충전 신청', 'charge_request', 0, 'pending');

        return array(
            'ok'      => $result['ok'],
            'message' => $result['ok'] ? '충전 신청이 접수되었습니다.' : $result['message'],
        );
    }
}

if (!function_exists('lc_wallet_merchant_summary')) {
    function lc_wallet_merchant_summary($mt_id)
    {
        if (!lc_db_installed()) {
            return array(
                'balance'          => 0,
                'monthlyCharge'    => 0,
                'monthlySpend'     => 0,
                'availableBalance' => 0,
            );
        }

        $mt_id = (int) $mt_id;
        $balance = lc_wallet_get_balance($mt_id);
        $table = lc_table('wallet_transactions');
        $month_start = date('Y-m-01');

        $charge_row = lc_sql_fetch(" SELECT COALESCE(SUM(wt_amount), 0) AS total
            FROM `{$table}`
            WHERE mt_id = '{$mt_id}'
              AND wt_type = 'charge'
              AND wt_status = 'completed'
              AND wt_created_at >= '{$month_start}' ");

        $spend_row = lc_sql_fetch(" SELECT COALESCE(SUM(ABS(wt_amount)), 0) AS total
            FROM `{$table}`
            WHERE mt_id = '{$mt_id}'
              AND wt_type = 'deduct'
              AND wt_status = 'completed'
              AND wt_created_at >= '{$month_start}' ");

        return array(
            'balance'          => $balance,
            'monthlyCharge'    => (int) ($charge_row['total'] ?? 0),
            'monthlySpend'     => (int) ($spend_row['total'] ?? 0),
            'availableBalance' => $balance,
        );
    }
}

if (!function_exists('lc_wallet_list_for_api')) {
    function lc_wallet_list_for_api($mt_id)
    {
        if (lc_db_installed()) {
            return array_map('lc_wallet_transaction_to_api', lc_wallet_list_for_merchant($mt_id));
        }

        if (!function_exists('lc_sample_merchant_wallet_history')) {
            return array();
        }

        $items = array();
        foreach (lc_sample_merchant_wallet_history() as $row) {
            $items[] = array(
                'id'       => 0,
                'date'     => (string) $row['date'],
                'type'     => (string) $row['type'],
                'typeCode' => (string) $row['type'],
                'amount'   => (int) $row['amount'],
                'balance'  => (int) $row['balance'],
                'status'   => 'completed',
                'memo'     => (string) $row['memo'],
            );
        }

        return $items;
    }
}

if (!function_exists('lc_wallet_count_pending')) {
    function lc_wallet_count_pending()
    {
        if (!lc_db_installed()) {
            return 0;
        }

        $table = lc_table('wallet_transactions');
        $row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE wt_status = 'pending' AND wt_type = 'charge' ");

        return (int) ($row['cnt'] ?? 0);
    }
}

if (!function_exists('lc_wallet_list_pending_charges')) {
    function lc_wallet_list_pending_charges($limit = 50)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $wt_table = lc_table('wallet_transactions');
        $mt_table = lc_table('merchants');
        $limit = max(1, min(100, (int) $limit));

        $rows = array();
        $sql = " SELECT wt.*, m.mt_company, m.mt_code
            FROM `{$wt_table}` wt
            INNER JOIN `{$mt_table}` m ON m.mt_id = wt.mt_id
            WHERE wt.wt_status = 'pending' AND wt.wt_type = 'charge'
            ORDER BY wt.wt_id DESC
            LIMIT {$limit} ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_wallet_pending_to_api')) {
    function lc_wallet_pending_to_api(array $row)
    {
        return array(
            'id'         => (int) $row['wt_id'],
            'date'       => (string) $row['wt_created_at'],
            'merchant'   => (string) ($row['mt_company'] ?? ''),
            'merchantCode'=> (string) ($row['mt_code'] ?? ''),
            'mtId'       => (int) $row['mt_id'],
            'amount'     => (int) $row['wt_amount'],
            'memo'       => (string) $row['wt_memo'],
            'status'     => '충전대기',
        );
    }
}

if (!function_exists('lc_wallet_approve_transaction')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_wallet_approve_transaction($wt_id)
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $wt_id = (int) $wt_id;
        $wt_table = lc_table('wallet_transactions');
        $row = lc_sql_fetch(" SELECT * FROM `{$wt_table}` WHERE wt_id = '{$wt_id}' AND wt_status = 'pending' LIMIT 1 ");
        if (!$row) {
            return array('ok' => false, 'message' => '대기 중인 거래를 찾을 수 없습니다.');
        }

        $mt_id = (int) $row['mt_id'];
        $amount = (int) $row['wt_amount'];
        $merchant = lc_get_merchant_by_id($mt_id);
        if (!$merchant) {
            return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.');
        }

        $new_balance = (int) $merchant['mt_balance'] + $amount;
        $merchant_table = lc_table('merchants');

        lc_sql_query(" UPDATE `{$merchant_table}` SET mt_balance = '{$new_balance}', mt_updated_at = NOW() WHERE mt_id = '{$mt_id}' ", false);
        lc_sql_query(" UPDATE `{$wt_table}` SET wt_status = 'completed', wt_balance_after = '{$new_balance}' WHERE wt_id = '{$wt_id}' ", false);

        return array('ok' => true, 'message' => '충전이 승인되었습니다.');
    }
}

if (!function_exists('lc_wallet_reject_transaction')) {
    function lc_wallet_reject_transaction($wt_id, $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $wt_id = (int) $wt_id;
        $wt_table = lc_table('wallet_transactions');
        $row = lc_sql_fetch(" SELECT * FROM `{$wt_table}` WHERE wt_id = '{$wt_id}' AND wt_status = 'pending' LIMIT 1 ");
        if (!$row) {
            return array('ok' => false, 'message' => '대기 중인 거래를 찾을 수 없습니다.');
        }

        $memo_esc = lc_sql_escape($memo !== '' ? $memo : '충전 신청 반려');
        lc_sql_query(" UPDATE `{$wt_table}` SET wt_status = 'rejected', wt_memo = CONCAT(wt_memo, ' / ', '{$memo_esc}') WHERE wt_id = '{$wt_id}' ", false);

        return array('ok' => true, 'message' => '충전 신청이 반려되었습니다.');
    }
}

if (!function_exists('lc_wallet_admin_summary')) {
    function lc_wallet_admin_summary()
    {
        if (!lc_db_installed()) {
            return array(
                'totalBalance' => 0,
                'totalPending' => 0,
                'todayCharge'  => 0,
                'todaySpend'   => 0,
                'todayRefund'  => 0,
                'lowBalance'   => 0,
            );
        }

        $mt_table = lc_table('merchants');
        $wt_table = lc_table('wallet_transactions');
        $today = date('Y-m-d');

        $balance_row = lc_sql_fetch(" SELECT COALESCE(SUM(mt_balance), 0) AS total FROM `{$mt_table}` WHERE mt_status = '" . lc_sql_escape(LC_MERCHANT_STATUS_ACTIVE) . "' ");
        $low_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$mt_table}` WHERE mt_status = '" . lc_sql_escape(LC_MERCHANT_STATUS_ACTIVE) . "' AND mt_balance < 500000 ");
        $today_charge = lc_sql_fetch(" SELECT COALESCE(SUM(wt_amount), 0) AS total FROM `{$wt_table}` WHERE wt_type = 'charge' AND wt_status = 'completed' AND DATE(wt_created_at) = '{$today}' ");
        $today_spend = lc_sql_fetch(" SELECT COALESCE(SUM(ABS(wt_amount)), 0) AS total FROM `{$wt_table}` WHERE wt_type = 'deduct' AND wt_status = 'completed' AND DATE(wt_created_at) = '{$today}' ");
        $today_refund = lc_sql_fetch(" SELECT COALESCE(SUM(wt_amount), 0) AS total FROM `{$wt_table}` WHERE wt_type = 'refund' AND wt_status = 'completed' AND DATE(wt_created_at) = '{$today}' ");
        $pending_row = lc_sql_fetch(" SELECT COALESCE(SUM(wt_amount), 0) AS total FROM `{$wt_table}` WHERE wt_type = 'charge' AND wt_status = 'pending' ");

        return array(
            'totalBalance' => (int) ($balance_row['total'] ?? 0),
            'totalPending' => (int) ($pending_row['total'] ?? 0),
            'todayCharge'  => (int) ($today_charge['total'] ?? 0),
            'todaySpend'   => (int) ($today_spend['total'] ?? 0),
            'todayRefund'  => (int) ($today_refund['total'] ?? 0),
            'lowBalance'   => (int) ($low_row['cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_wallet_admin_merchant_balances')) {
    function lc_wallet_admin_merchant_balances($q = '')
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_table = lc_table('merchants');
        $wt_table = lc_table('wallet_transactions');
        $where = " 1=1 ";
        if ($q !== '') {
            $q_esc = lc_sql_escape($q);
            $where .= " AND (m.mt_company LIKE '%{$q_esc}%' OR m.mt_code LIKE '%{$q_esc}%') ";
        }

        $rows = array();
        $sql = " SELECT m.*,
            (SELECT COALESCE(SUM(wt_amount), 0) FROM `{$wt_table}` wt WHERE wt.mt_id = m.mt_id AND wt_type = 'charge' AND wt_status = 'completed') AS total_charged,
            (SELECT COALESCE(SUM(ABS(wt_amount)), 0) FROM `{$wt_table}` wt WHERE wt.mt_id = m.mt_id AND wt_type = 'deduct' AND wt_status = 'completed') AS total_used,
            (SELECT COALESCE(SUM(wt_amount), 0) FROM `{$wt_table}` wt WHERE wt.mt_id = m.mt_id AND wt_type = 'refund' AND wt_status = 'completed') AS total_refund,
            (SELECT MAX(wt_created_at) FROM `{$wt_table}` wt WHERE wt.mt_id = m.mt_id AND wt_type = 'charge' AND wt_status = 'completed') AS last_charged
            FROM `{$mt_table}` m
            WHERE {$where}
            ORDER BY m.mt_balance DESC
            LIMIT 100 ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $balance = (int) ($row['mt_balance'] ?? 0);
                $status = '정상';
                if ($row['mt_status'] !== LC_MERCHANT_STATUS_ACTIVE) {
                    $status = '사용중지';
                } elseif ($balance < 500000) {
                    $status = '광고비부족';
                }
                $rows[] = array(
                    'id'           => (int) $row['mt_id'],
                    'name'         => (string) $row['mt_company'],
                    'code'         => (string) $row['mt_code'],
                    'balance'      => $balance,
                    'pending'      => 0,
                    'available'    => $balance,
                    'totalCharged' => (int) ($row['total_charged'] ?? 0),
                    'totalUsed'    => (int) ($row['total_used'] ?? 0),
                    'totalRefund'  => (int) ($row['total_refund'] ?? 0),
                    'lastCharged'  => !empty($row['last_charged']) ? date('Y.m.d', strtotime($row['last_charged'])) : '-',
                    'status'       => $status,
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_wallet_admin_transactions')) {
    function lc_wallet_admin_transactions(array $filters = array(), $limit = 50)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $wt_table = lc_table('wallet_transactions');
        $mt_table = lc_table('merchants');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $where = ' 1=1 ';
        if (!empty($filters['type'])) {
            $where .= " AND wt.wt_type = '" . lc_sql_escape($filters['type']) . "' ";
        }
        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND m.mt_company LIKE '%{$q}%' ";
        }

        $limit = max(1, min(200, (int) $limit));
        $rows = array();
        $sql = " SELECT wt.*, m.mt_company,
            cv.cv_code, c.cp_name
            FROM `{$wt_table}` wt
            INNER JOIN `{$mt_table}` m ON m.mt_id = wt.mt_id
            LEFT JOIN `{$cv_table}` cv ON wt.wt_ref_type = 'conversion' AND cv.cv_id = wt.wt_ref_id
            LEFT JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE {$where}
            ORDER BY wt.wt_id DESC
            LIMIT {$limit} ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $type_labels = array(
                    'charge' => '충전',
                    'deduct' => '차감',
                    'refund' => '환급',
                    'adjust' => '수동조정',
                );
                $rows[] = array(
                    'id'       => (int) $row['wt_id'],
                    'date'     => date('Y.m.d H:i', strtotime($row['wt_created_at'])),
                    'merchant' => (string) ($row['mt_company'] ?? ''),
                    'mtId'     => (int) $row['mt_id'],
                    'type'     => isset($type_labels[$row['wt_type']]) ? $type_labels[$row['wt_type']] : (string) $row['wt_type'],
                    'typeCode' => (string) $row['wt_type'],
                    'dbCode'   => (string) ($row['cv_code'] ?? '-'),
                    'campaign' => (string) ($row['cp_name'] ?? '-'),
                    'amount'   => (int) $row['wt_amount'],
                    'balance'  => (int) $row['wt_balance_after'],
                    'processor'=> '시스템',
                    'memo'     => (string) $row['wt_memo'],
                    'status'   => (string) $row['wt_status'],
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_wallet_admin_adjust')) {
    /**
     * @return array{ok:bool,message:string}
     */
    function lc_wallet_admin_adjust($mt_id, $type, $amount, $memo = '')
    {
        $amount = (int) $amount;
        if ($amount === 0) {
            return array('ok' => false, 'message' => '금액을 입력해주세요.');
        }

        $type_map = array(
            'charge' => array('charge', abs($amount)),
            'deduct' => array('deduct', -1 * abs($amount)),
            'refund' => array('refund', abs($amount)),
            'adjust' => array('adjust', $amount),
        );
        if (!isset($type_map[$type])) {
            return array('ok' => false, 'message' => '유효하지 않은 유형입니다.');
        }

        list($wt_type, $signed) = $type_map[$type];
        $result = lc_wallet_record($mt_id, $wt_type, $signed, $memo !== '' ? $memo : '관리자 수동 처리', 'admin_adjust', 0, 'completed');

        return array(
            'ok'      => $result['ok'],
            'message' => $result['ok'] ? '거래가 처리되었습니다.' : $result['message'],
        );
    }
}

if (!function_exists('lc_wallet_low_balance_threshold')) {
    function lc_wallet_low_balance_threshold()
    {
        if (function_exists('lc_settings_get_int')) {
            return max(0, lc_settings_get_int('minChargeAmount', 500000));
        }

        if (function_exists('lc_settings_get_all')) {
            $settings = lc_settings_get_all();

            return max(0, (int) ($settings['minChargeAmount'] ?? 500000));
        }

        return 500000;
    }
}

if (!function_exists('lc_wallet_check_low_balance')) {
    function lc_wallet_check_low_balance($mt_id, $balance)
    {
        $mt_id = (int) $mt_id;
        $balance = (int) $balance;
        $threshold = lc_wallet_low_balance_threshold();

        if ($mt_id <= 0 || $threshold <= 0 || $balance >= $threshold) {
            return;
        }

        if (!function_exists('lc_notification_emit_low_balance')) {
            return;
        }

        if (function_exists('lc_notification_recent_exists')) {
            if (lc_notification_recent_exists('merchant', $mt_id, 'wallet', 24)) {
                return;
            }
        }

        lc_notification_emit_low_balance($mt_id, $balance, $threshold);

        // SMS/Kakao 등 보조 채널 로깅 (이메일은 lc_email_notify_on_low_balance에서 발송)
        if (function_exists('lc_wallet_send_recharge_notices')) {
            lc_wallet_send_recharge_notices($mt_id, $balance, $threshold);
        }
    }
}

if (!function_exists('lc_wallet_send_recharge_notices')) {
    function lc_wallet_send_recharge_notices($mt_id, $balance, $threshold)
    {
        $merchant = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($mt_id) : null;
        if (!is_array($merchant)) {
            return;
        }

        $company = (string) ($merchant['mt_company'] ?? '광고주');
        $balance_fmt = number_format((int) $balance);
        $threshold_fmt = number_format((int) $threshold);

        $email_tpl = function_exists('lc_settings_get') ? lc_settings_get('notifyLowBalanceEmailTpl') : '';
        if ($email_tpl === '') {
            $email_tpl = '[{site}] {company}님, 광고비 잔액이 {balance}원입니다. (기준 {threshold}원) 충전을 진행해 주세요.';
        }
        $sms_tpl = function_exists('lc_settings_get') ? lc_settings_get('notifyLowBalanceSmsTpl') : '';
        if ($sms_tpl === '') {
            $sms_tpl = '[{site}] 광고비 잔액 {balance}원. 충전 필요.';
        }
        $kakao_tpl = function_exists('lc_settings_get') ? lc_settings_get('notifyLowBalanceKakaoTpl') : '';
        if ($kakao_tpl === '') {
            $kakao_tpl = '{company}님, 광고비 잔액 {balance}원입니다. 충전해 주세요.';
        }

        $vars = array(
            '{site}'      => function_exists('lc_settings_get') ? lc_settings_get('siteName', '링크커넥트') : '링크커넥트',
            '{company}'   => $company,
            '{balance}'   => $balance_fmt,
            '{threshold}' => $threshold_fmt,
        );
        $render = function ($tpl) use ($vars) {
            return str_replace(array_keys($vars), array_values($vars), (string) $tpl);
        };

        if (function_exists('lc_admin_log_write')) {
            // 이메일은 lc_email_notify_on_low_balance에서 실제 발송
            if (function_exists('lc_settings_get_bool') && lc_settings_get_bool('notifyLowBalanceSms', false)) {
                lc_admin_log_write('notify_sms', 'merchant', $mt_id, $render($sms_tpl));
            }
            if (function_exists('lc_settings_get_bool') && lc_settings_get_bool('notifyLowBalanceKakao', false)) {
                lc_admin_log_write('notify_kakao', 'merchant', $mt_id, $render($kakao_tpl));
            }
        }
    }
}
