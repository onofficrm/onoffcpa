<?php
if (!defined('ABSPATH')) {
    exit;
}

class Hasugu_Landing_Page_Template
{
    const SLUG = 'hasugu-landing-full.php';

    public static function init()
    {
        add_filter('theme_page_templates', array(__CLASS__, 'register_template'));
        add_filter('template_include', array(__CLASS__, 'load_template'));
    }

    public static function register_template($templates)
    {
        $templates[self::SLUG] = __('하수구폴리스 랜딩', 'hasugu-landing');
        return $templates;
    }

    public static function load_template($template)
    {
        if (!is_page()) {
            return $template;
        }
        $page_template = get_page_template_slug(get_queried_object_id());
        if ($page_template !== self::SLUG) {
            return $template;
        }
        $plugin_template = HSG_LANDING_PLUGIN_DIR . 'templates/page-full.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }
}
