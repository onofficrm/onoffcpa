<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_api_allow_public_cors')) {
    /** 워드프레스·외부 사이트 임베드용 CORS (credentials 없음) */
    function lc_api_allow_public_cors()
    {
        if (headers_sent()) {
            return;
        }
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
        if ($origin !== '' && preg_match('#^https?://#i', $origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Max-Age: 86400');
    }
}

if (!function_exists('lc_api_handle_cors_preflight')) {
    function lc_api_handle_cors_preflight()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        if ($method !== 'OPTIONS') {
            return;
        }
        lc_api_allow_public_cors();
        http_response_code(204);
        exit;
    }
}

if (!function_exists('lc_site_absolute_url')) {
    function lc_site_absolute_url($path = '')
    {
        $base = defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
        if ($base === '' && !empty($_SERVER['HTTP_HOST'])) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
            $base = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        }
        $path = (string) $path;
        if ($path === '') {
            return $base;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('lc_embed_source_label')) {
    function lc_embed_source_label($source = '', $channel = '')
    {
        $s = strtolower(trim((string) $source));
        $c = strtolower(trim((string) $channel));
        if ($s === 'embed' || in_array($c, array('embed', 'wordpress', 'widget', 'external'), true)) {
            return '외부위젯';
        }
        if ($s === 'call') {
            return '콜디비';
        }
        if ($c === 'seo') {
            return 'SEO';
        }
        return $channel !== '' ? (string) $channel : ($source !== '' ? (string) $source : '-');
    }
}

if (!function_exists('lc_embed_brand_name')) {
    function lc_embed_brand_name()
    {
        if (function_exists('lc_settings_get')) {
            $name = trim((string) lc_settings_get('siteName', ''));
            if ($name !== '') {
                return $name;
            }
        }
        if (function_exists('g5site_cfg')) {
            $name = trim((string) g5site_cfg('site_name', ''));
            if ($name !== '') {
                return $name;
            }
        }
        return '온오프CPA';
    }
}

if (!function_exists('lc_embed_script_url')) {
    function lc_embed_script_url()
    {
        return function_exists('lc_asset_url')
            ? lc_asset_url('js/lead-embed.js')
            : lc_site_absolute_url('/plugin/linkconnect/assets/js/lead-embed.js');
    }
}

if (!function_exists('lc_embed_frame_url')) {
    function lc_embed_frame_url()
    {
        return lc_site_absolute_url('/plugin/linkconnect/api/embed_frame.php');
    }
}

if (!function_exists('lc_embed_client_ip')) {
    function lc_embed_client_ip()
    {
        $candidates = array(
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        );
        foreach ($candidates as $raw) {
            $ip = trim(explode(',', (string) $raw)[0]);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('lc_embed_rate_limit_dir')) {
    function lc_embed_rate_limit_dir()
    {
        if (defined('G5_DATA_PATH') && (string) G5_DATA_PATH !== '') {
            $dir = rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/embed_rate';
        } else {
            $dir = rtrim(sys_get_temp_dir(), '/') . '/linkconnect_embed_rate';
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('lc_embed_rate_limit_check')) {
    /**
     * 외부 위젯 스팸 방지: IP(+선택 키) 기준 슬라이딩 윈도우.
     *
     * @return array{ok:bool,message:string,count:int,limit:int}
     */
    function lc_embed_rate_limit_check($key = '', $limit = 8, $window_sec = 600)
    {
        $limit = max(1, (int) $limit);
        $window_sec = max(60, (int) $window_sec);
        $ip = lc_embed_client_ip();
        $bucket = strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '_', $ip . '|' . trim((string) $key)));
        if ($bucket === '') {
            $bucket = 'unknown';
        }
        $file = rtrim(lc_embed_rate_limit_dir(), '/') . '/' . substr(hash('sha256', $bucket), 0, 40) . '.json';
        $now = time();
        $hits = array();
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded) && isset($decoded['hits']) && is_array($decoded['hits'])) {
                foreach ($decoded['hits'] as $ts) {
                    $ts = (int) $ts;
                    if ($ts > 0 && ($now - $ts) < $window_sec) {
                        $hits[] = $ts;
                    }
                }
            }
        }
        if (count($hits) >= $limit) {
            return array(
                'ok'      => false,
                'message' => '잠시 후 다시 시도해 주세요. (요청이 너무 많습니다)',
                'count'   => count($hits),
                'limit'   => $limit,
            );
        }
        $hits[] = $now;
        @file_put_contents(
            $file,
            json_encode(array('hits' => $hits, 'updated' => $now), JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        return array(
            'ok'      => true,
            'message' => '',
            'count'   => count($hits),
            'limit'   => $limit,
        );
    }
}

if (!function_exists('lc_embed_normalize_host')) {
    function lc_embed_normalize_host($host)
    {
        $host = strtolower(preg_replace('/:\d+$/', '', trim((string) $host)));
        if ($host === '') {
            return '';
        }
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }
}

if (!function_exists('lc_embed_host_from_url')) {
    function lc_embed_host_from_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (strpos($url, '://') === false && strpos($url, '/') === false) {
            return lc_embed_normalize_host($url);
        }
        if (strpos($url, '://') === false) {
            $url = 'https://' . ltrim($url, '/');
        }
        return lc_embed_normalize_host((string) parse_url($url, PHP_URL_HOST));
    }
}

if (!function_exists('lc_embed_request_host')) {
    function lc_embed_request_host()
    {
        $candidates = array();
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $candidates[] = (string) $_SERVER['HTTP_ORIGIN'];
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $candidates[] = (string) $_SERVER['HTTP_REFERER'];
        }
        if (isset($_GET['page_url'])) {
            $candidates[] = (string) $_GET['page_url'];
        }
        if (isset($_GET['host'])) {
            $candidates[] = (string) $_GET['host'];
        }
        foreach ($candidates as $candidate) {
            $host = lc_embed_host_from_url($candidate);
            if ($host !== '') {
                return $host;
            }
        }
        return '';
    }
}

if (!function_exists('lc_embed_partner_allowed_domains')) {
    /**
     * @return string[] lowercase hosts without www
     */
    function lc_embed_partner_allowed_domains($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return array();
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_domains')) {
            return array();
        }
        $row = lc_sql_fetch(" SELECT pt_embed_domains FROM `{$partners}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
        if (!is_array($row)) {
            return array();
        }
        $raw = trim((string) ($row['pt_embed_domains'] ?? ''));
        if ($raw === '') {
            return array();
        }
        $list = array();
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $host = lc_embed_host_from_url((string) $item);
                if ($host !== '') {
                    $list[] = $host;
                }
            }
        } else {
            foreach (preg_split('/[\s,]+/', $raw) as $item) {
                $host = lc_embed_host_from_url((string) $item);
                if ($host !== '') {
                    $list[] = $host;
                }
            }
        }
        return array_values(array_unique($list));
    }
}

