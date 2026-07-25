<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('onoff_builder_get_home_bridge_id')) {
    function onoff_builder_get_home_bridge_id()
    {
        $id = '';
        if (function_exists('g5site_cfg')) {
            $id = onoff_builder_sanitize_project_id(g5site_cfg('home_builder_bridge_id', ''));
        }

        if ($id === '' && function_exists('onoff_builder_read_runtime_config')) {
            $runtime = onoff_builder_read_runtime_config();
            $id = isset($runtime['home_builder_bridge_id'])
                ? onoff_builder_sanitize_project_id($runtime['home_builder_bridge_id'])
                : '';
        }

        return $id;
    }
}

if (!function_exists('onoff_builder_home_enabled')) {
    function onoff_builder_home_enabled()
    {
        if (function_exists('g5site_cfg_bool') && !g5site_cfg_bool('onoff_builder_bridge_enabled', true)) {
            return false;
        }

        $id = onoff_builder_get_home_bridge_id();

        return $id !== '' && onoff_builder_project_exists($id);
    }
}

if (!function_exists('onoff_builder_spa_route_prefixes')) {
    /**
     * LinkConnect React SPA (BrowserRouter) 최상위 경로
     *
     * @return string[]
     */
    function onoff_builder_spa_route_prefixes()
    {
        return array(
            'about',
            'affiliate',
            'notice',
            'select-center',
            'cpa-list',
            'cpa',
            'cps',
            'events',
            'partner',
            'advertiser',
            'admin',
        );
    }
}

if (!function_exists('onoff_builder_get_request_path')) {
    function onoff_builder_get_request_path()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        if (defined('G5_URL')) {
            $base_path = parse_url(G5_URL, PHP_URL_PATH);
            if (is_string($base_path) && $base_path !== '' && $base_path !== '/') {
                if (strpos($path, $base_path) === 0) {
                    $path = substr($path, strlen($base_path));
                    if ($path === '') {
                        $path = '/';
                    }
                }
            }
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/index.php') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }
}

if (!function_exists('onoff_builder_is_spa_request')) {
    function onoff_builder_is_spa_request()
    {
        $path = onoff_builder_get_request_path();
        if ($path === '/') {
            return true;
        }

        $relative = ltrim($path, '/');
        foreach (onoff_builder_spa_route_prefixes() as $prefix) {
            if ($relative === $prefix || strpos($relative, $prefix . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('onoff_builder_maybe_render_home')) {
    /**
     * index.php 에서 호출 — 홈(/) 및 SPA 경로를 빌더 페이지로 출력
     *
     * @return bool 렌더 후 종료해야 하면 true
     */
    function onoff_builder_maybe_render_home()
    {
        if (!onoff_builder_home_enabled()) {
            return false;
        }

        if (!onoff_builder_is_spa_request()) {
            return false;
        }

        $id = onoff_builder_get_home_bridge_id();
        onoff_builder_render_import_page($id);

        return true;
    }
}
