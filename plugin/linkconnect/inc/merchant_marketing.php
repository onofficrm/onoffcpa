<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_merchant_marketing_parse_filters')) {
    function lc_merchant_marketing_parse_filters(array $input = array())
    {
        $period = isset($input['period']) ? (int) $input['period'] : 30;
        if (!in_array($period, array(7, 30, 90), true)) {
            $period = 30;
        }

        return array(
            'period'   => $period,
            'dateFrom' => isset($input['dateFrom']) ? trim((string) $input['dateFrom']) : '',
            'dateTo'   => isset($input['dateTo']) ? trim((string) $input['dateTo']) : '',
            'cpId'     => isset($input['cpId']) ? (int) $input['cpId'] : 0,
        );
    }
}

if (!function_exists('lc_merchant_marketing_resolve_range')) {
    function lc_merchant_marketing_resolve_range(array $filters)
    {
        if (function_exists('lc_partner_analytics_resolve_range')) {
            return lc_partner_analytics_resolve_range($filters);
        }

        $dateTo = !empty($filters['dateTo']) ? $filters['dateTo'] : date('Y-m-d');
        $days = max(1, (int) ($filters['period'] ?? 30));
        $dateFrom = !empty($filters['dateFrom']) ? $filters['dateFrom'] : date('Y-m-d', strtotime($dateTo . ' -' . ($days - 1) . ' days'));

        return array($dateFrom, $dateTo);
    }
}

if (!function_exists('lc_merchant_marketing_cp_sql')) {
    function lc_merchant_marketing_cp_sql(array $filters, $alias = 'c')
    {
        if (!empty($filters['cpId'])) {
            return " AND {$alias}.cp_id = '" . (int) $filters['cpId'] . "' ";
        }

        return '';
    }
}