if (!function_exists('lc_embed_set_partner_allowed_domains')) {
    /**
     * @param string[]|string $domains
     * @return array{ok:bool,message:string,domains:string[]}
     */
    function lc_embed_set_partner_allowed_domains($pt_id, $domains)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 정보가 없습니다.', 'domains' => array());
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_domains')) {
            if (function_exists('lc_db_run_migrations')) {
                lc_db_run_migrations();
            }
        }
        if (!lc_db_column_exists($partners, 'pt_embed_domains')) {
            return array('ok' => false, 'message' => '도메인 설정을 저장할 수 없습니다. 관리자에게 문의해 주세요.', 'domains' => array());
        }

        $list = array();
        if (is_string($domains)) {
            $domains = preg_split('/[\s,]+/', $domains);
        }
        if (is_array($domains)) {
            foreach ($domains as $item) {
                $host = lc_embed_host_from_url((string) $item);
                if ($host !== '') {
                    $list[] = $host;
                }
            }
        }
        $list = array_values(array_unique($list));
        if (count($list) > 30) {
            return array('ok' => false, 'message' => '허용 도메인은 최대 30개까지 등록할 수 있습니다.', 'domains' => array());
        }

        $json = $list ? json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        lc_sql_query(
            " UPDATE `{$partners}` SET pt_embed_domains = '" . lc_sql_escape((string) $json) . "', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' LIMIT 1 ",
            false
        );

        return array(
            'ok'      => true,
            'message' => '허용 도메인을 저장했습니다.',
            'domains' => $list,
        );
    }
}

if (!function_exists('lc_embed_default_options')) {
    /**
     * @return array{accent:string,title:string,submitLabel:string,successMessage:string,successRedirectUrl:string}
     */
    function lc_embed_default_options()
    {
        return array(
            'preset'               => 'default',
            'pcLayout'             => 'auto',
            'accent'               => '#0d9488',
            'title'                => '무료 상담 신청',
            'submitLabel'          => '지금 무료 상담 받기',
            'buttonLabel'          => '지금 무료 상담 받기',
            'callLabel'            => '전화 상담',
            'successMessage'       => '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.',
            'successRedirectUrl'   => '',
            'trackConversion'      => true,
            'conversionEventName'  => 'lc_lead_submit',
            'showRegion'           => true,
            'showInquiry'          => true,
            'privacyText'          => '개인정보 수집·이용에 동의합니다.',
            'requireWidgetKey'     => false,
            'minimalForm'          => true,
            'showTrustBadges'      => true,
            'badgeFree'            => true,
            'badgeCallback'        => true,
            'badgePrivacy'         => true,
            'benefitText'          => '상담비 없음 · 3분 내 연락',
            'ctaHint'              => '영업전화 없음 · 개인정보 안전',
            'showLiveCount'        => true,
            'liveCountText'        => '지금 상담 신청이 활발합니다',
            'stickyMobileCta'      => true,
            'successShowCall'      => true,
            'showFormCall'         => true,
            'successNextStep'      => '담당자가 확인 후 곧 연락드립니다.',
        );
    }
}

if (!function_exists('lc_embed_normalize_accent')) {
    function lc_embed_normalize_accent($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if ($value[0] !== '#') {
            $value = '#' . $value;
        }
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return '';
        }
        return strtolower($value);
    }
}

if (!function_exists('lc_embed_normalize_pc_layout')) {
    /**
     * @return string auto|split|wide|hero
     */
    function lc_embed_normalize_pc_layout($value)
    {
        $value = strtolower(trim((string) $value));
        $allowed = array('auto', 'split', 'wide', 'hero');
        if (in_array($value, $allowed, true)) {
            return $value;
        }
        return 'auto';
    }
}

if (!function_exists('lc_embed_normalize_preset')) {
    /**
     * @return string default|simple|card|bold|soft|dark
     */
    function lc_embed_normalize_preset($value)
    {
        $value = strtolower(trim((string) $value));
        $allowed = array('default', 'simple', 'card', 'bold', 'soft', 'dark');
        if (in_array($value, $allowed, true)) {
            return $value;
        }
        return 'default';
    }
}

if (!function_exists('lc_embed_theme_for_preset')) {
    /**
     * @return array{accent:string,accentText:string,border:string,bg:string,text:string,muted:string,call:string,preset:string,radius:string,shadow:string,padding:string,inputBg:string,headerBg?:string,headerText?:string}
     */
    function lc_embed_theme_for_preset($preset, $accent = '#0d9488')
    {
        $preset = lc_embed_normalize_preset($preset);
        $accent = lc_embed_normalize_accent($accent);
        if ($accent === '') {
            $accent = '#0d9488';
        }
        $base = array(
            'accent'     => $accent,
            'accentText' => '#ffffff',
            'border'     => '#e2e8f0',
            'bg'         => '#ffffff',
            'text'       => '#0f172a',
            'muted'      => '#64748b',
            'call'       => '#059669',
            'preset'     => $preset,
            'radius'     => '16px',
            'shadow'     => '0 8px 24px rgba(15,23,42,.06)',
            'padding'    => '20px',
            'inputBg'    => '#f8fafc',
        );
        if ($preset === 'simple') {
            $base['radius'] = '10px';
            $base['shadow'] = 'none';
            $base['padding'] = '16px';
            $base['inputBg'] = '#ffffff';
        } elseif ($preset === 'card') {
            $base['radius'] = '22px';
            $base['shadow'] = '0 18px 40px rgba(15,23,42,.12)';
            $base['padding'] = '22px';
        } elseif ($preset === 'bold') {
            $base['border'] = 'transparent';
            $base['padding'] = '0 20px 20px';
            $base['headerBg'] = $accent;
            $base['headerText'] = '#ffffff';
            $base['shadow'] = '0 12px 28px rgba(15,23,42,.1)';
        } elseif ($preset === 'soft') {
            $base['border'] = $accent . '33';
            $base['bg'] = $accent . '14';
            $base['call'] = $accent;
            $base['radius'] = '18px';
            $base['shadow'] = '0 8px 20px rgba(15,23,42,.05)';
            $base['inputBg'] = '#ffffff';
        } elseif ($preset === 'dark') {
            $base['accentText'] = '#0f172a';
            $base['border'] = '#334155';
            $base['bg'] = '#0f172a';
            $base['text'] = '#f8fafc';
            $base['muted'] = '#94a3b8';
            $base['call'] = $accent;
            $base['shadow'] = '0 12px 32px rgba(0,0,0,.35)';
            $base['inputBg'] = '#1e293b';
        }
        return $base;
    }
}

