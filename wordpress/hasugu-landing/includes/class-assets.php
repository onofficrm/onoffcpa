<?php
if (!defined('ABSPATH')) {
    exit;
}

class Hasugu_Landing_Assets
{
    /** @var bool */
    private static $needed = false;

    public static function init()
    {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue'));
    }

    public static function mark_needed()
    {
        self::$needed = true;
    }

    public static function maybe_enqueue()
    {
        if (!self::$needed && !self::should_load()) {
            return;
        }
        self::enqueue();
    }

    public static function enqueue()
    {
        wp_enqueue_style(
            'hasugu-landing',
            HSG_LANDING_PLUGIN_URL . 'assets/landing.css',
            array(),
            HSG_LANDING_VERSION
        );

        wp_enqueue_script(
            'hasugu-landing',
            HSG_LANDING_PLUGIN_URL . 'assets/landing.js',
            array(),
            HSG_LANDING_VERSION,
            true
        );
    }

    /**
     * 숏코드가 콘텐츠 렌더 시점에 호출될 때 head enqueue를 놓치지 않도록 1회만 출력.
     */
    public static function print_assets_once()
    {
        static $printed = false;
        if ($printed) {
            return '';
        }
        // head에서 이미 출력됐으면 중복 방지
        if (wp_style_is('hasugu-landing', 'done')) {
            $printed = true;
            return '';
        }
        $printed = true;
        $css = HSG_LANDING_PLUGIN_URL . 'assets/landing.css?ver=' . rawurlencode(HSG_LANDING_VERSION);
        $js = HSG_LANDING_PLUGIN_URL . 'assets/landing.js?ver=' . rawurlencode(HSG_LANDING_VERSION);
        return '<link rel="stylesheet" href="' . esc_url($css) . '" />'
            . '<script src="' . esc_url($js) . '" defer></script>';
    }

    private static function should_load()
    {
        if (!is_singular()) {
            return false;
        }
        if (get_page_template_slug(get_queried_object_id()) === Hasugu_Landing_Page_Template::SLUG) {
            return true;
        }
        $post = get_post();
        if (!$post) {
            return false;
        }
        return has_shortcode($post->post_content, 'hasugu_landing')
            || has_shortcode($post->post_content, 'drainpolice_landing');
    }
}
