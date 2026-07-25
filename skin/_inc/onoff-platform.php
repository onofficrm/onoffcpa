<?php
/**
 * 온오프 플랫폼 스킨 공통 헤더
 * @onoff-platform-managed
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('onoff_platform_linkconnect_config')) {
    function onoff_platform_linkconnect_config()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        if (defined('G5_PLUGIN_PATH') && is_file(G5_PLUGIN_PATH . '/linkconnect/config.php')) {
            include_once G5_PLUGIN_PATH . '/linkconnect/config.php';
        }
        if (defined('G5_PLUGIN_PATH') && is_file(G5_PLUGIN_PATH . '/linkconnect/inc/db.php')) {
            include_once G5_PLUGIN_PATH . '/linkconnect/inc/db.php';
        }
        if (defined('G5_PLUGIN_PATH') && is_file(G5_PLUGIN_PATH . '/linkconnect/inc/settings.php')) {
            include_once G5_PLUGIN_PATH . '/linkconnect/inc/settings.php';
        }
    }
}

if (!function_exists('onoff_platform_is_linkconnect')) {
    function onoff_platform_is_linkconnect()
    {
        if (function_exists('g5site_cfg')) {
            return g5site_cfg('home_builder_bridge_id', '') === 'linkconnect';
        }

        return false;
    }
}

if (!function_exists('onoff_platform_member_styles')) {
    function onoff_platform_member_styles($skin_url = '')
    {
        if (!function_exists('add_stylesheet')) {
            return;
        }

        $tokens = G5_URL . '/css/icrm-design-tokens.css';
        $platform = G5_URL . '/css/onoff-platform.css';
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($tokens, ENT_QUOTES, 'UTF-8') . '">', 0);
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') . '">', 1);

        if (onoff_platform_is_linkconnect()) {
            $favicon = G5_URL . '/plugin/onoff-builder-bridge/imports/linkconnect/favicon.png';
            echo '<link rel="icon" type="image/png" href="' . htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8') . '">' . PHP_EOL;
        }

        if ($skin_url !== '') {
            add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($skin_url, ENT_QUOTES, 'UTF-8') . '/style.css">', 2);
        }
    }
}

if (!function_exists('onoff_platform_board_styles')) {
    function onoff_platform_board_styles($board_skin_url = '')
    {
        if (!function_exists('add_stylesheet')) {
            return;
        }

        $tokens = G5_URL . '/css/icrm-design-tokens.css';
        $platform = G5_URL . '/css/onoff-platform.css';
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($tokens, ENT_QUOTES, 'UTF-8') . '">', 0);
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') . '">', 1);

        if ($board_skin_url !== '') {
            add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($board_skin_url, ENT_QUOTES, 'UTF-8') . '/style.css">', 2);
        }
    }
}

if (!function_exists('onoff_platform_outlogin_styles')) {
    function onoff_platform_outlogin_styles($outlogin_skin_url = '')
    {
        if (!function_exists('add_stylesheet')) {
            return;
        }

        $tokens = G5_URL . '/css/icrm-design-tokens.css';
        $platform = G5_URL . '/css/onoff-platform.css';
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($tokens, ENT_QUOTES, 'UTF-8') . '">', 0);
        add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') . '">', 1);

        if ($outlogin_skin_url !== '') {
            add_stylesheet('<link rel="stylesheet" href="' . htmlspecialchars($outlogin_skin_url, ENT_QUOTES, 'UTF-8') . '/style.css">', 2);
        }
    }
}

if (!function_exists('onoff_platform_homepage_title')) {
    /**
     * 회원 화면 브랜드명
     */
    function onoff_platform_homepage_title()
    {
        if (onoff_platform_is_linkconnect()) {
            onoff_platform_linkconnect_config();
            if (function_exists('lc_settings_get')) {
                $name = trim((string) lc_settings_get('siteName', ''));
                if ($name !== '') {
                    return $name;
                }
            }

            return '링크커넥트';
        }

        if (function_exists('g5site_cfg')) {
            $name = trim((string) g5site_cfg('site_name', ''));
            if ($name !== '') {
                return $name;
            }
        }

        global $config;
        $title = isset($config['cf_title']) ? trim(get_text($config['cf_title'])) : '';

        return $title !== '' ? $title : '온오프빌더';
    }
}