if (!function_exists('lc_embed_normalize_redirect_url')) {
    /**
     * 허용 도메인이 있으면 해당 호스트만, 없으면 https/http 절대 URL 허용.
     */
    function lc_embed_normalize_redirect_url($url, $pt_id = 0)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }
        $url = function_exists('mb_substr') ? mb_substr($url, 0, 500) : substr($url, 0, 500);
        $pt_id = (int) $pt_id;
        if ($pt_id > 0) {
            $allowed = lc_embed_partner_allowed_domains($pt_id);
            if ($allowed) {
                $host = lc_embed_normalize_host((string) $parts['host']);
                if ($host === '' || !in_array($host, $allowed, true)) {
                    return false;
                }
            }
        }
        return $url;
    }
}

if (!function_exists('lc_embed_partner_options')) {
    /**
     * @return array{accent:string,title:string,submitLabel:string,successMessage:string,successRedirectUrl:string}
     */
    function lc_embed_partner_options($pt_id)
    {
        $defaults = lc_embed_default_options();
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return $defaults;
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_options')) {
            return $defaults;
        }
        $row = lc_sql_fetch(" SELECT pt_embed_options FROM `{$partners}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
        if (!is_array($row)) {
            return $defaults;
        }
        $raw = trim((string) ($row['pt_embed_options'] ?? ''));
        if ($raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }
        if (isset($decoded['preset'])) {
            $defaults['preset'] = lc_embed_normalize_preset($decoded['preset']);
        }
        if (isset($decoded['pcLayout'])) {
            $defaults['pcLayout'] = lc_embed_normalize_pc_layout($decoded['pcLayout']);
        }
        $accent = lc_embed_normalize_accent($decoded['accent'] ?? '');
        if ($accent !== '') {
            $defaults['accent'] = $accent;
        }
        foreach (array('title', 'submitLabel', 'buttonLabel', 'callLabel', 'successMessage', 'benefitText', 'ctaHint', 'liveCountText', 'successNextStep') as $key) {
            if (!isset($decoded[$key])) {
                continue;
            }
            $val = trim((string) $decoded[$key]);
            if ($val === '') {
                continue;
            }
            $max = ($key === 'benefitText' || $key === 'ctaHint' || $key === 'liveCountText' || $key === 'successNextStep') ? 160 : 120;
            $defaults[$key] = function_exists('mb_substr') ? mb_substr($val, 0, $max) : substr($val, 0, $max);
        }
        if (isset($decoded['successRedirectUrl'])) {
            $redirect = lc_embed_normalize_redirect_url($decoded['successRedirectUrl'], $pt_id);
            if ($redirect === false) {
                $defaults['successRedirectUrl'] = '';
            } else {
                $defaults['successRedirectUrl'] = $redirect;
            }
        }
        if (array_key_exists('trackConversion', $decoded)) {
            $defaults['trackConversion'] = !empty($decoded['trackConversion']);
        }
        if (isset($decoded['conversionEventName'])) {
            $event = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $decoded['conversionEventName']);
            if ($event !== '') {
                $defaults['conversionEventName'] = function_exists('mb_substr')
                    ? mb_substr($event, 0, 64)
                    : substr($event, 0, 64);
            }
        }
        foreach (array(
            'showRegion',
            'showInquiry',
            'requireWidgetKey',
            'minimalForm',
            'showTrustBadges',
            'badgeFree',
            'badgeCallback',
            'badgePrivacy',
            'showLiveCount',
            'stickyMobileCta',
            'successShowCall',
            'showFormCall',
        ) as $boolKey) {
            if (array_key_exists($boolKey, $decoded)) {
                $defaults[$boolKey] = !empty($decoded[$boolKey]);
            }
        }
        if (isset($decoded['privacyText'])) {
            $privacy = trim((string) $decoded['privacyText']);
            if ($privacy !== '') {
                $defaults['privacyText'] = function_exists('mb_substr')
                    ? mb_substr($privacy, 0, 200)
                    : substr($privacy, 0, 200);
            }
        }
        return $defaults;
    }
}

if (!function_exists('lc_embed_partner_has_custom_options')) {
    /** 파트너가 위젯 옵션을 한 번이라도 저장했는지 */
    function lc_embed_partner_has_custom_options($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return false;
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_options')) {
            return false;
        }
        $row = lc_sql_fetch(" SELECT pt_embed_options FROM `{$partners}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
        if (!is_array($row)) {
            return false;
        }
        return trim((string) ($row['pt_embed_options'] ?? '')) !== '';
    }
}

if (!function_exists('lc_embed_set_partner_options')) {
    /**
     * @param array<string,mixed> $options
     * @return array{ok:bool,message:string,options:array}
     */
    function lc_embed_set_partner_options($pt_id, array $options)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 정보가 없습니다.', 'options' => lc_embed_default_options());
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_options')) {
            if (function_exists('lc_db_run_migrations')) {
                lc_db_run_migrations();
            }
        }
        if (!lc_db_column_exists($partners, 'pt_embed_options')) {
            return array('ok' => false, 'message' => '위젯 옵션을 저장할 수 없습니다. 관리자에게 문의해 주세요.', 'options' => lc_embed_default_options());
        }

        $current = lc_embed_partner_options($pt_id);
        $next = $current;

        if (array_key_exists('preset', $options)) {
            $next['preset'] = lc_embed_normalize_preset($options['preset']);
        }
        if (array_key_exists('pcLayout', $options)) {
            $next['pcLayout'] = lc_embed_normalize_pc_layout($options['pcLayout']);
        }
        if (array_key_exists('accent', $options)) {
            $accent = lc_embed_normalize_accent($options['accent']);
            if ($accent === '' && trim((string) $options['accent']) !== '') {
                return array('ok' => false, 'message' => '강조색은 #RGB 또는 #RRGGBB 형식이어야 합니다.', 'options' => $current);
            }
            $next['accent'] = $accent !== '' ? $accent : lc_embed_default_options()['accent'];
        }
        foreach (array('title', 'submitLabel', 'buttonLabel', 'callLabel', 'successMessage', 'benefitText', 'ctaHint', 'liveCountText', 'successNextStep') as $key) {
            if (!array_key_exists($key, $options)) {
                continue;
            }
            $val = trim((string) $options[$key]);
            $max = ($key === 'benefitText' || $key === 'ctaHint' || $key === 'liveCountText' || $key === 'successNextStep') ? 160 : 120;
            if ($val === '') {
                $next[$key] = lc_embed_default_options()[$key] ?? '';
            } else {
                $next[$key] = function_exists('mb_substr') ? mb_substr($val, 0, $max) : substr($val, 0, $max);
            }
        }
        if (array_key_exists('successRedirectUrl', $options)) {
            $redirect_raw = trim((string) $options['successRedirectUrl']);
            if ($redirect_raw === '') {
                $next['successRedirectUrl'] = '';
            } else {
                $redirect = lc_embed_normalize_redirect_url($redirect_raw, $pt_id);
                if ($redirect === false) {
                    $allowed = lc_embed_partner_allowed_domains($pt_id);
                    $hint = $allowed
                        ? '리다이렉트 URL은 허용 도메인(http/https)만 사용할 수 있습니다.'
                        : '리다이렉트 URL은 http(s):// 로 시작하는 절대 주소여야 합니다.';
                    return array('ok' => false, 'message' => $hint, 'options' => $current);
                }
                $next['successRedirectUrl'] = $redirect;
            }
        }
        if (array_key_exists('trackConversion', $options)) {
            $next['trackConversion'] = !empty($options['trackConversion']);
        }
        if (array_key_exists('conversionEventName', $options)) {
            $event = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $options['conversionEventName']);
            $next['conversionEventName'] = $event !== ''
                ? (function_exists('mb_substr') ? mb_substr($event, 0, 64) : substr($event, 0, 64))
                : lc_embed_default_options()['conversionEventName'];
        }
        if (array_key_exists('showRegion', $options)) {
            $next['showRegion'] = !empty($options['showRegion']);
        }
        if (array_key_exists('showInquiry', $options)) {
            $next['showInquiry'] = !empty($options['showInquiry']);
        }
        if (array_key_exists('privacyText', $options)) {
            $privacy = trim((string) $options['privacyText']);
            $next['privacyText'] = $privacy !== ''
                ? (function_exists('mb_substr') ? mb_substr($privacy, 0, 200) : substr($privacy, 0, 200))
                : lc_embed_default_options()['privacyText'];
        }
        foreach (array(
            'requireWidgetKey',
            'minimalForm',
            'showTrustBadges',
            'badgeFree',
            'badgeCallback',
            'badgePrivacy',
            'showLiveCount',
            'stickyMobileCta',
            'successShowCall',
            'showFormCall',
        ) as $boolKey) {
            if (array_key_exists($boolKey, $options)) {
                $next[$boolKey] = !empty($options[$boolKey]);
            }
        }

        if (!empty($next['requireWidgetKey']) && function_exists('lc_embed_ensure_partner_widget_key')) {
            $ensured = lc_embed_ensure_partner_widget_key($pt_id);
            if (empty($ensured['ok'])) {
                return array(
                    'ok'      => false,
                    'message' => (string) ($ensured['message'] ?? '위젯 키 필수 설정에 필요한 키 발급에 실패했습니다.'),
                    'options' => $current,
                );
            }
        }

        $json = json_encode($next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        lc_sql_query(
            " UPDATE `{$partners}` SET pt_embed_options = '" . lc_sql_escape((string) $json) . "', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' LIMIT 1 ",
            false
        );

        return array(
            'ok'      => true,
            'message' => '위젯 디자인·완료 설정을 저장했습니다.',
            'options' => $next,
        );
    }
}

