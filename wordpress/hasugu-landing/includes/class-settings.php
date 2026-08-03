<?php
if (!defined('ABSPATH')) {
    exit;
}

class Hasugu_Landing_Settings
{
    const OPTION_KEY = 'hasugu_landing_settings';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_filter('plugin_action_links_' . plugin_basename(HSG_LANDING_PLUGIN_FILE), array(__CLASS__, 'action_links'));
    }

    public static function defaults()
    {
        return array(
            'lk_code'     => '',
            'origin'      => HSG_LANDING_DEFAULT_ORIGIN,
            'channel'     => 'wordpress',
            'sub_id'      => 'hasugu-landing',
            'brand_name'  => '하수구폴리스',
            'phone'       => '',
            'privacy_url' => '',
            'areas'       => '서울 · 인천 · 경기',
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
            __('하수구폴리스 랜딩', 'hasugu-landing'),
            __('하수구폴리스 랜딩', 'hasugu-landing'),
            'manage_options',
            'hasugu-landing',
            array(__CLASS__, 'render_page')
        );
    }

    public static function register_settings()
    {
        register_setting(
            'hasugu_landing_group',
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

        $out['lk_code']     = isset($input['lk_code']) ? sanitize_text_field($input['lk_code']) : '';
        $out['channel']     = isset($input['channel']) ? sanitize_text_field($input['channel']) : 'wordpress';
        $out['sub_id']      = isset($input['sub_id']) ? sanitize_text_field($input['sub_id']) : '';
        $out['brand_name']  = isset($input['brand_name']) ? sanitize_text_field($input['brand_name']) : '하수구폴리스';
        $out['phone']       = isset($input['phone']) ? sanitize_text_field($input['phone']) : '';
        $out['areas']       = isset($input['areas']) ? sanitize_text_field($input['areas']) : '서울 · 인천 · 경기';
        $out['privacy_url'] = isset($input['privacy_url']) ? esc_url_raw(trim((string) $input['privacy_url'])) : '';

        $origin = isset($input['origin']) ? esc_url_raw(trim((string) $input['origin'])) : HSG_LANDING_DEFAULT_ORIGIN;
        $origin = untrailingslashit($origin);
        if ($origin === '' || !preg_match('#^https?://#i', $origin)) {
            $origin = HSG_LANDING_DEFAULT_ORIGIN;
        }
        $out['origin'] = $origin;

        if ($out['brand_name'] === '') {
            $out['brand_name'] = '하수구폴리스';
        }

        return $out;
    }

    public static function action_links($links)
    {
        $url = admin_url('options-general.php?page=hasugu-landing');
        array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('설정', 'hasugu-landing') . '</a>');
        return $links;
    }

    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $opts = self::get();
        $key = self::OPTION_KEY;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('하수구폴리스 랜딩', 'hasugu-landing'); ?></h1>
            <p><?php echo esc_html__('파트너 홍보코드(lkCode)를 저장한 뒤, 페이지에 숏코드를 넣거나 「하수구폴리스 랜딩」 페이지 템플릿을 선택하세요.', 'hasugu-landing'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('hasugu_landing_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="hsg_lk_code"><?php echo esc_html__('홍보코드 (lkCode)', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[lk_code]" type="text" id="hsg_lk_code" value="<?php echo esc_attr($opts['lk_code']); ?>" class="regular-text" placeholder="예: 7f2939daec" />
                            <p class="description"><?php echo esc_html__('파트너센터 → 내 홍보 링크에서 확인할 수 있습니다. URL의 ?lkCode= 값이 있으면 그 값이 우선합니다.', 'hasugu-landing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_origin"><?php echo esc_html__('온오프CPA 도메인', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[origin]" type="url" id="hsg_origin" value="<?php echo esc_attr($opts['origin']); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__('기본값: https://onoffcpa.icrm.co.kr', 'hasugu-landing'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_brand"><?php echo esc_html__('브랜드명', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[brand_name]" type="text" id="hsg_brand" value="<?php echo esc_attr($opts['brand_name']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_phone"><?php echo esc_html__('표시 전화 (선택)', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[phone]" type="text" id="hsg_phone" value="<?php echo esc_attr($opts['phone']); ?>" class="regular-text" placeholder="070-0000-0000" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_areas"><?php echo esc_html__('출동 권역', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[areas]" type="text" id="hsg_areas" value="<?php echo esc_attr($opts['areas']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_channel"><?php echo esc_html__('채널명 (선택)', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[channel]" type="text" id="hsg_channel" value="<?php echo esc_attr($opts['channel']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_sub_id"><?php echo esc_html__('링크이름 / sub_id (선택)', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[sub_id]" type="text" id="hsg_sub_id" value="<?php echo esc_attr($opts['sub_id']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hsg_privacy"><?php echo esc_html__('개인정보처리방침 URL (선택)', 'hasugu-landing'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr($key); ?>[privacy_url]" type="url" id="hsg_privacy" value="<?php echo esc_attr($opts['privacy_url']); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('설정 저장', 'hasugu-landing')); ?>
            </form>

            <hr />
            <h2><?php echo esc_html__('사용 방법', 'hasugu-landing'); ?></h2>
            <ol>
                <li><?php echo esc_html__('위에서 홍보코드를 저장합니다.', 'hasugu-landing'); ?></li>
                <li><?php echo esc_html__('새 페이지를 만들고 페이지 템플릿에서 「하수구폴리스 랜딩」을 선택하거나, 본문에 숏코드를 넣습니다.', 'hasugu-landing'); ?></li>
            </ol>
            <p><code>[hasugu_landing]</code></p>
            <p><?php echo esc_html__('특정 코드로 덮어쓰려면:', 'hasugu-landing'); ?> <code>[hasugu_landing lk_code="YOUR_CODE"]</code></p>
            <p><?php echo esc_html__('동의어 숏코드:', 'hasugu-landing'); ?> <code>[drainpolice_landing]</code></p>
        </div>
        <?php
    }
}
