<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/**
 * Partner/merchant email notification prefs + send helpers.
 *
 * Pref modes for high-volume "new DB":
 * - off
 * - realtime
 * - digest (daily summary flush)
 */

if (!function_exists('lc_email_notify_ensure_schema')) {
    function lc_email_notify_ensure_schema()
    {
        if (!lc_db_installed()) {
            return;
        }

        $partners = lc_table('partners');
        $merchants = lc_table('merchants');
        $digest = lc_table('notify_digest');

        if (lc_db_table_exists($partners) && !lc_db_column_exists($partners, 'pt_notify_prefs')) {
            lc_sql_query(" ALTER TABLE `{$partners}` ADD COLUMN `pt_notify_prefs` text AFTER `pt_balance` ", false);
        }
        if (lc_db_table_exists($merchants) && !lc_db_column_exists($merchants, 'mt_notify_prefs')) {
            lc_sql_query(" ALTER TABLE `{$merchants}` ADD COLUMN `mt_notify_prefs` text AFTER `mt_balance` ", false);
        }

        if (!lc_db_table_exists($digest)) {
            lc_sql_query(" CREATE TABLE IF NOT EXISTS `{$digest}` (
                `nd_id` int unsigned NOT NULL AUTO_INCREMENT,
                `nd_center` varchar(20) NOT NULL DEFAULT '',
                `nd_user_id` int unsigned NOT NULL DEFAULT 0,
                `nd_type` varchar(40) NOT NULL DEFAULT '',
                `nd_count` int unsigned NOT NULL DEFAULT 0,
                `nd_last_subject` varchar(200) NOT NULL DEFAULT '',
                `nd_last_body` varchar(500) NOT NULL DEFAULT '',
                `nd_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `nd_last_sent_at` datetime DEFAULT NULL,
                PRIMARY KEY (`nd_id`),
                UNIQUE KEY `uk_center_user_type` (`nd_center`, `nd_user_id`, `nd_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ", false);
        }
    }
}

if (!function_exists('lc_email_notify_partner_defaults')) {
    function lc_email_notify_partner_defaults()
    {
        return array(
            'dbReceived'     => 'realtime', // off | realtime | digest
            'dbApproved'     => true,
            'dbRejected'     => true,
            'settlementPaid' => true,
        );
    }
}

if (!function_exists('lc_email_notify_merchant_defaults')) {
    function lc_email_notify_merchant_defaults()
    {
        return array(
            'dbReceived' => 'realtime', // off | realtime | digest
            'lowBalance' => true,
        );
    }
}

if (!function_exists('lc_email_notify_normalize_prefs')) {
    function lc_email_notify_normalize_prefs($center, array $prefs)
    {
        $defaults = $center === 'merchant' ? lc_email_notify_merchant_defaults() : lc_email_notify_partner_defaults();
        $out = $defaults;

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $prefs)) {
                continue;
            }
            if ($key === 'dbReceived') {
                $mode = strtolower(trim((string) $prefs[$key]));
                $out[$key] = in_array($mode, array('off', 'realtime', 'digest'), true) ? $mode : 'realtime';
            } else {
                $val = $prefs[$key];
                $out[$key] = !($val === false || $val === 0 || $val === '0' || $val === 'false' || $val === 'off');
            }
        }

        return $out;
    }
}

if (!function_exists('lc_email_notify_decode_prefs')) {
    function lc_email_notify_decode_prefs($center, $raw)
    {
        $decoded = array();
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $decoded = $json;
            }
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        return lc_email_notify_normalize_prefs($center, $decoded);
    }
}

if (!function_exists('lc_email_notify_get_prefs')) {
    function lc_email_notify_get_prefs($center, $user_id)
    {
        lc_email_notify_ensure_schema();
        $center = $center === 'merchant' ? 'merchant' : 'partner';
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return lc_email_notify_normalize_prefs($center, array());
        }

        if ($center === 'merchant') {
            $row = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($user_id) : null;
            return lc_email_notify_decode_prefs('merchant', is_array($row) ? ($row['mt_notify_prefs'] ?? '') : '');
        }

        $row = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($user_id) : null;
        return lc_email_notify_decode_prefs('partner', is_array($row) ? ($row['pt_notify_prefs'] ?? '') : '');
    }
}