if (!function_exists('lc_embed_platform_host')) {
    function lc_embed_platform_host()
    {
        $candidates = array();
        if (defined('G5_URL')) {
            $candidates[] = (string) parse_url((string) G5_URL, PHP_URL_HOST);
        }
        if (function_exists('lc_site_absolute_url')) {
            $candidates[] = (string) parse_url(lc_site_absolute_url('/'), PHP_URL_HOST);
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $candidates[] = (string) $_SERVER['HTTP_HOST'];
        }
        foreach ($candidates as $host) {
            $norm = lc_embed_normalize_host($host);
            if ($norm !== '') {
                return $norm;
            }
        }
        return '';
    }
}

if (!function_exists('lc_embed_domain_allowed')) {
    /**
     * 허용 도메인이 비어 있으면 전체 허용(기존 호환). 등록된 경우만 검증.
     * 플랫폼 자체 도메인은 미리보기용으로 항상 허용.
     */
    function lc_embed_domain_allowed($pt_id, $request_host = null)
    {
        $host = $request_host !== null ? lc_embed_normalize_host($request_host) : lc_embed_request_host();
        $platform = lc_embed_platform_host();
        if ($host !== '' && $platform !== '' && $host === $platform) {
            return true;
        }
        $allowed = lc_embed_partner_allowed_domains($pt_id);
        if (!$allowed) {
            return true;
        }
        if ($host === '') {
            return false;
        }
        return in_array($host, $allowed, true);
    }
}

if (!function_exists('lc_embed_parse_utm_from_url')) {
    /**
     * @return array{utm_source:string,utm_medium:string,utm_campaign:string}
     */
    function lc_embed_parse_utm_from_url($url)
    {
        $out = array(
            'utm_source'   => '',
            'utm_medium'   => '',
            'utm_campaign' => '',
        );
        $url = trim((string) $url);
        if ($url === '') {
            return $out;
        }
        $query = (string) parse_url($url, PHP_URL_QUERY);
        if ($query === '') {
            return $out;
        }
        $params = array();
        parse_str($query, $params);
        foreach (array('utm_source', 'utm_medium', 'utm_campaign') as $key) {
            if (!empty($params[$key])) {
                $val = trim((string) $params[$key]);
                if (function_exists('mb_substr')) {
                    $val = mb_substr($val, 0, 100);
                } else {
                    $val = substr($val, 0, 100);
                }
                $out[$key] = $val;
            }
        }
        return $out;
    }
}

if (!function_exists('lc_embed_normalize_widget_key')) {
    function lc_embed_normalize_widget_key($key)
    {
        $key = strtolower(trim((string) $key));
        if ($key === '' || !preg_match('/^wgt_[a-z0-9]{16,32}$/', $key)) {
            return '';
        }
        return $key;
    }
}

if (!function_exists('lc_embed_generate_widget_key')) {
    function lc_embed_generate_widget_key()
    {
        try {
            $rand = bin2hex(random_bytes(12));
        } catch (Exception $e) {
            $rand = substr(hash('sha256', uniqid((string) mt_rand(), true)), 0, 24);
        }
        return 'wgt_' . $rand;
    }
}

if (!function_exists('lc_embed_partner_widget_key')) {
    function lc_embed_partner_widget_key($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return '';
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_key')) {
            return '';
        }
        $row = lc_sql_fetch(" SELECT pt_embed_key FROM `{$partners}` WHERE pt_id = '{$pt_id}' LIMIT 1 ");
        if (!is_array($row)) {
            return '';
        }
        return lc_embed_normalize_widget_key($row['pt_embed_key'] ?? '');
    }
}

