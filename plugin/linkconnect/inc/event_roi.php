<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_event_roi_for_api')) {
    function lc_event_roi_for_api($ev_id = 0)
    {
        if (!lc_db_installed()) {
            return array('items' => array(), 'summary' => array());
        }

        $ev_id = (int) $ev_id;
        $er = lc_table('event_rewards');
        $ev = lc_table('events');
        $cv = lc_table('conversions');
        $ep = lc_table('event_participants');

        $where = $ev_id > 0 ? ' WHERE e.ev_id = ' . $ev_id : '';
        $items = array();
        $result = lc_sql_query("
            SELECT e.ev_id, e.ev_code, e.ev_title, e.ev_status,
                COALESCE(SUM(CASE WHEN er.er_status = 'paid' THEN er.er_amount ELSE 0 END), 0) AS paid_rewards,
                COALESCE(SUM(CASE WHEN er.er_status = 'pending' THEN er.er_amount ELSE 0 END), 0) AS pending_rewards,
                (SELECT COUNT(*) FROM `{$ep}` ep2 WHERE ep2.ev_id = e.ev_id) AS participants
            FROM `{$ev}` e
            LEFT JOIN `{$er}` er ON er.ev_id = e.ev_id
            {$where}
            GROUP BY e.ev_id
            ORDER BY e.ev_id DESC
            LIMIT 50
        ", false);

        $total_reward = 0;
        $total_revenue = 0;
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $ev_id_row = (int) ($row['ev_id'] ?? 0);
                $paid = (int) ($row['paid_rewards'] ?? 0);
                $pending = (int) ($row['pending_rewards'] ?? 0);
                $participants = (int) ($row['participants'] ?? 0);

                $cv_stats = lc_sql_fetch("
                    SELECT
                        COUNT(*) AS total_db,
                        SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved_db,
                        SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN cv_price ELSE 0 END) AS revenue
                    FROM `{$cv}` cv
                    INNER JOIN `{$ep}` ep ON ep.pt_id = cv.pt_id AND ep.ev_id = {$ev_id_row}
                    WHERE cv.cv_created_at >= (SELECT ev_created_at FROM `{$ev}` WHERE ev_id = {$ev_id_row} LIMIT 1)
                ", false);

                $total_db = (int) ($cv_stats['total_db'] ?? 0);
                $approved_db = (int) ($cv_stats['approved_db'] ?? 0);
                $revenue = (int) ($cv_stats['revenue'] ?? 0);
                $reward_total = $paid + $pending;
                $roi = $reward_total > 0 ? round((($revenue - $reward_total) / $reward_total) * 100, 1) : 0;

                $items[] = array(
                    'evId'         => $ev_id_row,
                    'code'         => (string) ($row['ev_code'] ?? ''),
                    'title'        => (string) ($row['ev_title'] ?? ''),
                    'status'       => (string) ($row['ev_status'] ?? ''),
                    'participants' => $participants,
                    'totalDb'      => $total_db,
                    'approvedDb'   => $approved_db,
                    'revenue'      => $revenue,
                    'paidRewards'  => $paid,
                    'pendingRewards'=> $pending,
                    'roi'          => $roi,
                );
                $total_reward += $reward_total;
                $total_revenue += $revenue;
            }
        }

        return array(
            'items'   => $items,
            'summary' => array(
                'totalReward'  => $total_reward,
                'totalRevenue' => $total_revenue,
                'netRoi'       => $total_reward > 0 ? round((($total_revenue - $total_reward) / $total_reward) * 100, 1) : 0,
            ),
        );
    }
}
