<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

/* ── 버전·경로 ── */
define('LC_VERSION', '0.2.0');
define('LC_PLUGIN_DIR', 'linkconnect');
define('LC_PLUGIN_PATH', G5_PLUGIN_PATH . '/' . LC_PLUGIN_DIR);
define('LC_PLUGIN_URL', G5_PLUGIN_URL . '/' . LC_PLUGIN_DIR);
define('LC_LAYOUT_PATH', LC_PLUGIN_PATH . '/layout');
define('LC_ASSETS_PATH', LC_PLUGIN_PATH . '/assets');
define('LC_ASSETS_URL', LC_PLUGIN_URL . '/assets');
define('LC_INC_PATH', LC_PLUGIN_PATH . '/inc');
define('LC_VIEWS_PATH', LC_INC_PATH . '/views');

/* ── 센터 구분 ── */
define('LC_CENTER_PUBLIC', 'public');
define('LC_CENTER_PARTNER', 'partner');
define('LC_CENTER_MERCHANT', 'merchant');
define('LC_CENTER_ADMIN', 'admin');

/* ── 디자인·브랜드 토큰 (온오프빌더 Tailwind 매핑) ── */
define('LC_COLOR_SLATE_950', '#020617');
define('LC_COLOR_CYAN', '#06b6d4');
define('LC_COLOR_EMERALD', '#10b981');
define('LC_COLOR_PARTNER', '#10b981');
define('LC_COLOR_MERCHANT', '#06b6d4');
define('LC_COLOR_ADMIN', '#6366f1');

/* ── 권한 (관리자센터) ── */
define('LC_ADMIN_MIN_LEVEL', 10);
/** 링크커넥트 전담 관리자 최소 레벨 (추후 mb_ 필드·역할 테이블로 대체 가능) */
define('LC_LINKCONNECT_ADMIN_MIN_LEVEL', 9);
/**
 * true 전환 시 lc_require_admin_access()가 비관리자 URL 직접 접근을 차단합니다.
 * 디자인·샘플 UI 단계에서는 false 유지.
 */
define('LC_ADMIN_GUARD_ENABLED', true);
define('LC_URL_ADMIN_DASHBOARD', LC_PLUGIN_URL . '/admin/dashboard.php');

/* ── 권한 (파트너센터) ── */
define('LC_PARTNER_STATUS_PENDING', 'pending');
define('LC_PARTNER_STATUS_ACTIVE', 'active');
define('LC_PARTNER_STATUS_SUSPENDED', 'suspended');
/**
 * true 전환 시 lc_require_partner_access()가 비파트너·미승인 접근을 차단합니다.
 * lc_db_installed() 가 true 일 때만 실제 동작합니다.
 */
define('LC_PARTNER_GUARD_ENABLED', true);

/* ── 권한 (광고주센터) ── */
define('LC_MERCHANT_STATUS_PENDING', 'pending');
define('LC_MERCHANT_STATUS_ACTIVE', 'active');
define('LC_MERCHANT_STATUS_SUSPENDED', 'suspended');
/**
 * true 전환 시 lc_require_merchant_access()가 비광고주·미승인 접근을 차단합니다.
 * lc_db_installed() 가 true 일 때만 실제 동작합니다.
 */
define('LC_MERCHANT_GUARD_ENABLED', true);

/**
 * CPA 광고주 계약 미체결 시 광고주센터 접근 제한
 */
if (!defined('LC_MERCHANT_CONTRACT_GUARD_ENABLED')) {
    define('LC_MERCHANT_CONTRACT_GUARD_ENABLED', true);
}
/**
 * 기존 광고주 유예 종료일 (Y-m-d). 비우면 즉시 제한. lc_settings advertiserContractGraceUntil 우선.
 */
if (!defined('ADVERTISER_CONTRACT_GRACE_UNTIL')) {
    define('ADVERTISER_CONTRACT_GRACE_UNTIL', '');
}

