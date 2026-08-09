<?php
if (!defined('ABSPATH')) {
    exit;
}

class LinkConnect_Lead_Block
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register'));
    }

    public static function register()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'linkconnect-lead-block',
            LC_LEAD_PLUGIN_URL . 'assets/block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render'),
            LC_LEAD_VERSION,
            true
        );

        register_block_type(
            'linkconnect/lead-form',
            array(
                'api_version'     => 2,
                'title'           => __('LinkConnect 상담폼', 'linkconnect-lead'),
                'description'     => __('파트너 홍보코드가 연결된 상담신청 위젯을 삽입합니다.', 'linkconnect-lead'),
                'category'        => 'widgets',
                'icon'            => 'feedback',
                'keywords'        => array('linkconnect', '상담', 'lead', 'cpa', 'widget'),
                'editor_script'   => 'linkconnect-lead-block',
                'render_callback' => array(__CLASS__, 'render'),
                'attributes'      => array(
                    'lkCode'     => array('type' => 'string', 'default' => ''),
                    'widgetKey'  => array('type' => 'string', 'default' => ''),
                    'mode'       => array('type' => 'string', 'default' => ''),
                    'channel'    => array('type' => 'string', 'default' => ''),
                    'subId'      => array('type' => 'string', 'default' => ''),
                ),
            )
        );
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public static function render($attributes = array())
    {
        $atts = array();
        if (!empty($attributes['lkCode'])) {
            $atts['lk_code'] = (string) $attributes['lkCode'];
        }
        if (!empty($attributes['widgetKey'])) {
            $atts['widget_key'] = (string) $attributes['widgetKey'];
        }
        if (!empty($attributes['mode'])) {
            $atts['mode'] = (string) $attributes['mode'];
        }
        if (!empty($attributes['channel'])) {
            $atts['channel'] = (string) $attributes['channel'];
        }
        if (!empty($attributes['subId'])) {
            $atts['sub_id'] = (string) $attributes['subId'];
        }
        return LinkConnect_Lead_Shortcode::render($atts);
    }
}
