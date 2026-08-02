<?php
if (!defined('ABSPATH')) {
    exit;
}

class LinkConnect_Lead_Shortcode
{
    public static function init()
    {
        add_shortcode('linkconnect_lead', array(__CLASS__, 'render'));
        add_shortcode('lc_lead', array(__CLASS__, 'render'));
    }

    /**
     * @param array<string,string>|string $atts
     */
    public static function render($atts = array())
    {
        $defaults = LinkConnect_Lead_Settings::get();
        $atts = shortcode_atts(
            array(
                'lk_code' => $defaults['lk_code'],
                'origin'  => $defaults['origin'],
                'channel' => $defaults['channel'],
                'sub_id'  => $defaults['sub_id'],
                'id'      => '',
            ),
            $atts,
            'linkconnect_lead'
        );

        $lk_code = trim((string) $atts['lk_code']);
        if ($lk_code === '') {
            if (current_user_can('manage_options')) {
                return '<p style="color:#be123c;font-size:14px;">온오프CPA: 설정에서 홍보코드(lkCode)를 입력해 주세요. <a href="' . esc_url(admin_url('options-general.php?page=linkconnect-lead')) . '">설정 열기</a></p>';
            }
            return '';
        }

        $origin = untrailingslashit(esc_url_raw((string) $atts['origin']));
        if ($origin === '' || !preg_match('#^https?://#i', $origin)) {
            $origin = LC_LEAD_DEFAULT_ORIGIN;
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $lk_code);
        if ($safe === '') {
            $safe = 'form';
        }
        $mount_id = trim((string) $atts['id']);
        if ($mount_id === '') {
            $mount_id = 'lc-lead-' . substr($safe, 0, 24) . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
        }

        $script_src = $origin . '/plugin/linkconnect/assets/js/lead-embed.js';
        $channel = sanitize_text_field((string) $atts['channel']);
        $sub_id = sanitize_text_field((string) $atts['sub_id']);

        // 스크립트는 숏코드마다 인라인으로 로드해 페이지 어디에 넣어도 동작
        ob_start();
        ?>
        <div id="<?php echo esc_attr($mount_id); ?>" class="linkconnect-lead-root" data-lk-code="<?php echo esc_attr($lk_code); ?>"<?php
            echo $channel !== '' ? ' data-channel="' . esc_attr($channel) . '"' : '';
            echo $sub_id !== '' ? ' data-sub-id="' . esc_attr($sub_id) . '"' : '';
        ?>></div>
        <script
            src="<?php echo esc_url($script_src); ?>"
            data-lk-code="<?php echo esc_attr($lk_code); ?>"
            data-target="#<?php echo esc_attr($mount_id); ?>"
            <?php echo $channel !== '' ? 'data-channel="' . esc_attr($channel) . '" ' : ''; ?>
            <?php echo $sub_id !== '' ? 'data-sub-id="' . esc_attr($sub_id) . '" ' : ''; ?>
            data-config-url="<?php echo esc_url($origin . '/plugin/linkconnect/api/embed.php'); ?>"
            async
        ></script>
        <?php
        return (string) ob_get_clean();
    }
}