if (!function_exists('lc_merchant_marketing_summary')) {
    function lc_merchant_marketing_summary($mt_id, array $filters)
    {
        $empty = array(
            'activePartners' => 0,
            'promoLinks'     => 0,
            'totalClicks'    => 0,
            'uniqueVisitors' => 0,
            'totalDb'        => 0,
            'approvedDb'     => 0,
            'rejectedDb'     => 0,
            'convRate'       => 0,
            'approvalRate'   => 0,
            'activeCampaigns'=> 0,
        );

        if (!lc_db_installed()) {
            return $empty;
        }

        $mt_id = (int) $mt_id;
        $campaign_ids = lc_conversion_merchant_campaign_ids($mt_id);
        if (!$campaign_ids) {
            return $empty;
        }

        if (!empty($filters['cpId']) && !in_array((int) $filters['cpId'], $campaign_ids, true)) {
            return $empty;
        }

        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $cp_sql = lc_merchant_marketing_cp_sql($filters, 'c');
        $from_dt = lc_sql_escape($dateFrom) . ' 00:00:00';
        $to_dt = lc_sql_escape($dateTo) . ' 23:59:59';

        $lk_table = lc_table('links');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');

        $partner_row = lc_sql_fetch(" SELECT COUNT(DISTINCT src.pt_id) AS cnt FROM (
            SELECT cl.pt_id FROM `{$cl_table}` cl
            INNER JOIN `{$cp_table}` c ON c.cp_id = cl.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            UNION
            SELECT cv.pt_id FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
        ) src ", false);

        $link_row = lc_sql_fetch(" SELECT COUNT(DISTINCT lk.lk_id) AS cnt
            FROM `{$lk_table}` lk
            INNER JOIN `{$cp_table}` c ON c.cp_id = lk.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql} ", false);

        $click_row = lc_sql_fetch(" SELECT COUNT(*) AS clicks
            FROM `{$cl_table}` cl
            INNER JOIN `{$cp_table}` c ON c.cp_id = cl.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}' ", false);

        $visitor_hashes = array();
        $visitor_result = lc_sql_query(" SELECT cl.cl_ip
            FROM `{$cl_table}` cl
            INNER JOIN `{$cp_table}` c ON c.cp_id = cl.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}' ", false);
        if ($visitor_result) {
            while ($row = sql_fetch_array($visitor_result)) {
                $ip = $row['cl_ip'] ?? '';
                if (function_exists('lc_partner_analytics_visitor_hash')) {
                    $visitor_hashes[lc_partner_analytics_visitor_hash($ip)] = true;
                } else {
                    $visitor_hashes[hash('sha256', 'lc-visitor-v1|' . trim((string) $ip))] = true;
                }
            }
        }

        $cv_row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}' ", false);

        $active_cp_row = lc_sql_fetch(" SELECT COUNT(DISTINCT c.cp_id) AS cnt
            FROM `{$cp_table}` c
            LEFT JOIN `{$cl_table}` cl ON cl.cp_id = c.cp_id
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND (cl.cl_id IS NOT NULL OR cv.cv_id IS NOT NULL) ", false);

        $clicks = (int) ($click_row['clicks'] ?? 0);
        $total_db = (int) ($cv_row['total_cnt'] ?? 0);
        $approved = (int) ($cv_row['approved_cnt'] ?? 0);
        $rejected = (int) ($cv_row['rejected_cnt'] ?? 0);

        return array(
            'activePartners'  => (int) ($partner_row['cnt'] ?? 0),
            'promoLinks'      => (int) ($link_row['cnt'] ?? 0),
            'totalClicks'     => $clicks,
            'uniqueVisitors'  => count($visitor_hashes),
            'totalDb'         => $total_db,
            'approvedDb'      => $approved,
            'rejectedDb'      => $rejected,
            'convRate'        => $clicks > 0 ? round(($total_db / $clicks) * 100, 1) : 0,
            'approvalRate'    => $total_db > 0 ? round(($approved / $total_db) * 100, 1) : 0,
            'activeCampaigns' => (int) ($active_cp_row['cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_merchant_marketing_chart')) {
    function lc_merchant_marketing_chart($mt_id, array $filters)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        if (!lc_conversion_merchant_campaign_ids($mt_id)) {
            return array();
        }

        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $cp_sql = lc_merchant_marketing_cp_sql($filters, 'c');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $items = array();

        $start = strtotime($dateFrom);
        $end = strtotime($dateTo);
        for ($ts = $start; $ts <= $end; $ts += 86400) {
            $day = date('Y-m-d', $ts);
            $label = date('m.d', $ts);
            $from_dt = $day . ' 00:00:00';
            $to_dt = $day . ' 23:59:59';

            $click_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt
                FROM `{$cl_table}` cl
                INNER JOIN `{$cp_table}` c ON c.cp_id = cl.cp_id
                WHERE c.mt_id = '{$mt_id}' {$cp_sql}
                  AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}' ", false);

            $cv_row = lc_sql_fetch(" SELECT
                COUNT(*) AS db_cnt,
                SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approval_cnt
                FROM `{$cv_table}` cv
                INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
                WHERE c.mt_id = '{$mt_id}' {$cp_sql}
                  AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}' ", false);

            $items[] = array(
                'date'     => $label,
                'click'    => (int) ($click_row['cnt'] ?? 0),
                'db'       => (int) ($cv_row['db_cnt'] ?? 0),
                'approval' => (int) ($cv_row['approval_cnt'] ?? 0),
            );
        }

        return $items;
    }
}

if (!function_exists('lc_merchant_marketing_campaigns')) {
    function lc_merchant_marketing_campaigns($mt_id, array $filters, $limit = 50)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $limit = max(1, min(100, (int) $limit));
        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $cp_sql = lc_merchant_marketing_cp_sql($filters, 'c');
        $from_dt = lc_sql_escape($dateFrom) . ' 00:00:00';
        $to_dt = lc_sql_escape($dateTo) . ' 23:59:59';

        $lk_table = lc_table('links');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');

        $rows = array();
        $result = lc_sql_query(" SELECT c.cp_id, c.cp_name, c.cp_status,
            COUNT(DISTINCT lk.pt_id) AS partner_cnt,
            COUNT(DISTINCT lk.lk_id) AS link_cnt,
            COUNT(DISTINCT cl.cl_id) AS clicks,
            COUNT(DISTINCT cv.cv_id) AS db_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt
            FROM `{$cp_table}` c
            LEFT JOIN `{$lk_table}` lk ON lk.cp_id = c.cp_id
            LEFT JOIN `{$cl_table}` cl ON cl.lk_id = lk.lk_id
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            LEFT JOIN `{$cv_table}` cv ON cv.cp_id = c.cp_id
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
            GROUP BY c.cp_id
            ORDER BY clicks DESC, db_cnt DESC, c.cp_id DESC
            LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $clicks = (int) $row['clicks'];
                $db_cnt = (int) $row['db_cnt'];
                $approved = (int) $row['approved_cnt'];
                $rows[] = array(
                    'id'           => (int) $row['cp_id'],
                    'name'         => (string) $row['cp_name'],
                    'status'       => (string) ($row['cp_status'] ?? '') === LC_STATUS_ACTIVE ? '진행중' : '일시중지',
                    'partnerCount' => (int) $row['partner_cnt'],
                    'linkCount'    => (int) $row['link_cnt'],
                    'clicks'       => $clicks,
                    'dbCount'      => $db_cnt,
                    'approved'     => $approved,
                    'rejected'     => (int) $row['rejected_cnt'],
                    'convRate'     => $clicks > 0 ? round(($db_cnt / $clicks) * 100, 1) : 0,
                    'approvalRate' => $db_cnt > 0 ? round(($approved / $db_cnt) * 100, 1) : 0,
                    'isActive'     => $clicks > 0 || $db_cnt > 0,
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_marketing_partners')) {
    function lc_merchant_marketing_partners($mt_id, array $filters, $limit = 20)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $limit = max(1, min(50, (int) $limit));
        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $cp_sql = lc_merchant_marketing_cp_sql($filters, 'c');
        $from_dt = lc_sql_escape($dateFrom) . ' 00:00:00';
        $to_dt = lc_sql_escape($dateTo) . ' 23:59:59';

        $lk_table = lc_table('links');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');
        $pt_table = lc_table('partners');

        $rows = array();
        $result = lc_sql_query(" SELECT p.pt_id, p.pt_code, p.pt_name,
            COUNT(DISTINCT lk.cp_id) AS campaign_cnt,
            COUNT(DISTINCT lk.lk_id) AS link_cnt,
            COUNT(DISTINCT cl.cl_id) AS clicks,
            COUNT(DISTINCT cv.cv_id) AS db_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt,
            MAX(GREATEST(COALESCE(cl.cl_created_at, '1970-01-01'), COALESCE(cv.cv_created_at, '1970-01-01'))) AS last_activity
            FROM `{$pt_table}` p
            INNER JOIN `{$lk_table}` lk ON lk.pt_id = p.pt_id
            INNER JOIN `{$cp_table}` c ON c.cp_id = lk.cp_id AND c.mt_id = '{$mt_id}' {$cp_sql}
            LEFT JOIN `{$cl_table}` cl ON cl.lk_id = lk.lk_id
              AND cl.cl_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            LEFT JOIN `{$cv_table}` cv ON cv.lk_id = lk.lk_id
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            GROUP BY p.pt_id
            HAVING clicks > 0 OR db_cnt > 0
            ORDER BY clicks DESC, db_cnt DESC
            LIMIT {$limit} ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $clicks = (int) $row['clicks'];
                $db_cnt = (int) $row['db_cnt'];
                $approved = (int) $row['approved_cnt'];
                $last = (string) ($row['last_activity'] ?? '');
                $rows[] = array(
                    'id'           => (int) $row['pt_id'],
                    'code'         => (string) $row['pt_code'],
                    'name'         => (string) ($row['pt_name'] !== '' ? $row['pt_name'] : $row['pt_code']),
                    'campaignCount'=> (int) $row['campaign_cnt'],
                    'linkCount'    => (int) $row['link_cnt'],
                    'clicks'       => $clicks,
                    'dbCount'      => $db_cnt,
                    'approved'     => $approved,
                    'convRate'     => $clicks > 0 ? round(($db_cnt / $clicks) * 100, 1) : 0,
                    'approvalRate' => $db_cnt > 0 ? round(($approved / $db_cnt) * 100, 1) : 0,
                    'lastActivity' => $last !== '' && $last !== '1970-01-01 00:00:00' ? $last : '',
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_merchant_marketing_channels')) {
    function lc_merchant_marketing_channels($mt_id, array $filters, $limit = 8)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $mt_id = (int) $mt_id;
        $limit = max(1, min(20, (int) $limit));
        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $cp_sql = lc_merchant_marketing_cp_sql($filters, 'c');
        $from_dt = lc_sql_escape($dateFrom) . ' 00:00:00';
        $to_dt = lc_sql_escape($dateTo) . ' 23:59:59';

        $cv_table = lc_table('conversions');
        $cp_table = lc_table('campaigns');

        $channels = array();
        $result = lc_sql_query(" SELECT
            CASE WHEN cv.cv_channel = '' THEN '미지정' ELSE cv.cv_channel END AS channel,
            COUNT(*) AS db_cnt,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_cnt
            FROM `{$cv_table}` cv
            INNER JOIN `{$cp_table}` c ON c.cp_id = cv.cp_id
            WHERE c.mt_id = '{$mt_id}' {$cp_sql}
              AND cv.cv_created_at BETWEEN '{$from_dt}' AND '{$to_dt}'
            GROUP BY channel
            ORDER BY db_cnt DESC
            LIMIT {$limit} ", false);

        if ($result) {
            $total = 0;
            $items = array();
            while ($row = sql_fetch_array($result)) {
                $db_cnt = (int) $row['db_cnt'];
                $total += $db_cnt;
                $items[] = array(
                    'channel'  => (string) $row['channel'],
                    'dbCount'  => $db_cnt,
                    'approved' => (int) $row['approved_cnt'],
                );
            }
            foreach ($items as &$item) {
                $item['percentage'] = $total > 0 ? (int) round(($item['dbCount'] / $total) * 100) : 0;
            }
            unset($item);
            return $items;
        }

        return array();
    }
}

if (!function_exists('lc_merchant_marketing_filter_options')) {
    function lc_merchant_marketing_filter_options($mt_id)
    {
        if (!lc_db_installed()) {
            return array('campaigns' => array());
        }

        $mt_id = (int) $mt_id;
        $cp_table = lc_table('campaigns');
        $items = array();
        $result = lc_sql_query(" SELECT cp_id, cp_name, cp_status
            FROM `{$cp_table}`
            WHERE mt_id = '{$mt_id}'
            ORDER BY cp_id DESC ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'     => (int) $row['cp_id'],
                    'name'   => (string) $row['cp_name'],
                    'status' => (string) ($row['cp_status'] ?? '') === LC_STATUS_ACTIVE ? '진행중' : '일시중지',
                );
            }
        }

        return array('campaigns' => $items);
    }
}

if (!function_exists('lc_merchant_marketing_for_api')) {
    function lc_merchant_marketing_for_api($mt_id, array $filters = array())
    {
        $filters = lc_merchant_marketing_parse_filters($filters);
        list($dateFrom, $dateTo) = lc_merchant_marketing_resolve_range($filters);
        $summary = lc_merchant_marketing_summary($mt_id, $filters);

        return array(
            'summary'       => $summary,
            'range'         => array(
                'dateFrom' => $dateFrom,
                'dateTo'   => $dateTo,
                'period'   => (int) $filters['period'],
            ),
            'funnel'        => array(
                'links'    => (int) ($summary['promoLinks'] ?? 0),
                'clicks'   => (int) ($summary['totalClicks'] ?? 0),
                'received' => (int) ($summary['totalDb'] ?? 0),
                'approved' => (int) ($summary['approvedDb'] ?? 0),
            ),
            'chart'         => lc_merchant_marketing_chart($mt_id, $filters),
            'campaigns'     => lc_merchant_marketing_campaigns($mt_id, $filters),
            'partners'      => lc_merchant_marketing_partners($mt_id, $filters),
            'channels'      => lc_merchant_marketing_channels($mt_id, $filters),
            'filterOptions' => lc_merchant_marketing_filter_options($mt_id),
        );
    }
}
