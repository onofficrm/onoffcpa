<?php
/**
 * LinkConnect — 회원(로그인·가입) 화면 브랜딩
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (is_file(G5_PATH . '/_site.config.php')) {
    include_once G5_PATH . '/_site.config.php';
}

if (!function_exists('linkconnect_is_active_site')) {
    function linkconnect_is_active_site()
    {
        return function_exists('g5site_cfg') && g5site_cfg('home_builder_bridge_id', '') === 'linkconnect';
    }
}

if (!function_exists('linkconnect_is_member_bbs_page')) {
    function linkconnect_is_member_bbs_page()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';

        return strpos($script, '/bbs/login.php') !== false
            || strpos($script, '/bbs/register.php') !== false
            || strpos($script, '/bbs/register_form.php') !== false
            || strpos($script, '/bbs/register_result.php') !== false
            || strpos($script, '/bbs/register_email.php') !== false
            || strpos($script, '/bbs/password_lost.php') !== false
            || strpos($script, '/bbs/member_confirm.php') !== false;
    }
}

if (!function_exists('linkconnect_member_use_minimal_layout')) {
    /** 로그인과 동일하게 그누보드 기본 헤더·사이드·푸터 없이 회원 스킨만 표시 */
    function linkconnect_member_use_minimal_layout()
    {
        return linkconnect_is_active_site() && linkconnect_is_member_bbs_page();
    }
}

if (!function_exists('linkconnect_member_head_sub_before')) {
    function linkconnect_member_head_sub_before()
    {
        if (!linkconnect_is_active_site() || !linkconnect_is_member_bbs_page()) {
            return;
        }

        global $config;
        $config['cf_title'] = '링크커넥트';
    }
}

if (!function_exists('linkconnect_bootstrap_login_redirect')) {
    function linkconnect_bootstrap_login_redirect()
    {
        static $booted = false;
        if ($booted || !defined('G5_PLUGIN_PATH')) {
            return;
        }

        $base = G5_PLUGIN_PATH . '/linkconnect';
        if (!is_file($base . '/config.php')) {
            return;
        }

        $booted = true;
        require_once $base . '/config.php';

        if (is_file($base . '/inc/db.php')) {
            require_once $base . '/inc/db.php';
        }
        if (is_file($base . '/inc/partner.php')) {
            require_once $base . '/inc/partner.php';
        }
        if (is_file($base . '/inc/merchant.php')) {
            require_once $base . '/inc/merchant.php';
        }
    }
}

if (!function_exists('linkconnect_member_login_home_url')) {
    function linkconnect_member_login_home_url()
    {
        linkconnect_bootstrap_login_redirect();

        if (function_exists('lc_spa_url')) {
            return lc_spa_url('/');
        }
        if (function_exists('lc_public_home_url')) {
            return lc_public_home_url();
        }

        return defined('G5_URL') ? G5_URL : '';
    }
}

if (!function_exists('linkconnect_mb_should_home_after_login')) {
    /**
     * 파트너·광고주·관리자센터 계정은 로그인 후 홈으로 이동
     */
    function linkconnect_mb_should_home_after_login(array $mb)
    {
        $mb_id = trim((string) ($mb['mb_id'] ?? ''));
        if ($mb_id === '') {
            return false;
        }

        linkconnect_bootstrap_login_redirect();

        $level = isset($mb['mb_level']) ? (int) $mb['mb_level'] : 0;

        if (function_exists('is_admin') && is_admin($mb_id) === 'super') {
            return true;
        }
        if (defined('LC_ADMIN_MIN_LEVEL') && $level >= LC_ADMIN_MIN_LEVEL) {
            return true;
        }
        if (defined('LC_LINKCONNECT_ADMIN_MIN_LEVEL') && $level >= LC_LINKCONNECT_ADMIN_MIN_LEVEL) {
            return true;
        }

        if (function_exists('lc_db_installed') && lc_db_installed()) {
            if (function_exists('lc_get_partner_by_mb_id') && lc_get_partner_by_mb_id($mb_id)) {
                return true;
            }
            if (function_exists('lc_get_merchant_by_mb_id') && lc_get_merchant_by_mb_id($mb_id)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('linkconnect_member_login_check_home_redirect')) {
    function linkconnect_member_login_check_home_redirect($mb, $link, $is_social_login)
    {
        if (!linkconnect_is_active_site() || !is_array($mb) || empty($mb['mb_id'])) {
            return;
        }

        if (!linkconnect_mb_should_home_after_login($mb)) {
            return;
        }

        $home = linkconnect_member_login_home_url();
        if ($home === '' || $home === $link) {
            return;
        }

        goto_url($home);
    }
}

if (function_exists('add_event')) {
    add_event('head_sub_before', 'linkconnect_member_head_sub_before', 5, 0);
    add_event('member_login_check', 'linkconnect_member_login_check_home_redirect', 10, 3);
}