if (!function_exists('lc_email_notify_save_prefs')) {
    /**
     * @return array{ok:bool,message:string,prefs:array}
     */
    function lc_email_notify_save_prefs($center, $user_id, array $prefs)
    {
        lc_email_notify_ensure_schema();
        $center = $center === 'merchant' ? 'merchant' : 'partner';
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return array('ok' => false, 'message' => '대상이 올바르지 않습니다.', 'prefs' => array());
        }

        $normalized = lc_email_notify_normalize_prefs($center, $prefs);
        $json = lc_sql_escape(json_encode($normalized, JSON_UNESCAPED_UNICODE));

        if ($center === 'merchant') {
            $table = lc_table('merchants');
            lc_sql_query(" UPDATE `{$table}` SET mt_notify_prefs = '{$json}', mt_updated_at = NOW() WHERE mt_id = '{$user_id}' LIMIT 1 ", false);
        } else {
            $table = lc_table('partners');
            lc_sql_query(" UPDATE `{$table}` SET pt_notify_prefs = '{$json}', pt_updated_at = NOW() WHERE pt_id = '{$user_id}' LIMIT 1 ", false);
        }

        return array('ok' => true, 'message' => '알림 설정이 저장되었습니다.', 'prefs' => $normalized);
    }
}

if (!function_exists('lc_email_notify_member_email')) {
    function lc_email_notify_member_email($mb_id)
    {
        global $g5;
        $mb_id = trim((string) $mb_id);
        if ($mb_id === '' || empty($g5['member_table'])) {
            return '';
        }

        $row = lc_sql_fetch(" SELECT mb_email FROM `{$g5['member_table']}` WHERE mb_id = '" . lc_sql_escape($mb_id) . "' LIMIT 1 ");
        $email = is_array($row) ? trim((string) ($row['mb_email'] ?? '')) : '';
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return '';
    }
}

if (!function_exists('lc_email_notify_resolve_recipient')) {
    function lc_email_notify_resolve_recipient($center, $user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return array('email' => '', 'name' => '');
        }

        if ($center === 'merchant') {
            $row = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($user_id) : null;
            if (!is_array($row)) {
                return array('email' => '', 'name' => '');
            }
            return array(
                'email' => lc_email_notify_member_email((string) ($row['mb_id'] ?? '')),
                'name'  => (string) ($row['mt_company'] ?? $row['mt_code'] ?? '광고주'),
            );
        }

        $row = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($user_id) : null;
        if (!is_array($row)) {
            return array('email' => '', 'name' => '');
        }
        return array(
            'email' => lc_email_notify_member_email((string) ($row['mb_id'] ?? '')),
            'name'  => (string) ($row['pt_name'] ?? $row['pt_code'] ?? '파트너'),
        );
    }
}

if (!function_exists('lc_email_notify_mailer_ready')) {
    function lc_email_notify_mailer_ready()
    {
        if (!function_exists('mailer') && defined('G5_LIB_PATH') && is_file(G5_LIB_PATH . '/mailer.lib.php')) {
            include_once G5_LIB_PATH . '/mailer.lib.php';
        }
        return function_exists('mailer');
    }
}

