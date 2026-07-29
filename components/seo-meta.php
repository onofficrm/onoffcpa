<?php
/**
 * SEO 메타·OG·Twitter·JSON-LD 컴포넌트
 * - head.php / head_sub_before / 빌더 SPA 렌더러에서 사용
 * - 그누보드 html_process_add_meta / html_process_buffer 훅으로 head.sub.php 수정 없이 주입
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('g5site_cfg') && is_file(G5_PATH . '/_site.config.php')) {
    include_once G5_PATH . '/_site.config.php';
}

/**
 * HTML 속성·텍스트 이스케이프
 *
 * @param string $str
 * @return string
 */
if (!function_exists('g5b_seo_escape')) {
    function g5b_seo_escape($str)
    {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * 공개 사이트 절대 URL 베이스 (localhost 회피, G5_URL 우선)
 *
 * @return string
 */
if (!function_exists('g5b_seo_site_base')) {
    function g5b_seo_site_base()
    {
        if (defined('G5_URL') && G5_URL !== '') {
            $base = rtrim((string) G5_URL, '/');
            if ($base !== '' && !preg_match('#^https?://(localhost|127\.0\.0\.1)(:|/|$)#i', $base)) {
                return $base;
            }
        }

        if (!empty($_SERVER['HTTP_HOST']) && !preg_match('#^(localhost|127\.0\.0\.1)(:|$)#i', (string) $_SERVER['HTTP_HOST'])) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }

        return defined('G5_URL') ? rtrim((string) G5_URL, '/') : '';
    }
}

/**
 * 요청 경로 (사이트 base path 제거, 쿼리 제외)
 *
 * @return string
 */
if (!function_exists('g5b_seo_request_path')) {
    function g5b_seo_request_path()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        if (defined('G5_URL')) {
            $base_path = parse_url(G5_URL, PHP_URL_PATH);
            if (is_string($base_path) && $base_path !== '' && $base_path !== '/') {
                if (strpos($path, $base_path) === 0) {
                    $path = substr($path, strlen($base_path));
                    if ($path === '') {
                        $path = '/';
                    }
                }
            }
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/index.php') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }
}

/**
 * 현재 페이지 canonical URL (쿼리 제외, G5_URL 기준 HTTPS)
 *
 * @return string
 */
if (!function_exists('g5b_seo_current_url')) {
    function g5b_seo_current_url()
    {
        $base = g5b_seo_site_base();
        $path = g5b_seo_request_path();

        if ($base === '') {
            return $path;
        }

        return $base . ($path === '/' ? '/' : $path);
    }
}

/**
 * 상대/절대 경로를 공개 HTTPS URL로 변환
 *
 * @param string $path
 * @return string
 */
if (!function_exists('g5b_seo_absolute_url')) {
    function g5b_seo_absolute_url($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            if (preg_match('#^https?://(localhost|127\.0\.0\.1)#i', $path) && defined('G5_URL') && G5_URL !== '') {
                $parsed = parse_url($path);
                $rel = isset($parsed['path']) ? $parsed['path'] : '/';
                if (!empty($parsed['query'])) {
                    $rel .= '?' . $parsed['query'];
                }
                return rtrim((string) G5_URL, '/') . $rel;
            }
            return $path;
        }

        $base = g5b_seo_site_base();
        if ($base === '') {
            return $path;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

/**
 * 로컬 파일 존재 여부 (웹 경로 → G5_PATH)
 *
 * @param string $web_path
 * @return bool
 */
if (!function_exists('g5b_seo_public_file_exists')) {
    function g5b_seo_public_file_exists($web_path)
    {
        $web_path = trim((string) $web_path);
        if ($web_path === '' || preg_match('#^https?://#i', $web_path)) {
            return $web_path !== '';
        }

        if (!defined('G5_PATH')) {
            return true;
        }

        $full = G5_PATH . '/' . ltrim($web_path, '/');
        return is_file($full);
    }
}

/**
 * OG 이미지 MIME 타입 추정
 *
 * @param string $url
 * @return string
 */
if (!function_exists('g5b_seo_image_mime')) {
    function g5b_seo_image_mime($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $url;
        }

        if (defined('G5_PATH') && $path[0] === '/') {
            $full = G5_PATH . $path;
            if (is_file($full)) {
                if (function_exists('mime_content_type')) {
                    $mime = @mime_content_type($full);
                    if (is_string($mime) && strpos($mime, 'image/') === 0) {
                        return $mime;
                    }
                }
                $info = @getimagesize($full);
                if (is_array($info) && !empty($info['mime'])) {
                    return (string) $info['mime'];
                }
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = array(
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        );

        return isset($map[$ext]) ? $map[$ext] : '';
    }
}

/**
 * 기본 OG 이미지 (파일 존재 확인 + fallback)
 *
 * @return array{url:string,width:int,height:int,alt:string}
 */
if (!function_exists('g5b_seo_default_og_image')) {
    function g5b_seo_default_og_image()
    {
        $site_name = function_exists('g5site_cfg') ? g5site_cfg('site_name', '온오프CPA') : '온오프CPA';
        $candidates = array();

        if (function_exists('g5site_cfg')) {
            $cfg = g5site_cfg('og_image', '');
            if ($cfg !== '') {
                $candidates[] = array(
                    'path'   => $cfg,
                    'width'  => (int) g5site_cfg('og_image_width', '0'),
                    'height' => (int) g5site_cfg('og_image_height', '0'),
                );
            }
        }

        $candidates[] = array(
            'path'   => '/plugin/onoff-builder-bridge/imports/linkconnect/hero_dashboard_mockup.png',
            'width'  => 1536,
            'height' => 1024,
        );
        $candidates[] = array(
            'path'   => '/plugin/linkconnect/install/assets/thumb-dasibom.jpg',
            'width'  => 1200,
            'height' => 900,
        );
        $candidates[] = array(
            'path'   => '/img/brand/apple-touch-icon.png',
            'width'  => 180,
            'height' => 180,
        );

        foreach ($candidates as $item) {
            $path = $item['path'];
            if (!g5b_seo_public_file_exists($path) && !preg_match('#^https?://#i', $path)) {
                continue;
            }

            $width = (int) $item['width'];
            $height = (int) $item['height'];
            if (defined('G5_PATH') && !preg_match('#^https?://#i', $path)) {
                $full = G5_PATH . '/' . ltrim($path, '/');
                if (is_file($full)) {
                    $size = @getimagesize($full);
                    if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
                        $width = (int) $size[0];
                        $height = (int) $size[1];
                    }
                }
            }

            return array(
                'url'    => g5b_seo_absolute_url($path),
                'width'  => $width > 0 ? $width : 1200,
                'height' => $height > 0 ? $height : 630,
                'alt'    => $site_name . ' 대표 이미지',
            );
        }

        return array(
            'url'    => '',
            'width'  => 0,
            'height' => 0,
            'alt'    => $site_name,
        );
    }
}

/**
 * 관리자·로그인·회원·API 등 noindex 대상인지
 *
 * @return bool
 */
if (!function_exists('g5b_seo_should_noindex')) {
    function g5b_seo_should_noindex()
    {
        if (defined('G5_IS_ADMIN') && G5_IS_ADMIN) {
            return true;
        }

        $path = g5b_seo_request_path();
        $noindex_prefixes = array(
            '/adm',
            '/admin',
            '/partner',
            '/advertiser',
            '/api',
            '/plugin/linkconnect/admin',
            '/plugin/linkconnect/partner',
            '/plugin/linkconnect/merchant',
            '/plugin/linkconnect/api',
            '/plugin/seo_meta',
            '/plugin/onoff-builder-bridge/admin',
            '/plugin/onoff-builder-bridge/member',
            '/bbs/login',
            '/bbs/logout',
            '/bbs/register',
            '/bbs/member_confirm',
            '/bbs/password',
            '/bbs/password_lost',
            '/bbs/password_reset',
            '/bbs/memo',
            '/bbs/scrap',
            '/bbs/point',
            '/bbs/profile',
            '/bbs/qalist',
            '/bbs/qawrite',
            '/bbs/qaview',
            '/icrm',
            '/install',
            '/cron',
        );

        foreach ($noindex_prefixes as $prefix) {
            if ($path === $prefix || strpos($path, $prefix . '/') === 0 || strpos($path, $prefix . '.php') === 0) {
                return true;
            }
        }

        if (preg_match('#^/notice/(write|\d+/edit)$#', $path)) {
            return true;
        }

        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        if (preg_match('#/(adm|admin|api|cron|install)(/|$)#i', $script)) {
            return true;
        }
        if (preg_match('#/bbs/(login|logout|register|member_confirm|password|memo|scrap|point|profile)#i', $script)) {
            return true;
        }

        return false;
    }
}

/**
 * SPA 공개 경로별 기본 타이틀 힌트
 *
 * @param string $path
 * @return array{title?:string,description?:string}
 */
if (!function_exists('g5b_seo_spa_path_meta')) {
    function g5b_seo_spa_path_meta($path)
    {
        $path = '/' . ltrim((string) $path, '/');
        $path = rtrim($path, '/') ?: '/';

        $map = array(
            '/'            => array(),
            '/about'       => array('title' => '회사소개'),
            '/affiliate'   => array('title' => '제휴안내'),
            '/cpa-list'    => array(
                'title'       => 'CPA 광고상품',
                'description' => '온오프CPA에서 홍보 가능한 CPA 광고상품을 확인하고 파트너로 참여하세요.',
            ),
            '/events'      => array('title' => '이벤트'),
            '/notice'      => array('title' => '공지사항'),
            '/select-center' => array('title' => '센터 선택'),
        );

        if (isset($map[$path])) {
            return $map[$path];
        }

        if (preg_match('#^/notice/#', $path)) {
            return array('title' => '공지사항');
        }
        if (preg_match('#^/events/#', $path)) {
            return array('title' => '이벤트');
        }

        return array();
    }
}

/**
 * 게시글 기본 메타(수동 SEO 없을 때 제목·요약·썸네일)
 */
if (!function_exists('g5b_seo_apply_board_fallbacks')) {
    function g5b_seo_apply_board_fallbacks()
    {
        global $bo_table, $wr_id, $write, $board, $page_title, $page_description, $page_og_image, $page_canonical;

        if (empty($bo_table) || empty($wr_id) || !is_array($write) || empty($write['wr_id']) || !empty($write['wr_is_comment'])) {
            return;
        }

        if (empty($page_title) && !empty($write['wr_subject'])) {
            $page_title = trim(strip_tags((string) $write['wr_subject']));
        }

        if (empty($page_description) && !empty($write['wr_content'])) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $write['wr_content'])));
            if ($plain !== '') {
                $page_description = function_exists('cut_str') ? cut_str($plain, 160) : mb_substr($plain, 0, 160);
            }
        }

        if (empty($page_og_image) && function_exists('get_list_thumbnail')) {
            $thumb = get_list_thumbnail($bo_table, (int) $wr_id, 1200, 630, false, true);
            if (!empty($thumb['src'])) {
                $page_og_image = $thumb['src'];
            }
        }

        if (empty($page_canonical) && function_exists('get_pretty_url')) {
            $page_canonical = get_pretty_url($bo_table, (int) $wr_id);
        } elseif (empty($page_canonical) && defined('G5_BBS_URL')) {
            $page_canonical = G5_BBS_URL . '/board.php?bo_table=' . urlencode($bo_table) . '&wr_id=' . (int) $wr_id;
        }

        if (empty($page_title) && is_array($board) && !empty($board['bo_subject'])) {
            $page_title = trim(strip_tags((string) $board['bo_subject']));
        }
    }
}