if (!function_exists('lc_embed_set_partner_widget_key')) {
    /**
     * @return array{ok:bool,message:string,widgetKey:string}
     */
    function lc_embed_set_partner_widget_key($pt_id, $key)
    {
        $pt_id = (int) $pt_id;
        $key = lc_embed_normalize_widget_key($key);
        if ($pt_id <= 0 || $key === '') {
            return array('ok' => false, 'message' => '위젯 키를 저장할 수 없습니다.', 'widgetKey' => '');
        }
        $partners = lc_table('partners');
        if (!function_exists('lc_db_column_exists') || !lc_db_column_exists($partners, 'pt_embed_key')) {
            if (function_exists('lc_db_run_migrations')) {
                lc_db_run_migrations();
            }
        }
        if (!lc_db_column_exists($partners, 'pt_embed_key')) {
            return array('ok' => false, 'message' => '위젯 키 컬럼을 사용할 수 없습니다.', 'widgetKey' => '');
        }
        $dup = lc_sql_fetch(
            " SELECT pt_id FROM `{$partners}` WHERE pt_embed_key = '" . lc_sql_escape($key) . "' AND pt_id <> '{$pt_id}' LIMIT 1 "
        );
        if (is_array($dup)) {
            return array('ok' => false, 'message' => '중복된 위젯 키입니다. 다시 시도해 주세요.', 'widgetKey' => '');
        }
        lc_sql_query(
            " UPDATE `{$partners}` SET pt_embed_key = '" . lc_sql_escape($key) . "', pt_updated_at = NOW() WHERE pt_id = '{$pt_id}' LIMIT 1 ",
            false
        );
        return array('ok' => true, 'message' => '위젯 키를 저장했습니다.', 'widgetKey' => $key);
    }
}

if (!function_exists('lc_embed_ensure_partner_widget_key')) {
    /**
     * 없으면 발급, 있으면 기존 키 반환.
     *
     * @return array{ok:bool,message:string,widgetKey:string,created:bool}
     */
    function lc_embed_ensure_partner_widget_key($pt_id)
    {
        $pt_id = (int) $pt_id;
        $existing = lc_embed_partner_widget_key($pt_id);
        if ($existing !== '') {
            return array('ok' => true, 'message' => '', 'widgetKey' => $existing, 'created' => false);
        }
        for ($i = 0; $i < 5; $i++) {
            $key = lc_embed_generate_widget_key();
            $result = lc_embed_set_partner_widget_key($pt_id, $key);
            if (!empty($result['ok'])) {
                return array(
                    'ok'        => true,
                    'message'   => '위젯 키를 발급했습니다.',
                    'widgetKey' => (string) ($result['widgetKey'] ?? $key),
                    'created'   => true,
                );
            }
        }
        return array('ok' => false, 'message' => '위젯 키 발급에 실패했습니다.', 'widgetKey' => '', 'created' => false);
    }
}

if (!function_exists('lc_embed_rotate_partner_widget_key')) {
    /**
     * @return array{ok:bool,message:string,widgetKey:string}
     */
    function lc_embed_rotate_partner_widget_key($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => '파트너 정보가 없습니다.', 'widgetKey' => '');
        }
        for ($i = 0; $i < 5; $i++) {
            $key = lc_embed_generate_widget_key();
            $result = lc_embed_set_partner_widget_key($pt_id, $key);
            if (!empty($result['ok'])) {
                return array(
                    'ok'        => true,
                    'message'   => '위젯 키를 재발급했습니다. 기존 설치 코드의 키를 새 코드로 교체해 주세요.',
                    'widgetKey' => (string) ($result['widgetKey'] ?? $key),
                );
            }
        }
        return array('ok' => false, 'message' => '위젯 키 재발급에 실패했습니다.', 'widgetKey' => '');
    }
}

if (!function_exists('lc_embed_widget_key_allowed')) {
    /**
     * - requireWidgetKey=false: 키가 없으면 통과, 발급된 경우 요청 키가 일치해야 함.
     * - requireWidgetKey=true: 저장된 키와 요청 키가 반드시 일치해야 함.
     */
    function lc_embed_widget_key_allowed($pt_id, $request_key = '')
    {
        $pt_id = (int) $pt_id;
        $stored = lc_embed_partner_widget_key($pt_id);
        $request_key = lc_embed_normalize_widget_key($request_key);
        $require = false;
        if ($pt_id > 0 && function_exists('lc_embed_partner_options')) {
            $opts = lc_embed_partner_options($pt_id);
            $require = !empty($opts['requireWidgetKey']);
        }

        if ($stored === '') {
            return !$require;
        }

        return $request_key !== '' && hash_equals($stored, $request_key);
    }
}

