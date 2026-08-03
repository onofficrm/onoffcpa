<?php
if (!defined('ABSPATH')) {
    exit;
}

class Hasugu_Landing_Shortcode
{
    public static function init()
    {
        add_shortcode('hasugu_landing', array(__CLASS__, 'render'));
        add_shortcode('drainpolice_landing', array(__CLASS__, 'render'));
    }

    /**
     * @param array<string,string>|string $atts
     */
    public static function render($atts = array())
    {
        $defaults = Hasugu_Landing_Settings::get();
        $atts = shortcode_atts(
            array(
                'lk_code'     => $defaults['lk_code'],
                'origin'      => $defaults['origin'],
                'channel'     => $defaults['channel'],
                'sub_id'      => $defaults['sub_id'],
                'brand_name'  => $defaults['brand_name'],
                'phone'       => $defaults['phone'],
                'areas'       => $defaults['areas'],
                'privacy_url' => $defaults['privacy_url'],
            ),
            $atts,
            'hasugu_landing'
        );

        $origin = untrailingslashit(esc_url_raw((string) $atts['origin']));
        if ($origin === '' || !preg_match('#^https?://#i', $origin)) {
            $origin = HSG_LANDING_DEFAULT_ORIGIN;
        }

        $lk_code = trim((string) $atts['lk_code']);
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $lk_code);
        if ($safe === '') {
            $safe = 'form';
        }
        $mount_id = 'hsg-lead-' . substr($safe, 0, 20) . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 6);

        $ctx = array(
            'lk_code'     => $lk_code,
            'origin'      => $origin,
            'channel'     => sanitize_text_field((string) $atts['channel']),
            'sub_id'      => sanitize_text_field((string) $atts['sub_id']),
            'brand_name'  => sanitize_text_field((string) $atts['brand_name']),
            'phone'       => sanitize_text_field((string) $atts['phone']),
            'areas'       => sanitize_text_field((string) $atts['areas']),
            'privacy_url' => esc_url((string) $atts['privacy_url']),
            'mount_id'    => $mount_id,
            'script_src'  => $origin . '/plugin/linkconnect/assets/js/lead-embed.js',
            'config_url'  => $origin . '/plugin/linkconnect/api/embed.php',
        );

        Hasugu_Landing_Assets::mark_needed();
        Hasugu_Landing_Assets::enqueue();

        ob_start();
        echo Hasugu_Landing_Assets::print_assets_once();
        include HSG_LANDING_PLUGIN_DIR . 'templates/landing.php';
        return (string) ob_get_clean();
    }
}
