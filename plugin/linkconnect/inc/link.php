<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_link_tracking_base_url')) {
    /**
     * @param string|null $campaign_tracking_base_url 광고상품별 독립 도메인 (비우면 전역 설정 → G5_URL)
     */
    function lc_link_tracking_base_url($campaign_tracking_base_url = null)
    {
        $campaign_base = trim((string) $campaign_tracking_base_url);
        if ($campaign_base !== '') {
            return rtrim($campaign_base, '/');
        }

        if (function_exists('lc_settings_get_bool') && lc_settings_get_bool('cpaTrackingDomainEnabled')) {
            $base = trim((string) lc_settings_get('cpaTrackingBaseUrl', ''));
            if ($base !== '') {
                return rtrim($base, '/');
            }
        }

        return defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
    }
}

if (!function_exists('lc_link_main_site_hosts')) {
    /**
     * @return array<int,string>
     */
    function lc_link_main_site_hosts()
    {
        $hosts = array();
        if (defined('G5_URL') && G5_URL !== '') {
            $parts = parse_url((string) G5_URL);
            if (is_array($parts) && !empty($parts['host'])) {
                $hosts[] = strtolower((string) $parts['host']);
            }
        }
        // 운영 메인 도메인 별칭 (신규 → 레거시)
        $hosts[] = 'onoffcpa.icrm.co.kr';
        $hosts[] = 'www.onoffcpa.icrm.co.kr';
        $hosts[] = 'onoffcpa.iwinv.net';
        $hosts[] = 'www.onoffcpa.iwinv.net';

        return array_values(array_unique(array_filter($hosts)));
    }
}

if (!function_exists('lc_link_request_host')) {
    /**
     * Cloudflare Worker 독립 도메인 프록시 시 X-Forwarded-Host 를 신뢰한다.
     * (Worker가 CF IP에서 Origin으로 전달하고 X-Forwarded-Host=공개 도메인을 세팅)
     */
    function lc_link_request_host()
    {
        $host = '';
        $remote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $from_cf = !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && class_exists('G5CloudflareRequestHandler')
            && method_exists('G5CloudflareRequestHandler', 'check_cloudflare_ips')
            && G5CloudflareRequestHandler::check_cloudflare_ips($remote);

        if ($from_cf && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $xff = (string) $_SERVER['HTTP_X_FORWARDED_HOST'];
            $first = trim(explode(',', $xff)[0]);
            if ($first !== '' && preg_match('/^[a-z0-9.-]+$/i', preg_replace('/:\d+$/', '', $first))) {
                $host = strtolower($first);
            }
        }

        if ($host === '' && isset($_SERVER['HTTP_HOST'])) {
            $host = strtolower((string) $_SERVER['HTTP_HOST']);
        }

        return preg_replace('/:\d+$/', '', $host);
    }
}

if (!function_exists('lc_link_is_main_request_host')) {
    function lc_link_is_main_request_host()
    {
        $host = lc_link_request_host();

        return $host !== '' && in_array($host, lc_link_main_site_hosts(), true);
    }
}

if (!function_exists('lc_link_host_from_base_url')) {
    function lc_link_host_from_base_url($base_url)
    {
        $base_url = trim((string) $base_url);
        if ($base_url === '') {
            return '';
        }
        $parts = parse_url($base_url);

        return (is_array($parts) && !empty($parts['host'])) ? strtolower((string) $parts['host']) : '';
    }
}

if (!function_exists('lc_link_is_linkconnect_platform')) {
    function lc_link_is_linkconnect_platform()
    {
        if (function_exists('lc_mp_local_platform_code') && defined('LC_PLATFORM_LINKCONNECT')) {
            return strtoupper((string) lc_mp_local_platform_code()) === strtoupper((string) LC_PLATFORM_LINKCONNECT);
        }

        return false;
    }
}

if (!function_exists('lc_link_is_onoffcpa_platform')) {
    function lc_link_is_onoffcpa_platform()
    {
        if (function_exists('lc_mp_local_platform_code') && defined('LC_PLATFORM_ONOFFCPA')) {
            return strtoupper((string) lc_mp_local_platform_code()) === strtoupper((string) LC_PLATFORM_ONOFFCPA);
        }

        // 이 코드베이스 기본값은 onoffcpa
        return !lc_link_is_linkconnect_platform();
    }
}

