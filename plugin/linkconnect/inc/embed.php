<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_api_allow_public_cors')) {
    /** 워드프레스 등 외부 사이트 임베드용 CORS (credentials 없음) */
    function lc_api_allow_public_cors()
    {
        if (headers_sent()) {
            return;
        }
        header('Access-Control-Allow-Origin: *');
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

if (!function_exists('lc_embed_script_url')) {
    function lc_embed_script_url()
    {
        return function_exists('lc_asset_url')
            ? lc_asset_url('js/lead-embed.js')
            : lc_site_absolute_url('/plugin/linkconnect/assets/js/lead-embed.js');
    }
}

if (!function_exists('lc_embed_config_for_lk_code')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_embed_config_for_lk_code($lk_code)
    {
        $lk_code = trim((string) $lk_code);
        if ($lk_code === '' || !function_exists('lc_link_get_with_campaign')) {
            return null;
        }

        $link = lc_link_get_with_campaign($lk_code);
        if (!$link || ($link['lk_status'] ?? '') !== 'active' || ($link['cp_status'] ?? '') !== LC_STATUS_ACTIVE) {
            return null;
        }

        $campaign_title = trim((string) ($link['cp_name'] ?? '상담 신청'));
        $partner_code = '';
        $pt_id = (int) ($link['pt_id'] ?? 0);
        if ($pt_id > 0 && function_exists('lc_get_partner_by_id')) {
            $partner = lc_get_partner_by_id($pt_id);
            if (is_array($partner)) {
                $partner_code = (string) ($partner['pt_code'] ?? '');
            }
        }
        $submit_url = lc_site_absolute_url('/plugin/linkconnect/api/receive.php');
        $config_url = lc_site_absolute_url('/plugin/linkconnect/api/embed.php');
        $privacy_url = lc_site_absolute_url('/privacy');

        return array(
            'lkCode'        => (string) ($link['lk_code'] ?? $lk_code),
            'campaignId'    => (int) ($link['cp_id'] ?? 0),
            'campaignCode'  => (string) ($link['cp_code'] ?? ''),
            'campaignTitle' => $campaign_title,
            'partnerCode'   => $partner_code,
            'submitUrl'     => $submit_url,
            'configUrl'     => $config_url,
            'privacyUrl'    => $privacy_url,
            'scriptUrl'     => lc_embed_script_url(),
            'pluginDownloadUrl' => lc_site_absolute_url('/plugin/linkconnect/assets/wordpress/linkconnect-lead.zip'),
            'channel'       => 'wordpress',
            'title'         => '상담 신청',
            'subtitle'      => $campaign_title !== '' ? $campaign_title : '빠른 상담을 남겨 주세요.',
            'submitLabel'   => '상담 신청하기',
            'successMessage'=> '상담 신청이 접수되었습니다. 곧 연락드리겠습니다.',
            'fields'        => array(
                array('name' => 'name', 'label' => '이름', 'type' => 'text', 'required' => true, 'placeholder' => '홍길동'),
                array('name' => 'phone', 'label' => '연락처', 'type' => 'tel', 'required' => true, 'placeholder' => '010-1234-5678'),
                array('name' => 'region', 'label' => '지역', 'type' => 'text', 'required' => false, 'placeholder' => '서울 / 경기 등'),
                array('name' => 'inquiry', 'label' => '문의 내용', 'type' => 'textarea', 'required' => false, 'placeholder' => '상담이 필요하신 내용을 적어 주세요.'),
            ),
            'theme'         => array(
                'accent'     => '#0d9488',
                'accentText' => '#ffffff',
                'border'     => '#e2e8f0',
                'bg'         => '#ffffff',
                'text'       => '#0f172a',
                'muted'      => '#64748b',
            ),
        );
    }
}

if (!function_exists('lc_embed_snippet_html')) {
    function lc_embed_snippet_html($lk_code)
    {
        $lk_code = trim((string) $lk_code);
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $lk_code);
        if ($safe === '') {
            $safe = 'form';
        }
        $id = 'lc-lead-' . substr($safe, 0, 32);
        $script = htmlspecialchars(lc_embed_script_url(), ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars($lk_code, ENT_QUOTES, 'UTF-8');

        return "<!-- 온오프CPA 상담신청 폼 -->\n"
            . '<div id="' . $id . '"></div>' . "\n"
            . '<script src="' . $script . '" data-lk-code="' . $code . '" data-target="#' . $id . '" async></script>';
    }
}
