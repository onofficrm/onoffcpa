<?php
/**
 * LinkConnect React SPA — BrowserRouter 경로를 index.php로 전달
 *
 * /partner, /advertiser, /admin 등이 그누보드 게시판 short URL 규칙에 잡히지 않도록
 * add_mod_rewrite_rules 훅으로 board.php 규칙보다 앞에 삽입합니다.
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (is_file(G5_PATH . '/_site.config.php')) {
    include_once G5_PATH . '/_site.config.php';
}

if (!function_exists('linkconnect_spa_rewrite_enabled')) {
    function linkconnect_spa_rewrite_enabled()
    {
        if (!function_exists('g5site_cfg')) {
            return false;
        }

        return g5site_cfg('home_builder_bridge_id', '') === 'linkconnect';
    }
}

if (!function_exists('linkconnect_spa_route_pattern')) {
    function linkconnect_spa_route_pattern()
    {
        // cps 제외 — CPS 미취급, /cps 는 cps/index.php 가 CPA 목록으로 리다이렉트
        return '^(about|affiliate|select-center|cpa-list|cpa|events|notice|community|inquiry|advertiser-apply|terms|privacy|partner|advertiser|admin)(/.*)?$';
    }
}

if (!function_exists('linkconnect_add_tracking_rewrite_rules')) {
    function linkconnect_add_tracking_rewrite_rules($rules, $get_path_url, $base_path, $return_string)
    {
        if (!linkconnect_spa_rewrite_enabled()) {
            return $rules;
        }

        $extra = "# LinkConnect tracking / landing\n";
        $extra .= 'RewriteRule ^r/([a-zA-Z0-9_-]+)$ r/index.php?code=$1 [L,QSA]' . "\n";
        $extra .= 'RewriteRule ^c/([a-zA-Z0-9_-]+)$ c/index.php?code=$1 [L,QSA]' . "\n";
        $extra .= 'RewriteRule ^s/([a-zA-Z0-9_-]+)$ s/index.php?code=$1 [L,QSA]' . "\n";
        $extra .= 'RewriteRule ^go/lp/([A-Za-z0-9_-]+)$ go/lp/index.php?m=$1 [L,QSA]' . "\n";
        $extra .= 'RewriteRule ^api/external/linkprice/postback/?$ api/external/linkprice/postback.php [L,QSA]' . "\n";

        return $rules . $extra;
    }
}

if (!function_exists('linkconnect_add_spa_rewrite_rules')) {
    function linkconnect_add_spa_rewrite_rules($rules, $get_path_url, $base_path, $return_string)
    {
        if (!linkconnect_spa_rewrite_enabled()) {
            return $rules;
        }

        $pattern = linkconnect_spa_route_pattern();
        $extra = "# LinkConnect React SPA (BrowserRouter)\n";
        $extra .= 'RewriteRule ' . $pattern . ' index.php [L,QSA]' . "\n";

        return $rules . $extra;
    }
}

if (function_exists('add_replace')) {
    add_replace('add_mod_rewrite_rules', 'linkconnect_add_tracking_rewrite_rules', 4, 4);
    add_replace('add_mod_rewrite_rules', 'linkconnect_add_spa_rewrite_rules', 5, 4);
}

if (!function_exists('linkconnect_legal_content_redirect')) {
    function linkconnect_legal_content_redirect()
    {
        if (!function_exists('linkconnect_spa_rewrite_enabled') || !linkconnect_spa_rewrite_enabled()) {
            return;
        }

        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        if (strpos($script, 'content.php') === false) {
            return;
        }

        $co_id = isset($_GET['co_id']) ? preg_replace('/[^a-z0-9_]/i', '', (string) $_GET['co_id']) : '';
        if ($co_id === 'provision') {
            header('Location: ' . G5_URL . '/terms', true, 301);
            exit;
        }
        if ($co_id === 'privacy') {
            header('Location: ' . G5_URL . '/privacy', true, 301);
            exit;
        }
    }
}

linkconnect_legal_content_redirect();

if (!function_exists('linkconnect_tracking_home_landing_file')) {
    /**
     * 독립 도메인 루트(/)에 매핑할 머천트 랜딩 파일 (호스팅에 도메인 연결 후 사용)
     *
     * @return string absolute path or ''
     */
    function linkconnect_tracking_home_landing_file($host)
    {
        $host = strtolower(preg_replace('/:\d+$/', '', (string) $host));
        if ($host === '' || !defined('G5_PATH')) {
            return '';
        }

        // 광고상품 cp_tracking_base_url 호스트와 매칭되는 랜딩 경로 조회
        $landing_path = '';
        if (function_exists('sql_fetch')) {
            $table = (defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_') . 'lc_campaigns';
            if (function_exists('sql_escape_string')) {
                $host_esc = sql_escape_string($host);
            } elseif (function_exists('sql_real_escape_string')) {
                $host_esc = sql_real_escape_string($host);
            } else {
                $host_esc = addslashes($host);
            }
            $row = sql_fetch(
                " SELECT cp_landing_url
                  FROM `{$table}`
                  WHERE cp_tracking_base_url <> ''
                    AND cp_tracking_base_url LIKE '%{$host_esc}%'
                    AND cp_status = 'active'
                  ORDER BY cp_id DESC
                  LIMIT 1 "
            );
            if (is_array($row)) {
                $landing_path = (string) ($row['cp_landing_url'] ?? '');
            }
        }

        // 플랫폼별 폴백 (DB 조회 실패·플러그인 미로드 시)
        if ($landing_path === '') {
            if ($host === 'air911.co.kr' || $host === 'www.air911.co.kr') {
                // 링크커넥트 예약 도메인 — onoffcpa 에서는 쓰지 않음
                $is_lc = function_exists('lc_link_is_linkconnect_platform') && lc_link_is_linkconnect_platform();
                if ($is_lc || (!function_exists('lc_link_is_onoffcpa_platform'))) {
                    $landing_path = '/merchant/dasibom/';
                }
            } elseif ($host === 'iloves.kr' || $host === 'www.iloves.kr') {
                // onoffcpa 독립 도메인
                $landing_path = '/merchant/dasibom/';
            }
        }

        if ($landing_path === '') {
            return '';
        }

        $path = parse_url($landing_path, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $landing_path;
        }
        $path = '/' . trim(str_replace('\\', '/', $path), '/');
        if (substr($path, -1) === '/') {
            $candidate = G5_PATH . $path . 'index.php';
        } else {
            $candidate = G5_PATH . $path;
            if (!is_file($candidate) && is_dir(G5_PATH . $path)) {
                $candidate = G5_PATH . $path . '/index.php';
            }
        }

        return is_file($candidate) ? $candidate : '';
    }
}