/* ── LinkConnect 전용 DB (그누보드 기본 DB와 분리) ── */
if (!defined('LC_MYSQL_DB')) {
    if (function_exists('g5site_cfg')) {
        $lc_db_name = g5site_cfg('linkconnect_db_name', 'linkconnect');
    } else {
        $lc_db_name = 'linkconnect';
    }
    define('LC_MYSQL_DB', $lc_db_name);
}
/** 비우면 G5_MYSQL_HOST / USER / PASSWORD 사용 */
if (!defined('LC_MYSQL_HOST')) {
    define('LC_MYSQL_HOST', '');
}
if (!defined('LC_MYSQL_USER')) {
    define('LC_MYSQL_USER', '');
}
if (!defined('LC_MYSQL_PASSWORD')) {
    define('LC_MYSQL_PASSWORD', '');
}

/* ── 콜디비 ── */
/** 전환 유입 유형 */
define('LC_SOURCE_FORM', 'form');
define('LC_SOURCE_CALL', 'call');
/** 가상번호 상태 */
define('LC_CALL_NUMBER_AVAILABLE', 'available');
define('LC_CALL_NUMBER_ASSIGNED', 'assigned');
define('LC_CALL_NUMBER_PAUSED', 'paused');
define('LC_CALL_NUMBER_RELEASED', 'released');
/** 번호 신청 상태 */
define('LC_CALL_REQ_PENDING', 'pending');
define('LC_CALL_REQ_ASSIGNED', 'assigned');
define('LC_CALL_REQ_REJECTED', 'rejected');
define('LC_CALL_REQ_REVOKED', 'revoked');
/** 통화 결과 */
define('LC_CALL_RESULT_SUCCESS', 'success');
define('LC_CALL_RESULT_MISSED', 'missed');
define('LC_CALL_RESULT_BUSY', 'busy');
define('LC_CALL_RESULT_FAIL', 'fail');

define('LC_CALL_REC_REQ_PENDING', 'pending');
define('LC_CALL_REC_REQ_FULFILLED', 'fulfilled');
define('LC_CALL_REC_REQ_REJECTED', 'rejected');
/** 관리자 최종확정 상태 */
define('LC_FINAL_APPROVED', 'approved');
define('LC_FINAL_REJECTED', 'rejected');

/* ── 링크프라이스 CPS (외부 네트워크, CPA와 분리) ── */
define('LC_LP_NETWORK_CODE', 'LINKPRICE');
/** 내부 실적 상태 (공식 status와 별도 매핑) */
define('LC_LP_ORDER_EXPECTED', 'expected');       // 하위호환 (=pending)
define('LC_LP_ORDER_PENDING', 'pending');         // 100 일반
define('LC_LP_ORDER_REVIEW', 'review');           // 200 정산대기
define('LC_LP_ORDER_CONFIRMED', 'confirmed');     // 하위호환 (=approved)
define('LC_LP_ORDER_APPROVED', 'approved');       // 210 정산완료
define('LC_LP_ORDER_CANCEL_PENDING', 'cancel_pending'); // 300 취소신청
define('LC_LP_ORDER_CANCELLED', 'cancelled');     // 하위호환 (=canceled)
define('LC_LP_ORDER_CANCELED', 'canceled');       // 310 취소완료
define('LC_LP_ORDER_HOLD', 'hold');               // 하위호환 (review/cancel_pending)
define('LC_LP_ORDER_UNMATCHED', 'unmatched');
define('LC_LP_ORDER_ERROR', 'error');
/** 링크프라이스 원본 status */
define('LC_LP_RAW_NORMAL', '100');
define('LC_LP_RAW_SETTLE_WAIT', '200');
define('LC_LP_RAW_SETTLED', '210');
define('LC_LP_RAW_CANCEL_WAIT', '300');
define('LC_LP_RAW_CANCELLED', '310');
/** 원장 전표 유형 */
define('LC_LP_LEDGER_CREDIT', 'CREDIT');
define('LC_LP_LEDGER_DEBIT', 'DEBIT');
define('LC_LP_LEDGER_REVERSAL', 'REVERSAL');
/** POSTBACK 처리 상태 */
define('LC_LP_PB_RECEIVED', 'received');
define('LC_LP_PB_PROCESSED', 'processed');
define('LC_LP_PB_DUPLICATE', 'duplicate');
define('LC_LP_PB_UNMATCHED', 'unmatched');
define('LC_LP_PB_ERROR', 'error');
/** 동기화 종류 */
define('LC_LP_SYNC_MERCHANTS', 'merchants');
define('LC_LP_SYNC_ORDERS', 'orders');
define('LC_LP_SYNC_POSTBACK', 'postback');
define('LC_LP_SYNC_REDIRECT_FAIL', 'redirect_fail');