/**
 * 페이지·site_config·fallback 병합
 *
 * @param bool $fresh true면 캐시 무시
 * @return array
 */
if (!function_exists('g5b_seo_resolve')) {
    function g5b_seo_resolve($fresh = false)
    {
        static $cache = null;

        if ($fresh) {
            $cache = null;
        }
        if ($cache !== null) {
            return $cache;
        }

        global $g5, $config, $page_title, $page_description, $page_keywords,
               $page_og_image, $page_canonical, $page_robots, $page_schema_type,
               $page_og_image_width, $page_og_image_height, $page_og_image_alt;

        if (function_exists('g5b_seo_apply_board_fallbacks')) {
            g5b_seo_apply_board_fallbacks();
        }

        $site_name = function_exists('g5site_cfg') ? g5site_cfg('site_name', '') : '';
        $cf_title = isset($config['cf_title']) ? trim(strip_tags($config['cf_title'])) : '';
        if ($site_name === '') {
            $site_name = $cf_title !== '' ? $cf_title : '온오프CPA';
        }
        if ($cf_title === '') {
            $cf_title = $site_name;
        }

        $fallback_desc = '광고주와 파트너를 연결하는 CPA 제휴마케팅 플랫폼입니다.';
        $site_desc = function_exists('g5site_cfg') ? g5site_cfg('site_desc', $fallback_desc) : $fallback_desc;
        $seo_desc = function_exists('g5site_cfg') ? g5site_cfg('seo_description', '') : '';
        if ($seo_desc === '') {
            $seo_desc = $site_desc;
        }

        $default_title = function_exists('g5site_cfg')
            ? g5site_cfg('seo_title', $site_name . ' | CPA 제휴마케팅 플랫폼')
            : $site_name . ' | CPA 제휴마케팅 플랫폼';
        if ($default_title === '') {
            $default_title = $site_name . ' | CPA 제휴마케팅 플랫폼';
        }

        $is_home = g5b_seo_request_path() === '/';

        $title = '';
        if (!empty($page_title)) {
            $title = trim(strip_tags((string) $page_title));
            if ($title !== '' && stripos($title, $site_name) === false && !$is_home) {
                $title = $title . ' | ' . $site_name;
            }
        } elseif ($is_home) {
            $title = $default_title;
        } elseif (!empty($g5['title'])) {
            $g5_title = trim(strip_tags((string) $g5['title']));
            if ($g5_title === $site_name || $g5_title === $cf_title) {
                $title = $default_title;
            } else {
                $parts = array_filter(array($g5_title, $site_name));
                $title = implode(' | ', $parts);
            }
        } else {
            $title = $default_title;
        }

        $description = '';
        if (!empty($page_description)) {
            $description = trim(strip_tags((string) $page_description));
        } else {
            $description = $seo_desc !== '' ? $seo_desc : $fallback_desc;
        }

        $keywords = '';
        if (!empty($page_keywords)) {
            $keywords = trim(strip_tags((string) $page_keywords));
        } elseif (function_exists('g5site_cfg')) {
            $main_kw = g5site_cfg('main_keyword', '');
            $sub_kw = g5site_cfg('sub_keywords', '');
            if (is_array($sub_kw)) {
                $sub_kw = implode(', ', array_filter(array_map('trim', $sub_kw)));
            }
            $kw_parts = array_filter(array($main_kw, $sub_kw));
            $keywords = implode(', ', $kw_parts);
        }

        $canonical = '';
        if (!empty($page_canonical)) {
            $canonical = g5b_seo_absolute_url(trim((string) $page_canonical));
        } else {
            $canonical = g5b_seo_current_url();
        }

        $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        if (g5b_seo_should_noindex()) {
            $robots = 'noindex, nofollow';
        } elseif (!empty($page_robots)) {
            $robots = trim(strip_tags((string) $page_robots));
        } elseif (function_exists('g5site_cfg') && g5site_cfg('robots', '') !== '') {
            $cfg_robots = trim(g5site_cfg('robots', ''));
            if ($cfg_robots === 'index,follow' || $cfg_robots === 'index, follow') {
                $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
            } else {
                $robots = $cfg_robots;
            }
        }

        $default_og = g5b_seo_default_og_image();
        $og_image = '';
        $og_w = (int) $default_og['width'];
        $og_h = (int) $default_og['height'];
        $og_alt = $default_og['alt'];
        $og_type_mime = '';

        if (!empty($page_og_image)) {
            $og_image = g5b_seo_absolute_url(trim((string) $page_og_image));
            if (!empty($page_og_image_width)) {
                $og_w = (int) $page_og_image_width;
            }
            if (!empty($page_og_image_height)) {
                $og_h = (int) $page_og_image_height;
            }
            if (!empty($page_og_image_alt)) {
                $og_alt = trim(strip_tags((string) $page_og_image_alt));
            } else {
                $og_alt = $title !== '' ? $title : $site_name;
            }
        } else {
            $og_image = $default_og['url'];
        }

        if ($og_image !== '') {
            $og_type_mime = g5b_seo_image_mime($og_image);
        }

        $og_url = $canonical !== '' ? $canonical : g5b_seo_current_url();

        $company_name = function_exists('g5site_cfg') ? g5site_cfg('company_name', $site_name) : $site_name;
        $logo_url = function_exists('g5site_cfg_url') ? g5site_cfg_url('logo_path', '') : '';
        if ($logo_url !== '') {
            $logo_url = g5b_seo_absolute_url($logo_url);
        }
        $phone = function_exists('g5site_cfg') ? g5site_cfg('phone', '') : '';
        $email = function_exists('g5site_cfg') ? g5site_cfg('email', '') : '';
        $address = function_exists('g5site_cfg') ? g5site_cfg('address', '') : '';
        if ($address === '주소를 입력하세요') {
            $address = '';
        }
        if ($phone === '010-0000-0000') {
            $phone = '';
        }

        $theme_color = function_exists('g5site_cfg') ? g5site_cfg('theme_color', g5site_cfg('primary_color', '#2563eb')) : '#2563eb';
        $author = $company_name !== '' ? $company_name : $site_name;

        $schema_type = 'Organization';
        if (!empty($page_schema_type)) {
            $schema_type = preg_replace('/[^a-zA-Z]/', '', (string) $page_schema_type);
            if ($schema_type === '') {
                $schema_type = 'Organization';
            }
        }

        $og_type = ($is_home || g5b_seo_request_path() === '/') ? 'website' : 'article';

        $cache = array(
            'title'             => $title,
            'description'       => $description,
            'keywords'          => $keywords,
            'canonical'         => $canonical,
            'robots'            => $robots,
            'googlebot'         => $robots,
            'theme_color'       => $theme_color,
            'author'            => $author,
            'application_name'  => $site_name,
            'og_title'          => $title,
            'og_description'    => $description,
            'og_image'          => $og_image,
            'og_image_width'    => $og_w,
            'og_image_height'   => $og_h,
            'og_image_alt'      => $og_alt,
            'og_image_type'     => $og_type_mime,
            'og_url'            => $og_url,
            'og_type'           => $og_type,
            'og_locale'         => 'ko_KR',
            'site_name'         => $site_name,
            'company_name'      => $company_name,
            'logo_url'          => $logo_url,
            'phone'             => $phone,
            'email'             => $email,
            'address'           => $address,
            'schema_type'       => $schema_type,
            'site_url'          => g5b_seo_site_base(),
            'include_jsonld'    => !g5b_seo_should_noindex(),
        );

        return $cache;
    }
}

