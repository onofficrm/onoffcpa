<?php
/**
 * Plugin Name: 온오프CPA 상담폼
 * Plugin URI:  https://onoffcpa.icrm.co.kr/
 * Description: 파트너 홍보코드(lkCode)로 온오프CPA 상담신청 폼을 워드프레스에 삽입합니다.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      온오프CPA
 * Author URI:  https://onoffcpa.icrm.co.kr/
 * License:     GPLv2 or later
 * Text Domain: linkconnect-lead
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LC_LEAD_VERSION', '1.0.0');
define('LC_LEAD_PLUGIN_FILE', __FILE__);
define('LC_LEAD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LC_LEAD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LC_LEAD_DEFAULT_ORIGIN', 'https://onoffcpa.icrm.co.kr');

require_once LC_LEAD_PLUGIN_DIR . 'includes/class-settings.php';
require_once LC_LEAD_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once LC_LEAD_PLUGIN_DIR . 'includes/class-block.php';

final class LinkConnect_Lead_Plugin
{
    /** @var LinkConnect_Lead_Plugin|null */
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
        LinkConnect_Lead_Settings::init();
        LinkConnect_Lead_Shortcode::init();
        LinkConnect_Lead_Block::init();

        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('linkconnect-lead', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

LinkConnect_Lead_Plugin::instance();
