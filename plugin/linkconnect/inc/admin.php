<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_admin_partner_status_ui')) {
    function lc_admin_partner_status_ui($status)
    {
        $map = array(
            LC_PARTNER_STATUS_PENDING   => '승인대기',
            LC_PARTNER_STATUS_ACTIVE    => '활성',
            LC_PARTNER_STATUS_SUSPENDED => '차단',
        );

        return isset($map[$status]) ? $map[$status] : (string) $status;
    }
}

if (!function_exists('lc_admin_merchant_status_ui')) {
    function lc_admin_merchant_status_ui($status)
    {
        $map = array(
            LC_MERCHANT_STATUS_PENDING   => '승인대기',
            LC_MERCHANT_STATUS_ACTIVE    => '운영중',
            LC_MERCHANT_STATUS_SUSPENDED => '일시중지',
        );

        return isset($map[$status]) ? $map[$status] : (string) $status;
    }
}

if (!function_exists('lc_admin_list_partners')) {
    function lc_admin_list_partners(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('partners');
        $cv_table = lc_table('conversions');
        $where = '1=1';

        if (!empty($filters['status'])) {
            $where .= " AND p.pt_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (p.pt_code LIKE '%{$q}%' OR p.pt_name LIKE '%{$q}%' OR p.mb_id LIKE '%{$q}%') ";
        }

        $sql = " SELECT p.*,
            COUNT(cv.cv_id) AS total_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS canceled_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS confirmed_profit
            FROM `{$table}` p
            LEFT JOIN `{$cv_table}` cv ON cv.pt_id = p.pt_id
            WHERE {$where}
            GROUP BY p.pt_id
            ORDER BY p.pt_id DESC ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_admin_partner_to_api')) {
    function lc_admin_partner_to_api(array $row)
    {
        $total = (int) ($row['total_db'] ?? 0);
        $approved = (int) ($row['approved_db'] ?? 0);
        $rate = $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '-';

        return array(
            'id'              => (int) $row['pt_id'],
            'code'            => (string) $row['pt_code'],
            'name'            => (string) $row['pt_name'],
            'memberId'        => (string) $row['mb_id'],
            'date'            => date('Y.m.d', strtotime($row['pt_created_at'])),
            'totalDb'         => $total,
            'approvedDb'      => $approved,
            'canceledDb'      => (int) ($row['canceled_db'] ?? 0),
            'rate'            => $rate,
            'confirmedProfit' => (int) ($row['confirmed_profit'] ?? 0),
            'balance'         => (int) $row['pt_balance'],
            'status'          => lc_admin_partner_status_ui($row['pt_status']),
            'statusCode'      => (string) $row['pt_status'],
        ) + (function_exists('lc_admin_entity_meta_for_api') ? lc_admin_entity_meta_for_api($row, 'partner') : array());
    }
}

if (!function_exists('lc_admin_list_merchants')) {
    function lc_admin_list_merchants(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array();
        }

        $table = lc_table('merchants');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $where = '1=1';

        if (!empty($filters['status'])) {
            $where .= " AND m.mt_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        if (!empty($filters['q'])) {
            $q = lc_sql_escape($filters['q']);
            $where .= " AND (m.mt_code LIKE '%{$q}%' OR m.mt_company LIKE '%{$q}%' OR m.mb_id LIKE '%{$q}%') ";
        }

        $sql = " SELECT m.*,
            COUNT(DISTINCT c.cp_id) AS campaigns,
            COUNT(cv.cv_id) AS total_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS canceled_db,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS spend
            FROM `{$table}` m
            LEFT JOIN `{$cp_table}` c ON c.mt_id = m.mt_id
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
            WHERE {$where}
            GROUP BY m.mt_id
            ORDER BY m.mt_id DESC ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_admin_merchant_to_api')) {
    function lc_admin_merchant_to_api(array $row)
    {
        $total = (int) ($row['total_db'] ?? 0);
        $approved = (int) ($row['approved_db'] ?? 0);
        $rate = $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '-';
        $balance = (int) $row['mt_balance'];

        return array(
            'id'         => (int) $row['mt_id'],
            'code'       => (string) $row['mt_code'],
            'name'       => (string) $row['mt_company'],
            'memberId'   => (string) $row['mb_id'],
            'date'       => date('Y.m.d', strtotime($row['mt_created_at'])),
            'campaigns'  => (int) ($row['campaigns'] ?? 0),
            'totalDb'    => $total,
            'approvedDb' => $approved,
            'canceledDb' => (int) ($row['canceled_db'] ?? 0),
            'rate'       => $rate,
            'balance'    => $balance,
            'spend'      => (int) ($row['spend'] ?? 0),
            'status'     => lc_admin_merchant_status_ui($row['mt_status']),
            'statusCode' => (string) $row['mt_status'],
        ) + (function_exists('lc_admin_entity_meta_for_api') ? lc_admin_entity_meta_for_api($row, 'merchant') : array());
    }
}

if (!function_exists('lc_admin_partner_summary')) {
    function lc_admin_partner_summary()
    {
        if (!lc_db_installed()) {
            return array('total' => 0, 'active' => 0, 'pending' => 0);
        }

        $table = lc_table('partners');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "' THEN 1 ELSE 0 END) AS active_cnt,
            SUM(CASE WHEN pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_PENDING) . "' THEN 1 ELSE 0 END) AS pending_cnt
            FROM `{$table}` ");

        return array(
            'total'   => (int) ($row['total_cnt'] ?? 0),
            'active'  => (int) ($row['active_cnt'] ?? 0),
            'pending' => (int) ($row['pending_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_admin_merchant_summary')) {
    function lc_admin_merchant_summary()
    {
        if (!lc_db_installed()) {
            return array('total' => 0, 'active' => 0, 'pending' => 0, 'lowBalance' => 0);
        }

        $table = lc_table('merchants');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN mt_status = '" . lc_sql_escape(LC_MERCHANT_STATUS_ACTIVE) . "' THEN 1 ELSE 0 END) AS active_cnt,
            SUM(CASE WHEN mt_status = '" . lc_sql_escape(LC_MERCHANT_STATUS_PENDING) . "' THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN mt_balance < 500000 AND mt_status = '" . lc_sql_escape(LC_MERCHANT_STATUS_ACTIVE) . "' THEN 1 ELSE 0 END) AS low_balance_cnt
            FROM `{$table}` ");

        return array(
            'total'      => (int) ($row['total_cnt'] ?? 0),
            'active'     => (int) ($row['active_cnt'] ?? 0),
            'pending'    => (int) ($row['pending_cnt'] ?? 0),
            'lowBalance' => (int) ($row['low_balance_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_admin_list_conversions')) {
    function lc_admin_list_conversions(array $filters = array(), $limit = 100)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');
        $mt_table = lc_table('merchants');
        $where = '1=1';

        if (!empty($filters['status'])) {
            $where .= " AND cv.cv_status = '" . lc_sql_escape($filters['status']) . "' ";
        }

        $limit = max(1, min(200, (int) $limit));

        $sql = " SELECT cv.*, c.cp_name, p.pt_code, m.mt_company
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            LEFT JOIN `{$pt_table}` p ON p.pt_id = cv.pt_id
            LEFT JOIN `{$mt_table}` m ON m.mt_id = c.mt_id
            WHERE {$where}
            ORDER BY cv.cv_id DESC
            LIMIT {$limit} ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_admin_conversion_to_api')) {
    function lc_admin_conversion_to_api(array $row)
    {
        return array(
            'id'         => (string) $row['cv_code'],
            'cvId'       => (int) $row['cv_id'],
            'date'       => date('m.d H:i', strtotime($row['cv_created_at'])),
            'campaign'   => (string) ($row['cp_name'] ?? ''),
            'partner'    => (string) ($row['pt_code'] ?? '-'),
            'advertiser' => (string) ($row['mt_company'] ?? '-'),
            'customer'   => (string) $row['cv_name'],
            'status'     => lc_conversion_status_label($row['cv_status']),
            'statusCode' => (string) $row['cv_status'],
            'price'      => (int) $row['cv_price'],
        );
    }
}

if (!function_exists('lc_admin_dashboard_data')) {
    function lc_admin_dashboard_data()
    {
        if (!lc_db_installed()) {
            return null;
        }

        $cv_table = lc_table('conversions');
        $today = date('Y-m-d');

        $today_row = lc_sql_fetch(" SELECT
            COUNT(*) AS received,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS revenue
            FROM `{$cv_table}` WHERE DATE(cv_created_at) = '{$today}' ");

        $pending_cv = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$cv_table}` WHERE cv_status = '" . lc_sql_escape(LC_STATUS_PENDING) . "' ");
        $pending_charge = 0;
        if (function_exists('lc_wallet_count_pending')) {
            $pending_charge = lc_wallet_count_pending();
        }

        $chart = array();
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $label = date('m.d', strtotime($day));
            $row = lc_sql_fetch(" SELECT
                COUNT(*) AS received,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS revenue
                FROM `{$cv_table}` WHERE DATE(cv_created_at) = '{$day}' ");
            $chart[] = array(
                'date'    => $label,
                'received'=> (int) ($row['received'] ?? 0),
                'approved'=> (int) ($row['approved'] ?? 0),
                'rejected'=> (int) ($row['rejected'] ?? 0),
                'revenue' => (int) ($row['revenue'] ?? 0),
            );
        }

        $received = (int) ($today_row['received'] ?? 0);
        $approved = (int) ($today_row['approved'] ?? 0);

        $settlement_summary = function_exists('lc_settlement_admin_summary') ? lc_settlement_admin_summary() : array('pending' => 0);
        $inspection_summary = function_exists('lc_conversion_inspection_summary') ? lc_conversion_inspection_summary() : array('pending' => 0);

        $pending_call_requests = 0;
        $pending_recording_requests = 0;
        if (lc_db_table_exists(lc_table('call_requests'))) {
            $car = lc_table('call_requests');
            $pending_call_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$car}` WHERE car_status = '" . lc_sql_escape(LC_CALL_REQ_PENDING) . "' ", false);
            $pending_call_requests = (int) ($pending_call_row['cnt'] ?? 0);
        }
        if (lc_db_table_exists(lc_table('call_recording_requests'))) {
            $crr = lc_table('call_recording_requests');
            $pending_rec_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$crr}` WHERE crr_status = '" . lc_sql_escape(LC_CALL_REC_REQ_PENDING) . "' ", false);
            $pending_recording_requests = (int) ($pending_rec_row['cnt'] ?? 0);
        }

        $pending_contracts = 0;
        $mc_table = function_exists('lc_merchant_contract_table') ? lc_merchant_contract_table() : lc_table('merchant_contracts');
        if ($mc_table && lc_db_table_exists($mc_table)) {
            $review_status = defined('LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING')
                ? LC_MERCHANT_CONTRACT_STATUS_REVIEW_PENDING
                : 'review_pending';
            $pending_contract_row = lc_sql_fetch(
                " SELECT COUNT(*) AS cnt FROM `{$mc_table}` WHERE mc_status = '" . lc_sql_escape($review_status) . "' ",
                false
            );
            $pending_contracts = (int) ($pending_contract_row['cnt'] ?? 0);
        }

        return array(
            'summary' => array(
                'todayReceived'  => $received,
                'todayApproved'  => $approved,
                'todayRejected'  => (int) ($today_row['rejected'] ?? 0),
                'todayRate'      => $received > 0 ? round(($approved / $received) * 100, 1) : 0,
                'todayRevenue'   => (int) ($today_row['revenue'] ?? 0),
                'pendingDb'      => (int) ($pending_cv['cnt'] ?? 0),
                'pendingCharge'  => $pending_charge,
                'pendingPartners'=> (int) (lc_admin_partner_summary()['pending'] ?? 0),
                'pendingMerchants'=> (int) (lc_admin_merchant_summary()['pending'] ?? 0),
                'pendingSettlements' => (int) ($settlement_summary['pending'] ?? 0),
                'pendingInspections' => (int) ($inspection_summary['pending'] ?? 0),
                'pendingCallRequests' => $pending_call_requests,
                'pendingRecordingRequests' => $pending_recording_requests,
                'pendingContracts' => $pending_contracts,
            ),
            'chart7d' => $chart,
            'recent'  => array_map('lc_admin_conversion_to_api', lc_admin_list_conversions(array(), 8)),
            'partners'=> lc_admin_partner_summary(),
            'merchants'=> lc_admin_merchant_summary(),
            'campaignTop' => lc_admin_campaign_performance(5),
            'partnerTop5' => lc_admin_partner_top(5),
            'advertiserTop5' => lc_admin_advertiser_top(5),
            'recentCancels' => array_map('lc_conversion_to_inspection_api', array_slice(lc_conversion_list_for_inspection(array('status' => 'pending')), 0, 5)),
            'apiErrors' => function_exists('lc_api_log_list') ? array_map(function ($row) {
                return array(
                    'time' => date('H:i:s', strtotime($row['al_created_at'] ?? 'now')),
                    'name' => (string) ($row['al_client_name'] ?? ''),
                    'code' => (string) ($row['al_status'] ?? ''),
                    'msg'  => (string) ($row['al_error'] ?? ''),
                    'alId' => (int) ($row['al_id'] ?? 0),
                );
            }, lc_api_log_list(array('errors_only' => true, 'since_hours' => 24), 5)) : array(),
        );
    }
}

if (!function_exists('lc_admin_campaign_performance')) {
    function lc_admin_campaign_performance($limit = 5)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $limit = max(1, min(20, (int) $limit));
        $cp_table = lc_table('campaigns');
        $cv_table = lc_table('conversions');
        $mt_table = lc_table('merchants');
        $rows = array();
        $result = lc_sql_query(" SELECT c.cp_name AS name, m.mt_company AS advertiser,
            COUNT(cv.cv_id) AS total,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS canceled,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS revenue
            FROM `{$cp_table}` c
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
            LEFT JOIN `{$mt_table}` m ON m.mt_id = c.mt_id
            GROUP BY c.cp_id
            HAVING total > 0
            ORDER BY revenue DESC
            LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $total = (int) $row['total'];
                $approved = (int) $row['approved'];
                $rows[] = array(
                    'name'      => (string) $row['name'],
                    'advertiser'=> (string) ($row['advertiser'] ?? '-'),
                    'total'     => $total,
                    'approved'  => $approved,
                    'canceled'  => (int) $row['canceled'],
                    'rate'      => $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '-',
                    'revenue'   => (int) $row['revenue'],
                    'status'    => '정상',
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_admin_partner_top')) {
    function lc_admin_partner_top($limit = 5)
    {
        $rows = lc_admin_list_partners(array());
        usort($rows, function ($a, $b) {
            return (int) ($b['confirmed_profit'] ?? 0) <=> (int) ($a['confirmed_profit'] ?? 0);
        });

        $items = array();
        foreach (array_slice($rows, 0, $limit) as $row) {
            $total = (int) ($row['total_db'] ?? 0);
            $approved = (int) ($row['approved_db'] ?? 0);
            $items[] = array(
                'code'   => (string) $row['pt_code'],
                'total'  => $total,
                'approved'=> $approved,
                'rate'   => $total > 0 ? round(($approved / $total) * 100, 1) . '%' : '-',
                'profit' => (int) ($row['confirmed_profit'] ?? 0),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_admin_advertiser_top')) {
    function lc_admin_advertiser_top($limit = 5)
    {
        $rows = lc_admin_list_merchants(array());
        usort($rows, function ($a, $b) {
            return (int) ($b['spend'] ?? 0) <=> (int) ($a['spend'] ?? 0);
        });

        $items = array();
        foreach (array_slice($rows, 0, $limit) as $row) {
            $total = (int) ($row['total_db'] ?? 0);
            $approved = (int) ($row['approved_db'] ?? 0);
            $items[] = array(
                'name'    => (string) $row['mt_company'],
                'total'   => $total,
                'approved'=> $approved,
                'spend'   => (int) ($row['spend'] ?? 0),
                'balance' => (int) $row['mt_balance'],
            );
        }

        return $items;
    }
}

if (!function_exists('lc_admin_partners_for_api')) {
    function lc_admin_partners_for_api(array $filters = array())
    {
        if (lc_db_installed()) {
            return array_map('lc_admin_partner_to_api', lc_admin_list_partners($filters));
        }

        if (!function_exists('lc_sample_admin_partners')) {
            return array();
        }

        $items = array();
        foreach (lc_sample_admin_partners() as $row) {
            $items[] = array(
                'id'              => 0,
                'code'            => (string) $row['code'],
                'name'            => (string) $row['name'],
                'memberId'        => '',
                'date'            => '',
                'totalDb'         => (int) $row['received'],
                'approvedDb'      => (int) $row['approved'],
                'canceledDb'      => 0,
                'rate'            => (string) $row['rate'],
                'confirmedProfit' => 0,
                'balance'         => 0,
                'status'          => (string) $row['status'],
                'statusCode'      => LC_PARTNER_STATUS_ACTIVE,
            );
        }

        return $items;
    }
}

if (!function_exists('lc_admin_merchants_for_api')) {
    function lc_admin_merchants_for_api(array $filters = array())
    {
        if (lc_db_installed()) {
            return array_map('lc_admin_merchant_to_api', lc_admin_list_merchants($filters));
        }

        if (!function_exists('lc_sample_admin_merchants')) {
            return array();
        }

        $items = array();
        foreach (lc_sample_admin_merchants() as $row) {
            $items[] = array(
                'id'         => 0,
                'code'       => '',
                'name'       => (string) $row['name'],
                'memberId'   => '',
                'date'       => '',
                'campaigns'  => 0,
                'totalDb'    => (int) $row['received'],
                'approvedDb' => (int) $row['approved'],
                'canceledDb' => 0,
                'rate'       => (string) $row['rate'],
                'balance'    => 0,
                'spend'      => 0,
                'status'     => (string) $row['status'],
                'statusCode' => LC_MERCHANT_STATUS_ACTIVE,
            );
        }

        return $items;
    }
}