/**
 * meta·OG·canonical·JSON-LD HTML
 *
 * @param array|null $data
 * @param array      $options include_viewport
 * @return string
 */
if (!function_exists('g5b_seo_build_meta_html')) {
    function g5b_seo_build_meta_html($data = null, $options = array())
    {
        if ($data === null) {
            $data = g5b_seo_resolve();
        }

        $include_viewport = !empty($options['include_viewport']);
        $lines = array();

        if ($include_viewport) {
            $lines[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        }

        if ($data['description'] !== '') {
            $lines[] = '<meta name="description" content="' . g5b_seo_escape($data['description']) . '">';
        }
        if ($data['keywords'] !== '') {
            $lines[] = '<meta name="keywords" content="' . g5b_seo_escape($data['keywords']) . '">';
        }
        if ($data['robots'] !== '') {
            $lines[] = '<meta name="robots" content="' . g5b_seo_escape($data['robots']) . '">';
            $lines[] = '<meta name="googlebot" content="' . g5b_seo_escape($data['googlebot']) . '">';
        }
        if (!empty($data['author'])) {
            $lines[] = '<meta name="author" content="' . g5b_seo_escape($data['author']) . '">';
        }
        if (!empty($data['application_name'])) {
            $lines[] = '<meta name="application-name" content="' . g5b_seo_escape($data['application_name']) . '">';
        }
        if (!empty($data['theme_color'])) {
            $lines[] = '<meta name="theme-color" content="' . g5b_seo_escape($data['theme_color']) . '">';
        }
        if ($data['canonical'] !== '') {
            $lines[] = '<link rel="canonical" href="' . g5b_seo_escape($data['canonical']) . '">';
        }

        $lines[] = '<meta property="og:type" content="' . g5b_seo_escape($data['og_type']) . '">';
        $lines[] = '<meta property="og:site_name" content="' . g5b_seo_escape($data['site_name']) . '">';
        $lines[] = '<meta property="og:locale" content="' . g5b_seo_escape($data['og_locale']) . '">';
        if ($data['og_title'] !== '') {
            $lines[] = '<meta property="og:title" content="' . g5b_seo_escape($data['og_title']) . '">';
        }
        if ($data['og_description'] !== '') {
            $lines[] = '<meta property="og:description" content="' . g5b_seo_escape($data['og_description']) . '">';
        }
        if ($data['og_url'] !== '') {
            $lines[] = '<meta property="og:url" content="' . g5b_seo_escape($data['og_url']) . '">';
        }
        if ($data['og_image'] !== '') {
            $lines[] = '<meta property="og:image" content="' . g5b_seo_escape($data['og_image']) . '">';
            if (!empty($data['og_image_width'])) {
                $lines[] = '<meta property="og:image:width" content="' . (int) $data['og_image_width'] . '">';
            }
            if (!empty($data['og_image_height'])) {
                $lines[] = '<meta property="og:image:height" content="' . (int) $data['og_image_height'] . '">';
            }
            if (!empty($data['og_image_type'])) {
                $lines[] = '<meta property="og:image:type" content="' . g5b_seo_escape($data['og_image_type']) . '">';
            }
            if (!empty($data['og_image_alt'])) {
                $lines[] = '<meta property="og:image:alt" content="' . g5b_seo_escape($data['og_image_alt']) . '">';
            }
        }

        $lines[] = '<meta name="twitter:card" content="' . ($data['og_image'] !== '' ? 'summary_large_image' : 'summary') . '">';
        if ($data['og_title'] !== '') {
            $lines[] = '<meta name="twitter:title" content="' . g5b_seo_escape($data['og_title']) . '">';
        }
        if ($data['og_description'] !== '') {
            $lines[] = '<meta name="twitter:description" content="' . g5b_seo_escape($data['og_description']) . '">';
        }
        if ($data['og_image'] !== '') {
            $lines[] = '<meta name="twitter:image" content="' . g5b_seo_escape($data['og_image']) . '">';
            if (!empty($data['og_image_alt'])) {
                $lines[] = '<meta name="twitter:image:alt" content="' . g5b_seo_escape($data['og_image_alt']) . '">';
            }
        }

        if (!empty($data['include_jsonld'])) {
            $jsonld = g5b_seo_build_jsonld($data);
            if ($jsonld !== '') {
                $lines[] = '<script type="application/ld+json">' . $jsonld . '</script>';
            }
        }

        return implode(PHP_EOL, $lines);
    }
}

/**
 * JSON-LD (@graph: Organization + WebSite + WebPage)
 *
 * @param array $data
 * @return string
 */
if (!function_exists('g5b_seo_build_jsonld')) {
    function g5b_seo_build_jsonld($data)
    {
        $site_url = !empty($data['site_url']) ? $data['site_url'] : g5b_seo_site_base();
        if ($site_url === '') {
            return '';
        }

        $org = array(
            '@type' => $data['schema_type'],
            '@id'   => $site_url . '#organization',
            'name'  => $data['company_name'],
            'url'   => $site_url,
        );
        if (!empty($data['logo_url'])) {
            $org['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $data['logo_url'],
            );
        }
        if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $org['email'] = $data['email'];
        }
        if (!empty($data['phone'])) {
            $org['telephone'] = $data['phone'];
        }
        if (!empty($data['address'])) {
            $org['address'] = array(
                '@type'         => 'PostalAddress',
                'streetAddress' => $data['address'],
            );
        }

        $website = array(
            '@type'       => 'WebSite',
            '@id'         => $site_url . '#website',
            'url'         => $site_url,
            'name'        => $data['site_name'],
            'description' => $data['description'],
            'inLanguage'  => 'ko-KR',
            'publisher'   => array('@id' => $site_url . '#organization'),
        );

        $page_url = !empty($data['canonical']) ? $data['canonical'] : $site_url . '/';
        $webpage = array(
            '@type'       => 'WebPage',
            '@id'         => $page_url . '#webpage',
            'url'         => $page_url,
            'name'        => $data['title'],
            'description' => $data['description'],
            'isPartOf'    => array('@id' => $site_url . '#website'),
            'inLanguage'  => 'ko-KR',
        );
        if (!empty($data['og_image'])) {
            $webpage['primaryImageOfPage'] = array(
                '@type' => 'ImageObject',
                '@id'   => $page_url . '#primaryimage',
                'url'   => $data['og_image'],
            );
            if (!empty($data['og_image_width'])) {
                $webpage['primaryImageOfPage']['width'] = (int) $data['og_image_width'];
            }
            if (!empty($data['og_image_height'])) {
                $webpage['primaryImageOfPage']['height'] = (int) $data['og_image_height'];
            }
        }

        $graph = array(
            '@context' => 'https://schema.org',
            '@graph'   => array($org, $website, $webpage),
        );

        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '';
        }

        return str_replace('</', '<\/', $json);
    }
}

