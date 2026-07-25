<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_abuse_compute_conversion_score')) {
    function lc_abuse_compute_conversion_score(array $payload, $duplicate = false)
    {
        $score = 0;
        if ($duplicate) {
            $score += 40;
        }
        $phone = preg_replace('/\D/', '', (string) ($payload['phone'] ?? ''));
        if ($phone !== '' && strlen($phone) < 10) {
            $score += 20;
        }
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name !== '' && mb_strlen($name, 'UTF-8') <= 1) {
            $score += 10;
        }
        $channel = strtolower(trim((string) ($payload['channel'] ?? '')));
        $forbidden = array('당근', 'daangn', 'carrot', 'telegram', '텔레그램');
        foreach ($forbidden as $word) {
            if ($channel !== '' && strpos($channel, $word) !== false) {
                $score += 30;
                break;
            }
        }
        return min(100, $score);
    }
}

if (!function_exists('lc_abuse_check_duplicate')) {
    function lc_abuse_check_duplicate(array $payload)
    {
        if (!lc_db_installed()) {
            return array('duplicate' => false, 'count' => 0);
        }

        $phone = preg_replace('/\D/', '', (string) ($payload['phone'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $cp_id = (int) ($payload['cp_id'] ?? 0);
        if ($phone === '' && $email === '') {
            return array('duplicate' => false, 'count' => 0);
        }

        $days = function_exists('lc_settings_get_int') ? lc_settings_get_int('duplicateDays', 30) : 30;
        $by_phone = function_exists('lc_settings_get_bool') ? lc_settings_get_bool('duplicateByPhone', true) : true;
        $by_campaign = function_exists('lc_settings_get_bool') ? lc_settings_get_bool('duplicateByCampaign', true) : true;
        $table = lc_table('conversions');
        $where = array("cv_created_at >= DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)");
        if ($by_phone && $phone !== '') {
            $where[] = "REGEXP_REPLACE(cv_phone, '[^0-9]', '') = '" . lc_sql_escape($phone) . "'";
        } elseif ($email !== '') {
            $where[] = "cv_email = '" . lc_sql_escape($email) . "'";
        }
        if ($by_campaign && $cp_id > 0) {
            $where[] = 'cp_id = ' . $cp_id;
        }
        $row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` WHERE " . implode(' AND ', $where) . " ", false);
        $count = (int) ($row['cnt'] ?? 0);
        return array('duplicate' => $count > 0, 'count' => $count);
    }
}

if (!function_exists('lc_abuse_check_recent_duplicate')) {
    /**
     * 최근 N시간 내 동일 연락처·캠페인 중복 접수 여부 (사용자 재제출 차단용)
     *
     * @return array{duplicate:bool,count:int}
     */
    function lc_abuse_check_recent_duplicate(array $payload, $hours = 24)
    {
        if (!lc_db_installed()) {
            return array('duplicate' => false, 'count' => 0);
        }

        $phone = preg_replace('/\D/', '', (string) ($payload['phone'] ?? ''));
        $cp_id = (int) ($payload['cp_id'] ?? 0);
        if ($phone === '' || $cp_id <= 0) {
            return array('duplicate' => false, 'count' => 0);
        }

        $hours = max(1, min(168, (int) $hours));
        $table = lc_table('conversions');
        $row = lc_sql_fetch("
            SELECT COUNT(*) AS cnt
            FROM `{$table}`
            WHERE cp_id = {$cp_id}
              AND REGEXP_REPLACE(cv_phone, '[^0-9]', '') = '" . lc_sql_escape($phone) . "'
              AND cv_created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
        ", false);

        $count = (int) ($row['cnt'] ?? 0);
        return array('duplicate' => $count > 0, 'count' => $count);
    }
}

if (!function_exists('lc_abuse_refresh_partner_score')) {
    function lc_abuse_refresh_partner_score($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return 0;
        }
        $cv = lc_table('conversions');
        $row = lc_sql_fetch("
            SELECT
                COUNT(*) AS total_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt,
                SUM(CASE WHEN cv_is_duplicate = 1 THEN 1 ELSE 0 END) AS dup_cnt,
                AVG(cv_abuse_score) AS avg_abuse
            FROM `{$cv}`
            WHERE pt_id = {$pt_id}
              AND cv_created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", false);
        $total = (int) ($row['total_cnt'] ?? 0);
        $rejected = (int) ($row['rejected_cnt'] ?? 0);
        $dup = (int) ($row['dup_cnt'] ?? 0);
        $avg = (float) ($row['avg_abuse'] ?? 0);
        $score = (int) round($avg);
        if ($total > 0) {
            $cancel_rate = ($rejected / $total) * 100;
            if ($cancel_rate >= 50) {
                $score += 30;
            } elseif ($cancel_rate >= 30) {
                $score += 15;
            }
            if ($dup > 0) {
                $score += min(20, $dup * 5);
            }
        }
        $score = min(100, max(0, $score));
        lc_sql_query(" UPDATE `" . lc_table('partners') . "` SET pt_abuse_score = {$score} WHERE pt_id = {$pt_id} ", false);
        return $score;
    }
}

if (!function_exists('lc_abuse_check_cancel_spike')) {
    function lc_abuse_check_cancel_spike($pt_id = 0, $cp_id = 0)
    {
        if (!lc_db_installed()) {
            return;
        }
        $cv = lc_table('conversions');
        $where = array("cv_created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $ref_type = 'partner';
        $ref_id = (int) $pt_id;
        $label = '파트너';
        if ($cp_id > 0) {
            $where[] = 'cp_id = ' . (int) $cp_id;
            $ref_type = 'campaign';
            $ref_id = (int) $cp_id;
            $label = '캠페인';
        } elseif ($pt_id > 0) {
            $where[] = 'pt_id = ' . (int) $pt_id;
        } else {
            return;
        }
        $row = lc_sql_fetch("
            SELECT
                COUNT(*) AS total_cnt,
                SUM(CASE WHEN cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "' THEN 1 ELSE 0 END) AS rejected_cnt
            FROM `{$cv}`
            WHERE " . implode(' AND ', $where) . "
        ", false);
        $total = (int) ($row['total_cnt'] ?? 0);
        $rejected = (int) ($row['rejected_cnt'] ?? 0);
        if ($total < 5) {
            return;
        }
        $rate = ($rejected / $total) * 100;
        if ($rate < 40) {
            return;
        }
        if (function_exists('lc_notification_recent_exists') && lc_notification_recent_exists('admin', 0, 'abuse', 24)) {
            return;
        }
        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'abuse',
                'title'   => $label . ' 취소율 급증',
                'body'    => '최근 7일 취소율 ' . round($rate, 1) . '% (' . $rejected . '/' . $total . ')',
                'link'    => '/admin/partners',
                'refType' => $ref_type,
                'refId'   => $ref_id,
            ));
        }
    }
}

if (!function_exists('lc_abuse_on_conversion_created')) {
    function lc_abuse_on_conversion_created($cv_id, array $payload, $duplicate = false, $abuse_score = 0)
    {
        $cv_id = (int) $cv_id;
        $pt_id = (int) ($payload['pt_id'] ?? 0);
        $cp_id = (int) ($payload['cp_id'] ?? 0);
        if ($cv_id > 0) {
            $table = lc_table('conversions');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? lc_sql_escape((string) $_SERVER['REMOTE_ADDR']) : '';
            lc_sql_query("
                UPDATE `{$table}`
                SET cv_ip = '{$ip}',
                    cv_abuse_score = " . (int) $abuse_score . ",
                    cv_is_duplicate = " . ($duplicate ? 1 : 0) . "
                WHERE cv_id = {$cv_id}
            ", false);
        }
        if ($duplicate && function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'admin',
                'userId'  => 0,
                'type'    => 'conversion',
                'title'   => '중복 DB 탐지',
                'body'    => (string) ($payload['phone'] ?? '') . ' · ' . (string) ($payload['name'] ?? ''),
                'link'    => '/admin/conversions',
                'refType' => 'conversion',
                'refId'   => $cv_id,
            ));
        }
        if ($pt_id > 0) {
            lc_abuse_refresh_partner_score($pt_id);
            lc_abuse_check_cancel_spike($pt_id, 0);
        }
        if ($cp_id > 0) {
            lc_abuse_check_cancel_spike(0, $cp_id);
        }
    }
}