/* ── 샘플 UI 상태값 (실제 DB 연동 전) ── */
define('LC_STATUS_DRAFT', 'draft');
define('LC_STATUS_ACTIVE', 'active');
define('LC_STATUS_PENDING', 'pending');
define('LC_STATUS_APPROVED', 'approved');
define('LC_STATUS_REJECTED', 'rejected');
define('LC_STATUS_SETTLED', 'settled');

define('LC_DB_STATUS_LABELS', serialize(array(
    LC_STATUS_PENDING   => '검수중',
    LC_STATUS_APPROVED  => '승인완료',
    LC_STATUS_REJECTED  => '취소/무효',
    LC_STATUS_SETTLED   => '정산완료',
)));

/* ── 빌더 dist 에셋 (홈 히어로 등) ── */
define('LC_BUILDER_IMPORT_ID', 'linkconnect');
define('LC_BUILDER_ASSETS_URL', G5_PLUGIN_URL . '/onoff-builder-bridge/imports/' . LC_BUILDER_IMPORT_ID . '/assets');

if (!function_exists('lc_h')) {
    function lc_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lc_url')) {
    function lc_url($path = '')
    {
        $path = ltrim((string) $path, '/');

        return $path === '' ? LC_PLUGIN_URL : LC_PLUGIN_URL . '/' . $path;
    }
}

if (!function_exists('lc_asset_url')) {
    function lc_asset_url($path)
    {
        return LC_ASSETS_URL . '/' . ltrim((string) $path, '/');
    }
}

if (!function_exists('lc_builder_asset_url')) {
    function lc_builder_asset_url($filename)
    {
        return LC_BUILDER_ASSETS_URL . '/' . ltrim((string) $filename, '/');
    }
}

if (!function_exists('lc_site_name')) {
    function lc_site_name()
    {
        if (function_exists('g5site_cfg')) {
            $name = g5site_cfg('site_name', 'LinkConnect');
            if ($name !== '') {
                return $name;
            }
        }

        return 'LinkConnect';
    }
}

if (!function_exists('lc_site_desc')) {
    function lc_site_desc()
    {
        if (function_exists('g5site_cfg')) {
            return g5site_cfg('site_desc', 'CPA/CPS 제휴마케팅 플랫폼');
        }

        return 'CPA/CPS 제휴마케팅 플랫폼';
    }
}

if (!function_exists('lc_contact_email')) {
    function lc_contact_email()
    {
        if (function_exists('lc_settings_get')) {
            $email = trim((string) lc_settings_get('supportEmail', ''));
            if ($email !== '') {
                return $email;
            }
        }

        if (function_exists('g5site_cfg')) {
            $email = g5site_cfg('email', '');
            if ($email !== '') {
                return $email;
            }
        }

        return 'support2580_@linkconnect.co.kr';
    }
}

if (!function_exists('lc_contact_phone')) {
    function lc_contact_phone()
    {
        $placeholder = '010-0000-0000';
        $fallback = '070-8098-6824';

        if (function_exists('lc_settings_get')) {
            $phone = trim((string) lc_settings_get('supportPhone', ''));
            if ($phone !== '' && $phone !== $placeholder) {
                return $phone;
            }
        }

        if (function_exists('g5site_cfg')) {
            $phone = trim((string) g5site_cfg('phone', ''));
            if ($phone !== '' && $phone !== $placeholder) {
                return $phone;
            }
        }

        return $fallback;
    }
}