/**
 * SPA/독립 HTML에 SEO 메타 주입 (중복 title·description·og 제거)
 *
 * @param string $html
 * @param array|null $data
 * @return string
 */
if (!function_exists('g5b_seo_inject_into_html')) {
    function g5b_seo_inject_into_html($html, $data = null)
    {
        if ($data === null) {
            $data = g5b_seo_resolve();
        }

        $html = (string) $html;
        if ($html === '') {
            return $html;
        }

        // 기존 SEO 관련 태그 제거 (중복 방지)
        $patterns = array(
            '#<title[^>]*>.*?</title>#is',
            '#<meta\s+name=["\']description["\'][^>]*>#i',
            '#<meta\s+name=["\']keywords["\'][^>]*>#i',
            '#<meta\s+name=["\']robots["\'][^>]*>#i',
            '#<meta\s+name=["\']googlebot["\'][^>]*>#i',
            '#<meta\s+name=["\']author["\'][^>]*>#i',
            '#<meta\s+name=["\']application-name["\'][^>]*>#i',
            '#<meta\s+name=["\']theme-color["\'][^>]*>#i',
            '#<link\s+rel=["\']canonical["\'][^>]*>#i',
            '#<meta\s+property=["\']og:[^"\']+["\'][^>]*>#i',
            '#<meta\s+name=["\']twitter:[^"\']+["\'][^>]*>#i',
            '#<script[^>]*type=["\']application/ld\+json["\'][^>]*>.*?</script>#is',
        );
        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        $meta_block = '<title>' . g5b_seo_escape($data['title']) . '</title>' . PHP_EOL
            . g5b_seo_build_meta_html($data, array('include_viewport' => false));

        if (preg_match('#</head>#i', $html)) {
            $html = preg_replace('#</head>#i', $meta_block . PHP_EOL . '</head>', $html, 1);
        } else {
            $html = $meta_block . $html;
        }

        // lang
        $html = preg_replace('#<html([^>]*)\slang=["\'][^"\']*["\']#i', '<html$1 lang="ko"', $html, 1);
        if (!preg_match('#<html[^>]*\slang=#i', $html)) {
            $html = preg_replace('#<html#i', '<html lang="ko"', $html, 1);
        }

        // viewport 없으면 추가
        if (stripos($html, 'name="viewport"') === false && stripos($html, "name='viewport'") === false) {
            $html = preg_replace(
                '#<meta\s+charset=["\'][^"\']*["\'][^>]*>#i',
                '$0' . PHP_EOL . '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                $html,
                1
            );
        }

        return $html;
    }
}