if (!function_exists('lc_link_linkconnect_reserved_tracking_hosts')) {
    /**
     * 링크커넥트에 이미 연결된 독립 도메인 — onoffcpa 에서 재사용 금지.
     * DNS는 한 서버에만 연결되므로 플랫폼별로 도메인을 분리한다.
     *
     * @return array<int,string>
     */
    function lc_link_linkconnect_reserved_tracking_hosts()
    {
        return array(
            'air911.co.kr',
            'www.air911.co.kr',
            'yevely.kr',
            'www.yevely.kr',
            'skawning.co.kr',
            'www.skawning.co.kr',
        );
    }
}

if (!function_exists('lc_link_is_reserved_linkconnect_tracking_host')) {
    function lc_link_is_reserved_linkconnect_tracking_host($host)
    {
        $host = strtolower(preg_replace('/:\d+$/', '', trim((string) $host)));
        if ($host === '') {
            return false;
        }

        return in_array($host, lc_link_linkconnect_reserved_tracking_hosts(), true);
    }
}

if (!function_exists('lc_campaign_seed_tracking_base_for_platform')) {
    /**
     * 시드용 독립 도메인: 링크커넥트만 하드코딩 기본값을 쓰고,
     * onoffcpa 는 빈 값(관리자에서 별도 도메인 설정).
     */
    function lc_campaign_seed_tracking_base_for_platform($linkconnect_default)
    {
        $linkconnect_default = rtrim(trim((string) $linkconnect_default), '/');
        if ($linkconnect_default === '') {
            return '';
        }
        if (lc_link_is_linkconnect_platform()) {
            return $linkconnect_default;
        }

        return '';
    }
}

if (!function_exists('lc_link_configured_tracking_hosts')) {
    /**
     * 전역·광고상품별 독립 도메인 호스트 목록
     *
     * @return array<int,string>
     */
    function lc_link_configured_tracking_hosts()
    {
        static $hosts = null;
        if (is_array($hosts)) {
            return $hosts;
        }

        $hosts = array();
        if (function_exists('lc_settings_get_bool') && lc_settings_get_bool('cpaTrackingDomainEnabled')) {
            $h = lc_link_host_from_base_url((string) lc_settings_get('cpaTrackingBaseUrl', ''));
            if ($h !== '') {
                $hosts[] = $h;
            }
        }

        if (function_exists('lc_db_installed') && lc_db_installed()) {
            $table = lc_table('campaigns');
            if (function_exists('lc_db_column_exists') && lc_db_column_exists($table, 'cp_tracking_base_url')) {
                $result = lc_sql_query(
                    " SELECT DISTINCT cp_tracking_base_url FROM `{$table}`
                      WHERE cp_tracking_base_url <> '' ",
                    false
                );
                if ($result) {
                    while ($row = sql_fetch_array($result)) {
                        $h = lc_link_host_from_base_url((string) ($row['cp_tracking_base_url'] ?? ''));
                        if ($h !== '') {
                            $hosts[] = $h;
                        }
                    }
                }
            }
        }

        $hosts = array_values(array_unique(array_filter($hosts)));

        return $hosts;
    }
}

if (!function_exists('lc_link_allowed_redirect_hosts')) {
    /**
     * /s/ 숏링크 타겟 등으로 허용할 호스트
     *
     * @return array<int,string>
     */
    function lc_link_allowed_redirect_hosts()
    {
        return array_values(array_unique(array_filter(array_merge(
            lc_link_main_site_hosts(),
            lc_link_configured_tracking_hosts()
        ))));
    }
}

if (!function_exists('lc_link_is_tracking_request_host')) {
    function lc_link_is_tracking_request_host()
    {
        $host = lc_link_request_host();
        if ($host === '' || lc_link_is_main_request_host()) {
            return false;
        }

        return in_array($host, lc_link_configured_tracking_hosts(), true);
    }
}

