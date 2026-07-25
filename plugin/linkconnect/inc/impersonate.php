<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_impersonate_session_key')) {
    function lc_impersonate_session_key()
    {
        return 'lc_impersonate';
    }
}

if (!function_exists('lc_impersonate_get')) {
    function lc_impersonate_get()
    {
        if (!lc_is_super_admin()) {
            return null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $key = lc_impersonate_session_key();
        if (empty($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            return null;
        }

        $data = $_SESSION[$key];
        $type = trim((string) ($data['type'] ?? ''));
        $id = (int) ($data['id'] ?? 0);

        if (!in_array($type, array('partner', 'merchant'), true) || $id <= 0) {
            return null;
        }

        return array(
            'type' => $type,
            'id'   => $id,
            'by'   => (string) ($data['by'] ?? ''),
        );
    }
}

if (!function_exists('lc_impersonate_is_active')) {
    function lc_impersonate_is_active($type = '')
    {
        $current = lc_impersonate_get();
        if (!$current) {
            return false;
        }

        if ($type === '') {
            return true;
        }

        return (string) $current['type'] === (string) $type;
    }
}

if (!function_exists('lc_impersonate_record')) {
    function lc_impersonate_record()
    {
        $current = lc_impersonate_get();
        if (!$current) {
            return null;
        }

        if ($current['type'] === 'partner' && function_exists('lc_get_partner_by_id')) {
            return lc_get_partner_by_id((int) $current['id']);
        }

        if ($current['type'] === 'merchant' && function_exists('lc_get_merchant_by_id')) {
            return lc_get_merchant_by_id((int) $current['id']);
        }

        return null;
    }
}

if (!function_exists('lc_impersonate_label')) {
    function lc_impersonate_label()
    {
        $record = lc_impersonate_record();
        if (!is_array($record)) {
            return '';
        }

        $current = lc_impersonate_get();
        if (!$current) {
            return '';
        }

        if ($current['type'] === 'partner') {
            $code = (string) ($record['pt_code'] ?? '');
            $name = (string) ($record['pt_name'] ?? '');

            return $name !== '' ? $name . ($code !== '' ? ' (' . $code . ')' : '') : $code;
        }

        $company = (string) ($record['mt_company'] ?? '');
        $code = (string) ($record['mt_code'] ?? '');

        return $company !== '' ? $company . ($code !== '' ? ' (' . $code . ')' : '') : $code;
    }
}

if (!function_exists('lc_impersonate_start')) {
    function lc_impersonate_start($type, $id)
    {
        if (!lc_is_super_admin()) {
            return array('ok' => false, 'message' => '최고관리자만 이용할 수 있습니다.');
        }

        global $member;
        $type = trim((string) $type);
        $id = (int) $id;

        if (!in_array($type, array('partner', 'merchant'), true) || $id <= 0) {
            return array('ok' => false, 'message' => '유효하지 않은 요청입니다.');
        }

        if ($type === 'partner') {
            $record = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($id) : null;
            if (!is_array($record) || empty($record['pt_id'])) {
                return array('ok' => false, 'message' => '파트너를 찾을 수 없습니다.');
            }
        } else {
            $record = function_exists('lc_get_merchant_by_id') ? lc_get_merchant_by_id($id) : null;
            if (!is_array($record) || empty($record['mt_id'])) {
                return array('ok' => false, 'message' => '광고주를 찾을 수 없습니다.');
            }
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION[lc_impersonate_session_key()] = array(
            'type'       => $type,
            'id'         => $id,
            'by'         => isset($member['mb_id']) ? (string) $member['mb_id'] : '',
            'started_at' => time(),
        );

        if (function_exists('lc_admin_log_write')) {
            lc_admin_log_write(
                'impersonate_start',
                $type,
                $id,
                ($type === 'partner' ? '파트너' : '광고주') . ' 계정으로 보기: ' . lc_impersonate_label()
            );
        }

        if (function_exists('lc_impersonate_log_start')) {
            lc_impersonate_log_start($type, $id, lc_impersonate_label());
        }

        return array(
            'ok'      => true,
            'message' => '해당 계정 화면으로 전환합니다.',
            'type'    => $type,
            'id'      => $id,
            'label'   => lc_impersonate_label(),
            'redirect'=> $type === 'partner' ? '/partner' : '/advertiser',
        );
    }
}

if (!function_exists('lc_impersonate_clear')) {
    function lc_impersonate_clear()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $current = lc_impersonate_get();
        if ($current && function_exists('lc_impersonate_log_end_active')) {
            lc_impersonate_log_end_active((string) ($current['type'] ?? ''), (int) ($current['id'] ?? 0));
        }
        unset($_SESSION[lc_impersonate_session_key()]);

        if ($current && function_exists('lc_admin_log_write')) {
            lc_admin_log_write(
                'impersonate_exit',
                (string) ($current['type'] ?? ''),
                (int) ($current['id'] ?? 0),
                '계정 보기 종료'
            );
        }

        return array('ok' => true, 'message' => '관리자 모드로 돌아갑니다.');
    }
}

if (!function_exists('lc_impersonate_state_for_api')) {
    function lc_impersonate_state_for_api()
    {
        $current = lc_impersonate_get();
        if (!$current) {
            return array(
                'active' => false,
                'type'   => null,
                'id'     => null,
                'label'  => '',
            );
        }

        return array(
            'active' => true,
            'type'   => (string) $current['type'],
            'id'     => (int) $current['id'],
            'label'  => lc_impersonate_label(),
        );
    }
}