/**
 * SPA 경로 기준 page_* 전역 설정
 *
 * @param string|null $path
 */
if (!function_exists('g5b_seo_prepare_spa_context')) {
    function g5b_seo_prepare_spa_context($path = null)
    {
        global $page_title, $page_description, $page_robots, $page_canonical;

        if ($path === null) {
            $path = g5b_seo_request_path();
        }

        // resolve 캐시 초기화용 — 함수 재호출 전 page_* 설정
        $hint = g5b_seo_spa_path_meta($path);
        if (!empty($hint['title']) && empty($page_title)) {
            $page_title = $hint['title'];
        }
        if (!empty($hint['description']) && empty($page_description)) {
            $page_description = $hint['description'];
        }

        if (g5b_seo_should_noindex() && empty($page_robots)) {
            $page_robots = 'noindex,nofollow';
        }

        if (empty($page_canonical)) {
            $base = g5b_seo_site_base();
            $page_canonical = $base . ($path === '/' ? '/' : $path);
        }
    }
}

/**
 * html_process_add_meta 필터
 *
 * @param string $meta
 * @return string
 */
if (!function_exists('g5b_seo_filter_add_meta')) {
    function g5b_seo_filter_add_meta($meta)
    {
        return g5b_seo_build_meta_html(g5b_seo_resolve(true));
    }
}

