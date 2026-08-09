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
            'lk_code'     => '',
            'widget_key'  => '',
            'origin'      => LC_LEAD_DEFAULT_ORIGIN,
            'channel'     => 'wordpress',
            'sub_id'      => '',
            'mode'        => 'form',
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

    public static function normalize_mode($mode)
    {
        $mode = strtolower(trim((string) $mode));
        if ($mode === 'modal' || $mode === 'btn') {
            $mode = 'button';
        }
        if ($mode === 'call' || $mode === 'tel') {
            $mode = 'phone';
        }
        if (!in_array($mode, array('form', 'button', 'phone'), true)) {
            $mode = 'form';
        }
        return $mode;
    }

    public static function normalize_widget_key($key)
    {
        $key = strtolower(trim((string) $key));
        if ($key === '' || !preg_match('/^wgt_[a-z0-9]{16,32}$/', $key)) {
            return '';
        }
        return $key;
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
        $out['widget_key'] = isset($input['widget_key'])
            ? self::normalize_widget_key($input['widget_key'])
            : '';
        $out['channel'] = isset($input['channel']) ? sanitize_text_field($input['channel']) : 'wordpress';
        $out['sub_id']  = isset($input['sub_id']) ? sanitize_text_field($input['sub_id']) : '';
        $out['mode'] = isset($input['mode']) ? self::normalize_mode($input['mode']) : 'form';

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
            <p><?php echo esc_html__('파트너센터에서 발급한 홍보코드(lkCode)·위젯 키를 입력한 뒤, 페이지에 숏코드 또는 블록을 넣으세요.', 'linkconnect-lead'); ?></p>

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
                        <th scope="row"><label for="lc_widget_key"><?php echo esc_html__('위젯 키 (권장)', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_KEY); ?>[widget_key]" type="text" id="lc_widget_key" value="<?php echo esc_attr($opts['widget_key']); ?>" class="regular-text" placeholder="wgt_..." />
                            <p class="description"><?php echo esc_html__('파트너센터 HTML 위젯 안내에서 발급한 키입니다. 발급된 경우 필수입니다.', 'linkconnect-lead'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lc_mode"><?php echo esc_html__('위젯 형태', 'linkconnect-lead'); ?></label></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[mode]" id="lc_mode">
                                <option value="form" <?php selected($opts['mode'], 'form'); ?>><?php echo esc_html__('폼형 (인라인)', 'linkconnect-lead'); ?></option>
                                <option value="button" <?php selected($opts['mode'], 'button'); ?>><?php echo esc_html__('버튼형 (모달)', 'linkconnect-lead'); ?></option>
                                <option value="phone" <?php selected($opts['mode'], 'phone'); ?>><?php echo esc_html__('전화형', 'linkconnect-lead'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lc_origin"><?php echo esc_html__('LinkConnect 도메인', 'linkconnect-lead'); ?></label></th>
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
                <li><?php echo esc_html__('위에서 홍보코드·위젯 키를 저장합니다.', 'linkconnect-lead'); ?></li>
                <li><?php echo esc_html__('페이지/글 편집에서 숏코드 또는 「온오프CPA 상담폼」 블록을 넣습니다.', 'linkconnect-lead'); ?></li>
                <li><?php echo esc_html__('(권장) 파트너센터에서 허용 도메인에 이 사이트 주소를 등록합니다.', 'linkconnect-lead'); ?></li>
            </ol>
            <p><code>[linkconnect_lead]</code></p>
            <p><?php echo esc_html__('옵션 예시:', 'linkconnect-lead'); ?> <code>[linkconnect_lead lk_code="YOUR_CODE" widget_key="wgt_xxx" mode="button"]</code></p>

            <h2><?php echo esc_html__('디자인 · GTM', 'linkconnect-lead'); ?></h2>
            <ul>
                <li><?php echo esc_html__('강조색·제목·버튼/전화 라벨·표시 필드·개인정보 문구·완료 후 이동 URL은 파트너센터 HTML 위젯 안내에서 저장합니다.', 'linkconnect-lead'); ?></li>
                <li><?php echo esc_html__('GTM: 파트너센터에서 전환 추적을 켜면 접수 성공 시 dataLayer 이벤트(기본 lc_lead_submit)가 전송됩니다. 테마에 GTM이 설치되어 있어야 합니다.', 'linkconnect-lead'); ?></li>
                <li><?php echo esc_html__('위젯은 iframe으로 삽입되어 테마 CSS 충돌을 줄입니다.', 'linkconnect-lead'); ?></li>
            </ul>
        </div>
        <?php
    }
}