if (!function_exists('onoff_platform_member_shell_class')) {
    function onoff_platform_member_shell_class()
    {
        $classes = array('onoff-platform', 'onoff-platform--member');
        if (onoff_platform_is_linkconnect()) {
            $classes[] = 'onoff-platform--linkconnect';
        }

        return implode(' ', $classes);
    }
}

if (!function_exists('onoff_platform_login_return_path')) {
    function onoff_platform_login_return_path()
    {
        global $url;

        $raw = isset($url) ? (string) $url : '';
        if ($raw === '') {
            return '';
        }

        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $raw;
        }

        return strtolower($path);
    }
}

if (!function_exists('onoff_platform_member_center_meta')) {
    /**
     * 로그인 return URL 기준 센터 컨텍스트
     *
     * @return array{eyebrow:string, hint:string}
     */
    function onoff_platform_member_center_meta()
    {
        $path = onoff_platform_login_return_path();

        if ($path !== '' && (strpos($path, '/admin') !== false || $path === '/admin')) {
            return array(
                'eyebrow' => '관리자센터',
                'hint'    => '관리자 계정으로 로그인하세요',
            );
        }
        if ($path !== '' && (strpos($path, '/advertiser') !== false || $path === '/advertiser')) {
            return array(
                'eyebrow' => '광고주센터',
                'hint'    => '광고주 계정으로 로그인하세요',
            );
        }
        if ($path !== '' && (strpos($path, '/partner') !== false || $path === '/partner')) {
            return array(
                'eyebrow' => '파트너센터',
                'hint'    => '파트너 계정으로 로그인하세요',
            );
        }

        return array(
            'eyebrow' => 'MEMBER',
            'hint'    => 'CPA/CPS 제휴마케팅 플랫폼',
        );
    }
}

if (!function_exists('onoff_platform_member_page_hint')) {
    /**
     * 페이지별 부제 (센터 힌트보다 우선)
     */
    function onoff_platform_member_page_hint($page_label, $fallback = '')
    {
        $label = trim((string) $page_label);
        $hints = array(
            '회원가입'           => '약관에 동의한 뒤 회원 정보를 입력해 주세요',
            '정보수정'           => '변경할 항목만 수정하셔도 됩니다',
            '가입 완료'          => '가입이 완료되었습니다. 아래에서 센터로 이동하세요',
            '아이디/비밀번호 찾기' => '가입 시 등록한 정보로 계정을 찾을 수 있습니다',
        );

        if ($label === '로그인' || $label === '') {
            return trim((string) $fallback);
        }

        return isset($hints[$label]) ? $hints[$label] : trim((string) $fallback);
    }
}

if (!function_exists('onoff_platform_member_top_bar')) {
    /** 회원 화면 상단 — LinkConnect는 카드 히어로에 브랜드를 통합 */
    function onoff_platform_member_top_bar()
    {
        if (onoff_platform_is_linkconnect()) {
            return;
        }

        $title = onoff_platform_homepage_title();
        $home = defined('G5_URL') ? rtrim(G5_URL, '/') . '/' : '/';

        echo '<div class="onoff-platform__top onoff-platform__top--lc">';
        echo '<a href="' . htmlspecialchars($home, ENT_QUOTES, 'UTF-8') . '" class="onoff-platform__top-link">';
        echo '<span class="onoff-platform__top-icon" aria-hidden="true">';
        echo '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 1 7.07 0l1.41 1.41a5 5 0 0 1-7.07 7.07l-.71-.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M14 11a5 5 0 0 1-7.07 0L5.52 9.59a5 5 0 0 1 7.07-7.07l.71.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        echo '</span>';
        echo '<span class="onoff-platform__top-text">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '</a>';
        echo '</div>';
    }
}

