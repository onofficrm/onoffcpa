<?php
if (!defined('ABSPATH')) {
    exit;
}

class LinkConnect_Lead_Settings
{
    const OPTION_KEY = 'linkconnect_lead_settings';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_filter('plugin_action_links_' . plugin_basename(LC_LEAD_PLUGIN_FILE), array(__CLASS__, 'action_links'));
    }

    public static function defaults()
    {
        return array(
            'lk_code' => '',
            'origin'  => LC_LEAD_DEFAULT_ORIGIN,
            'channel' => 'wordpress',
            'sub_id'  => '',
        );
    }

    public static function get()
    {
        $stored = get_option(self::OPTION_KEY, array());
        if (!is_array($stored)) {
            $stored = array();
        }
        return wp_parse_args($stored, self::defaults());
    }

    public static function register_menu()
    {
        add_options_page(
            __('온오프CPA 상담폼', 'linkconnect-lead'),
            __('온오프CPA 상담폼', 'linkconnect-lead'),
            'manage_options',
            'linkconnect-lead',
            array(__CLASS__, 'render_page')
        );
    }

    public static function register_settings()
    {
        register_setting(
            'linkconnect_lead_group',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize'),
                'default'           => self::defaults(),
            )
        );
    }

    public static function sanitize($input)
    {
        $out = self::defaults();
        if (!is_array($input)) {
            return $out;
        }

        $out['lk_code'] = isset($input['lk_code']) ? sanitize_text_field($input['lk_code']) : '';
        $out['channel'] = isset($input['channel']) ? sanitize_text_field($input['channel']) : 'wordpress';
        $out['sub_id']  = isset($input['sub_id']) ? sanitize_text_field($input['sub_id']) : '';

        $origin = isset($input['origin']) ? esc_url_raw(trim((string) $input['origin'])) : LC_LEAD_DEFAULT_ORIGIN;
        $origin = untrailingslashit($origin);
        if ($origin === '' || !preg_match('#^https?://#i', $origin)) {
            $origin = LC_LEAD_DEFAULT_ORIGIN;
        }
        $out['origin'] = $origin;

        return $out;
    }

    public static function action_links($links)
    {
        $url = admin_url('options-general.php?page=linkconnect-lead');
        array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('설정', 'linkconnect-lead') . '</a>');
        return $links;
    }

    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $opts = self::get();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('온오프CPA 상담폼', 'linkconnect-lead'); ?></h1>
            <p><?php echo esc_html__('파트너센터에서 발급한 홍보코드(lkCode)를 입력한 뒤, 페이지에 숏코드 또는 블록을 넣으세요.', 'linkconnect-lead'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('linkconnect_lead_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="lc_lk_code"><?php echo esc_html__('홍보코드 (lkCode)', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[lk_code]" type="text" id="lc_lk_code" value="<?php echo esc_attr($opts['lk_code']); ?>" class="regular-text" placeholder="예: 7f2939daec" required />
                            <p class="description"><?php echo esc_html__('파트너센터 → 내 홍보 링크에서 확인할 수 있습니다.', 'linkconnect-lead'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lc_origin"><?php echo esc_html__('온오프CPA 도메인', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[origin]" type="url" id="lc_origin" value="<?php echo esc_attr($opts['origin']); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('기본값: https://onoffcpa.icrm.co.kr (변경 불필요)', 'linkconnect-lead'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lc_channel"><?php echo esc_html__('채널명 (선택)', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[channel]" type="text" id="lc_channel" value="<?php echo esc_attr($opts['channel']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lc_sub_id"><?php echo esc_html__('링크이름 / sub_id (선택)', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[sub_id]" type="text" id="lc_sub_id" value="<?php echo esc_attr($opts['sub_id']); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('설정 저장', 'linkconnect-lead')); ?>
            </form>

            <hr />
            <h2><?php echo esc_html__('사용 방법', 'linkconnect-lead'); ?></h2>
            <ol>
                <li><?php echo esc_html__('위에서 홍보코드를 저장합니다.', 'linkconnect-lead'); ?></li>
                <li><?php echo esc_html__('페이지/글 편집에서 숏코드 또는 「온오프CPA 상담폼」 블록을 넣습니다.', 'linkconnect-lead'); ?></li>
            </ol>
            <p><code>[linkconnect_lead]</code></p>
            <p><?php echo esc_html__('특정 코드로 덮어쓰려면:', 'linkconnect-lead'); ?> <code>[linkconnect_lead lk_code="YOUR_CODE"]</code></p>
        </div>
        <?php
    }
}
