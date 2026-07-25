<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('LC_SETTLEMENT_PENDING', 'pending');
define('LC_SETTLEMENT_REVIEWING', 'reviewing');
define('LC_SETTLEMENT_APPROVED', 'approved');
define('LC_SETTLEMENT_PAID', 'paid');
define('LC_SETTLEMENT_HOLD', 'hold');
define('LC_SETTLEMENT_REJECTED', 'rejected');

if (!function_exists('lc_settlement_status_label')) {
    function lc_settlement_status_label($status)
    {
        $labels = array(
            LC_SETTLEMENT_PENDING   => '신청완료',
            LC_SETTLEMENT_REVIEWING => '승인대기',
            LC_SETTLEMENT_APPROVED  => '승인완료',
            LC_SETTLEMENT_PAID      => '지급완료',
            LC_SETTLEMENT_HOLD      => '보류',
            LC_SETTLEMENT_REJECTED  => '반려',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_settlement_generate_code')) {
    function lc_settlement_generate_code()
    {
        return 'ST-' . date('ymd') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('lc_settlement_get_by_id')) {
    function lc_settlement_get_by_id($st_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $st_id = (int) $st_id;
        $table = lc_table('settlements');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE st_id = '{$st_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_settlement_pending_amount')) {
    function lc_settlement_pending_amount($pt_id)
    {
        if (!lc_db_installed()) {
            return 0;
        }

        $pt_id = (int) $pt_id;
        $table = lc_table('settlements');
        $statuses = array(
            LC_SETTLEMENT_PENDING,
            LC_SETTLEMENT_REVIEWING,
            LC_SETTLEMENT_APPROVED,
        );
        $in = "'" . implode("','", array_map('lc_sql_escape', $statuses)) . "'";
        $row = lc_sql_fetch(" SELECT COALESCE(SUM(st_amount), 0) AS total FROM `{$table}`
            WHERE pt_id = '{$pt_id}' AND st_status IN ({$in}) ");

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('lc_settlement_partner_summary')) {
    function lc_settlement_partner_summary($pt_id)
    {
        $partner = lc_get_partner_by_id($pt_id);
        $balance = is_array($partner) ? (int) $partner['pt_balance'] : 0;
        $pending = lc_settlement_pending_amount($pt_id);
        $month_start = date('Y-m-01');

        $table = lc_table('settlements');
        $paid_row = lc_sql_fetch(" SELECT COALESCE(SUM(st_approved_amount), 0) AS total FROM `{$table}`
            WHERE pt_id = '" . (int) $pt_id . "' AND st_status = '" . lc_sql_escape(LC_SETTLEMENT_PAID) . "' ");
        $month_conf = 0;
        if (function_exists('lc_conversion_partner_summary')) {
            $month_conf = (int) (lc_conversion_partner_summary($pt_id)['confRevenue'] ?? 0);
        }

        return array(
            'balance'          => $balance,
            'pendingAmount'    => $pending,
            'availableAmount'  => max(0, $balance - $pending),
            'paidTotal'        => (int) ($paid_row['total'] ?? 0),
            'monthConfirmed'   => $month_conf,
            'minAmount'        => 50000,
            'bankName'         => is_array($partner) ? (string) $partner['pt_bank_name'] : '',
            'bankAccount'      => is_array($partner) ? (string) $partner['pt_bank_account'] : '',
            'bankHolder'       => is_array($partner) ? (string) $partner['pt_bank_holder'] : '',
        );
    }
}

if (!function_exists('lc_settlement_list_for_partner')) {
    function lc_settlement_list_for_partner($pt_id, $limit = 20)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $limit = max(1, min(100, (int) $limit));
        $table = lc_table('settlements');
        $rows = array();
        $result = lc_sql_query(" SELECT * FROM `{$table}` WHERE pt_id = '{$pt_id}' ORDER BY st_id DESC LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_settlement_to_partner_api')) {
    function lc_settlement_to_partner_api(array $row)
    {
        $approved = (int) ($row['st_approved_amount'] ?? 0);
        if ($approved <= 0 && in_array($row['st_status'], array(LC_SETTLEMENT_APPROVED, LC_SETTLEMENT_PAID), true)) {
            $approved = (int) $row['st_amount'];
        }

        return array(
            'id'           => (int) $row['st_id'],
            'code'         => (string) $row['st_code'],
            'date'         => date('Y.m.d H:i', strtotime($row['st_requested_at'])),
            'reqAmount'    => (int) $row['st_amount'],
            'appAmount'    => $approved,
            'status'       => lc_settlement_status_label($row['st_status']),
            'statusCode'   => (string) $row['st_status'],
            'payDate'      => !empty($row['st_processed_at']) ? date('Y.m.d', strtotime($row['st_processed_at'])) : '-',
            'memo'         => (string) ($row['st_memo'] ?? ''),
        );
    }
}

if (!function_exists('lc_settlement_request')) {
    /**
     * @return array{ok:bool,message:string,settlement?:array|null}
     */
    function lc_settlement_request($pt_id, $amount, array $bank = array(), $memo = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $pt_id = (int) $pt_id;
        $amount = (int) $amount;
        $partner = lc_get_partner_by_id($pt_id);
        if (!$partner) {
            return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
        }

        if ($amount < 50000) {
            return array('ok' => false, 'message' => '최소 정산 금액은 50,000원입니다.');
        }

        $summary = lc_settlement_partner_summary($pt_id);
        if ($amount > $summary['availableAmount']) {
            return array('ok' => false, 'message' => '정산 가능 금액을 초과했습니다.');
        }

        $bank_name = isset($bank['bankName']) ? trim((string) $bank['bankName']) : (string) $partner['pt_bank_name'];
        $bank_account = isset($bank['bankAccount']) ? trim((string) $bank['bankAccount']) : (string) $partner['pt_bank_account'];
        $bank_holder = isset($bank['bankHolder']) ? trim((string) $bank['bankHolder']) : (string) $partner['pt_bank_holder'];

        if ($bank_name === '' || $bank_account === '' || $bank_holder === '') {
            return array('ok' => false, 'message' => '계좌 정보를 입력해주세요.');
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
        $row = lc_settlement_get_by_id($st_id);

        return array(
            'ok'         => true,
            'message'    => '정산 신청이 접수되었습니다.',
            'settlement' => is_array($row) ? lc_settlement_to_partner_api($row) : null,
        );
    }
}

if (!function_exists('lc_settlement_list_admin')) {
    function lc_settlement_list_admin(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $st_table = lc_table('settlements');
        $pt_table = lc_table('partners');
        $where = '1=1';

        if (!empty($filters['status'])) {
            $where .= " AND s.st_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (p.pt_code LIKE '%{$q}%' OR p.pt_name LIKE '%{$q}%' OR s.st_code LIKE '%{$q}%') ";
        }

        $rows = array();
        $sql = " SELECT s.*, p.pt_code, p.pt_name
            FROM `{$st_table}` s
            INNER JOIN `{$pt_table}` p ON p.pt_id = s.pt_id
            WHERE {$where}
            ORDER BY s.st_id DESC
            LIMIT 100 ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_settlement_admin_summary')) {
    function lc_settlement_admin_summary()
    {
        if (!lc_db_installed()) {
            return array(
                'pending'      => 0,
                'pendingAmount'=> 0,
                'todayApproved'=> 0,
                'monthPaid'    => 0,
                'hold'         => 0,
                'rejected'     => 0,
            );
        }

        $table = lc_table('settlements');
        $today = date('Y-m-d');
        $month_start = date('Y-m-01');

        $row = lc_sql_fetch(" SELECT
            SUM(CASE WHEN st_status IN ('" . lc_sql_escape(LC_SETTLEMENT_PENDING) . "','" . lc_sql_escape(LC_SETTLEMENT_REVIEWING) . "') THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN st_status IN ('" . lc_sql_escape(LC_SETTLEMENT_PENDING) . "','" . lc_sql_escape(LC_SETTLEMENT_REVIEWING) . "') THEN st_amount ELSE 0 END) AS pending_amount,
            SUM(CASE WHEN st_status = '" . lc_sql_escape(LC_SETTLEMENT_APPROVED) . "' AND DATE(st_requested_at) = '{$today}' THEN st_approved_amount ELSE 0 END) AS today_approved,
            SUM(CASE WHEN st_status = '" . lc_sql_escape(LC_SETTLEMENT_PAID) . "' AND st_processed_at >= '{$month_start}' THEN st_approved_amount ELSE 0 END) AS month_paid,
            SUM(CASE WHEN st_status = '" . lc_sql_escape(LC_SETTLEMENT_HOLD) . "' THEN 1 ELSE 0 END) AS hold_cnt,
            SUM(CASE WHEN st_status = '" . lc_sql_escape(LC_SETTLEMENT_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt
            FROM `{$table}` ");

        return array(
            'pending'       => (int) ($row['pending_cnt'] ?? 0),
            'pendingAmount' => (int) ($row['pending_amount'] ?? 0),
            'todayApproved' => (int) ($row['today_approved'] ?? 0),
            'monthPaid'     => (int) ($row['month_paid'] ?? 0),
            'hold'          => (int) ($row['hold_cnt'] ?? 0),
            'rejected'      => (int) ($row['rejected_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_settlement_to_admin_api')) {
    function lc_settlement_to_admin_api(array $row)
    {
        $approved = (int) ($row['st_approved_amount'] ?? 0);
        if ($approved <= 0 && in_array($row['st_status'], array(LC_SETTLEMENT_APPROVED, LC_SETTLEMENT_PAID), true)) {
            $approved = (int) $row['st_amount'];
        }

        $account = (string) $row['st_bank_account'];
        if (strlen($account) > 6) {
            $account = substr($account, 0, 3) . '-***-' . substr($account, -3);
        }

        return array(
            'id'             => (int) $row['st_id'],
            'code'           => (string) $row['st_code'],
            'date'           => date('Y.m.d H:i', strtotime($row['st_requested_at'])),
            'partnerCode'    => (string) ($row['pt_code'] ?? ''),
            'partnerName'    => (string) ($row['pt_name'] ?? ''),
            'requestAmount'  => (int) $row['st_amount'],
            'approvedAmount' => $approved,
            'bank'           => (string) $row['st_bank_name'],
            'account'        => $account,
            'accountHolder'  => (string) $row['st_bank_holder'],
            'status'         => lc_settlement_status_label($row['st_status']),
            'statusCode'     => (string) $row['st_status'],
            'memo'           => (string) ($row['st_memo'] ?? ''),
        );
    }
}

if (!function_exists('lc_settlement_admin_update')) {
    /**
     * @return array{ok:bool,message:string,settlement?:array|null}
     */
    function lc_settlement_admin_update($st_id, $action, array $payload = array())
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.');
        }

        $st_id = (int) $st_id;
        $row = lc_settlement_get_by_id($st_id);
        if (!$row) {
            return array('ok' => false, 'message' => '정산 건을 찾을 수 없습니다.');
        }

        $table = lc_table('settlements');
        $pt_id = (int) $row['pt_id'];
        $status = (string) $row['st_status'];

        if ($action === 'review') {
            if (!in_array($status, array(LC_SETTLEMENT_PENDING), true)) {
                return array('ok' => false, 'message' => '검토할 수 없는 상태입니다.');
            }
            lc_sql_query(" UPDATE `{$table}` SET st_status = '" . lc_sql_escape(LC_SETTLEMENT_REVIEWING) . "' WHERE st_id = '{$st_id}' ", false);
        } elseif ($action === 'approve') {
            $approved_amount = isset($payload['approvedAmount']) ? (int) $payload['approvedAmount'] : (int) $row['st_amount'];
            if ($approved_amount <= 0) {
                return array('ok' => false, 'message' => '승인 금액이 올바르지 않습니다.');
            }
            lc_sql_query(" UPDATE `{$table}` SET
                st_status = '" . lc_sql_escape(LC_SETTLEMENT_APPROVED) . "',
                st_approved_amount = '{$approved_amount}'
                WHERE st_id = '{$st_id}' ", false);
        } elseif ($action === 'pay') {
            $approved_amount = (int) ($row['st_approved_amount'] > 0 ? $row['st_approved_amount'] : $row['st_amount']);
            if ($approved_amount <= 0) {
                return array('ok' => false, 'message' => '승인 금액이 없습니다.');
            }

            $partner = lc_get_partner_by_id($pt_id);
            if (!$partner || (int) $partner['pt_balance'] < $approved_amount) {
                return array('ok' => false, 'message' => '파트너 잔액이 부족합니다.');
            }

            $pt_table = lc_table('partners');
            $new_balance = (int) $partner['pt_balance'] - $approved_amount;
            lc_sql_query(" UPDATE `{$pt_table}` SET pt_balance = '{$new_balance}', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' ", false);
            lc_sql_query(" UPDATE `{$table}` SET
                st_status = '" . lc_sql_escape(LC_SETTLEMENT_PAID) . "',
                st_approved_amount = '{$approved_amount}',
                st_processed_at = NOW()
                WHERE st_id = '{$st_id}' ", false);
            $paid_row = lc_settlement_get_by_id($st_id);
            if (is_array($paid_row) && function_exists('lc_email_notify_on_settlement_paid')) {
                lc_email_notify_on_settlement_paid($paid_row);
            }
        } elseif ($action === 'hold') {
            $memo = isset($payload['memo']) ? trim((string) $payload['memo']) : '보류 처리';
            lc_sql_query(" UPDATE `{$table}` SET
                st_status = '" . lc_sql_escape(LC_SETTLEMENT_HOLD) . "',
                st_memo = CONCAT(st_memo, ' / ', '" . lc_sql_escape($memo) . "')
                WHERE st_id = '{$st_id}' ", false);
        } elseif ($action === 'reject') {
            $memo = isset($payload['memo']) ? trim((string) $payload['memo']) : '반려';
            lc_sql_query(" UPDATE `{$table}` SET
                st_status = '" . lc_sql_escape(LC_SETTLEMENT_REJECTED) . "',
                st_approved_amount = 0,
                st_memo = CONCAT(st_memo, ' / ', '" . lc_sql_escape($memo) . "'),
                st_processed_at = NOW()
                WHERE st_id = '{$st_id}' ", false);
        } else {
            return array('ok' => false, 'message' => '유효하지 않은 action입니다.');
        }

        $updated = lc_settlement_get_by_id($st_id);
        $updated['pt_code'] = '';
        $updated['pt_name'] = '';
        $partner = lc_get_partner_by_id($pt_id);
        if (is_array($partner)) {
            $updated['pt_code'] = $partner['pt_code'];
            $updated['pt_name'] = $partner['pt_name'];
        }

        return array(
            'ok'         => true,
            'message'    => '처리되었습니다.',
            'settlement' => lc_settlement_to_admin_api($updated),
        );
    }
}
