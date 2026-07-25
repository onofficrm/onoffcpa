<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_partner_tier_label')) {
    function lc_partner_tier_label(array $row)
    {
        $tier = trim((string) ($row['pt_tier'] ?? ''));
        if ($tier !== '') {
            return $tier;
        }
        return lc_partner_tier_calculate($row);
    }
}

if (!function_exists('lc_partner_tier_calculate')) {
    function lc_partner_tier_calculate(array $row)
    {
        $total = (int) ($row['total_db'] ?? 0);
        $approved = (int) ($row['approved_db'] ?? 0);
        $canceled = (int) ($row['canceled_db'] ?? 0);
        $profit = (int) ($row['confirmed_profit'] ?? 0);
        $approval_rate = $total > 0 ? ($approved / $total) * 100 : 0;
        $cancel_rate = $total > 0 ? ($canceled / $total) * 100 : 0;

        if ($approval_rate >= 70 && $cancel_rate <= 15 && $profit >= 1000000) {
            return 'gold';
        }
        if ($approval_rate >= 50 && $cancel_rate <= 30 && $profit >= 300000) {
            return 'silver';
        }
        return 'bronze';
    }
}

if (!function_exists('lc_partner_tier_bonus_rate')) {
    function lc_partner_tier_bonus_rate($tier)
    {
        $map = array(
            'gold'   => 0.05,
            'silver' => 0.03,
            'bronze' => 0,
        );
        $tier = strtolower(trim((string) $tier));
        return isset($map[$tier]) ? $map[$tier] : 0;
    }
}

if (!function_exists('lc_partner_tier_label_ko')) {
    function lc_partner_tier_label_ko($tier)
    {
        $map = array(
            'gold'   => 'Gold',
            'silver' => 'Silver',
            'bronze' => 'Bronze',
        );
        $tier = strtolower(trim((string) $tier));
        return isset($map[$tier]) ? $map[$tier] : 'Bronze';
    }
}

if (!function_exists('lc_partner_tier_refresh')) {
    function lc_partner_tier_refresh($pt_id = 0)
    {
        if (!lc_db_installed()) {
            return 0;
        }
        $table = lc_table('partners');
        $cv = lc_table('conversions');
        $where = $pt_id > 0 ? ' WHERE p.pt_id = ' . (int) $pt_id : '';
        $updated = 0;
        $result = lc_sql_query("
            SELECT p.pt_id,
                COUNT(cv.cv_id) AS total_db,
                SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_db,
                SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS canceled_db,
                SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv.cv_price ELSE 0 END) AS confirmed_profit
            FROM `{$table}` p
            LEFT JOIN `{$cv}` cv ON cv.pt_id = p.pt_id
            {$where}
            GROUP BY p.pt_id
        ", false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $tier = lc_partner_tier_calculate($row);
                lc_sql_query(" UPDATE `{$table}` SET pt_tier = '" . lc_sql_escape($tier) . "' WHERE pt_id = " . (int) $row['pt_id'] . " ", false);
                $updated++;
            }
        }
        return $updated;
    }
}