if (!function_exists('linkconnect_tracking_home_landing_path')) {
    /**
     * 독립 도메인 루트용 랜딩 URL path (/merchant/.../) — 파일 require 실패 시 상대 리다이렉트용
     */
    function linkconnect_tracking_home_landing_path($host)
    {
        $file = linkconnect_tracking_home_landing_file($host);
        if ($file === '' || !defined('G5_PATH')) {
            // 폴백 path만
            $host = strtolower(preg_replace('/:\d+$/', '', (string) $host));
            if ($host === 'iloves.kr' || $host === 'www.iloves.kr') {
                return '/merchant/dasibom/';
            }
            if ($host === 'air911.co.kr' || $host === 'www.air911.co.kr') {
                return '/merchant/dasibom/';
            }

            return '';
        }
        $rel = substr($file, strlen(G5_PATH));
        $rel = str_replace('\\', '/', $rel);
        if (substr($rel, -9) === 'index.php') {
            $rel = substr($rel, 0, -9);
        }

        return '/' . trim($rel, '/') . '/';
    }
}

if (!function_exists('linkconnect_tracking_domain_spa_gate')) {
    /**
     * 독립 도메인(비-G5 호스트):
     *  - / → 해당 캠페인 머천트 랜딩(다시봄 등)
     *  - /r /c /s /merchant /plugin 허용
     *  - 파트너/관리 SPA 등은 메인 사이트로 이동
     */
    function linkconnect_tracking_domain_spa_gate()
    {
        if (!linkconnect_spa_rewrite_enabled() || !defined('G5_URL') || G5_URL === '') {
            return;
        }

        if (function_exists('lc_link_request_host')) {
            $host = lc_link_request_host();
        } else {
            $http_host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
            $http_host = preg_replace('/:\d+$/', '', $http_host);
            $xff_host = '';
            if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $xff_host = strtolower(trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_HOST'])[0]));
                $xff_host = preg_replace('/:\d+$/', '', $xff_host);
            }
            $main_hosts_early = array('onoffcpa.icrm.co.kr', 'www.onoffcpa.icrm.co.kr', 'onoffcpa.iwinv.net', 'www.onoffcpa.iwinv.net');
            $g5_host_early = defined('G5_URL') ? parse_url((string) G5_URL, PHP_URL_HOST) : '';
            if (is_string($g5_host_early) && $g5_host_early !== '') {
                $main_hosts_early[] = strtolower($g5_host_early);
            }
            if ($xff_host !== '' && $xff_host !== $http_host && in_array($http_host, array_values(array_unique($main_hosts_early)), true)) {
                $host = $xff_host;
            } else {
                $host = $http_host;
            }
        }
        $host = preg_replace('/:\d+$/', '', $host);
        if ($host === '') {
            return;
        }

        // onoffcpa 메인 호스트만 사용 (linkconnect.co.kr 와 분리)
        if (function_exists('lc_link_main_site_hosts')) {
            $main_hosts = lc_link_main_site_hosts();
        } else {
            $main_hosts = array();
            $g5_host = parse_url((string) G5_URL, PHP_URL_HOST);
            if (is_string($g5_host) && $g5_host !== '') {
                $main_hosts[] = strtolower($g5_host);
            }
            $main_hosts[] = 'onoffcpa.icrm.co.kr';
            $main_hosts[] = 'www.onoffcpa.icrm.co.kr';
            $main_hosts[] = 'onoffcpa.iwinv.net';
            $main_hosts[] = 'www.onoffcpa.iwinv.net';
            $main_hosts = array_values(array_unique($main_hosts));
        }
        if (in_array($host, $main_hosts, true)) {
            return;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        // 루트 = 독립 도메인 홈 랜딩
        if ($path === '/' || $path === '/index.php') {
            $file = linkconnect_tracking_home_landing_file($host);
            if ($file !== '') {
                require $file;
                exit;
            }
            // Worker가 Location(origin 루트)을 공개 도메인으로 다시 쓰면 루프가 난다.
            // 같은 호스트의 머천트 경로로 상대 리다이렉트한다.
            $landing_rel = function_exists('linkconnect_tracking_home_landing_path')
                ? linkconnect_tracking_home_landing_path($host)
                : '';
            if ($landing_rel !== '') {
                header('Location: ' . $landing_rel, true, 302);
                exit;
            }
        }

        $allow = array('/r/', '/c/', '/s/', '/merchant/', '/plugin/', '/go/', '/data/', '/api/');
        foreach ($allow as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return;
            }
        }
        if (preg_match('#^/(r|c|s)/[A-Za-z0-9_-]+/?$#', $path)) {
            return;
        }
        // 독립 도메인: 브라우저 기본 요청 /favicon.ico 등
        if (preg_match('#^/(favicon\.ico|favicon\.svg|favicon-32x32\.png|apple-touch-icon(?:-precomposed)?\.png|lawyer-portrait\.jpg)$#', $path, $fm)) {
            $landing_file = linkconnect_tracking_home_landing_file($host);
            $import_id = 'dasibom';
            if ($landing_file !== '' && preg_match('#/merchant/([A-Za-z0-9_-]+)/#', str_replace('\\', '/', $landing_file), $mm)) {
                $import_id = $mm[1];
            }
            $name = $fm[1];
            if ($name === 'apple-touch-icon-precomposed.png') {
                $name = 'apple-touch-icon.png';
            }
            $icon = G5_PATH . '/plugin/onoff-builder-bridge/imports/' . $import_id . '/' . $name;
            if ($name === 'lawyer-portrait.jpg' && !is_file($icon)) {
                $icon = G5_PATH . '/lawyer-portrait.jpg';
            }
            if (is_file($icon)) {
                $mimes = array(
                    'favicon.ico' => 'image/x-icon',
                    'favicon.svg' => 'image/svg+xml',
                    'favicon-32x32.png' => 'image/png',
                    'apple-touch-icon.png' => 'image/png',
                    'lawyer-portrait.jpg' => 'image/jpeg',
                );
                if (!headers_sent()) {
                    header('Content-Type: ' . (isset($mimes[$name]) ? $mimes[$name] : 'application/octet-stream'));
                    header('Cache-Control: public, max-age=604800');
                    header('Content-Length: ' . (string) filesize($icon));
                }
                readfile($icon);
                exit;
            }
        }
        if ($path === '/robots.txt') {
            return;
        }

        $dest = rtrim((string) G5_URL, '/');
        if ($path !== '/' && $path !== '/index.php') {
            $dest .= $path;
            $query = parse_url($uri, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                $dest .= '?' . $query;
            }
        }

        if (!headers_sent()) {
            header('Location: ' . $dest, true, 302);
        }
        exit;
    }
}

linkconnect_tracking_domain_spa_gate();
