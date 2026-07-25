<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('LC_EVENT_ACTIVE', 'active');
define('LC_EVENT_CLOSING', 'closing');
define('LC_EVENT_ENDED', 'ended');
define('LC_EVENT_SCHEDULED', 'scheduled');

if (!function_exists('lc_event_table')) {
    function lc_event_table()
    {
        return lc_table('events');
    }
}

if (!function_exists('lc_event_status_ui')) {
    function lc_event_status_ui($status)
    {
        $labels = array(
            LC_EVENT_ACTIVE    => '진행중',
            LC_EVENT_CLOSING   => '마감임박',
            LC_EVENT_ENDED     => '종료',
            LC_EVENT_SCHEDULED => '예정',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_event_generate_code')) {
    function lc_event_generate_code()
    {
        if (!lc_db_installed()) {
            return 'EVT-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        }

        $table = lc_event_table();
        for ($i = 0; $i < 20; $i++) {
            $code = 'EVT-' . str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
            $row = lc_sql_fetch(" SELECT ev_id FROM `{$table}` WHERE ev_code = '" . lc_sql_escape($code) . "' LIMIT 1 ", false);
            if (!is_array($row) || empty($row['ev_id'])) {
                return $code;
            }
        }

        return 'EVT-' . date('His');
    }
}

if (!function_exists('lc_event_decode_badges')) {
    function lc_event_decode_badges($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

if (!function_exists('lc_event_encode_badges')) {
    function lc_event_encode_badges($badges)
    {
        if (!is_array($badges)) {
            return '';
        }

        $items = array_values(array_filter(array_map('trim', array_map('strval', $badges))));

        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('lc_event_row_to_public_card')) {
    function lc_event_row_to_public_card(array $row)
    {
        return array(
            'id'      => (string) ($row['ev_code'] ?? ''),
            'badges'  => lc_event_decode_badges($row['ev_badges'] ?? ''),
            'title'   => (string) ($row['ev_title'] ?? ''),
            'desc'    => (string) ($row['ev_desc'] ?? ''),
            'period'  => (string) ($row['ev_period'] ?? ''),
            'product' => (string) ($row['ev_product'] ?? ''),
            'benefit' => (string) ($row['ev_benefit'] ?? ''),
            'ribbon'  => (string) ($row['ev_ribbon'] ?? ''),
        );
    }
}

if (!function_exists('lc_event_row_to_admin')) {
    function lc_event_row_to_admin(array $row)
    {
        $ev_id = (int) ($row['ev_id'] ?? 0);
        $stats = lc_event_admin_row_stats($ev_id);

        return array(
            'id'            => $ev_id,
            'code'          => (string) ($row['ev_code'] ?? ''),
            'title'         => (string) ($row['ev_title'] ?? ''),
            'type'          => (string) ($row['ev_type'] ?? ''),
            'desc'          => (string) ($row['ev_desc'] ?? ''),
            'period'        => (string) ($row['ev_period'] ?? ''),
            'product'       => (string) ($row['ev_product'] ?? ''),
            'benefit'       => (string) ($row['ev_benefit'] ?? ''),
            'badges'        => lc_event_decode_badges($row['ev_badges'] ?? ''),
            'ribbon'        => (string) ($row['ev_ribbon'] ?? ''),
            'status'        => lc_event_status_ui($row['ev_status'] ?? ''),
            'statusCode'    => (string) ($row['ev_status'] ?? ''),
            'target'        => (string) ($row['ev_target'] ?? 'partner'),
            'campaigns'     => (string) ($row['ev_campaign_labels'] ?? ''),
            'campaignIds'   => (string) ($row['ev_campaign_ids'] ?? ''),
            'partners'      => (int) $stats['partners'],
            'received'      => (int) $stats['received'],
            'approved'      => (int) $stats['approved'],
            'rewardPending' => (string) $stats['rewardPending'],
            'featured'      => !empty($row['ev_featured']),
            'sort'          => (int) ($row['ev_sort'] ?? 0),
        );
    }
}

if (!function_exists('lc_event_admin_summary')) {
    function lc_event_admin_summary()
    {
        if (!lc_db_table_exists(lc_event_table())) {
            return array(
                'total'     => 0,
                'active'    => 0,
                'closing'   => 0,
                'scheduled' => 0,
                'ended'     => 0,
            );
        }

        $table = lc_event_table();
        $row = lc_sql_fetch("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ev_status = '" . LC_EVENT_ACTIVE . "' THEN 1 ELSE 0 END) AS active_cnt,
                SUM(CASE WHEN ev_status = '" . LC_EVENT_CLOSING . "' THEN 1 ELSE 0 END) AS closing_cnt,
                SUM(CASE WHEN ev_status = '" . LC_EVENT_SCHEDULED . "' THEN 1 ELSE 0 END) AS scheduled_cnt,
                SUM(CASE WHEN ev_status = '" . LC_EVENT_ENDED . "' THEN 1 ELSE 0 END) AS ended_cnt
            FROM `{$table}`
        ", false);

        return array(
            'total'     => (int) ($row['total'] ?? 0),
            'active'    => (int) ($row['active_cnt'] ?? 0),
            'closing'   => (int) ($row['closing_cnt'] ?? 0),
            'scheduled' => (int) ($row['scheduled_cnt'] ?? 0),
            'ended'     => (int) ($row['ended_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_event_public_summary')) {
    function lc_event_public_summary()
    {
        if (!lc_db_table_exists(lc_event_table())) {
            return function_exists('lc_sample_event_summary') ? lc_sample_event_summary() : array();
        }

        $summary = lc_event_admin_summary();
        $bonus = 0;
        $priceUp = 0;
        $table = lc_event_table();
        $result = lc_sql_query("
            SELECT ev_type, COUNT(*) AS cnt
            FROM `{$table}`
            WHERE ev_status IN ('" . LC_EVENT_ACTIVE . "', '" . LC_EVENT_CLOSING . "')
            GROUP BY ev_type
        ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $type = (string) ($row['ev_type'] ?? '');
                $cnt = (int) ($row['cnt'] ?? 0);
                if (strpos($type, '보너스') !== false) {
                    $bonus += $cnt;
                }
                if (strpos($type, '단가') !== false) {
                    $priceUp += $cnt;
                }
            }
        }

        return array(
            array('label' => '진행 중 이벤트', 'value' => (string) ($summary['active'] + $summary['closing']), 'suffix' => '개', 'icon' => 'megaphone'),
            array('label' => '보너스 지급 캠페인', 'value' => (string) $bonus, 'suffix' => '개', 'icon' => 'gift'),
            array('label' => '단가 상승 상품', 'value' => (string) $priceUp, 'suffix' => '개', 'icon' => 'trending'),
            array('label' => '마감 임박 이벤트', 'value' => (string) $summary['closing'], 'suffix' => '개', 'icon' => 'clock'),
        );
    }
}

if (!function_exists('lc_event_admin_for_api')) {
    function lc_event_admin_for_api(array $filters = array())
    {
        if (!lc_db_table_exists(lc_event_table())) {
            return array();
        }

        lc_event_ensure_seed();

        $table = lc_event_table();
        $where = array('1=1');
        $q = trim((string) ($filters['q'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($q !== '') {
            $esc = lc_sql_escape($q);
            $where[] = "(ev_title LIKE '%{$esc}%' OR ev_code LIKE '%{$esc}%' OR ev_product LIKE '%{$esc}%' OR ev_campaign_labels LIKE '%{$esc}%')";
        }

        if ($status !== '') {
            $where[] = "ev_status = '" . lc_sql_escape($status) . "'";
        }

        $sql = "
            SELECT *
            FROM `{$table}`
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ev_sort DESC, ev_id DESC
            LIMIT 200
        ";

        $items = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_event_row_to_admin($row);
            }
        }

        return $items;
    }
}

if (!function_exists('lc_event_public_items')) {
    function lc_event_public_items(array $filters = array())
    {
        if (!lc_db_table_exists(lc_event_table())) {
            $cards = function_exists('lc_sample_event_cards') ? lc_sample_event_cards() : array();
            $items = array();
            foreach ($cards as $i => $card) {
                $items[] = array(
                    'id'      => 'EVT-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'badges'  => $card['badges'] ?? array(),
                    'title'   => (string) ($card['title'] ?? ''),
                    'desc'    => (string) ($card['desc'] ?? ''),
                    'period'  => (string) ($card['period'] ?? ''),
                    'product' => (string) ($card['product'] ?? ''),
                    'benefit' => (string) ($card['benefit'] ?? ''),
                    'ribbon'  => (string) ($card['ribbon'] ?? ''),
                );
            }

            return $items;
        }

        lc_event_ensure_seed();

        $table = lc_event_table();
        $where = array("ev_status IN ('" . LC_EVENT_ACTIVE . "', '" . LC_EVENT_CLOSING . "', '" . LC_EVENT_SCHEDULED . "')");
        $q = trim((string) ($filters['q'] ?? ''));

        if ($q !== '') {
            $esc = lc_sql_escape($q);
            $where[] = "(ev_title LIKE '%{$esc}%' OR ev_product LIKE '%{$esc}%')";
        }

        $sql = "
            SELECT *
            FROM `{$table}`
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ev_featured DESC, ev_sort DESC, ev_id DESC
            LIMIT 100
        ";

        $items = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = lc_event_row_to_public_card($row);
            }
        }

        return $items;
    }
}

if (!function_exists('lc_event_get_by_code')) {
    function lc_event_get_by_code($code)
    {
        $code = trim((string) $code);
        if ($code === '' || !lc_db_table_exists(lc_event_table())) {
            return null;
        }

        $table = lc_event_table();
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ev_code = '" . lc_sql_escape($code) . "' LIMIT 1 ", false);

        return is_array($row) && !empty($row['ev_id']) ? $row : null;
    }
}

if (!function_exists('lc_event_partner_progress')) {
    function lc_event_partner_progress(array $row)
    {
        $benefit = (string) ($row['ev_benefit'] ?? '');
        $target = 5;
        if (preg_match('/(\d+)\s*건/u', $benefit, $m)) {
            $target = max(1, (int) $m[1]);
        }

        $progress = array(
            'joined'  => false,
            'current' => 0,
            'target'  => $target,
            'pct'     => 0,
            'reward'  => $benefit !== '' ? $benefit : '—',
            'alert'   => '파트너 로그인 후 참여 현황을 확인할 수 있습니다.',
        );

        global $member;
        if (!isset($member['mb_id']) || $member['mb_id'] === '' || !function_exists('lc_get_partner_by_mb_id')) {
            return $progress;
        }

        $partner = lc_get_partner_by_mb_id($member['mb_id']);
        if (!is_array($partner) || empty($partner['pt_id'])) {
            return $progress;
        }

        $pt_id = (int) $partner['pt_id'];
        $ev_id = (int) ($row['ev_id'] ?? 0);
        if ($ev_id > 0 && lc_event_participant_is_joined($ev_id, $pt_id)) {
            $progress['joined'] = true;
            $progress['alert'] = '이벤트에 참여 중입니다. 승인 DB를 모아 목표를 달성하세요!';
        }

        if (!lc_db_table_exists(lc_table('conversions'))) {
            return $progress;
        }

        $cv = lc_table('conversions');
        $count_row = lc_sql_fetch("
            SELECT COUNT(*) AS cnt
            FROM `{$cv}`
            WHERE pt_id = {$pt_id}
              AND cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "'
        ", false);
        $current = (int) ($count_row['cnt'] ?? 0);
        $pct = $target > 0 ? min(100, (int) round(($current / $target) * 100)) : 0;
        $remaining = max(0, $target - $current);

        if ($progress['joined']) {
            $alert = $remaining > 0
                ? '목표까지 승인 DB ' . $remaining . '건 남았습니다!'
                : '목표를 성공적으로 달성했습니다!';
        } else {
            $alert = '이벤트 참여 후 승인 DB가 집계됩니다.';
        }

        return array(
            'joined'  => $progress['joined'],
            'current' => $current,
            'target'  => $target,
            'pct'     => $pct,
            'reward'  => $benefit !== '' ? $benefit : '—',
            'alert'   => $alert,
        );
    }
}

if (!function_exists('lc_event_row_to_public_detail')) {
    function lc_event_row_to_public_detail(array $row)
    {
        $products = array();
        foreach (array($row['ev_product'] ?? '', $row['ev_campaign_labels'] ?? '') as $chunk) {
            foreach (preg_split('/[,，]/u', (string) $chunk) as $part) {
                $part = trim($part);
                if ($part !== '' && !in_array($part, $products, true)) {
                    $products[] = $part;
                }
            }
        }

        $rules = function_exists('lc_sample_event_rules_checklist') ? lc_sample_event_rules_checklist() : array();
        $promoTabs = function_exists('lc_sample_promo_copy_tabs') ? lc_sample_promo_copy_tabs() : array();

        return array(
            'id'         => (string) ($row['ev_code'] ?? ''),
            'title'      => (string) ($row['ev_title'] ?? ''),
            'type'       => (string) ($row['ev_type'] ?? ''),
            'desc'       => (string) ($row['ev_desc'] ?? ''),
            'period'     => (string) ($row['ev_period'] ?? ''),
            'product'    => (string) ($row['ev_product'] ?? ''),
            'benefit'    => (string) ($row['ev_benefit'] ?? ''),
            'badges'     => lc_event_decode_badges($row['ev_badges'] ?? ''),
            'ribbon'     => (string) ($row['ev_ribbon'] ?? ''),
            'status'     => lc_event_status_ui($row['ev_status'] ?? ''),
            'statusCode' => (string) ($row['ev_status'] ?? ''),
            'condition'  => (string) ($row['ev_benefit'] ?? ''),
            'campaigns'  => (string) ($row['ev_campaign_labels'] ?? ''),
            'products'   => $products,
            'rules'      => $rules,
            'promoTabs'  => $promoTabs,
            'progress'   => lc_event_partner_progress($row),
            'dbReady'    => lc_db_installed(),
        );
    }
}

if (!function_exists('lc_event_public_detail')) {
    function lc_event_public_detail($code)
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        if (lc_db_table_exists(lc_event_table())) {
            lc_event_ensure_seed();
            $row = lc_event_get_by_code($code);
            if (is_array($row)) {
                return lc_event_row_to_public_detail($row);
            }
        }

        if (function_exists('lc_sample_event_cards')) {
            foreach (lc_sample_event_cards() as $i => $card) {
                $sample_code = 'EVT-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
                if ($sample_code !== $code && ($card['title'] ?? '') !== $code) {
                    continue;
                }

                return lc_event_row_to_public_detail(array(
                    'ev_code'            => $sample_code,
                    'ev_title'           => $card['title'] ?? '',
                    'ev_type'            => $card['badges'][1] ?? ($card['badges'][0] ?? ''),
                    'ev_desc'            => $card['desc'] ?? '',
                    'ev_period'          => $card['period'] ?? '',
                    'ev_product'         => $card['product'] ?? '',
                    'ev_benefit'         => $card['benefit'] ?? '',
                    'ev_badges'          => lc_event_encode_badges($card['badges'] ?? array()),
                    'ev_ribbon'          => $card['ribbon'] ?? '',
                    'ev_status'          => LC_EVENT_ACTIVE,
                    'ev_campaign_labels' => $card['product'] ?? '',
                ));
            }
        }

        if (function_exists('lc_sample_admin_events_list')) {
            foreach (lc_sample_admin_events_list() as $item) {
                if (($item['code'] ?? '') !== $code) {
                    continue;
                }

                return lc_event_row_to_public_detail(array(
                    'ev_code'            => $item['code'],
                    'ev_title'           => $item['title'] ?? '',
                    'ev_type'            => $item['type'] ?? '',
                    'ev_desc'            => $item['title'] ?? '',
                    'ev_period'          => $item['period'] ?? '',
                    'ev_product'         => $item['campaigns'] ?? '',
                    'ev_benefit'         => '',
                    'ev_badges'          => lc_event_encode_badges(array($item['status'] ?? '진행중', $item['type'] ?? '')),
                    'ev_ribbon'          => '',
                    'ev_status'          => LC_EVENT_ACTIVE,
                    'ev_campaign_labels' => $item['campaigns'] ?? '',
                ));
            }
        }

        return null;
    }
}

if (!function_exists('lc_event_participant_table')) {
    function lc_event_participant_table()
    {
        return lc_table('event_participants');
    }
}

if (!function_exists('lc_event_reward_table')) {
    function lc_event_reward_table()
    {
        return lc_table('event_rewards');
    }
}

if (!function_exists('lc_event_ranking_reward_amount')) {
    function lc_event_ranking_reward_amount($rank)
    {
        $rank = (int) $rank;
        if ($rank === 1) {
            return 1000000;
        }
        if ($rank === 2) {
            return 500000;
        }
        if ($rank === 3) {
            return 300000;
        }
        if ($rank >= 4 && $rank <= 10) {
            return 100000;
        }

        return 0;
    }
}

if (!function_exists('lc_event_ranking_reward_label')) {
    function lc_event_ranking_reward_label($rank)
    {
        $amount = lc_event_ranking_reward_amount($rank);
        if ($amount <= 0) {
            return '—';
        }
        if ($amount >= 1000000) {
            return number_format($amount) . '원';
        }
        if ($amount === 100000) {
            return '10만원';
        }

        return number_format($amount) . '원';
    }
}

if (!function_exists('lc_event_ranking_tone')) {
    function lc_event_ranking_tone($rank)
    {
        $rank = (int) $rank;
        if ($rank === 1) {
            return 'gold';
        }
        if ($rank === 2) {
            return 'silver';
        }
        if ($rank === 3) {
            return 'bronze';
        }

        return 'default';
    }
}

if (!function_exists('lc_event_mask_partner_code')) {
    function lc_event_mask_partner_code($code, $is_self = false)
    {
        $code = trim((string) $code);
        if ($code === '' || $is_self) {
            return $code;
        }
        if (strlen($code) <= 4) {
            return $code;
        }

        return substr($code, 0, -2) . '**';
    }
}

if (!function_exists('lc_event_ranking_month_start')) {
    function lc_event_ranking_month_start()
    {
        return date('Y-m-01 00:00:00');
    }
}

if (!function_exists('lc_event_ranking_rows_from_db')) {
    function lc_event_ranking_rows_from_db()
    {
        if (!lc_db_table_exists(lc_table('conversions')) || !lc_db_table_exists(lc_table('partners'))) {
            return array();
        }

        $month_start = lc_event_ranking_month_start();
        $cv = lc_table('conversions');
        $pt = lc_table('partners');
        $approved = lc_sql_escape(LC_STATUS_APPROVED);
        $active = lc_sql_escape(LC_PARTNER_STATUS_ACTIVE);

        $sql = "
            SELECT
                p.pt_id,
                p.pt_code,
                p.pt_name,
                COUNT(c.cv_id) AS approved_cnt,
                COALESCE(SUM(c.cv_price), 0) AS earnings
            FROM `{$pt}` p
            LEFT JOIN `{$cv}` c
                ON c.pt_id = p.pt_id
               AND c.cv_status = '{$approved}'
               AND c.cv_updated_at >= '{$month_start}'
            WHERE p.pt_status = '{$active}'
            GROUP BY p.pt_id, p.pt_code, p.pt_name
            HAVING approved_cnt > 0
            ORDER BY approved_cnt DESC, earnings DESC, p.pt_id ASC
            LIMIT 50
        ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = array(
                    'pt_id'    => (int) ($row['pt_id'] ?? 0),
                    'pt_code'  => (string) ($row['pt_code'] ?? ''),
                    'pt_name'  => (string) ($row['pt_name'] ?? ''),
                    'dbs'      => (int) ($row['approved_cnt'] ?? 0),
                    'earnings' => (int) ($row['earnings'] ?? 0),
                );
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_event_ranking_item_for_api')) {
    function lc_event_ranking_item_for_api($rank, array $row, $pt_id_self = 0)
    {
        $rank = (int) $rank;
        $pt_id = (int) ($row['pt_id'] ?? 0);
        $is_self = $pt_id_self > 0 && $pt_id === $pt_id_self;
        $dbs = (int) ($row['dbs'] ?? 0);
        $earnings = (int) ($row['earnings'] ?? 0);
        $item = array(
            'rank'    => $rank,
            'ptId'    => $pt_id,
            'partner' => lc_event_mask_partner_code((string) ($row['pt_code'] ?? ''), $is_self),
            'dbs'     => $dbs,
            'reward'  => lc_event_ranking_reward_label($rank),
            'tone'    => lc_event_ranking_tone($rank),
        );

        if ($rank === 1 && $earnings > 0) {
            $item['earnings'] = number_format($earnings) . '원';
        }

        return $item;
    }
}

if (!function_exists('lc_event_ranking_from_sample')) {
    function lc_event_ranking_from_sample($pt_id_self = 0)
    {
        $top = function_exists('lc_sample_ranking_top') ? lc_sample_ranking_top() : array();
        $list = function_exists('lc_sample_ranking_list') ? lc_sample_ranking_list() : array();

        return array(
            'summary' => array(
                'topDbs'           => 128,
                'myRank'           => $pt_id_self > 0 ? 18 : 0,
                'remainingToTop10' => 7,
                'myBonus'          => '0원',
                'top10BonusHint'   => 'TOP 10 진입 시 100,000원',
            ),
            'top'   => $top,
            'list'  => $list,
            'my'    => $pt_id_self > 0 ? array(
                'rank'             => 18,
                'dbs'              => 42,
                'earnings'         => 1260000,
                'remainingToTop10' => 7,
                'bonus'            => '0원',
            ) : null,
            'tiers' => lc_event_ranking_tiers(),
        );
    }
}

if (!function_exists('lc_event_ranking_tiers')) {
    function lc_event_ranking_tiers()
    {
        return array(
            array('label' => '1위', 'reward' => '1,000,000원', 'tone' => 'amber'),
            array('label' => '2위', 'reward' => '500,000원', 'tone' => 'slate'),
            array('label' => '3위', 'reward' => '300,000원', 'tone' => 'orange'),
            array('label' => '4~10위', 'reward' => '100,000원', 'tone' => 'cyan'),
        );
    }
}

if (!function_exists('lc_event_current_partner_id')) {
    function lc_event_current_partner_id()
    {
        global $member;
        if (!isset($member['mb_id']) || $member['mb_id'] === '' || !function_exists('lc_get_partner_by_mb_id')) {
            return 0;
        }

        $partner = lc_get_partner_by_mb_id($member['mb_id']);
        if (!is_array($partner) || empty($partner['pt_id'])) {
            return 0;
        }

        return (int) $partner['pt_id'];
    }
}

if (!function_exists('lc_event_ranking_for_api')) {
    function lc_event_ranking_for_api($pt_id_self = 0)
    {
        if ($pt_id_self <= 0) {
            $pt_id_self = lc_event_current_partner_id();
        }

        $db_rows = lc_event_ranking_rows_from_db();
        if (count($db_rows) < 3) {
            return lc_event_ranking_from_sample($pt_id_self);
        }

        $all = array();
        foreach ($db_rows as $i => $row) {
            $all[] = lc_event_ranking_item_for_api($i + 1, $row, $pt_id_self);
        }

        $top = array();
        if (isset($all[1])) {
            $top[] = $all[1];
        }
        if (isset($all[0])) {
            $top[] = $all[0];
        }
        if (isset($all[2])) {
            $top[] = $all[2];
        }

        $list = array_slice($all, 3, 7);

        $top_dbs = isset($all[0]['dbs']) ? (int) $all[0]['dbs'] : 0;
        $my = null;
        $my_rank = 0;
        $my_dbs = 0;
        $my_earnings = 0;

        foreach ($db_rows as $i => $row) {
            if ((int) ($row['pt_id'] ?? 0) !== $pt_id_self) {
                continue;
            }
            $my_rank = $i + 1;
            $my_dbs = (int) ($row['dbs'] ?? 0);
            $my_earnings = (int) ($row['earnings'] ?? 0);
            break;
        }

        $tenth_dbs = isset($all[9]['dbs']) ? (int) $all[9]['dbs'] : (isset($all[count($all) - 1]['dbs']) ? (int) $all[count($all) - 1]['dbs'] : 0);
        $remaining = 0;
        $my_bonus = '0원';

        if ($pt_id_self > 0) {
            if ($my_rank > 0 && $my_rank <= 10) {
                $my_bonus = lc_event_ranking_reward_label($my_rank);
            }
            if ($my_rank === 0 || $my_rank > 10) {
                $remaining = max(0, $tenth_dbs - $my_dbs + ($my_dbs > 0 && $my_rank === 0 ? 1 : 0));
                if ($my_rank > 10 && $tenth_dbs > 0) {
                    $remaining = max(0, $tenth_dbs - $my_dbs + 1);
                }
            }

            $my = array(
                'rank'             => $my_rank,
                'dbs'              => $my_dbs,
                'earnings'         => $my_earnings,
                'remainingToTop10' => $remaining,
                'bonus'            => $my_bonus,
            );
        }

        return array(
            'summary' => array(
                'topDbs'           => $top_dbs,
                'myRank'           => $my_rank,
                'remainingToTop10' => $remaining,
                'myBonus'          => $my_bonus,
                'top10BonusHint'   => 'TOP 10 진입 시 100,000원',
            ),
            'top'   => $top,
            'list'  => $list,
            'my'    => $my,
            'tiers' => lc_event_ranking_tiers(),
        );
    }
}

if (!function_exists('lc_event_participant_is_joined')) {
    function lc_event_participant_is_joined($ev_id, $pt_id)
    {
        $ev_id = (int) $ev_id;
        $pt_id = (int) $pt_id;
        if ($ev_id <= 0 || $pt_id <= 0 || !lc_db_table_exists(lc_event_participant_table())) {
            return false;
        }

        $table = lc_event_participant_table();
        $row = lc_sql_fetch("
            SELECT ep_id
            FROM `{$table}`
            WHERE ev_id = {$ev_id} AND pt_id = {$pt_id}
            LIMIT 1
        ", false);

        return is_array($row) && !empty($row['ep_id']);
    }
}

if (!function_exists('lc_event_participant_join')) {
    function lc_event_participant_join($ev_id, $pt_id)
    {
        $ev_id = (int) $ev_id;
        $pt_id = (int) $pt_id;

        if ($ev_id <= 0 || $pt_id <= 0) {
            return array('ok' => false, 'message' => '이벤트 또는 파트너 정보가 올바르지 않습니다.');
        }

        if (!lc_db_table_exists(lc_event_participant_table())) {
            return array('ok' => false, 'message' => '참여 테이블이 없습니다. DB 마이그레이션을 실행해주세요.');
        }

        $event = lc_sql_fetch(' SELECT ev_id, ev_status FROM `' . lc_event_table() . "` WHERE ev_id = {$ev_id} LIMIT 1 ", false);
        if (!is_array($event) || empty($event['ev_id'])) {
            return array('ok' => false, 'message' => '이벤트를 찾을 수 없습니다.');
        }

        $status = (string) ($event['ev_status'] ?? '');
        if (!in_array($status, array(LC_EVENT_ACTIVE, LC_EVENT_CLOSING), true)) {
            return array('ok' => false, 'message' => '참여 가능한 이벤트가 아닙니다.');
        }

        if (lc_event_participant_is_joined($ev_id, $pt_id)) {
            return array('ok' => true, 'message' => '이미 참여 중인 이벤트입니다.', 'joined' => true);
        }

        $table = lc_event_participant_table();
        lc_sql_query("
            INSERT INTO `{$table}` (`ev_id`, `pt_id`, `ep_status`, `ep_approved_cnt`)
            VALUES ({$ev_id}, {$pt_id}, 'joined', 0)
        ", false);

        return array('ok' => true, 'message' => '이벤트 참여가 완료되었습니다.', 'joined' => true);
    }
}

if (!function_exists('lc_event_participant_join_by_code')) {
    function lc_event_participant_join_by_code($code, $pt_id)
    {
        $row = lc_event_get_by_code($code);
        if (!is_array($row) || empty($row['ev_id'])) {
            return array('ok' => false, 'message' => '이벤트를 찾을 수 없습니다.');
        }

        return lc_event_participant_join((int) $row['ev_id'], (int) $pt_id);
    }
}

if (!function_exists('lc_event_admin_row_stats')) {
    function lc_event_admin_row_stats($ev_id)
    {
        $ev_id = (int) $ev_id;
        $stats = array(
            'partners'      => 0,
            'received'      => 0,
            'approved'      => 0,
            'rewardPending' => '—',
        );

        if ($ev_id <= 0) {
            return $stats;
        }

        if (lc_db_table_exists(lc_event_participant_table())) {
            $ep = lc_event_participant_table();
            $row = lc_sql_fetch("
                SELECT COUNT(*) AS cnt, COALESCE(SUM(ep_approved_cnt), 0) AS approved_sum
                FROM `{$ep}`
                WHERE ev_id = {$ev_id}
            ", false);
            $stats['partners'] = (int) ($row['cnt'] ?? 0);
            $stats['approved'] = (int) ($row['approved_sum'] ?? 0);
            $stats['received'] = $stats['partners'];
        }

        if (lc_db_table_exists(lc_event_reward_table())) {
            $er = lc_event_reward_table();
            $row = lc_sql_fetch("
                SELECT COALESCE(SUM(er_amount), 0) AS pending_sum
                FROM `{$er}`
                WHERE ev_id = {$ev_id} AND er_status = 'pending'
            ", false);
            $pending = (int) ($row['pending_sum'] ?? 0);
            $stats['rewardPending'] = $pending > 0 ? number_format($pending) . '원' : '—';
        }

        return $stats;
    }
}

if (!function_exists('lc_event_participants_admin_for_api')) {
    function lc_event_participants_admin_for_api($ev_id)
    {
        $ev_id = (int) $ev_id;
        if ($ev_id <= 0 || !lc_db_table_exists(lc_event_participant_table())) {
            return array();
        }

        $ep = lc_event_participant_table();
        $pt = lc_table('partners');
        $items = array();
        $result = lc_sql_query("
            SELECT ep.*, p.pt_code, p.pt_name
            FROM `{$ep}` ep
            INNER JOIN `{$pt}` p ON p.pt_id = ep.pt_id
            WHERE ep.ev_id = {$ev_id}
            ORDER BY ep.ep_joined_at DESC
            LIMIT 200
        ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'       => (int) ($row['ep_id'] ?? 0),
                    'evId'     => $ev_id,
                    'ptId'     => (int) ($row['pt_id'] ?? 0),
                    'partner'  => (string) ($row['pt_code'] ?? ''),
                    'name'     => (string) ($row['pt_name'] ?? ''),
                    'status'   => (string) ($row['ep_status'] ?? 'joined'),
                    'approved' => (int) ($row['ep_approved_cnt'] ?? 0),
                    'joinedAt' => (string) ($row['ep_joined_at'] ?? ''),
                );
            }
        }

        return $items;
    }
}

if (!function_exists('lc_event_rewards_admin_for_api')) {
    function lc_event_rewards_admin_for_api(array $filters = array())
    {
        if (!lc_db_table_exists(lc_event_reward_table())) {
            return array();
        }

        $er = lc_event_reward_table();
        $pt = lc_table('partners');
        $ev = lc_event_table();
        $where = array('1=1');
        $status = trim((string) ($filters['status'] ?? ''));
        $ev_id = (int) ($filters['evId'] ?? 0);

        if ($status !== '') {
            $where[] = "er.er_status = '" . lc_sql_escape($status) . "'";
        }
        if ($ev_id > 0) {
            $where[] = "er.ev_id = {$ev_id}";
        }

        $items = array();
        $result = lc_sql_query("
            SELECT er.*, p.pt_code, p.pt_name, e.ev_code, e.ev_title
            FROM `{$er}` er
            INNER JOIN `{$pt}` p ON p.pt_id = er.pt_id
            LEFT JOIN `{$ev}` e ON e.ev_id = er.ev_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY er.er_id DESC
            LIMIT 200
        ", false);

        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $items[] = array(
                    'id'        => (int) ($row['er_id'] ?? 0),
                    'evId'      => (int) ($row['ev_id'] ?? 0),
                    'eventCode' => (string) ($row['ev_code'] ?? ''),
                    'eventTitle'=> (string) ($row['ev_title'] ?? ''),
                    'ptId'      => (int) ($row['pt_id'] ?? 0),
                    'partner'   => (string) ($row['pt_code'] ?? ''),
                    'name'      => (string) ($row['pt_name'] ?? ''),
                    'amount'    => (int) ($row['er_amount'] ?? 0),
                    'status'    => (string) ($row['er_status'] ?? 'pending'),
                    'condition' => (string) ($row['er_condition'] ?? ''),
                    'memo'      => (string) ($row['er_memo'] ?? ''),
                    'createdAt' => (string) ($row['er_created_at'] ?? ''),
                    'paidAt'    => (string) ($row['er_paid_at'] ?? ''),
                );
            }
        }

        return $items;
    }
}

if (!function_exists('lc_event_reward_update_status')) {
    function lc_event_reward_update_status($er_id, $status, $memo = '')
    {
        $er_id = (int) $er_id;
        $status = trim((string) $status);
        $allowed = array('pending', 'paid', 'rejected');
        if ($er_id <= 0 || !in_array($status, $allowed, true) || !lc_db_table_exists(lc_event_reward_table())) {
            return array('ok' => false, 'message' => '리워드 상태 변경에 실패했습니다.');
        }

        $table = lc_event_reward_table();
        $paid_at = $status === 'paid' ? ", er_paid_at = NOW()" : '';

        $reward = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE er_id = {$er_id} LIMIT 1 ", false);
        if (!is_array($reward) || empty($reward['er_id'])) {
            return array('ok' => false, 'message' => '리워드를 찾을 수 없습니다.');
        }

        if ($status === 'paid' && (string) ($reward['er_status'] ?? '') !== 'paid') {
            $pt_id = (int) ($reward['pt_id'] ?? 0);
            $amount = (int) ($reward['er_amount'] ?? 0);
            if ($pt_id > 0 && $amount > 0) {
                $pt_table = lc_table('partners');
                lc_sql_query(" UPDATE `{$pt_table}` SET pt_balance = pt_balance + {$amount}, pt_updated_at = NOW() WHERE pt_id = {$pt_id} ", false);
            }
        }

        lc_sql_query("
            UPDATE `{$table}`
            SET er_status = '" . lc_sql_escape($status) . "',
                er_memo = '" . lc_sql_escape($memo) . "'
                {$paid_at}
            WHERE er_id = {$er_id}
            LIMIT 1
        ", false);

        if ($status === 'paid' && function_exists('lc_notification_emit_event_reward')) {
            $ev_title = '';
            if ((int) ($reward['ev_id'] ?? 0) > 0 && lc_db_table_exists(lc_event_table())) {
                $ev = lc_sql_fetch(" SELECT ev_title FROM `" . lc_event_table() . "` WHERE ev_id = " . (int) $reward['ev_id'] . " LIMIT 1 ", false);
                $ev_title = is_array($ev) ? (string) ($ev['ev_title'] ?? '') : '';
            }
            if ($ev_title === '') {
                $ev_title = (string) ($reward['er_condition'] ?? '이벤트');
            }
            lc_notification_emit_event_reward((int) $reward['pt_id'], (int) $reward['er_amount'], $ev_title, $er_id);
        }

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write(
                $status === 'paid' ? 'event_reward_pay' : 'event_reward_reject',
                'event_reward',
                $er_id,
                ($status === 'paid' ? '리워드 지급' : '리워드 거절') . ' #' . $er_id
            );
        }

        return array('ok' => true, 'message' => $status === 'paid' ? '리워드 지급이 완료되었습니다.' : '리워드 상태가 변경되었습니다.');
    }
}

if (!function_exists('lc_event_reward_create')) {
    function lc_event_reward_create(array $payload)
    {
        if (!lc_db_table_exists(lc_event_reward_table())) {
            return array('ok' => false, 'message' => '리워드 테이블이 없습니다.');
        }

        $ev_id = (int) ($payload['evId'] ?? 0);
        $pt_id = (int) ($payload['ptId'] ?? 0);
        $amount = (int) ($payload['amount'] ?? 0);
        $condition = trim((string) ($payload['condition'] ?? ''));

        if ($pt_id <= 0 || $amount <= 0) {
            return array('ok' => false, 'message' => '파트너와 금액을 확인해주세요.');
        }

        $table = lc_event_reward_table();
        lc_sql_query("
            INSERT INTO `{$table}` (`ev_id`, `pt_id`, `er_amount`, `er_status`, `er_condition`)
            VALUES ({$ev_id}, {$pt_id}, {$amount}, 'pending', '" . lc_sql_escape($condition) . "')
        ", false);

        return array('ok' => true, 'message' => '리워드가 등록되었습니다.', 'id' => (int) lc_sql_insert_id());
    }
}

if (!function_exists('lc_event_promo_cpa_for_api')) {
    function lc_event_promo_cpa_for_api()
    {
        $items = function_exists('lc_sample_promo_cpa') ? lc_sample_promo_cpa() : array();
        $out = array();

        foreach ($items as $item) {
            $out[] = array(
                'event'          => (string) ($item['event'] ?? ''),
                'title'          => (string) ($item['title'] ?? ''),
                'category'       => (string) ($item['category'] ?? ''),
                'approvalRate'   => (string) ($item['approval_rate'] ?? ($item['approvalRate'] ?? '')),
                'oldPrice'       => (int) ($item['old_price'] ?? ($item['oldPrice'] ?? 0)),
                'price'          => (int) ($item['price'] ?? 0),
                'bonus'          => (string) ($item['bonus'] ?? ''),
                'highlight'      => !empty($item['highlight']),
            );
        }

        return $out;
    }
}

if (!function_exists('lc_events_public_payload')) {
    function lc_events_public_payload(array $filters = array())
    {
        $recommendations = function_exists('lc_sample_event_recommendations') ? lc_sample_event_recommendations() : array();
        $ranking = lc_event_ranking_for_api();

        return array(
            'summary'         => lc_event_public_summary(),
            'items'           => lc_event_public_items($filters),
            'recommendations' => $recommendations,
            'promoCpa'        => lc_event_promo_cpa_for_api(),
            'ranking'         => $ranking,
            'rankingTop'      => $ranking['top'] ?? array(),
            'rankingList'     => $ranking['list'] ?? array(),
            'dbReady'         => lc_db_installed(),
        );
    }
}

if (!function_exists('lc_event_save')) {
    function lc_event_save(array $payload, $ev_id = 0)
    {
        if (!lc_db_table_exists(lc_event_table())) {
            return array('ok' => false, 'message' => '이벤트 테이블이 없습니다.');
        }

        $ev_id = (int) $ev_id;
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return array('ok' => false, 'message' => '이벤트명을 입력해주세요.');
        }

        $table = lc_event_table();
        $badges = isset($payload['badges']) && is_array($payload['badges']) ? $payload['badges'] : array();
        $status = trim((string) ($payload['statusCode'] ?? LC_EVENT_ACTIVE));
        $allowed = array(LC_EVENT_ACTIVE, LC_EVENT_CLOSING, LC_EVENT_ENDED, LC_EVENT_SCHEDULED);
        if (!in_array($status, $allowed, true)) {
            $status = LC_EVENT_ACTIVE;
        }

        $fields = array(
            'ev_title'           => $title,
            'ev_type'            => trim((string) ($payload['type'] ?? '')),
            'ev_desc'            => trim((string) ($payload['desc'] ?? '')),
            'ev_period'          => trim((string) ($payload['period'] ?? '')),
            'ev_product'         => trim((string) ($payload['product'] ?? '')),
            'ev_benefit'         => trim((string) ($payload['benefit'] ?? '')),
            'ev_badges'          => lc_event_encode_badges($badges),
            'ev_ribbon'          => trim((string) ($payload['ribbon'] ?? '')),
            'ev_status'          => $status,
            'ev_target'          => trim((string) ($payload['target'] ?? 'partner')),
            'ev_campaign_ids'    => trim((string) ($payload['campaignIds'] ?? '')),
            'ev_campaign_labels' => trim((string) ($payload['campaigns'] ?? '')),
            'ev_featured'        => !empty($payload['featured']) ? 1 : 0,
            'ev_sort'            => (int) ($payload['sort'] ?? 0),
        );

        if ($ev_id > 0) {
            $exists = lc_sql_fetch(" SELECT ev_id FROM `{$table}` WHERE ev_id = {$ev_id} LIMIT 1 ", false);
            if (!is_array($exists) || empty($exists['ev_id'])) {
                return array('ok' => false, 'message' => '이벤트를 찾을 수 없습니다.');
            }

            $sets = array();
            foreach ($fields as $col => $value) {
                $sets[] = "`{$col}` = '" . lc_sql_escape($value) . "'";
            }
            lc_sql_query(" UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE ev_id = {$ev_id} ", false);
            $message = '이벤트가 저장되었습니다.';
        } else {
            $code = trim((string) ($payload['code'] ?? ''));
            if ($code === '') {
                $code = lc_event_generate_code();
            }

            $cols = array('ev_code');
            $vals = array("'" . lc_sql_escape($code) . "'");
            foreach ($fields as $col => $value) {
                $cols[] = $col;
                $vals[] = "'" . lc_sql_escape($value) . "'";
            }

            lc_sql_query(' INSERT INTO `' . $table . '` (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $vals) . ') ', false);
            $ev_id = (int) lc_sql_insert_id();
            $message = '이벤트가 등록되었습니다.';
        }

        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ev_id = {$ev_id} LIMIT 1 ", false);

        return array(
            'ok'      => true,
            'message' => $message,
            'event'   => is_array($row) ? lc_event_row_to_admin($row) : null,
        );
    }
}

if (!function_exists('lc_event_update_status')) {
    function lc_event_update_status($ev_id, $status)
    {
        $ev_id = (int) $ev_id;
        $status = trim((string) $status);
        $allowed = array(LC_EVENT_ACTIVE, LC_EVENT_CLOSING, LC_EVENT_ENDED, LC_EVENT_SCHEDULED);

        if ($ev_id <= 0 || !in_array($status, $allowed, true) || !lc_db_table_exists(lc_event_table())) {
            return array('ok' => false, 'message' => '상태 변경에 실패했습니다.');
        }

        $table = lc_event_table();
        lc_sql_query(" UPDATE `{$table}` SET ev_status = '" . lc_sql_escape($status) . "' WHERE ev_id = {$ev_id} LIMIT 1 ", false);
        $row = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE ev_id = {$ev_id} LIMIT 1 ", false);

        return array(
            'ok'      => true,
            'message' => '상태가 변경되었습니다.',
            'event'   => is_array($row) ? lc_event_row_to_admin($row) : null,
        );
    }
}

if (!function_exists('lc_event_ensure_seed')) {
    function lc_event_ensure_seed()
    {
        static $done = false;
        if ($done || !lc_db_table_exists(lc_event_table())) {
            return;
        }
        $done = true;

        $table = lc_event_table();
        $row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` ", false);
        if ((int) ($row['cnt'] ?? 0) > 0) {
            return;
        }

        $cards = function_exists('lc_sample_event_cards') ? lc_sample_event_cards() : array();
        $admin = function_exists('lc_sample_admin_events_list') ? lc_sample_admin_events_list() : array();
        $status_map = array(
            '진행중'   => LC_EVENT_ACTIVE,
            '마감임박' => LC_EVENT_CLOSING,
            '종료'     => LC_EVENT_ENDED,
            '예정'     => LC_EVENT_SCHEDULED,
        );

        foreach ($admin as $i => $item) {
            $card = $cards[$i] ?? array();
            $badges = $card['badges'] ?? array($item['type'] ?? '');
            lc_event_save(array(
                'code'       => $item['code'] ?? lc_event_generate_code(),
                'title'      => $item['title'] ?? '',
                'type'       => $item['type'] ?? '',
                'desc'       => $card['desc'] ?? '',
                'period'     => $item['period'] ?? ($card['period'] ?? ''),
                'product'    => $card['product'] ?? ($item['campaigns'] ?? ''),
                'benefit'    => $card['benefit'] ?? '',
                'badges'     => $badges,
                'ribbon'     => $card['ribbon'] ?? '',
                'statusCode' => $status_map[$item['status'] ?? ''] ?? LC_EVENT_ACTIVE,
                'campaigns'  => $item['campaigns'] ?? '',
                'featured'   => $i < 3,
                'sort'       => 100 - $i,
            ), 0);
        }
    }
}

if (!function_exists('lc_event_on_conversion_approved')) {
    function lc_event_on_conversion_approved(array $conversion)
    {
        $pt_id = (int) ($conversion['pt_id'] ?? 0);
        if ($pt_id <= 0 || !lc_db_table_exists(lc_table('event_participants'))) {
            return;
        }

        $ep = lc_table('event_participants');
        $ev = lc_event_table();
        $active = lc_sql_escape(LC_EVENT_ACTIVE);
        $closing = lc_sql_escape(LC_EVENT_CLOSING);

        lc_sql_query("
            UPDATE `{$ep}` ep
            INNER JOIN `{$ev}` e ON e.ev_id = ep.ev_id
            SET ep.ep_approved_cnt = ep.ep_approved_cnt + 1, ep.ep_updated_at = NOW()
            WHERE ep.pt_id = {$pt_id}
              AND ep.ep_status = 'joined'
              AND e.ev_status IN ('{$active}', '{$closing}')
        ", false);

        if (function_exists('lc_event_check_milestone_rewards')) {
            lc_event_check_milestone_rewards($pt_id);
        }
    }
}

if (!function_exists('lc_event_parse_milestone_target')) {
    function lc_event_parse_milestone_target($benefit)
    {
        $benefit = trim((string) $benefit);
        if ($benefit === '') {
            return 0;
        }

        if (preg_match('/(\d+)\s*건/u', $benefit, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}

if (!function_exists('lc_event_check_milestone_rewards')) {
    function lc_event_check_milestone_rewards($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_table_exists(lc_event_reward_table())) {
            return;
        }

        $ep = lc_table('event_participants');
        $ev = lc_event_table();
        $er = lc_event_reward_table();
        $active = lc_sql_escape(LC_EVENT_ACTIVE);
        $closing = lc_sql_escape(LC_EVENT_CLOSING);

        $result = lc_sql_query("
            SELECT ep.ev_id, ep.ep_approved_cnt, e.ev_title, e.ev_benefit, e.ev_type
            FROM `{$ep}` ep
            INNER JOIN `{$ev}` e ON e.ev_id = ep.ev_id
            WHERE ep.pt_id = {$pt_id}
              AND ep.ep_status = 'joined'
              AND e.ev_status IN ('{$active}', '{$closing}')
        ", false);

        if (!$result) {
            return;
        }

        while ($row = sql_fetch_array($result)) {
            $type = (string) ($row['ev_type'] ?? '');
            if (strpos($type, '랭킹') !== false) {
                continue;
            }

            $target = lc_event_parse_milestone_target($row['ev_benefit'] ?? '');
            $approved = (int) ($row['ep_approved_cnt'] ?? 0);
            $ev_id = (int) ($row['ev_id'] ?? 0);
            if ($target <= 0 || $approved < $target || $ev_id <= 0) {
                continue;
            }

            $condition = $target . '건 달성';
            $exists = lc_sql_fetch("
                SELECT er_id FROM `{$er}`
                WHERE ev_id = {$ev_id} AND pt_id = {$pt_id}
                  AND er_condition = '" . lc_sql_escape($condition) . "'
                LIMIT 1
            ", false);
            if (is_array($exists) && !empty($exists['er_id'])) {
                continue;
            }

            $amount = 50000;
            if (preg_match('/(\d[\d,]*)\s*원/u', (string) ($row['ev_benefit'] ?? ''), $m)) {
                $amount = (int) str_replace(',', '', $m[1]);
            }

            lc_event_reward_create(array(
                'evId'      => $ev_id,
                'ptId'      => $pt_id,
                'amount'    => $amount,
                'condition' => $condition,
            ));
        }
    }
}

if (!function_exists('lc_event_auto_ranking_rewards')) {
    function lc_event_auto_ranking_rewards($period = '')
    {
        if (!lc_db_table_exists(lc_event_reward_table())) {
            return array('ok' => false, 'message' => '리워드 테이블이 없습니다.', 'created' => 0);
        }

        $period = trim((string) $period);
        if ($period === '') {
            $period = date('Y-m');
        }

        $ranking = lc_event_ranking_for_api();
        $top = isset($ranking['top']) && is_array($ranking['top']) ? $ranking['top'] : array();
        $created = 0;
        $er = lc_event_reward_table();
        $condition_prefix = $period . ' 랭킹';

        foreach ($top as $item) {
            $rank = (int) ($item['rank'] ?? 0);
            $pt_id = (int) ($item['ptId'] ?? 0);
            $amount = lc_event_ranking_reward_amount($rank);
            if ($rank <= 0 || $pt_id <= 0 || $amount <= 0) {
                continue;
            }

            $condition = $condition_prefix . ' ' . $rank . '위';
            $exists = lc_sql_fetch("
                SELECT er_id FROM `{$er}`
                WHERE pt_id = {$pt_id}
                  AND er_condition = '" . lc_sql_escape($condition) . "'
                LIMIT 1
            ", false);
            if (is_array($exists) && !empty($exists['er_id'])) {
                continue;
            }

            $result = lc_event_reward_create(array(
                'evId'      => 0,
                'ptId'      => $pt_id,
                'amount'    => $amount,
                'condition' => $condition,
            ));
            if (!empty($result['ok'])) {
                $created++;
            }
        }

        return array(
            'ok'      => true,
            'message' => $created > 0 ? $created . '건의 랭킹 리워드가 생성되었습니다.' : '생성할 신규 랭킹 리워드가 없습니다.',
            'created' => $created,
        );
    }
}
