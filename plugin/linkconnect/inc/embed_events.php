<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_embed_events_table')) {
    function lc_embed_events_table()
    {
        return lc_table('embed_events');
    }
}

if (!function_exists('lc_embed_events_allowed')) {
    /**
     * @return string[]
     */
    function lc_embed_events_allowed()
    {
        return array('badge_click', 'extra_fields_open', 'sticky_submit', 'success_call_tap');
    }
}

if (!function_exists('lc_embed_events_ensure_schema')) {
    function lc_embed_events_ensure_schema()
    {
        if (!lc_db_installed()) {
            return false;
        }
        $table = lc_embed_events_table();
        if (function_exists('lc_db_table_exists') && lc_db_table_exists($table)) {
            return true;
        }

        $ok = lc_sql_query("CREATE TABLE IF NOT EXISTS `{$table}` (
            `ee_id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `pt_id` int unsigned NOT NULL DEFAULT 0,
            `lk_id` int unsigned NOT NULL DEFAULT 0,
            `lk_code` varchar(32) NOT NULL DEFAULT '',
            `ee_event` varchar(40) NOT NULL DEFAULT '',
            `ee_label` varchar(120) NOT NULL DEFAULT '',
            `ee_page_url` varchar(500) NOT NULL DEFAULT '',
            `ee_page_host` varchar(190) NOT NULL DEFAULT '',
            `ee_meta` text NULL,
            `ee_ip` varchar(45) NOT NULL DEFAULT '',
            `ee_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ee_id`),
            KEY `idx_ee_partner_created` (`pt_id`, `ee_created_at`),
            KEY `idx_ee_partner_event` (`pt_id`, `ee_event`, `ee_created_at`),
            KEY `idx_ee_host` (`pt_id`, `ee_page_host`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", false);

        return $ok !== false;
    }
}

if (!function_exists('lc_embed_events_normalize_name')) {
    function lc_embed_events_normalize_name($event)
    {
        $event = strtolower(trim((string) $event));
        $event = preg_replace('/[^a-z0-9_\-]/', '', $event);
        if (!in_array($event, lc_embed_events_allowed(), true)) {
            return '';
        }
        return $event;
    }
}

if (!function_exists('lc_embed_events_record')) {
    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,message:string}
     */
    function lc_embed_events_record(array $payload)
    {
        if (!lc_embed_events_ensure_schema()) {
            return array('ok' => false, 'message' => '이벤트 저장소를 준비할 수 없습니다.');
        }

        $event = lc_embed_events_normalize_name($payload['event'] ?? '');
        if ($event === '') {
            return array('ok' => false, 'message' => '지원하지 않는 이벤트입니다.');
        }

        $lk_code = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($payload['lkCode'] ?? $payload['lk_code'] ?? ''));
        $lk_code = substr((string) $lk_code, 0, 32);
        if ($lk_code === '') {
            return array('ok' => false, 'message' => 'lkCode가 필요합니다.');
        }

        $link = function_exists('lc_link_get_with_campaign') ? lc_link_get_with_campaign($lk_code) : null;
        if (!$link) {
            return array('ok' => false, 'message' => '유효하지 않은 홍보 링크입니다.');
        }

        $pt_id = (int) ($link['pt_id'] ?? 0);
        $lk_id = (int) ($link['lk_id'] ?? 0);
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 정보를 확인할 수 없습니다.');
        }

        $widget_key = trim((string) ($payload['widgetKey'] ?? $payload['widget_key'] ?? ''));
        if ($widget_key !== '' && function_exists('lc_embed_partner_widget_key')) {
            $expected = lc_embed_partner_widget_key($pt_id);
            if ($expected !== '' && !hash_equals($expected, $widget_key)) {
                return array('ok' => false, 'message' => '위젯 키가 올바르지 않습니다.');
            }
        }

        $page_url = trim((string) ($payload['page_url'] ?? $payload['pageUrl'] ?? ''));
        if ($page_url !== '') {
            $page_url = function_exists('mb_substr') ? mb_substr($page_url, 0, 500) : substr($page_url, 0, 500);
        }
        $host = '';
        if ($page_url !== '' && function_exists('lc_embed_host_from_url')) {
            $host = (string) lc_embed_host_from_url($page_url);
        } elseif ($page_url !== '') {
            $parts = parse_url($page_url);
            $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        }
        $host = function_exists('mb_substr') ? mb_substr($host, 0, 190) : substr($host, 0, 190);

        $label = trim((string) ($payload['label'] ?? $payload['lc_badge'] ?? $payload['lc_call_label'] ?? ''));
        $label = function_exists('mb_substr') ? mb_substr($label, 0, 120) : substr($label, 0, 120);

        $meta = array();
        foreach (array('lc_badge', 'lc_call_label', 'lc_submit_label', 'lc_extra_count') as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $meta[$key] = $payload[$key];
        }
        $meta_json = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : '';
        if ($meta_json === false) {
            $meta_json = '';
        }
        if (strlen($meta_json) > 1000) {
            $meta_json = substr($meta_json, 0, 1000);
        }

        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = substr((string) $_SERVER['REMOTE_ADDR'], 0, 45);
        }

        $table = lc_embed_events_table();
        $sql = " INSERT INTO `{$table}`
            (`pt_id`, `lk_id`, `lk_code`, `ee_event`, `ee_label`, `ee_page_url`, `ee_page_host`, `ee_meta`, `ee_ip`, `ee_created_at`)
            VALUES (
                '{$pt_id}',
                '{$lk_id}',
                '" . lc_sql_escape($lk_code) . "',
                '" . lc_sql_escape($event) . "',
                '" . lc_sql_escape($label) . "',
                '" . lc_sql_escape($page_url) . "',
                '" . lc_sql_escape($host) . "',
                '" . lc_sql_escape($meta_json) . "',
                '" . lc_sql_escape($ip) . "',
                NOW()
            ) ";
        $ok = lc_sql_query($sql, false);
        if ($ok === false) {
            return array('ok' => false, 'message' => '이벤트 저장에 실패했습니다.');
        }

        return array('ok' => true, 'message' => 'ok');
    }
}

if (!function_exists('lc_embed_events_stats_for_partner')) {
    /**
     * @return array<string,mixed>
     */
    function lc_embed_events_stats_for_partner($pt_id, $dateFrom, $dateTo, $db_received = 0, $db_approved = 0)
    {
        $pt_id = (int) $pt_id;
        $dateFrom = (string) $dateFrom;
        $dateTo = (string) $dateTo;
        $db_received = max(0, (int) $db_received);
        $db_approved = max(0, (int) $db_approved);

        $empty_counts = array(
            'badge_click' => 0,
            'extra_fields_open' => 0,
            'sticky_submit' => 0,
            'success_call_tap' => 0,
            'db_received' => $db_received,
            'db_approved' => $db_approved,
        );

        $labels = array(
            'badge_click' => array('label' => '배지 관심', 'desc' => '신뢰 배지를 누른 횟수'),
            'extra_fields_open' => array('label' => '추가정보 열람', 'desc' => '미니멀 폼에서 추가 항목을 펼친 횟수'),
            'sticky_submit' => array('label' => '모바일 제출', 'desc' => '모바일 sticky CTA로 제출 시도'),
            'db_received' => array('label' => 'DB 접수', 'desc' => '실제 상담 DB가 접수된 건수'),
            'success_call_tap' => array('label' => '완료 전화', 'desc' => '접수 완료 후 전화 버튼 탭'),
        );

        $steps = array();
        foreach (array('badge_click', 'extra_fields_open', 'sticky_submit', 'db_received', 'success_call_tap') as $id) {
            $steps[] = array(
                'id' => $id,
                'label' => $labels[$id]['label'],
                'desc' => $labels[$id]['desc'],
                'count' => (int) ($empty_counts[$id] ?? 0),
            );
        }

        $result = array(
            'ready' => false,
            'counts' => $empty_counts,
            'steps' => $steps,
            'rates' => array(
                'extraFromBadge' => 0,
                'stickyFromExtra' => 0,
                'dbFromSticky' => 0,
                'dbFromBadge' => 0,
                'callFromDb' => 0,
            ),
            'insight' => array(
                'tone' => 'info',
                'title' => '위젯 행동 데이터가 아직 없습니다',
                'body' => '최신 HTML 위젯을 설치하면 배지·추가정보·모바일 제출·완료 전화 행동이 유입분석에 쌓입니다.',
            ),
            'byHost' => array(),
            'daily' => array(),
        );

        if ($pt_id <= 0 || !lc_embed_events_ensure_schema()) {
            return $result;
        }

        $table = lc_embed_events_table();
        $counts = $empty_counts;
        $row = lc_sql_fetch(" SELECT
            SUM(CASE WHEN ee_event = 'badge_click' THEN 1 ELSE 0 END) AS badge_click,
            SUM(CASE WHEN ee_event = 'extra_fields_open' THEN 1 ELSE 0 END) AS extra_fields_open,
            SUM(CASE WHEN ee_event = 'sticky_submit' THEN 1 ELSE 0 END) AS sticky_submit,
            SUM(CASE WHEN ee_event = 'success_call_tap' THEN 1 ELSE 0 END) AS success_call_tap
            FROM `{$table}`
            WHERE pt_id = '{$pt_id}'
              AND DATE(ee_created_at) BETWEEN '" . lc_sql_escape($dateFrom) . "' AND '" . lc_sql_escape($dateTo) . "' ");
        if (is_array($row)) {
            $counts['badge_click'] = (int) ($row['badge_click'] ?? 0);
            $counts['extra_fields_open'] = (int) ($row['extra_fields_open'] ?? 0);
            $counts['sticky_submit'] = (int) ($row['sticky_submit'] ?? 0);
            $counts['success_call_tap'] = (int) ($row['success_call_tap'] ?? 0);
        }
        $counts['db_received'] = $db_received;
        $counts['db_approved'] = $db_approved;

        $steps = array();
        foreach (array('badge_click', 'extra_fields_open', 'sticky_submit', 'db_received', 'success_call_tap') as $id) {
            $steps[] = array(
                'id' => $id,
                'label' => $labels[$id]['label'],
                'desc' => $labels[$id]['desc'],
                'count' => (int) ($counts[$id] ?? 0),
            );
        }

        $rate = static function ($num, $den) {
            $num = (int) $num;
            $den = (int) $den;
            if ($den <= 0) {
                return 0.0;
            }
            return round(($num / $den) * 100, 1);
        };

        $rates = array(
            'extraFromBadge' => $rate($counts['extra_fields_open'], $counts['badge_click']),
            'stickyFromExtra' => $rate($counts['sticky_submit'], $counts['extra_fields_open']),
            'dbFromSticky' => $rate($counts['db_received'], max($counts['sticky_submit'], 1) > 0 ? $counts['sticky_submit'] : 0),
            'dbFromBadge' => $rate($counts['db_received'], $counts['badge_click']),
            'callFromDb' => $rate($counts['success_call_tap'], $counts['db_received']),
        );
        // sticky가 0이면 dbFromSticky는 의미 없음 → 0 유지
        if ($counts['sticky_submit'] <= 0) {
            $rates['dbFromSticky'] = 0.0;
        }

        $by_host = array();
        $host_result = lc_sql_query(" SELECT
            IF(ee_page_host = '', '(미기록)', ee_page_host) AS host,
            COUNT(*) AS total,
            SUM(CASE WHEN ee_event = 'badge_click' THEN 1 ELSE 0 END) AS badge_click,
            SUM(CASE WHEN ee_event = 'sticky_submit' THEN 1 ELSE 0 END) AS sticky_submit,
            SUM(CASE WHEN ee_event = 'success_call_tap' THEN 1 ELSE 0 END) AS success_call_tap
            FROM `{$table}`
            WHERE pt_id = '{$pt_id}'
              AND DATE(ee_created_at) BETWEEN '" . lc_sql_escape($dateFrom) . "' AND '" . lc_sql_escape($dateTo) . "'
            GROUP BY host
            ORDER BY total DESC
            LIMIT 8 ", false);
        if ($host_result) {
            while ($h = sql_fetch_array($host_result)) {
                $by_host[] = array(
                    'host' => (string) ($h['host'] ?? ''),
                    'total' => (int) ($h['total'] ?? 0),
                    'badgeClick' => (int) ($h['badge_click'] ?? 0),
                    'stickySubmit' => (int) ($h['sticky_submit'] ?? 0),
                    'successCallTap' => (int) ($h['success_call_tap'] ?? 0),
                );
            }
        }

        $daily = array();
        $start = strtotime($dateFrom);
        $end = strtotime($dateTo);
        if ($start && $end && $end >= $start) {
            $day_map = array();
            for ($ts = $start; $ts <= $end; $ts += 86400) {
                $day = date('Y-m-d', $ts);
                $day_map[$day] = array(
                    'date' => date('m.d', $ts),
                    'badge' => 0,
                    'extra' => 0,
                    'sticky' => 0,
                    'call' => 0,
                );
            }
            $daily_result = lc_sql_query(" SELECT DATE(ee_created_at) AS d, ee_event, COUNT(*) AS cnt
                FROM `{$table}`
                WHERE pt_id = '{$pt_id}'
                  AND DATE(ee_created_at) BETWEEN '" . lc_sql_escape($dateFrom) . "' AND '" . lc_sql_escape($dateTo) . "'
                GROUP BY d, ee_event ", false);
            if ($daily_result) {
                while ($drow = sql_fetch_array($daily_result)) {
                    $d = (string) ($drow['d'] ?? '');
                    if (!isset($day_map[$d])) {
                        continue;
                    }
                    $ev = (string) ($drow['ee_event'] ?? '');
                    $cnt = (int) ($drow['cnt'] ?? 0);
                    if ($ev === 'badge_click') {
                        $day_map[$d]['badge'] = $cnt;
                    } elseif ($ev === 'extra_fields_open') {
                        $day_map[$d]['extra'] = $cnt;
                    } elseif ($ev === 'sticky_submit') {
                        $day_map[$d]['sticky'] = $cnt;
                    } elseif ($ev === 'success_call_tap') {
                        $day_map[$d]['call'] = $cnt;
                    }
                }
            }
            $daily = array_values($day_map);
        }

        $insight = lc_embed_events_build_insight($counts, $rates);

        return array(
            'ready' => true,
            'counts' => $counts,
            'steps' => $steps,
            'rates' => $rates,
            'insight' => $insight,
            'byHost' => $by_host,
            'daily' => $daily,
        );
    }
}

if (!function_exists('lc_embed_events_build_insight')) {
    /**
     * @param array<string,int> $counts
     * @param array<string,float|int> $rates
     * @return array{tone:string,title:string,body:string}
     */
    function lc_embed_events_build_insight(array $counts, array $rates)
    {
        $badge = (int) ($counts['badge_click'] ?? 0);
        $extra = (int) ($counts['extra_fields_open'] ?? 0);
        $sticky = (int) ($counts['sticky_submit'] ?? 0);
        $db = (int) ($counts['db_received'] ?? 0);
        $call = (int) ($counts['success_call_tap'] ?? 0);
        $event_total = $badge + $extra + $sticky + $call;

        if ($event_total <= 0 && $db <= 0) {
            return array(
                'tone' => 'info',
                'title' => '위젯 행동 데이터가 아직 없습니다',
                'body' => '내 홍보 링크 → HTML 위젯으로 최신 코드를 설치하면, 방문자의 관심·제출·전화 행동이 여기에 연결됩니다.',
            );
        }

        if ($event_total <= 0 && $db > 0) {
            return array(
                'tone' => 'warn',
                'title' => 'DB는 있는데 행동 이벤트가 없습니다',
                'body' => '예전 설치 코드일 수 있습니다. HTML 위젯을 다시 복사해 설치하면 배지·추가정보·모바일 제출까지 숫자로 보입니다.',
            );
        }

        if ($badge > 20 && $db > 0 && (float) ($rates['dbFromBadge'] ?? 0) < 5) {
            return array(
                'tone' => 'warn',
                'title' => '관심은 많은데 접수가 적습니다',
                'body' => '배지 클릭 대비 DB 전환이 낮습니다. 전환 탭에서 CTA 문구·미니멀 폼·혜택 한 줄을 업종에 맞게 바꿔 보세요.',
            );
        }

        if ($extra > 15 && $sticky + $db < max(3, (int) floor($extra * 0.1))) {
            return array(
                'tone' => 'warn',
                'title' => '추가정보를 많이 열어보지만 제출이 적습니다',
                'body' => '지역·문의 필드가 부담일 수 있습니다. 필드를 줄이거나 혜택 문구를 더 명확히 하면 제출이 늘 수 있습니다.',
            );
        }

        if ($db > 5 && $call === 0) {
            return array(
                'tone' => 'tip',
                'title' => '완료 전화 CTA 활용 여지를 확인하세요',
                'body' => '접수는 있는데 완료 화면 전화 탭이 없습니다. 안심번호 배정과 「완료 화면 전화 CTA」 옵션을 확인해 주세요.',
            );
        }

        if ($sticky > 0 && $db > 0 && (float) ($rates['dbFromSticky'] ?? 0) >= 40) {
            return array(
                'tone' => 'good',
                'title' => '모바일 sticky CTA가 잘 작동합니다',
                'body' => '모바일 제출 시도 대비 접수율이 높습니다. 지금 설정을 유지하면서 업종 CTA만 다듬어 보세요.',
            );
        }

        if ($db > 0) {
            return array(
                'tone' => 'good',
                'title' => '위젯 퍼널이 쌓이고 있습니다',
                'body' => '배지→추가정보→제출→접수→전화 단계별 숫자를 비교해, 이탈이 큰 단계의 문구·옵션만 먼저 바꿔 보세요.',
            );
        }

        return array(
            'tone' => 'tip',
            'title' => '관심 행동은 있는데 접수가 없습니다',
            'body' => '제출 버튼 문구·개인정보 동의 문구·모바일 sticky를 점검하고, 테스트 접수로 흐름을 한 번 확인해 보세요.',
        );
    }
}