if (!function_exists('lc_link_tracking_host_path_allowed')) {
    function lc_link_tracking_host_path_allowed($path)
    {
        $path = '/' . ltrim((string) $path, '/');
        if ($path === '/') {
            return false;
        }

        $allow_prefixes = array(
            '/r/',
            '/c/',
            '/s/',
            '/merchant/',
            '/plugin/',
            '/data/',
            '/css/',
            '/js/',
            '/img/',
            '/skin/',
            '/theme/',
        );
        foreach ($allow_prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }

        $allow_exact = array('/favicon.ico', '/robots.txt', '/favicon.png');
        if (in_array($path, $allow_exact, true)) {
            return true;
        }

        return (bool) preg_match('#^/(r|c|s)/[A-Za-z0-9_-]+/?$#', $path);
    }
}

if (!function_exists('lc_link_enforce_tracking_host_gate')) {
    /**
     * 독립 도메인에서는 추적·랜딩·플러그인 경로만 허용하고 나머지는 메인 사이트로 보냅니다.
     */
    function lc_link_enforce_tracking_host_gate()
    {
        if (PHP_SAPI === 'cli' || !lc_link_is_tracking_request_host()) {
            return;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        if (lc_link_tracking_host_path_allowed($path)) {
            return;
        }

        $dest = defined('G5_URL') ? rtrim((string) G5_URL, '/') . '/' : '/';
        if (!headers_sent()) {
            header('Location: ' . $dest, true, 302);
        }
        exit;
    }
}

if (!function_exists('lc_link_apply_tracking_host')) {
    /**
     * 메인 사이트(온오프CPA) 랜딩 URL을 독립 도메인 호스트로 바꿉니다.
     * 외부 광고주 도메인은 그대로 둡니다.
     */
    function lc_link_apply_tracking_host($url, $tracking_base_url = null)
    {
        $url = trim((string) $url);
        $base = lc_link_tracking_base_url($tracking_base_url);
        if ($url === '' || $base === '') {
            return $url;
        }

        $base_parts = parse_url($base);
        if (!is_array($base_parts) || empty($base_parts['host'])) {
            return $url;
        }

        $base_host = strtolower((string) $base_parts['host']);
        $base_scheme = strtolower((string) ($base_parts['scheme'] ?? 'https'));
        $base_port = isset($base_parts['port']) ? ':' . (int) $base_parts['port'] : '';

        // protocol-relative
        if (strpos($url, '//') === 0 && strpos($url, '://') === false) {
            $url = $base_scheme . ':' . $url;
        }

        if (strpos($url, '://') === false) {
            return $base . '/' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $url_host = strtolower((string) $parts['host']);
        if ($url_host === $base_host) {
            return $url;
        }

        if (!in_array($url_host, lc_link_main_site_hosts(), true)) {
            return $url;
        }

        $path = isset($parts['path']) && $parts['path'] !== '' ? (string) $parts['path'] : '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $base_scheme . '://' . $base_host . $base_port . $path . $query . $fragment;
    }
}

if (!function_exists('lc_link_public_url')) {
    function lc_link_public_url($lk_code, $campaign_tracking_base_url = null)
    {
        $lk_code = trim((string) $lk_code);

        return lc_link_tracking_base_url($campaign_tracking_base_url) . '/r/' . rawurlencode($lk_code);
    }
}

if (!function_exists('lc_landing_public_url')) {
    function lc_landing_public_url($lk_code, $campaign_tracking_base_url = null)
    {
        $lk_code = trim((string) $lk_code);

        return lc_link_tracking_base_url($campaign_tracking_base_url) . '/c/' . rawurlencode($lk_code);
    }
}

if (!function_exists('lc_link_landing_seo_replace')) {
    function lc_link_landing_seo_replace($template, $campaign_name)
    {
        $template = trim((string) $template);
        $campaign_name = trim((string) $campaign_name);
        $site = function_exists('lc_settings_get')
            ? trim((string) lc_settings_get('siteName', '온오프CPA'))
            : '온오프CPA';

        return str_replace(
            array('{campaign}', '{site}'),
            array($campaign_name !== '' ? $campaign_name : '상담 신청', $site),
            $template
        );
    }
}

if (!function_exists('lc_link_landing_seo_meta')) {
    /**
     * @return array{title:string,description:string,keywords:string,robots:string,ogImage:string,canonical:string}
     */
    function lc_link_landing_seo_meta($campaign_name, $lk_code, $campaign_tracking_base_url = null)
    {
        $campaign_name = trim((string) $campaign_name);
        $lk_code = trim((string) $lk_code);
        $canonical = $lk_code !== ''
            ? lc_landing_public_url($lk_code, $campaign_tracking_base_url)
            : lc_link_tracking_base_url($campaign_tracking_base_url);

        $title_tpl = function_exists('lc_settings_get')
            ? (string) lc_settings_get('cpaLandingSeoTitle', '{campaign} 상담 신청 | {site}')
            : '{campaign} 상담 신청 | {site}';
        $desc_tpl = function_exists('lc_settings_get')
            ? (string) lc_settings_get('cpaLandingSeoDescription', '')
            : '';
        $keywords = function_exists('lc_settings_get')
            ? trim((string) lc_settings_get('cpaLandingSeoKeywords', ''))
            : '';
        $robots = function_exists('lc_settings_get')
            ? trim((string) lc_settings_get('cpaLandingSeoRobots', 'index,follow'))
            : 'index,follow';
        $og_image = function_exists('lc_settings_get')
            ? trim((string) lc_settings_get('cpaLandingSeoOgImage', ''))
            : '';

        $title = lc_link_landing_seo_replace($title_tpl, $campaign_name);
        if ($title === '') {
            $title = $campaign_name !== '' ? $campaign_name . ' 상담 신청' : '상담 신청';
        }

        $description = lc_link_landing_seo_replace($desc_tpl, $campaign_name);
        if ($description === '' && $campaign_name !== '') {
            $description = $campaign_name . ' 무료 상담 신청 페이지입니다.';
        }

        if ($robots === '') {
            $robots = 'index,follow';
        }

        if ($og_image === '' && $lk_code !== '') {
            if (function_exists('lc_link_get_with_campaign')) {
                $link_row = lc_link_get_with_campaign($lk_code);
                if (is_array($link_row) && !empty($link_row['cp_id']) && function_exists('lc_campaign_thumbnail_public_url')) {
                    $thumb = lc_campaign_thumbnail_public_url((int) $link_row['cp_id']);
                    if ($thumb !== '') {
                        $og_image = $thumb;
                    }
                }
            }
        }

        if ($og_image === '') {
            if (is_file(G5_PATH . '/components/seo-meta.php')) {
                include_once G5_PATH . '/components/seo-meta.php';
            }
            if (function_exists('g5b_seo_default_og_image')) {
                $default_og = g5b_seo_default_og_image();
                $og_image = isset($default_og['url']) ? (string) $default_og['url'] : '';
            } elseif (function_exists('g5site_cfg_url')) {
                $og_image = g5site_cfg_url('og_image', '');
            }
        } elseif (function_exists('g5b_seo_absolute_url')) {
            $og_image = g5b_seo_absolute_url($og_image);
        } elseif (!preg_match('#^https?://#i', $og_image) && defined('G5_URL')) {
            $og_image = rtrim(G5_URL, '/') . '/' . ltrim($og_image, '/');
        }

        return array(
            'title'       => $title,
            'description' => $description,
            'keywords'    => $keywords,
            'robots'      => $robots,
            'ogImage'     => $og_image,
            'canonical'   => $canonical,
        );
    }
}

if (!function_exists('lc_link_generate_code')) {
    function lc_link_generate_code()
    {
        return strtolower(substr(bin2hex(random_bytes(6)), 0, 10));
    }
}

if (!function_exists('lc_link_get_by_code')) {
    function lc_link_get_by_code($lk_code)
    {
        if (!lc_db_installed() || $lk_code === '') {
            return null;
        }

        $table = lc_table('links');
        $lk_code_esc = lc_sql_escape($lk_code);

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lk_code = '{$lk_code_esc}' LIMIT 1 ");
    }
}

if (!function_exists('lc_link_get_by_id')) {
    function lc_link_get_by_id($lk_id)
    {
        if (!lc_db_installed()) {
            return null;
        }

        $lk_id = (int) $lk_id;
        $table = lc_table('links');

        return lc_sql_fetch(" SELECT * FROM `{$table}` WHERE lk_id = '{$lk_id}' LIMIT 1 ");
    }
}

if (!function_exists('lc_link_get_with_campaign')) {
    function lc_link_get_with_campaign($lk_code)
    {
        if (!lc_db_installed() || $lk_code === '') {
            return null;
        }

        $lk_table = lc_table('links');
        $cp_table = lc_table('campaigns');
        $lk_code_esc = lc_sql_escape($lk_code);

        return lc_sql_fetch(" SELECT lk.*, c.cp_name, c.cp_code, c.cp_price, c.cp_status AS cp_status, c.cp_landing_url, c.cp_tracking_base_url, c.mt_id
            FROM `{$lk_table}` lk
            INNER JOIN `{$cp_table}` c ON c.cp_id = lk.cp_id
            WHERE lk.lk_code = '{$lk_code_esc}' LIMIT 1 ");
    }
}

if (!function_exists('lc_link_list_for_partner')) {
    function lc_link_list_for_partner($pt_id)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $lk_table = lc_table('links');
        $cp_table = lc_table('campaigns');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');

        $sql = " SELECT lk.*, c.cp_name, c.cp_price, c.cp_tracking_base_url,
            (SELECT COUNT(*) FROM `{$cl_table}` cl WHERE cl.lk_id = lk.lk_id) AS click_cnt,
            (SELECT COUNT(*) FROM `{$cv_table}` cv WHERE cv.lk_id = lk.lk_id) AS received_cnt,
            (SELECT COUNT(*) FROM `{$cv_table}` cv WHERE cv.lk_id = lk.lk_id AND cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "') AS approved_cnt,
            (SELECT COUNT(*) FROM `{$cv_table}` cv WHERE cv.lk_id = lk.lk_id AND cv.cv_status = '" . lc_sql_escape(LC_STATUS_REJECTED) . "') AS canceled_cnt,
            (SELECT COALESCE(SUM(cv.cv_price), 0) FROM `{$cv_table}` cv WHERE cv.lk_id = lk.lk_id) AS est_revenue,
            (SELECT COALESCE(SUM(cv.cv_price), 0) FROM `{$cv_table}` cv WHERE cv.lk_id = lk.lk_id AND cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "') AS conf_revenue
            FROM `{$lk_table}` lk
            INNER JOIN `{$cp_table}` c ON c.cp_id = lk.cp_id
            WHERE lk.pt_id = '{$pt_id}'
            ORDER BY lk.lk_id DESC ";

        $rows = array();
        $result = lc_sql_query($sql, false);
        if ($result) {
            while ($row = sql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}

if (!function_exists('lc_link_status_label')) {
    function lc_link_status_label($status)
    {
        $labels = array(
            'active'  => '운영중',
            'paused'  => '중지',
            'expired' => '만료',
        );

        return isset($labels[$status]) ? $labels[$status] : (string) $status;
    }
}

if (!function_exists('lc_link_to_api')) {
    function lc_link_to_api(array $row)
    {
        $tracking_base = isset($row['cp_tracking_base_url']) ? (string) $row['cp_tracking_base_url'] : '';

        return array(
            'id'          => (int) $row['lk_id'],
            'code'        => (string) $row['lk_code'],
            'campaign'    => (string) ($row['cp_name'] ?? ''),
            'campaignId'  => (int) $row['cp_id'],
            'channel'     => (string) $row['lk_channel'],
            'subId'       => (string) $row['lk_sub_id'],
            'url'         => lc_link_public_url($row['lk_code'], $tracking_base),
            'landingUrl'  => lc_landing_public_url($row['lk_code'], $tracking_base),
            'clicks'      => (int) ($row['click_cnt'] ?? 0),
            'received'    => (int) ($row['received_cnt'] ?? 0),
            'approved'    => (int) ($row['approved_cnt'] ?? 0),
            'canceled'    => (int) ($row['canceled_cnt'] ?? 0),
            'estRevenue'  => (int) ($row['est_revenue'] ?? 0),
            'confRevenue' => (int) ($row['conf_revenue'] ?? 0),
            'status'      => lc_link_status_label($row['lk_status']),
            'statusCode'  => (string) $row['lk_status'],
            'createdAt'   => (string) $row['lk_created_at'],
        );
    }
}

if (!function_exists('lc_cpa_partner_create_shortlink')) {
    /**
     * CPA /r/{code} 추적 링크를 /s/{short} 숏링크로 변환
     *
     * @return array{ok:bool,message:string,shortUrl:string,promoUrl:string,shortCode:string}
     */
    function lc_cpa_partner_create_shortlink($pt_id, $lk_id)
    {
        $pt_id = (int) $pt_id;
        $lk_id = (int) $lk_id;
        $empty = array('ok' => false, 'message' => '', 'shortUrl' => '', 'promoUrl' => '', 'shortCode' => '');

        if ($pt_id <= 0 || $lk_id <= 0) {
            $empty['message'] = '링크 정보가 올바르지 않습니다.';

            return $empty;
        }

        $link = lc_link_get_by_id($lk_id);
        if (!$link || (int) ($link['pt_id'] ?? 0) !== $pt_id) {
            $empty['message'] = '본인 홍보 링크만 숏코드로 변환할 수 있습니다.';

            return $empty;
        }
        if (($link['lk_status'] ?? '') !== 'active') {
            $empty['message'] = '운영중인 링크만 숏코드로 변환할 수 있습니다.';

            return $empty;
        }

        $lk_code = (string) ($link['lk_code'] ?? '');
        $tracking_base = '';
        if ($lk_code !== '') {
            $with = lc_link_get_with_campaign($lk_code);
            if (is_array($with)) {
                $tracking_base = (string) ($with['cp_tracking_base_url'] ?? '');
            }
        }
        $promo_url = lc_link_public_url($lk_code, $tracking_base);
        if ($promo_url === '' || !function_exists('lc_shortlink_store_for_partner')) {
            $empty['message'] = '숏링크를 만들 수 없습니다.';

            return $empty;
        }

        $public_base = lc_link_tracking_base_url($tracking_base);

        return lc_shortlink_store_for_partner($pt_id, $promo_url, array(
            'merchant_code' => 'cpa:' . (string) ($link['lk_code'] ?? ''),
            'product_url'   => 'lk:' . $lk_id,
            'public_base'   => $public_base,
        ));
    }
}

if (!function_exists('lc_cpa_partner_shortlink_for_campaign')) {
    /**
     * 캠페인 기준 원클릭 숏링크: 기존 활성 링크 재사용 또는 신규 생성 후 /s/{code}
     *
     * @return array{ok:bool,message:string,shortUrl:string,promoUrl:string,shortCode:string,link:array|null}
     */
    function lc_cpa_partner_shortlink_for_campaign($pt_id, $cp_id)
    {
        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        $empty = array(
            'ok'        => false,
            'message'   => '',
            'shortUrl'  => '',
            'promoUrl'  => '',
            'shortCode' => '',
            'link'      => null,
        );

        if ($pt_id <= 0 || $cp_id <= 0) {
            $empty['message'] = '캠페인 정보가 올바르지 않습니다.';

            return $empty;
        }

        $lk_table = lc_table('links');
        $existing = lc_sql_fetch(
            " SELECT * FROM `{$lk_table}`
              WHERE pt_id = {$pt_id} AND cp_id = {$cp_id} AND lk_status = 'active'
              ORDER BY lk_id DESC
              LIMIT 1 ",
            false
        );

        $lk_id = 0;
        $link_api = null;
        if (is_array($existing) && !empty($existing['lk_id'])) {
            $lk_id = (int) $existing['lk_id'];
            $with = lc_link_get_with_campaign((string) ($existing['lk_code'] ?? ''));
            $link_api = $with ? lc_link_to_api($with) : lc_link_to_api($existing);
        } else {
            $created = lc_link_create($pt_id, $cp_id, '숏코드', '');
            if (!$created['ok'] || empty($created['link']['id'])) {
                $empty['message'] = $created['message'] !== '' ? $created['message'] : '홍보 링크를 준비하지 못했습니다.';

                return $empty;
            }
            $lk_id = (int) $created['link']['id'];
            $link_api = $created['link'];
        }

        $short = lc_cpa_partner_create_shortlink($pt_id, $lk_id);
        if (!$short['ok']) {
            $empty['message'] = $short['message'];

            return $empty;
        }

        return array(
            'ok'        => true,
            'message'   => '숏코드 링크가 준비되었습니다.',
            'shortUrl'  => $short['shortUrl'],
            'promoUrl'  => $short['promoUrl'],
            'shortCode' => $short['shortCode'],
            'link'      => $link_api,
        );
    }
}

if (!function_exists('lc_link_create')) {
    /**
     * @return array{ok:bool,message:string,link:array|null}
     */
    function lc_link_create($pt_id, $cp_id, $channel = '', $sub_id = '')
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB가 설치되지 않았습니다.', 'link' => null);
        }

        $pt_id = (int) $pt_id;
        $cp_id = (int) $cp_id;
        $partner = lc_get_partner_by_id($pt_id);
        if (!$partner || $partner['pt_status'] !== LC_PARTNER_STATUS_ACTIVE) {
            return array('ok' => false, 'message' => '활성 파트너만 링크를 생성할 수 있습니다.', 'link' => null);
        }

        $cp_table = lc_table('campaigns');
        $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "' LIMIT 1 ");
        if (!$campaign) {
            return array('ok' => false, 'message' => '진행 중인 캠페인을 찾을 수 없습니다.', 'link' => null);
        }

        $channel = trim((string) $channel);
        $sub_id = trim((string) $sub_id);
        $lk_code = lc_link_generate_code();
        $attempts = 0;
        while (lc_link_get_by_code($lk_code) && $attempts < 5) {
            $lk_code = lc_link_generate_code();
            $attempts++;
        }

        $table = lc_table('links');
        lc_sql_query(" INSERT INTO `{$table}` SET
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            lk_code = '" . lc_sql_escape($lk_code) . "',
            lk_channel = '" . lc_sql_escape($channel) . "',
            lk_sub_id = '" . lc_sql_escape($sub_id) . "',
            lk_status = 'active',
            lk_created_at = NOW(),
            lk_updated_at = NOW() ", false);

        $lk_id = (int) lc_sql_insert_id();
        if ($lk_id <= 0) {
            return array('ok' => false, 'message' => '링크 생성에 실패했습니다.', 'link' => null);
        }

        $rows = lc_link_list_for_partner($pt_id);
        foreach ($rows as $row) {
            if ((int) $row['lk_id'] === $lk_id) {
                return array('ok' => true, 'message' => '홍보 링크가 생성되었습니다.', 'link' => lc_link_to_api($row));
            }
        }

        $link = lc_link_get_by_id($lk_id);

        return array(
            'ok'      => true,
            'message' => '홍보 링크가 생성되었습니다.',
            'link'    => $link ? lc_link_to_api(array_merge($link, array(
                'cp_name'              => $campaign['cp_name'],
                'cp_price'             => $campaign['cp_price'],
                'cp_tracking_base_url' => (string) ($campaign['cp_tracking_base_url'] ?? ''),
            ))) : null,
        );
    }
}

if (!function_exists('lc_link_record_click')) {
    function lc_link_record_click(array $link)
    {
        if (!lc_db_installed() || empty($link['lk_id'])) {
            return 0;
        }

        $lk_id = (int) $link['lk_id'];
        $pt_id = (int) $link['pt_id'];
        $cp_id = (int) $link['cp_id'];
        $ip = isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^0-9a-fA-F:\.]/', '', $_SERVER['REMOTE_ADDR']) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? substr((string) $_SERVER['HTTP_REFERER'], 0, 500) : '';

        $table = lc_table('clicks');
        lc_sql_query(" INSERT INTO `{$table}` SET
            lk_id = '{$lk_id}',
            pt_id = '{$pt_id}',
            cp_id = '{$cp_id}',
            cl_ip = '" . lc_sql_escape($ip) . "',
            cl_user_agent = '" . lc_sql_escape($ua) . "',
            cl_referer = '" . lc_sql_escape($referer) . "',
            cl_created_at = NOW() ", false);

        return (int) lc_sql_insert_id();
    }
}

if (!function_exists('lc_link_resolve_redirect_url')) {
    function lc_link_resolve_redirect_url(array $link)
    {
        $landing = trim((string) ($link['cp_landing_url'] ?? ''));
        $lk_code = trim((string) ($link['lk_code'] ?? ''));
        $tracking_base = isset($link['cp_tracking_base_url']) ? (string) $link['cp_tracking_base_url'] : '';

        if ($landing !== '') {
            $landing = lc_link_apply_tracking_host($landing, $tracking_base);
            if ($lk_code !== '' && stripos($landing, 'lkcode=') === false && stripos($landing, 'code=') === false) {
                $landing .= (strpos($landing, '?') !== false ? '&' : '?') . 'lkCode=' . rawurlencode($lk_code);
            }
            if (strpos($landing, '://') === false) {
                $prefix = lc_link_tracking_base_url($tracking_base);
                if ($prefix === '' && defined('G5_URL')) {
                    $prefix = rtrim((string) G5_URL, '/');
                }

                return $prefix !== '' ? ($prefix . '/' . ltrim($landing, '/')) : $landing;
            }

            return $landing;
        }

        return lc_landing_public_url($lk_code, $tracking_base);
    }
}

if (!function_exists('lc_link_partner_click_summary')) {
    function lc_link_partner_click_summary($pt_id)
    {
        if (!lc_db_installed()) {
            return array('today' => 0, 'total' => 0);
        }

        $pt_id = (int) $pt_id;
        $table = lc_table('clicks');
        $today = date('Y-m-d');
        $row = lc_sql_fetch(" SELECT
            COUNT(*) AS total_cnt,
            SUM(CASE WHEN DATE(cl_created_at) = '{$today}' THEN 1 ELSE 0 END) AS today_cnt
            FROM `{$table}` WHERE pt_id = '{$pt_id}' ");

        return array(
            'today' => (int) ($row['today_cnt'] ?? 0),
            'total' => (int) ($row['total_cnt'] ?? 0),
        );
    }
}

if (!function_exists('lc_link_partner_channel_stats')) {
    function lc_link_partner_channel_stats($pt_id, $limit = 5)
    {
        if (!lc_db_installed()) {
            return array();
        }

        $pt_id = (int) $pt_id;
        $limit = max(1, min(20, (int) $limit));
        $lk_table = lc_table('links');
        $cl_table = lc_table('clicks');
        $cv_table = lc_table('conversions');

        $rows = array();
        $result = lc_sql_query(" SELECT lk.lk_channel AS channel,
            COUNT(DISTINCT cl.cl_id) AS clicks,
            COUNT(DISTINCT cv.cv_id) AS dbs,
            SUM(CASE WHEN cv.cv_status = '" . lc_sql_escape(LC_STATUS_APPROVED) . "' THEN 1 ELSE 0 END) AS approved
            FROM `{$lk_table}` lk
            LEFT JOIN `{$cl_table}` cl ON cl.lk_id = lk.lk_id
            LEFT JOIN `{$cv_table}` cv ON cv.lk_id = lk.lk_id
            WHERE lk.pt_id = '{$pt_id}' AND lk.lk_channel != ''
            GROUP BY lk.lk_channel
            ORDER BY clicks DESC
            LIMIT {$limit} ", false);

        if ($result) {
            $total_clicks = 0;
            $items = array();
            while ($row = sql_fetch_array($result)) {
                $clicks = (int) $row['clicks'];
                $total_clicks += $clicks;
                $items[] = array(
                    'channel'  => (string) $row['channel'],
                    'clicks'   => $clicks,
                    'dbs'      => (int) $row['dbs'],
                    'approved' => (int) $row['approved'],
                );
            }

            foreach ($items as &$item) {
                $item['percentage'] = $total_clicks > 0 ? (int) round(($item['clicks'] / $total_clicks) * 100) : 0;
            }
            unset($item);

            return $items;
        }

        return array();
    }
}