if (!function_exists('lc_status_label')) {
    function lc_status_label($status)
    {
        $map = unserialize(LC_DB_STATUS_LABELS);
        if (!is_array($map)) {
            return (string) $status;
        }

        return isset($map[$status]) ? $map[$status] : (string) $status;
    }
}

if (!function_exists('lc_favicon_url')) {
    function lc_favicon_url()
    {
        if (function_exists('lc_builder_spa_enabled') && lc_builder_spa_enabled()) {
            $base = defined('G5_URL') ? rtrim(G5_URL, '/') : '';

            return $base . '/plugin/onoff-builder-bridge/imports/linkconnect/favicon.png';
        }

        return lc_asset_url('img/favicon.png');
    }
}

if (!function_exists('lc_enqueue_assets')) {
    function lc_enqueue_assets()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $ver = defined('G5_CSS_VER') ? G5_CSS_VER : LC_VERSION;
        $favicon = lc_favicon_url();
        echo '<link rel="icon" type="image/png" href="' . lc_h($favicon) . '">' . PHP_EOL;
        echo '<link rel="apple-touch-icon" href="' . lc_h($favicon) . '">' . PHP_EOL;
        echo '<link rel="stylesheet" href="' . lc_h(lc_asset_url('css/linkconnect.css?ver=' . $ver)) . '">' . PHP_EOL;
        echo '<script src="' . lc_h(lc_asset_url('js/linkconnect.js?ver=' . $ver)) . '" defer></script>' . PHP_EOL;
    }
}

if (!function_exists('lc_public_home_url')) {
    /**
     * 공개 메인 홈 canonical URL (빌더 브릿지 연결 시 사이트 루트 /)
     */
    function lc_public_home_url()
    {
        if (function_exists('g5site_cfg')) {
            $bridge_id = trim((string) g5site_cfg('home_builder_bridge_id', ''));
            if ($bridge_id !== '') {
                return defined('G5_URL') ? rtrim(G5_URL, '/') . '/' : '/';
            }
        }

        return lc_url('pages/home.php');
    }
}

if (!function_exists('lc_builder_spa_enabled')) {
    function lc_builder_spa_enabled()
    {
        return function_exists('onoff_builder_home_enabled') && onoff_builder_home_enabled();
    }
}