if (!function_exists('lc_embed_config_for_lk_code')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_embed_config_for_lk_code($lk_code, array $options = array())
    {
        $lk_code = trim((string) $lk_code);
        if ($lk_code === '' || !function_exists('lc_link_get_with_campaign')) {
            return null;
        }

        $link = lc_link_get_with_campaign($lk_code);
        if (!$link || ($link['lk_status'] ?? '') !== 'active' || ($link['cp_status'] ?? '') !== LC_STATUS_ACTIVE) {
            return null;
        }

        $pt_id = (int) ($link['pt_id'] ?? 0);
        $cp_id = (int) ($link['cp_id'] ?? 0);
        $check_domain = !array_key_exists('check_domain', $options) || !empty($options['check_domain']);
        if ($check_domain && $pt_id > 0 && !lc_embed_domain_allowed($pt_id)) {
            return array('_error' => 'DOMAIN_NOT_ALLOWED');
        }

        $check_widget_key = !array_key_exists('check_widget_key', $options) || !empty($options['check_widget_key']);
        $request_widget_key = isset($options['widget_key'])
            ? (string) $options['widget_key']
            : (isset($options['widgetKey']) ? (string) $options['widgetKey'] : '');
        if ($check_widget_key && $pt_id > 0 && !lc_embed_widget_key_allowed($pt_id, $request_widget_key)) {
            return array('_error' => 'WIDGET_KEY_INVALID');
        }

        $campaign_title = trim((string) ($link['cp_name'] ?? '상담 신청'));
        $partner_code = '';
        if ($pt_id > 0 && function_exists('lc_get_partner_by_id')) {
            $partner = lc_get_partner_by_id($pt_id);
            if (is_array($partner)) {
                $partner_code = (string) ($partner['pt_code'] ?? '');
            }
        }

        // 공개 config에서는 자동 발급하지 않음(기존 lkCode-only 스니펫 호환).
        // 발급은 관리자/파트너 설정 API에서 ensure 한다.
        $widget_key = $pt_id > 0 ? lc_embed_partner_widget_key($pt_id) : '';

        $partner_phone = '';
        $partner_phone_display = '';
        if (function_exists('lc_call_partner_phone_for_assignment')) {
            $partner_phone = (string) lc_call_partner_phone_for_assignment($pt_id, $cp_id);
            if ($partner_phone !== '' && function_exists('lc_call_number_normalize')) {
                $partner_phone = lc_call_number_normalize($partner_phone);
            }
            if ($partner_phone !== '') {
                $partner_phone_display = function_exists('lc_call_number_format')
                    ? lc_call_number_format($partner_phone)
                    : $partner_phone;
            }
        }

        $brand = lc_embed_brand_name();
        $submit_url = lc_site_absolute_url('/plugin/linkconnect/api/receive.php');
        $event_url = lc_site_absolute_url('/plugin/linkconnect/api/embed_event.php');
        $config_url = lc_site_absolute_url('/plugin/linkconnect/api/embed.php');
        $privacy_url = lc_site_absolute_url('/privacy');
        $allowed_domains = lc_embed_partner_allowed_domains($pt_id);
        $options = function_exists('lc_embed_partner_options')
            ? lc_embed_partner_options($pt_id)
            : lc_embed_default_options();
        $accent = (string) ($options['accent'] ?? '#0d9488');
        $preset = function_exists('lc_embed_normalize_preset')
            ? lc_embed_normalize_preset($options['preset'] ?? 'default')
            : 'default';
        $theme = function_exists('lc_embed_theme_for_preset')
            ? lc_embed_theme_for_preset($preset, $accent)
            : array(
                'accent'     => $accent,
                'accentText' => '#ffffff',
                'border'     => '#e2e8f0',
                'bg'         => '#ffffff',
                'text'       => '#0f172a',
                'muted'      => '#64748b',
                'call'       => '#059669',
                'preset'     => $preset,
            );

        return array(
            'lkCode'        => (string) ($link['lk_code'] ?? $lk_code),
            'widgetKey'     => $widget_key,
            'campaignId'    => $cp_id,
            'campaignCode'  => (string) ($link['cp_code'] ?? ''),
            'campaignTitle' => $campaign_title,
            'partnerCode'   => $partner_code,
            'partnerPhone'  => $partner_phone,
            'partnerPhoneDisplay' => $partner_phone_display,
            'hasPartnerPhone' => $partner_phone !== '',
            'trackingPhone' => $partner_phone,
            'trackingPhoneDisplay' => $partner_phone_display,
            'allowedDomains'=> $allowed_domains,
            'domainLock'    => count($allowed_domains) > 0,
            'submitUrl'     => $submit_url,
            'eventUrl'      => $event_url,
            'configUrl'     => $config_url,
            'frameUrl'      => lc_embed_frame_url(),
            'privacyUrl'    => $privacy_url,
            'scriptUrl'     => lc_embed_script_url(),
            'pluginDownloadUrl' => lc_site_absolute_url('/plugin/linkconnect/assets/wordpress/linkconnect-lead.zip'),
            'channel'       => 'embed',
            'source'        => defined('LC_SOURCE_EMBED') ? LC_SOURCE_EMBED : 'embed',
            'brandName'     => $brand,
            'title'         => (string) ($options['title'] ?? '무료 상담 신청'),
            'subtitle'      => $campaign_title !== '' ? $campaign_title : '빠른 상담을 남겨 주세요.',
            'submitLabel'   => (string) ($options['submitLabel'] ?? '상담 신청하기'),
            'buttonLabel'   => (string) ($options['buttonLabel'] ?? '무료 상담 신청'),
            'callLabel'     => (string) ($options['callLabel'] ?? '전화 상담'),
            'successMessage'=> (string) ($options['successMessage'] ?? '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.'),
            'successRedirectUrl' => (string) ($options['successRedirectUrl'] ?? ''),
            'trackConversion' => !array_key_exists('trackConversion', $options) || !empty($options['trackConversion']),
            'conversionEventName' => (string) ($options['conversionEventName'] ?? 'lc_lead_submit'),
            'privacyText'   => (string) ($options['privacyText'] ?? '개인정보 수집·이용에 동의합니다.'),
            'showRegion'    => !array_key_exists('showRegion', $options) || !empty($options['showRegion']),
            'showInquiry'   => !array_key_exists('showInquiry', $options) || !empty($options['showInquiry']),
            'preset'        => $preset,
            'pcLayout'      => function_exists('lc_embed_normalize_pc_layout')
                ? lc_embed_normalize_pc_layout($options['pcLayout'] ?? 'auto')
                : 'auto',
            'minimalForm'   => !array_key_exists('minimalForm', $options) || !empty($options['minimalForm']),
            'showTrustBadges' => !array_key_exists('showTrustBadges', $options) || !empty($options['showTrustBadges']),
            'badgeFree'     => !array_key_exists('badgeFree', $options) || !empty($options['badgeFree']),
            'badgeCallback' => !array_key_exists('badgeCallback', $options) || !empty($options['badgeCallback']),
            'badgePrivacy'  => !array_key_exists('badgePrivacy', $options) || !empty($options['badgePrivacy']),
            'benefitText'   => (string) ($options['benefitText'] ?? ''),
            'ctaHint'       => (string) ($options['ctaHint'] ?? ''),
            'showLiveCount' => !array_key_exists('showLiveCount', $options) || !empty($options['showLiveCount']),
            'liveCountText' => (string) ($options['liveCountText'] ?? ''),
            'stickyMobileCta' => !array_key_exists('stickyMobileCta', $options) || !empty($options['stickyMobileCta']),
            'successShowCall' => !array_key_exists('successShowCall', $options) || !empty($options['successShowCall']),
            'showFormCall' => !array_key_exists('showFormCall', $options) || !empty($options['showFormCall']),
            'successNextStep' => (string) ($options['successNextStep'] ?? ''),
            'options'       => $options,
            'fields'        => (static function () use ($options) {
                $fields = array(
                    array('name' => 'name', 'label' => '이름', 'type' => 'text', 'required' => true, 'placeholder' => '홍길동'),
                    array('name' => 'phone', 'label' => '연락처', 'type' => 'tel', 'required' => true, 'placeholder' => '010-1234-5678'),
                );
                $show_region = !array_key_exists('showRegion', $options) || !empty($options['showRegion']);
                $show_inquiry = !array_key_exists('showInquiry', $options) || !empty($options['showInquiry']);
                if ($show_region) {
                    $fields[] = array('name' => 'region', 'label' => '지역', 'type' => 'text', 'required' => false, 'placeholder' => '서울 / 경기 등');
                }
                if ($show_inquiry) {
                    $fields[] = array('name' => 'inquiry', 'label' => '문의 내용', 'type' => 'textarea', 'required' => false, 'placeholder' => '상담이 필요하신 내용을 적어 주세요.');
                }
                return $fields;
            })(),
            'theme'         => $theme,
        );
    }
}