if (!function_exists('onoff_platform_member_brand')) {
    /**
     * 로그인·가입·정보수정 상단
     *
     * @param string $page_label
     * @param array{compact?:bool} $options compact=true 이면 큰 제목 숨김 (탭과 중복 방지)
     */
    function onoff_platform_member_brand($page_label = '', $options = array())
    {
        global $g5;

        $compact = !empty($options['compact']);
        $brand = onoff_platform_homepage_title();
        $center = onoff_platform_member_center_meta();
        $label = trim((string) $page_label);
        if ($label === '' && isset($g5['title'])) {
            $label = trim(get_text($g5['title']));
        }
        if ($label === '') {
            $label = '로그인';
        }

        $hint = onoff_platform_member_page_hint($label, $center['hint']);
        $home = defined('G5_URL') ? rtrim(G5_URL, '/') . '/' : '/';
        $is_lc = onoff_platform_is_linkconnect();

        echo '<header class="onoff-platform__hero' . ($is_lc ? ' onoff-platform__hero--lc' : '') . ($compact ? ' onoff-platform__hero--compact' : '') . '">';
        echo '<div class="onoff-platform__hero-inner">';

        echo '<p class="onoff-platform__eyebrow">' . htmlspecialchars($center['eyebrow'], ENT_QUOTES, 'UTF-8') . '</p>';

        if ($is_lc) {
            echo '<a href="' . htmlspecialchars($home, ENT_QUOTES, 'UTF-8') . '" class="onoff-platform__hero-home">';
            echo '<span class="onoff-platform__hero-mark" aria-hidden="true">';
            echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 1 7.07 0l1.41 1.41a5 5 0 0 1-7.07 7.07l-.71-.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M14 11a5 5 0 0 1-7.07 0L5.52 9.59a5 5 0 0 1 7.07-7.07l.71.71" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
            echo '</span>';
            echo '<span class="onoff-platform__hero-brand">' . htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</a>';
        } else {
            echo '<p class="onoff-platform__brand-name">' . htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        if (!$compact) {
            echo '<h1 class="onoff-platform__page-title">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h1>';
        }

        if ($hint !== '') {
            echo '<p class="onoff-platform__brand-hint">' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        echo '</div>';
        echo '</header>';
    }
}

if (!function_exists('onoff_platform_member_footer')) {
    /** 회원 화면 하단 홈페이지 정보 */
    function onoff_platform_member_footer()
    {
        $title = onoff_platform_homepage_title();
        $phone = '';

        if (onoff_platform_is_linkconnect()) {
            onoff_platform_linkconnect_config();
            if (function_exists('lc_contact_phone')) {
                $phone = trim((string) lc_contact_phone());
            }
        }

        if ($phone === '' && function_exists('g5site_cfg')) {
            $phone = trim((string) g5site_cfg('phone', ''));
        }

        // 템플릿 플레이스홀더 · 미배포 _site.config 값 보정
        if ($phone === '' || $phone === '010-0000-0000') {
            $phone = '070-8098-6824';
        }

        echo '<p class="onoff-platform__footer">&copy; ' . date('Y') . ' ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        if ($phone !== '') {
            echo ' &middot; 문의 ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        }
        echo '</p>';
    }
}

if (!function_exists('onoff_platform_member_tabs')) {
    /** 로그인 / 회원가입 탭 */
    function onoff_platform_member_tabs($active = 'login')
    {
        global $url;

        $return_qs = isset($url) && $url !== '' ? '?url=' . urlencode($url) : '';
        $login_url = (defined('G5_BBS_URL') ? G5_BBS_URL : '') . '/login.php' . $return_qs;
        $register_url = (defined('G5_BBS_URL') ? G5_BBS_URL : '') . '/register.php' . $return_qs;

        echo '<nav class="onoff-platform__tabs mb_log_cate" aria-label="회원 메뉴">';

        if ($active === 'login') {
            echo '<span class="onoff-platform__tab is-active" aria-current="page">로그인</span>';
            echo '<a href="' . htmlspecialchars($register_url, ENT_QUOTES, 'UTF-8') . '" class="onoff-platform__tab">회원가입</a>';
        } else {
            echo '<a href="' . htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8') . '" class="onoff-platform__tab">로그인</a>';
            echo '<span class="onoff-platform__tab is-active" aria-current="page">회원가입</span>';
        }

        echo '</nav>';
    }
}