if (!function_exists('lc_email_notify_send')) {
    function lc_email_notify_send($to, $subject, $html)
    {
        if (!lc_email_notify_mailer_ready()) {
            return false;
        }

        $to = trim((string) $to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        global $config;
        $from_name = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $from_email = !empty($config['cf_admin_email']) ? (string) $config['cf_admin_email'] : (function_exists('lc_contact_email') ? lc_contact_email() : '');
        if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            return (bool) @mailer($from_name, $from_email, $to, $subject, $html, 1);
        } catch (Throwable $e) {
            error_log('[LinkConnect EmailNotify] send failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('lc_email_notify_esc')) {
    function lc_email_notify_esc($v)
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lc_email_notify_wrap')) {
    function lc_email_notify_wrap($title, $rows_html, $cta_label = '', $cta_url = '')
    {
        $html = '<p><strong>' . lc_email_notify_esc($title) . '</strong></p>';
        $html .= '<table style="border-collapse:collapse;width:100%;max-width:560px;font-size:14px;">' . $rows_html . '</table>';
        if ($cta_label !== '' && $cta_url !== '') {
            $html .= '<p style="margin-top:16px;"><a href="' . lc_email_notify_esc($cta_url) . '">' . lc_email_notify_esc($cta_label) . '</a></p>';
        }
        return $html;
    }
}

if (!function_exists('lc_email_notify_row')) {
    function lc_email_notify_row($label, $value)
    {
        return '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;width:120px;">'
            . lc_email_notify_esc($label) . '</th><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">'
            . lc_email_notify_esc($value) . '</td></tr>';
    }
}

if (!function_exists('lc_email_notify_digest_queue')) {
    function lc_email_notify_digest_queue($center, $user_id, $type, $subject, $body)
    {
        lc_email_notify_ensure_schema();
        $table = lc_table('notify_digest');
        $center = lc_sql_escape($center === 'merchant' ? 'merchant' : 'partner');
        $user_id = (int) $user_id;
        $type = lc_sql_escape(trim((string) $type));
        $subject = lc_sql_escape(function_exists('mb_substr') ? mb_substr((string) $subject, 0, 190) : substr((string) $subject, 0, 190));
        $body = lc_sql_escape(function_exists('mb_substr') ? mb_substr((string) $body, 0, 480) : substr((string) $body, 0, 480));

        $existing = lc_sql_fetch(" SELECT nd_id FROM `{$table}` WHERE nd_center = '{$center}' AND nd_user_id = '{$user_id}' AND nd_type = '{$type}' LIMIT 1 ");
        if ($existing) {
            lc_sql_query(" UPDATE `{$table}` SET
                nd_count = nd_count + 1,
                nd_last_subject = '{$subject}',
                nd_last_body = '{$body}',
                nd_updated_at = NOW()
                WHERE nd_id = '" . (int) $existing['nd_id'] . "' LIMIT 1 ", false);
        } else {
            lc_sql_query(" INSERT INTO `{$table}` SET
                nd_center = '{$center}',
                nd_user_id = '{$user_id}',
                nd_type = '{$type}',
                nd_count = 1,
                nd_last_subject = '{$subject}',
                nd_last_body = '{$body}',
                nd_updated_at = NOW() ", false);
        }
    }
}

if (!function_exists('lc_email_notify_flush_digest')) {
    function lc_email_notify_flush_digest($center, $user_id, $type, $force = false)
    {
        lc_email_notify_ensure_schema();
        $table = lc_table('notify_digest');
        $center_key = $center === 'merchant' ? 'merchant' : 'partner';
        $user_id = (int) $user_id;
        $type = trim((string) $type);

        $row = lc_sql_fetch(" SELECT * FROM `{$table}`
            WHERE nd_center = '" . lc_sql_escape($center_key) . "'
              AND nd_user_id = '{$user_id}'
              AND nd_type = '" . lc_sql_escape($type) . "'
            LIMIT 1 ");
        if (!$row || (int) ($row['nd_count'] ?? 0) <= 0) {
            return false;
        }

        if (!$force) {
            $last_sent = (string) ($row['nd_last_sent_at'] ?? '');
            if ($last_sent !== '' && strtotime($last_sent) > strtotime('-20 hours')) {
                return false;
            }
        }

        $recipient = lc_email_notify_resolve_recipient($center_key, $user_id);
        if ($recipient['email'] === '') {
            return false;
        }

        $count = (int) $row['nd_count'];
        $site = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $label = $type === 'dbReceived' ? '신규 DB' : $type;
        $cta = $center_key === 'merchant'
            ? ((defined('G5_URL') ? G5_URL : '') . '/advertiser/db')
            : ((defined('G5_URL') ? G5_URL : '') . '/partner/db-status');

        $rows = lc_email_notify_row('건수', number_format($count) . '건')
            . lc_email_notify_row('최근 건', (string) ($row['nd_last_subject'] ?? ''))
            . lc_email_notify_row('요약', (string) ($row['nd_last_body'] ?? ''));

        $ok = lc_email_notify_send(
            $recipient['email'],
            '[' . $site . '] ' . $label . ' 요약 알림 (' . number_format($count) . '건)',
            lc_email_notify_wrap($recipient['name'] . '님, ' . $label . ' 요약 안내입니다.', $rows, '센터에서 확인', $cta)
        );

        if ($ok) {
            lc_sql_query(" UPDATE `{$table}` SET nd_count = 0, nd_last_sent_at = NOW(), nd_updated_at = NOW()
                WHERE nd_id = '" . (int) $row['nd_id'] . "' LIMIT 1 ", false);
        }

        return $ok;
    }
}

if (!function_exists('lc_email_notify_dispatch_db_mode')) {
    function lc_email_notify_dispatch_db_mode($center, $user_id, $mode, $subject, $body_html, $digest_subject, $digest_body)
    {
        $mode = strtolower(trim((string) $mode));
        if ($mode === 'off') {
            return false;
        }

        $recipient = lc_email_notify_resolve_recipient($center, $user_id);
        if ($recipient['email'] === '') {
            return false;
        }

        if ($mode === 'digest') {
            lc_email_notify_digest_queue($center, $user_id, 'dbReceived', $digest_subject, $digest_body);
            lc_email_notify_flush_digest($center, $user_id, 'dbReceived', false);
            return true;
        }

        return lc_email_notify_send($recipient['email'], $subject, $body_html);
    }
}

if (!function_exists('lc_email_notify_on_conversion')) {
    function lc_email_notify_on_conversion(array $conversion, $event = 'received')
    {
        $cv_code = (string) ($conversion['cv_code'] ?? '');
        $cp_name = (string) ($conversion['cp_name'] ?? '캠페인');
        $pt_id = (int) ($conversion['pt_id'] ?? 0);
        $mt_id = (int) ($conversion['mt_id'] ?? 0);
        $price = (int) ($conversion['cv_price'] ?? $conversion['cv_partner_price'] ?? 0);
        $reason = trim((string) ($conversion['cv_reject_reason'] ?? ''));
        $site = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $base = defined('G5_URL') ? G5_URL : '';

        if ($event === 'received') {
            if ($mt_id > 0) {
                $prefs = lc_email_notify_get_prefs('merchant', $mt_id);
                $rows = lc_email_notify_row('캠페인', $cp_name) . lc_email_notify_row('DB코드', $cv_code);
                lc_email_notify_dispatch_db_mode(
                    'merchant',
                    $mt_id,
                    $prefs['dbReceived'] ?? 'realtime',
                    '[' . $site . '] 신규 DB 접수: ' . $cp_name,
                    lc_email_notify_wrap('신규 DB가 접수되었습니다.', $rows, '디비 확인', $base . '/advertiser/db'),
                    $cp_name . ' · ' . $cv_code,
                    '신규 DB 접수'
                );
            }
            if ($pt_id > 0) {
                $prefs = lc_email_notify_get_prefs('partner', $pt_id);
                $rows = lc_email_notify_row('캠페인', $cp_name) . lc_email_notify_row('DB코드', $cv_code);
                lc_email_notify_dispatch_db_mode(
                    'partner',
                    $pt_id,
                    $prefs['dbReceived'] ?? 'realtime',
                    '[' . $site . '] 신규 DB 발생: ' . $cp_name,
                    lc_email_notify_wrap('내 링크로 신규 DB가 발생했습니다.', $rows, 'CPA 실적 확인', $base . '/partner/db-status'),
                    $cp_name . ' · ' . $cv_code,
                    '신규 DB 발생'
                );
            }
            return;
        }

        if ($event === 'approved' && $pt_id > 0) {
            $prefs = lc_email_notify_get_prefs('partner', $pt_id);
            if (empty($prefs['dbApproved'])) {
                return;
            }
            $recipient = lc_email_notify_resolve_recipient('partner', $pt_id);
            if ($recipient['email'] === '') {
                return;
            }
            $rows = lc_email_notify_row('캠페인', $cp_name)
                . lc_email_notify_row('DB코드', $cv_code)
                . lc_email_notify_row('확정 적립', number_format($price) . '원');
            lc_email_notify_send(
                $recipient['email'],
                '[' . $site . '] DB 승인 완료 · +' . number_format($price) . '원',
                lc_email_notify_wrap('DB가 승인되어 적립이 확정되었습니다.', $rows, '실적 확인', $base . '/partner/db-status')
            );
            return;
        }

        if ($event === 'rejected' && $pt_id > 0) {
            $prefs = lc_email_notify_get_prefs('partner', $pt_id);
            if (empty($prefs['dbRejected'])) {
                return;
            }
            $recipient = lc_email_notify_resolve_recipient('partner', $pt_id);
            if ($recipient['email'] === '') {
                return;
            }
            $rows = lc_email_notify_row('캠페인', $cp_name)
                . lc_email_notify_row('DB코드', $cv_code)
                . lc_email_notify_row('사유', $reason !== '' ? $reason : '취소/무효 처리');
            lc_email_notify_send(
                $recipient['email'],
                '[' . $site . '] DB 취소/무효 처리 안내',
                lc_email_notify_wrap('DB가 취소/무효 처리되었습니다.', $rows, '취소 내역 확인', $base . '/partner/db-cancel')
            );
        }
    }
}

if (!function_exists('lc_email_notify_on_low_balance')) {
    function lc_email_notify_on_low_balance($mt_id, $balance, $threshold)
    {
        $mt_id = (int) $mt_id;
        if ($mt_id <= 0) {
            return false;
        }

        if (function_exists('lc_settings_get_bool') && !lc_settings_get_bool('notifyLowBalanceEmail', true)) {
            return false;
        }

        $prefs = lc_email_notify_get_prefs('merchant', $mt_id);
        if (empty($prefs['lowBalance'])) {
            return false;
        }

        $recipient = lc_email_notify_resolve_recipient('merchant', $mt_id);
        if ($recipient['email'] === '') {
            return false;
        }

        $site = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $rows = lc_email_notify_row('현재 잔액', number_format((int) $balance) . '원')
            . lc_email_notify_row('기준 금액', number_format((int) $threshold) . '원');
        $base = defined('G5_URL') ? G5_URL : '';

        return lc_email_notify_send(
            $recipient['email'],
            '[' . $site . '] 광고비 잔액 부족 안내',
            lc_email_notify_wrap($recipient['name'] . '님, 광고비 잔액이 기준 이하입니다.', $rows, '광고비 충전', $base . '/advertiser/billing')
        );
    }
}

if (!function_exists('lc_email_notify_on_settlement_paid')) {
    function lc_email_notify_on_settlement_paid(array $settlement)
    {
        $pt_id = (int) ($settlement['pt_id'] ?? 0);
        if ($pt_id <= 0) {
            return false;
        }

        $prefs = lc_email_notify_get_prefs('partner', $pt_id);
        if (empty($prefs['settlementPaid'])) {
            return false;
        }

        $recipient = lc_email_notify_resolve_recipient('partner', $pt_id);
        if ($recipient['email'] === '') {
            return false;
        }

        $amount = (int) ($settlement['st_approved_amount'] ?? 0);
        if ($amount <= 0) {
            $amount = (int) ($settlement['st_amount'] ?? 0);
        }
        $code = (string) ($settlement['st_code'] ?? '');
        $site = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
        $base = defined('G5_URL') ? G5_URL : '';
        $rows = lc_email_notify_row('정산번호', $code)
            . lc_email_notify_row('지급 금액', number_format($amount) . '원');

        // also in-app notification
        if (function_exists('lc_notification_create')) {
            lc_notification_create(array(
                'center'  => 'partner',
                'userId'  => $pt_id,
                'type'    => 'settlement',
                'title'   => '정산 지급 완료',
                'body'    => number_format($amount) . '원 · ' . $code,
                'link'    => '/partner/settlement',
                'refType' => 'settlement',
                'refId'   => (int) ($settlement['st_id'] ?? 0),
            ));
        }

        return lc_email_notify_send(
            $recipient['email'],
            '[' . $site . '] 정산 지급 완료 안내',
            lc_email_notify_wrap('정산 지급이 완료되었습니다.', $rows, '정산 내역 확인', $base . '/partner/settlement')
        );
    }
}

if (!function_exists('lc_email_notify_prefs_meta')) {
    function lc_email_notify_prefs_meta($center)
    {
        if ($center === 'merchant') {
            return array(
                'dbReceived' => array(
                    'label' => '신규 DB 접수',
                    'help'  => '캠페인에 리드가 들어올 때',
                    'type'  => 'mode',
                ),
                'lowBalance' => array(
                    'label' => '광고비 잔액 부족',
                    'help'  => '잔액이 기준 이하일 때',
                    'type'  => 'toggle',
                ),
            );
        }

        return array(
            'dbReceived' => array(
                'label' => '신규 DB 발생',
                'help'  => '내 링크로 리드가 생겼을 때',
                'type'  => 'mode',
            ),
            'dbApproved' => array(
                'label' => 'DB 승인 완료',
                'help'  => '적립 확정 금액 포함',
                'type'  => 'toggle',
            ),
            'dbRejected' => array(
                'label' => 'DB 취소/무효',
                'help'  => '처리 사유 포함',
                'type'  => 'toggle',
            ),
            'settlementPaid' => array(
                'label' => '정산 지급 완료',
                'help'  => '출금/정산 처리 완료 안내',
                'type'  => 'toggle',
            ),
        );
    }
}