if (!function_exists('lc_embed_snippet_html')) {
    /**
     * @param string $mode form|button|phone
     * @param array{widget_key?:string,widgetKey?:string,pt_id?:int} $options
     */
    function lc_embed_snippet_html($lk_code, $mode = 'form', array $options = array())
    {
        $lk_code = trim((string) $lk_code);
        $mode = strtolower(trim((string) $mode));
        if (!in_array($mode, array('form', 'button', 'phone'), true)) {
            $mode = 'form';
        }
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $lk_code);
        if ($safe === '') {
            $safe = 'form';
        }
        $id = 'lc-lead-' . substr($safe, 0, 32) . ($mode !== 'form' ? '-' . $mode : '');
        $script = htmlspecialchars(lc_embed_script_url(), ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($lk_code, ENT_QUOTES, 'UTF-8');
        $brand = htmlspecialchars(lc_embed_brand_name(), ENT_QUOTES, 'UTF-8');
        $mode_attr = $mode !== 'form' ? ' data-mode="' . htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') . '"' : '';
        $label = $mode === 'button' ? '상담신청 버튼 위젯' : ($mode === 'phone' ? '전화 상담 위젯' : '상담신청 위젯');

        $widget_key = isset($options['widget_key'])
            ? (string) $options['widget_key']
            : (isset($options['widgetKey']) ? (string) $options['widgetKey'] : '');
        $widget_key = lc_embed_normalize_widget_key($widget_key);
        if ($widget_key === '' && !empty($options['pt_id'])) {
            $ensured = lc_embed_ensure_partner_widget_key((int) $options['pt_id']);
            $widget_key = lc_embed_normalize_widget_key($ensured['widgetKey'] ?? '');
        }
        $widget_attr = $widget_key !== ''
            ? ' data-widget-key="' . htmlspecialchars($widget_key, ENT_QUOTES, 'UTF-8') . '"'
            : '';

        return '<!-- ' . $brand . ' ' . $label . " -->\n"
            . '<div id="' . $id . '"></div>' . "\n"
            . '<script src="' . $script . '" data-lk-code="' . $code . '"' . $widget_attr
            . ' data-target="#' . $id . '" data-channel="embed"' . $mode_attr . ' async></script>';
    }
}

if (!function_exists('lc_admin_embed_source_sql')) {
    function lc_admin_embed_source_sql($alias = 'cv')
    {
        $embed = defined('LC_SOURCE_EMBED') ? LC_SOURCE_EMBED : 'embed';
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $alias);
        if ($a === '') {
            $a = 'cv';
        }
        return " (
            {$a}.cv_source = '" . lc_sql_escape($embed) . "'
            OR LOWER({$a}.cv_channel) IN ('embed','wordpress','widget','external')
        ) ";
    }
}

if (!function_exists('lc_embed_stats_for_partner')) {
    /**
     * 파트너 외부위젯 실적: 도메인별·일별.
     *
     * @return array{
     *   embedTotal:int,embedToday:int,embedApproved:int,days:int,
     *   byDomain:array<int,array{host:string,total:int,today:int}>,
     *   daily:array<int,array{date:string,label:string,count:int}>
     * }
     */
    function lc_embed_stats_for_partner($pt_id, $days = 14)
    {
        $pt_id = (int) $pt_id;
        $days = max(1, min(90, (int) $days));
        $empty = array(
            'embedTotal'    => 0,
            'embedToday'    => 0,
            'embedApproved' => 0,
            'days'          => $days,
            'byDomain'      => array(),
            'daily'         => array(),
        );
        if ($pt_id <= 0 || !lc_db_installed() || !function_exists('lc_admin_embed_source_sql')) {
            return $empty;
        }

        $cv_table = lc_table('conversions');
        $today = date('Y-m-d');
        $since = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $embed_sql = lc_admin_embed_source_sql('cv');
        $approved = defined('LC_STATUS_APPROVED') ? LC_STATUS_APPROVED : 'approved';
        $has_page = function_exists('lc_db_column_exists') && lc_db_column_exists($cv_table, 'cv_page_url');
        $page_select = $has_page ? 'cv.cv_page_url' : "'' AS cv_page_url";

        $sum = lc_sql_fetch(" SELECT
            COUNT(*) AS embed_total,
            SUM(CASE WHEN DATE(cv.cv_created_at) = '{$today}' THEN 1 ELSE 0 END) AS embed_today,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape($approved) . "' THEN 1 ELSE 0 END) AS embed_approved
            FROM `{$cv_table}` cv
            WHERE cv.pt_id = '{$pt_id}' AND {$embed_sql} ");

        $daily_map = array();
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $daily_map[$d] = 0;
        }
        $domain_map = array();

        $sql = " SELECT {$page_select}, cv.cv_inquiry, DATE(cv.cv_created_at) AS d
            FROM `{$cv_table}` cv
            WHERE cv.pt_id = '{$pt_id}' AND {$embed_sql}
              AND cv.cv_created_at >= '{$since} 00:00:00'
            ORDER BY cv.cv_id DESC
            LIMIT 5000 ";
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $d = (string) ($row['d'] ?? '');
                if (isset($daily_map[$d])) {
                    $daily_map[$d]++;
                }

                $url = function_exists('lc_conversion_page_url')
                    ? lc_conversion_page_url($row)
                    : trim((string) ($row['cv_page_url'] ?? ''));
                $host = function_exists('lc_conversion_page_host')
                    ? lc_conversion_page_host($url)
                    : (function_exists('lc_embed_host_from_url') ? lc_embed_host_from_url($url) : '');
                if ($host === '') {
                    $host = '(미기록)';
                }
                if (!isset($domain_map[$host])) {
                    $domain_map[$host] = array('host' => $host, 'total' => 0, 'today' => 0);
                }
                $domain_map[$host]['total']++;
                if ($d === $today) {
                    $domain_map[$host]['today']++;
                }
            }
        }

        $by_domain = array_values($domain_map);
        usort($by_domain, static function ($a, $b) {
            $diff = (int) ($b['total'] ?? 0) - (int) ($a['total'] ?? 0);
            if ($diff !== 0) {
                return $diff;
            }
            return strcmp((string) ($a['host'] ?? ''), (string) ($b['host'] ?? ''));
        });
        $by_domain = array_slice($by_domain, 0, 20);

        $daily = array();
        foreach ($daily_map as $date => $count) {
            $daily[] = array(
                'date'  => $date,
                'label' => date('m.d', strtotime($date)),
                'count' => (int) $count,
            );
        }

        return array(
            'embedTotal'    => (int) ($sum['embed_total'] ?? 0),
            'embedToday'    => (int) ($sum['embed_today'] ?? 0),
            'embedApproved' => (int) ($sum['embed_approved'] ?? 0),
            'days'          => $days,
            'byDomain'      => $by_domain,
            'daily'         => $daily,
        );
    }
}