if (!function_exists('lc_spa_url')) {
    /**
     * React SPA 경로 (BrowserRouter, # 없음)
     */
    function lc_spa_url($path = '/')
    {
        $base = defined('G5_URL') ? rtrim(G5_URL, '/') : '';
        $path = '/' . ltrim((string) $path, '/');

        return $base . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('lc_nav_company_items')) {
    /** 회사소개 드롭다운 하위 (React publicNav.companySubItems 와 동일) */
    function lc_nav_company_items()
    {
        if (lc_builder_spa_enabled()) {
            return array(
                array('id' => 'home', 'label' => '회사소개', 'url' => lc_spa_url('/about'), 'tone' => ''),
                array('id' => 'affiliate', 'label' => '제휴마케팅이란?', 'url' => lc_spa_url('/affiliate'), 'tone' => ''),
                array('id' => 'notice', 'label' => '공지사항', 'url' => lc_spa_url('/notice'), 'tone' => ''),
            );
        }

        return array(
            array('id' => 'home', 'label' => '회사소개', 'url' => lc_url('pages/home.php'), 'tone' => ''),
            array('id' => 'notice', 'label' => '공지사항', 'url' => lc_spa_url('/notice'), 'tone' => ''),
        );
    }
}

if (!function_exists('lc_nav_items_public')) {
    function lc_nav_items_public()
    {
        if (lc_builder_spa_enabled()) {
            return array(
                array('id' => 'cpa', 'label' => 'CPA', 'url' => lc_spa_url('/cpa-list'), 'tone' => ''),
                array('id' => 'cps', 'label' => 'CPS', 'url' => lc_spa_url('/cps'), 'tone' => ''),
                array('id' => 'events', 'label' => '이벤트/프로모션', 'url' => lc_spa_url('/events'), 'tone' => ''),
                array('id' => 'partner', 'label' => '파트너센터', 'url' => lc_spa_url('/partner'), 'tone' => 'partner'),
                array('id' => 'merchant', 'label' => '광고주센터', 'url' => lc_spa_url('/advertiser'), 'tone' => 'merchant'),
            );
        }

        return array(
            array('id' => 'cpa', 'label' => 'CPA', 'url' => lc_url('pages/cpa.php'), 'tone' => ''),
            array('id' => 'cps', 'label' => 'CPS', 'url' => lc_url('pages/cps.php'), 'tone' => ''),
            array('id' => 'events', 'label' => '이벤트/프로모션', 'url' => lc_url('pages/events.php'), 'tone' => ''),
            array('id' => 'partner', 'label' => '파트너센터', 'url' => lc_url('partner/dashboard.php'), 'tone' => 'partner'),
            array('id' => 'merchant', 'label' => '광고주센터', 'url' => lc_url('merchant/dashboard.php'), 'tone' => 'merchant'),
        );
    }
}

if (!function_exists('lc_sidebar_items')) {
    function lc_sidebar_items($center)
    {
        $map = array(
            LC_CENTER_PARTNER => array(
                array('id' => 'dashboard', 'label' => '대시보드', 'url' => lc_url('partner/dashboard.php'), 'icon' => '◆'),
                array('id' => 'campaigns', 'label' => '광고상품 찾기', 'url' => lc_url('partner/campaigns.php'), 'icon' => '◇'),
                array('id' => 'links', 'label' => '내 홍보 링크', 'url' => lc_url('partner/links.php'), 'icon' => '🔗'),
                array('id' => 'traffic', 'label' => '유입 분석', 'url' => lc_url('partner/traffic.php'), 'icon' => '📊'),
                array('id' => 'conversions', 'label' => '디비 현황', 'url' => lc_url('partner/conversions.php'), 'icon' => '◎'),
                array('id' => 'canceled', 'label' => '취소/무효 디비', 'url' => lc_url('partner/canceled.php'), 'icon' => '✕'),
                array('id' => 'earnings', 'label' => '수익 리포트', 'url' => lc_url('partner/earnings.php'), 'icon' => '₩'),
                array('id' => 'settlements', 'label' => '정산 신청', 'url' => lc_url('partner/settlements.php'), 'icon' => '💳'),
                array('id' => 'inquiry', 'label' => '문의하기', 'url' => lc_url('partner/inquiry.php'), 'icon' => '✉'),
            ),
            LC_CENTER_MERCHANT => array(
                array('id' => 'dashboard', 'label' => '대시보드', 'url' => lc_url('merchant/dashboard.php'), 'icon' => '◆'),
                array('id' => 'campaigns', 'label' => '내 광고상품', 'url' => lc_url('merchant/campaigns.php'), 'icon' => '◇'),
                array('id' => 'conversions', 'label' => '디비 확인', 'url' => lc_url('merchant/conversions.php'), 'icon' => '◎', 'badge' => '9'),
                array('id' => 'wallet', 'label' => '광고비 충전/내역', 'url' => lc_url('merchant/wallet.php'), 'icon' => '💰'),
                array('id' => 'reports', 'label' => '성과 리포트', 'url' => lc_url('merchant/reports.php'), 'icon' => '📈'),
                array('id' => 'inquiry', 'label' => '문의하기', 'url' => lc_url('merchant/inquiry.php'), 'icon' => '✉'),
            ),
            LC_CENTER_ADMIN => array(
                array('id' => 'dashboard', 'label' => '통합 대시보드', 'url' => lc_url('admin/dashboard.php'), 'icon' => '◆'),
                array('id' => 'partners', 'label' => '파트너 관리', 'url' => lc_url('admin/partners.php'), 'icon' => '👥'),
                array('id' => 'merchants', 'label' => '광고주 관리', 'url' => lc_url('admin/merchants.php'), 'icon' => '🏢'),
                array('id' => 'campaigns', 'label' => '광고상품 관리', 'url' => lc_url('admin/campaigns.php'), 'icon' => '◇'),
                array('id' => 'conversions', 'label' => '전체 디비 관리', 'url' => lc_url('admin/conversions.php'), 'icon' => '◎'),
                array('id' => 'cancel_review', 'label' => '취소/무효 검수', 'url' => lc_url('admin/cancel_review.php'), 'icon' => '⚑', 'badge' => '18'),
                array('id' => 'wallet', 'label' => '광고비 관리', 'url' => lc_url('admin/wallet.php'), 'icon' => '💰'),
                array('id' => 'settlements', 'label' => '정산 관리', 'url' => lc_url('admin/settlements.php'), 'icon' => '💳', 'badge' => '7'),
                array('id' => 'api', 'label' => 'API 관리', 'url' => lc_url('admin/api.php'), 'icon' => '{ }', 'badge' => '2'),
                array('id' => 'events', 'label' => '이벤트/프로모션 관리', 'url' => lc_url('admin/events.php'), 'icon' => '🎁'),
                array('id' => 'inquiries', 'label' => '문의 관리', 'url' => lc_url('admin/inquiries.php'), 'icon' => '✉'),
                array('id' => 'settings', 'label' => '환경설정', 'url' => lc_url('admin/settings.php'), 'icon' => '⚙'),
            ),
        );

        return isset($map[$center]) ? $map[$center] : array();
    }
}

if (!function_exists('lc_center_label')) {
    function lc_center_label($center)
    {
        $labels = array(
            LC_CENTER_PARTNER  => '파트너센터',
            LC_CENTER_MERCHANT => '광고주센터',
            LC_CENTER_ADMIN    => '관리자센터',
        );

        return isset($labels[$center]) ? $labels[$center] : '';
    }
}

if (!function_exists('lc_is_logged_in')) {
    function lc_is_logged_in()
    {
        global $is_member;

        return !empty($is_member);
    }
}

if (!function_exists('lc_is_super_admin')) {
    /**
     * 최고관리자 여부 — 그누보드 is_admin(super) 또는 mb_level >= 10
     */
    function lc_is_super_admin()
    {
        global $is_admin, $member;

        if (isset($is_admin) && $is_admin === 'super') {
            return true;
        }

        if (lc_is_logged_in() && isset($member['mb_level']) && (int) $member['mb_level'] >= LC_ADMIN_MIN_LEVEL) {
            return true;
        }

        return false;
    }
}

if (!function_exists('lc_is_linkconnect_admin')) {
    /**
     * 링크커넥트 전담 관리자 (최고관리자 제외)
     * TODO: mb_1 등 커스텀 필드 또는 lc_admin_roles 테이블 연동
     */
    function lc_is_linkconnect_admin()
    {
        global $member;

        if (lc_is_super_admin()) {
            return false;
        }

        if (!lc_is_logged_in() || !isset($member['mb_level'])) {
            return false;
        }

        $level = (int) $member['mb_level'];

        return $level >= LC_LINKCONNECT_ADMIN_MIN_LEVEL && $level < LC_ADMIN_MIN_LEVEL;
    }
}

if (!function_exists('lc_can_access_admin')) {
    /** 최고관리자 또는 링크커넥트 관리자 */
    function lc_can_access_admin()
    {
        return lc_is_super_admin() || lc_is_linkconnect_admin();
    }
}

if (!function_exists('lc_require_admin_access')) {
    /**
     * 관리자센터 URL 직접 접근 차단
     * LC_ADMIN_GUARD_ENABLED 가 false 이면 디자인 단계용으로 통과
     */
    function lc_require_admin_access()
    {
        if (!LC_ADMIN_GUARD_ENABLED) {
            return;
        }

        if (lc_can_access_admin()) {
            return;
        }

        global $lc_page_title, $lc_body_class;
        $lc_page_title = '접근 권한 없음';
        $lc_body_class = 'lc-app lc-app--public';

        include LC_LAYOUT_PATH . '/header.php';
        echo '<main class="lc-main"><div class="lc-panel lc-admin-denied">';
        echo '<h1 class="lc-admin-denied__title">접근 권한이 없습니다</h1>';
        echo '<p class="lc-admin-denied__desc">관리자센터는 <strong>최고관리자</strong> 또는 <strong>링크커넥트 관리자</strong>만 이용할 수 있습니다.</p>';
        echo '<p class="lc-muted">일반 사용자의 URL 직접 접근은 차단됩니다. (LC_ADMIN_GUARD_ENABLED)</p>';
        echo '<a class="lc-btn lc-btn--primary" href="' . lc_h(lc_public_home_url()) . '">홈으로</a>';
        echo '</div></main>';
        include LC_LAYOUT_PATH . '/footer.php';
        exit;
    }
}

if (!function_exists('lc_login_url')) {
    function lc_login_url($return_url = '')
    {
        if ($return_url === '') {
            if (function_exists('lc_builder_spa_enabled') && lc_builder_spa_enabled()) {
                $return_url = function_exists('lc_spa_url') ? lc_spa_url('/') : lc_public_home_url();
            } elseif (defined('G5_URL') && isset($_SERVER['REQUEST_URI'])) {
                $return_url = G5_URL . $_SERVER['REQUEST_URI'];
            } else {
                $return_url = lc_public_home_url();
            }
        }
        if (defined('G5_BBS_URL')) {
            return G5_BBS_URL . '/login.php?url=' . urlencode($return_url);
        }

        return '#';
    }
}

if (!function_exists('lc_register_url')) {
    function lc_register_url()
    {
        if (defined('G5_BBS_URL')) {
            return G5_BBS_URL . '/register.php';
        }

        return '#';
    }
}

if (!function_exists('lc_member_edit_url')) {
    function lc_member_edit_url()
    {
        if (!defined('G5_BBS_URL')) {
            return '#';
        }

        return G5_BBS_URL . '/member_confirm.php?url=' . urlencode('register_form.php');
    }
}

if (!function_exists('lc_logout_url')) {
    function lc_logout_url($return_path = '')
    {
        if ($return_path === '') {
            $return_path = '/';
            if (defined('G5_URL')) {
                $base = parse_url(G5_URL, PHP_URL_PATH);
                if (is_string($base) && $base !== '' && $base !== '/') {
                    $return_path = rtrim($base, '/') . '/';
                }
            }
        }
        if (defined('G5_BBS_URL')) {
            return G5_BBS_URL . '/logout.php?url=' . urlencode($return_path);
        }

        return '#';
    }
}

if (!function_exists('lc_inquiry_url')) {
    function lc_inquiry_url()
    {
        return (defined('G5_URL') ? rtrim((string) G5_URL, '/') : '') . '/advertiser-apply';
    }
}

if (!function_exists('lc_render_header_actions')) {
    /**
     * @param bool $mobile 모바일 드로어용 세로 배치
     */
    function lc_render_header_actions($mobile = false)
    {
        $wrap_class = $mobile ? 'lc-header__actions lc-header__actions--mobile' : 'lc-header__actions lc-header__actions--desktop';

        echo '<div class="' . lc_h($wrap_class) . '">';

        if (lc_is_logged_in()) {
            global $member;
            $nick = is_array($member) && isset($member['mb_nick']) ? (string) $member['mb_nick'] : '';
            if ($nick !== '') {
                echo '<span class="lc-header__member-name">' . lc_h($nick) . '</span>';
            }
            echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_member_edit_url()) . '">정보수정</a>';
            echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_logout_url()) . '">로그아웃</a>';
        } else {
            echo '<a class="lc-btn lc-btn--ghost lc-btn--sm" href="' . lc_h(lc_login_url()) . '">로그인</a>';
            echo '<a class="lc-btn lc-btn--outline lc-btn--sm" href="' . lc_h(lc_register_url()) . '">회원가입</a>';
        }

        echo '</div>';
    }
}