/**
 * html_process_buffer: <title> 내용을 SEO title로 교체 (1회)
 *
 * @param string $buffer
 * @return string
 */
if (!function_exists('g5b_seo_filter_buffer')) {
    function g5b_seo_filter_buffer($buffer)
    {
        $data = g5b_seo_resolve();
        if ($data['title'] === '') {
            return $buffer;
        }

        $safe_title = g5b_seo_escape($data['title']);
        $replaced = preg_replace('#<title[^>]*>.*?</title>#is', '<title>' . $safe_title . '</title>', $buffer, 1);

        // 데스크톱에도 viewport 보장
        if (is_string($replaced) && stripos($replaced, 'name="viewport"') === false && stripos($replaced, "name='viewport'") === false) {
            $replaced = preg_replace(
                '#<meta\s+charset=["\'][^"\']*["\'][^>]*>#i',
                '$0' . PHP_EOL . '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
                $replaced,
                1
            );
        }

        return is_string($replaced) ? $replaced : $buffer;
    }
}

/**
 * head.php에서 호출 — 훅 등록·$g5['title'] 보조 동기화
 */
if (!function_exists('g5b_seo_init')) {
    function g5b_seo_init()
    {
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;

        global $g5, $page_title;

        if (!empty($page_title) && empty($g5['title'])) {
            $g5['title'] = strip_tags((string) $page_title);
        }

        if (function_exists('add_replace')) {
            add_replace('html_process_add_meta', 'g5b_seo_filter_add_meta', 10, 1);
            add_replace('html_process_buffer', 'g5b_seo_filter_buffer', 10, 1);
        }
    }
}