if (!function_exists('lc_admin_embed_partners_for_api')) {
    /**
     * @return array{items:array<int,array<string,mixed>>,summary:array<string,int>}
     */
    function lc_admin_embed_partners_for_api(array $filters = array())
    {
        if (!lc_db_installed()) {
            return array(
                'items'   => array(),
                'summary' => array(
                    'partners'      => 0,
                    'domainLocked'  => 0,
                    'embedTotal'    => 0,
                    'embedToday'    => 0,
                    'activeLinks'   => 0,
                ),
            );
        }

        $pt_table = lc_table('partners');
        $cv_table = lc_table('conversions');
        $lk_table = lc_table('links');
        $today = date('Y-m-d');
        $embed_sql = lc_admin_embed_source_sql('cv');
        $q = trim((string) ($filters['q'] ?? ''));
        $scope = strtolower(trim((string) ($filters['scope'] ?? '')));

        $where = " p.pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "' ";
        if ($q !== '') {
            $like = lc_sql_escape('%' . $q . '%');
            $where .= " AND (p.pt_code LIKE '{$like}' OR p.pt_name LIKE '{$like}') ";
        }

        $has_domains_col = function_exists('lc_db_column_exists') && lc_db_column_exists($pt_table, 'pt_embed_domains');
        $domains_select = $has_domains_col ? 'p.pt_embed_domains' : "'' AS pt_embed_domains";

        $sql = " SELECT p.pt_id, p.pt_code, p.pt_name, p.pt_status, {$domains_select},
                (
                    SELECT COUNT(*) FROM `{$cv_table}` cv
                    WHERE cv.pt_id = p.pt_id AND {$embed_sql}
                ) AS embed_total,
                (
                    SELECT COUNT(*) FROM `{$cv_table}` cv
                    WHERE cv.pt_id = p.pt_id AND {$embed_sql} AND DATE(cv.cv_created_at) = '{$today}'
                ) AS embed_today,
                (
                    SELECT COUNT(*) FROM `{$lk_table}` lk
                    WHERE lk.pt_id = p.pt_id AND lk.lk_status = 'active'
                ) AS active_links
            FROM `{$pt_table}` p
            WHERE {$where}
            ORDER BY embed_total DESC, p.pt_id DESC
            LIMIT 200 ";

        $items = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $domains = array();
                if ($has_domains_col) {
                    $domains = lc_embed_partner_allowed_domains((int) $row['pt_id']);
                }
                $embed_total = (int) ($row['embed_total'] ?? 0);
                $active_links = (int) ($row['active_links'] ?? 0);
                $domain_lock = count($domains) > 0;

                if ($scope === 'active' && !$domain_lock && $embed_total <= 0) {
                    continue;
                }
                if ($scope === 'locked' && !$domain_lock) {
                    continue;
                }

                $widget_key = lc_embed_partner_widget_key((int) $row['pt_id']);
                $items[] = array(
                    'ptId'         => (int) $row['pt_id'],
                    'code'         => (string) ($row['pt_code'] ?? ''),
                    'name'         => (string) ($row['pt_name'] ?? ''),
                    'status'       => (string) ($row['pt_status'] ?? ''),
                    'domains'      => $domains,
                    'domainLock'   => $domain_lock,
                    'hasWidgetKey' => $widget_key !== '',
                    'embedTotal'   => $embed_total,
                    'embedToday'   => (int) ($row['embed_today'] ?? 0),
                    'activeLinks'  => $active_links,
                );
            }
        }

        $sum = lc_sql_fetch(" SELECT
            SUM(CASE WHEN {$embed_sql} THEN 1 ELSE 0 END) AS embed_total,
            SUM(CASE WHEN {$embed_sql} AND DATE(cv.cv_created_at) = '{$today}' THEN 1 ELSE 0 END) AS embed_today
            FROM `{$cv_table}` cv ");
        $locked = 0;
        if ($has_domains_col) {
            $locked_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$pt_table}`
                WHERE pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "'
                  AND pt_embed_domains IS NOT NULL AND TRIM(pt_embed_domains) <> '' ");
            $locked = (int) ($locked_row['cnt'] ?? 0);
        }
        $active_partners = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$pt_table}`
            WHERE pt_status = '" . lc_sql_escape(LC_PARTNER_STATUS_ACTIVE) . "' ");
        $links_row = lc_sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$lk_table}` WHERE lk_status = 'active' ");

        return array(
            'items'   => $items,
            'summary' => array(
                'partners'     => (int) ($active_partners['cnt'] ?? count($items)),
                'domainLocked' => $locked,
                'embedTotal'   => (int) ($sum['embed_total'] ?? 0),
                'embedToday'   => (int) ($sum['embed_today'] ?? 0),
                'activeLinks'  => (int) ($links_row['cnt'] ?? 0),
            ),
        );
    }
}

if (!function_exists('lc_admin_embed_partner_detail_for_api')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_admin_embed_partner_detail_for_api($pt_id)
    {
        $pt_id = (int) $pt_id;
        if ($pt_id <= 0 || !lc_db_installed()) {
            return null;
        }
        $partner = function_exists('lc_get_partner_by_id') ? lc_get_partner_by_id($pt_id) : null;
        if (!is_array($partner)) {
            return null;
        }

        $domains = lc_embed_partner_allowed_domains($pt_id);
        // 자동 발급하지 않음 — 기존 설치 코드가 갑자기 막히지 않도록 수동 발급
        $widget_key = lc_embed_partner_widget_key($pt_id);
        $snippet_opts = array('widgetKey' => $widget_key);

        $links = function_exists('lc_link_list_for_partner') ? lc_link_list_for_partner($pt_id) : array();
        $link_items = array();
        foreach ($links as $link) {
            if (($link['lk_status'] ?? '') !== 'active') {
                continue;
            }
            $code = (string) ($link['lk_code'] ?? '');
            if ($code === '') {
                continue;
            }
            $link_items[] = array(
                'id'       => (int) ($link['lk_id'] ?? 0),
                'code'     => $code,
                'campaign' => (string) ($link['cp_name'] ?? ''),
                'channel'  => (string) ($link['lk_channel'] ?? ''),
                'subId'    => (string) ($link['lk_sub_id'] ?? ''),
                'snippets' => array(
                    'form'   => lc_embed_snippet_html($code, 'form', $snippet_opts),
                    'button' => lc_embed_snippet_html($code, 'button', $snippet_opts),
                    'phone'  => lc_embed_snippet_html($code, 'phone', $snippet_opts),
                ),
            );
        }

        $stats = function_exists('lc_embed_stats_for_partner')
            ? lc_embed_stats_for_partner($pt_id, 14)
            : array(
                'embedTotal'    => 0,
                'embedToday'    => 0,
                'embedApproved' => 0,
                'days'          => 14,
                'byDomain'      => array(),
                'daily'         => array(),
            );

        return array(
            'ptId'           => $pt_id,
            'code'           => (string) ($partner['pt_code'] ?? ''),
            'name'           => (string) ($partner['pt_name'] ?? ''),
            'status'         => (string) ($partner['pt_status'] ?? ''),
            'domains'        => $domains,
            'domainLock'     => count($domains) > 0,
            'widgetKey'      => $widget_key,
            'hasWidgetKey'   => $widget_key !== '',
            'embedTotal'     => (int) ($stats['embedTotal'] ?? 0),
            'embedToday'     => (int) ($stats['embedToday'] ?? 0),
            'embedApproved'  => (int) ($stats['embedApproved'] ?? 0),
            'statsDays'      => (int) ($stats['days'] ?? 14),
            'byDomain'       => isset($stats['byDomain']) && is_array($stats['byDomain']) ? $stats['byDomain'] : array(),
            'daily'          => isset($stats['daily']) && is_array($stats['daily']) ? $stats['daily'] : array(),
            'scriptUrl'      => lc_embed_script_url(),
            'brandName'      => lc_embed_brand_name(),
            'options'        => lc_embed_partner_options($pt_id),
            'links'          => $link_items,
        );
    }
}
