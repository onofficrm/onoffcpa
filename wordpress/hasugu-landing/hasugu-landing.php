<?php
/**
 * Plugin Name: 하수구폴리스 랜딩
 * Plugin URI:  https://onoffcpa.icrm.co.kr/
 * Description: 하수구·배관 CPA 상담 랜딩을 워드프레스 페이지에 삽입합니다. 온오프CPA 상담폼(lkCode)과 연동됩니다.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      온오프CPA
 * Author URI:  https://onoffcpa.icrm.co.kr/
 * License:     GPLv2 or later
 * Text Domain: hasugu-landing
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HSG_LANDING_VERSION', '1.0.0');
define('HSG_LANDING_PLUGIN_FILE', __FILE__);
define('HSG_LANDING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HSG_LANDING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSG_LANDING_DEFAULT_ORIGIN', 'https://onoffcpa.icrm.co.kr');

require_once HSG_LANDING_PLUGIN_DIR . 'includes/class-settings.php';
require_once HSG_LANDING_PLUGIN_DIR . 'includes/class-assets.php';
require_once HSG_LANDING_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once HSG_LANDING_PLUGIN_DIR . 'includes/class-page-template.php';

final class Hasugu_Landing_Plugin
{
    /** @var Hasugu_Landing_Plugin|null */
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        Hasugu_Landing_Settings::init();
        Hasugu_Landing_Assets::init();
        Hasugu_Landing_Shortcode::init();
        Hasugu_Landing_Page_Template::init();
    }
}

Hasugu_Landing_Plugin::instance();
