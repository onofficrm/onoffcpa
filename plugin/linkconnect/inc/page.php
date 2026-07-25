<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_render_public_page')) {
    /**
     * @param string $view inc/views/ 하위 경로 (예: public/home)
     */
    function lc_render_public_page($view)
    {
        global $lc_page_title, $lc_active_nav, $lc_body_class;

        if (!isset($lc_body_class)) {
            $lc_body_class = 'lc-app lc-app--public';
        }

        include LC_LAYOUT_PATH . '/header.php';
        $main_class = isset($lc_main_class) ? (string) $lc_main_class : 'lc-main';
        echo '<main class="' . lc_h($main_class) . '">';
        lc_include_view($view);
        echo '</main>';
        include LC_LAYOUT_PATH . '/footer.php';
    }
}

if (!function_exists('lc_render_center_page')) {
    /**
     * @param string $center partner|merchant|admin
     * @param string $view   inc/views/ 하위 경로
     */
    function lc_render_center_page($center, $view)
    {
        global $lc_page_title, $lc_active_nav, $lc_sidebar_active, $lc_body_class, $lc_center;

        $lc_center = $center;
        if (!isset($lc_body_class)) {
            $lc_body_class = 'lc-app lc-app--center lc-app--' . $center;
        }

        if ($center === LC_CENTER_ADMIN) {
            lc_require_admin_access();
        }

        if ($center === LC_CENTER_PARTNER) {
            lc_require_partner_access();
        }

        if ($center === LC_CENTER_MERCHANT) {
            lc_require_merchant_access();
        }

        include LC_LAYOUT_PATH . '/header.php';
        echo '<div class="lc-shell lc-shell--' . lc_h($center) . '">';
        lc_include_layout($center . '_sidebar.php');
        echo '<div class="lc-content lc-content--' . lc_h($center) . '">';
        if ($center === LC_CENTER_PARTNER && is_file(LC_LAYOUT_PATH . '/partner_toolbar.php')) {
            include LC_LAYOUT_PATH . '/partner_toolbar.php';
        }
        if ($center === LC_CENTER_MERCHANT && is_file(LC_LAYOUT_PATH . '/merchant_toolbar.php')) {
            include LC_LAYOUT_PATH . '/merchant_toolbar.php';
        }
        if ($center === LC_CENTER_ADMIN && is_file(LC_LAYOUT_PATH . '/admin_toolbar.php')) {
            include LC_LAYOUT_PATH . '/admin_toolbar.php';
        }
        echo '<div class="lc-center-body">';
        lc_include_view($view);
        echo '</div></div></div>';
        $lc_show_footer = false;
        include LC_LAYOUT_PATH . '/footer.php';
    }
}

if (!function_exists('lc_include_view')) {
    function lc_include_view($view)
    {
        $path = LC_VIEWS_PATH . '/' . str_replace('..', '', (string) $view) . '.php';
        if (is_file($path)) {
            include $path;
            return;
        }
        echo '<div class="lc-placeholder">뷰 파일이 없습니다: ' . lc_h($view) . '</div>';
    }
}

if (!function_exists('lc_include_layout')) {
    function lc_include_layout($file, array $vars = array())
    {
        foreach ($vars as $key => $value) {
            $$key = $value;
        }
        $layout = LC_LAYOUT_PATH . '/' . ltrim((string) $file, '/');
        if (is_file($layout)) {
            include $layout;
        }
    }
}
