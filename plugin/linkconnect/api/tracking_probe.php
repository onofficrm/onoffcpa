<?php
/**
 * 독립 도메인 /r/ 추적 점검 (운영 서버에서 DB·HTTP 확인)
 *
 * GET /plugin/linkconnect/api/tracking_probe.php?cpId=11
 * GET /plugin/linkconnect/api/tracking_probe.php?code=CPA-00011
 */
require_once dirname(__DIR__) . '/_common.php';

// 공개 남용 방지용 단순 키 (점검 후 제거·교체 가능)
$probe_key = isset($_GET['key']) ? (string) $_GET['key'] : '';
if ($probe_key !== 'lc-air911-probe-2026') {
    lc_api_error('권한이 없습니다.', 'FORBIDDEN', 403);
}

if (!function_exists('lc_api_json_response')) {
    header('Content-Type: application/json; charset=utf-8');
}

$cp_id = isset($_GET['cpId']) ? (int) $_GET['cpId'] : 0;
$cp_code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';

if (!lc_db_installed()) {
    lc_api_error('DB 없음', 'NO_DB', 500);
}

$cp_table = lc_table('campaigns');
$campaign = null;
if ($cp_id > 0) {
    $campaign = lc_sql_fetch(" SELECT * FROM `{$cp_table}` WHERE cp_id = '{$cp_id}' LIMIT 1 ");
} elseif ($cp_code !== '') {
    $campaign = lc_sql_fetch(
        " SELECT * FROM `{$cp_table}` WHERE cp_code = '" . lc_sql_escape($cp_code) . "' LIMIT 1 "
    );
} else {
    $campaign = lc_sql_fetch(
        " SELECT * FROM `{$cp_table}`
          WHERE cp_tracking_base_url <> '' AND cp_status = '" . lc_sql_escape(LC_STATUS_ACTIVE) . "'
          ORDER BY cp_id DESC LIMIT 1 "
    );
}

if (!is_array($campaign)) {
    lc_api_error('캠페인을 찾을 수 없습니다.', 'NOT_FOUND', 404);
}

$cp_id = (int) $campaign['cp_id'];
$tracking_base = trim((string) ($campaign['cp_tracking_base_url'] ?? ''));
$landing_stored = trim((string) ($campaign['cp_landing_url'] ?? ''));
$landing_public = function_exists('lc_link_apply_tracking_host')
    ? lc_link_apply_tracking_host($landing_stored, $tracking_base)
    : $landing_stored;

$lk_table = lc_table('links');
$link = lc_sql_fetch(
    " SELECT * FROM `{$lk_table}`
      WHERE cp_id = '{$cp_id}' AND lk_status = 'active'
      ORDER BY lk_id DESC LIMIT 1 "
);

$sample = null;
$http = null;

if (is_array($link) && !empty($link['lk_code'])) {
    $lk_code = (string) $link['lk_code'];
    $promo_tracking = lc_link_public_url($lk_code, $tracking_base);
    $promo_main = (defined('G5_URL') ? rtrim((string) G5_URL, '/') : '') . '/r/' . rawurlencode($lk_code);
    $redirect_target = lc_link_resolve_redirect_url(array_merge($link, array(
        'cp_landing_url'       => $landing_stored,
        'cp_tracking_base_url' => $tracking_base,
        'lk_code'              => $lk_code,
    )));

    $sample = array(
        'lkCode'           => $lk_code,
        'promoUrlTracking' => $promo_tracking,
        'promoUrlMain'     => $promo_main,
        'usesIndependent'  => ($tracking_base !== '' && strpos($promo_tracking, parse_url($tracking_base, PHP_URL_HOST) ?: '___') !== false),
        'redirectTarget'   => $redirect_target,
    );

    // air911 /r/{code} 실제 HTTP 점검 (리다이렉트 따라가지 않음)
    $probe_url = $promo_tracking;
    if ($probe_url !== '' && function_exists('curl_init')) {
        $ch = curl_init($probe_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'LinkConnect-TrackingProbe/1.0',
        ));
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $location = '';
        if (is_string($raw) && preg_match('/^Location:\s*(.+)$/im', $raw, $m)) {
            $location = trim($m[1]);
        }

        $http = array(
            'requestUrl'   => $probe_url,
            'curlOk'       => $errno === 0,
            'curlError'    => $err,
            'status'       => $status,
            'location'     => $location,
            'redirectOk'   => ($status >= 300 && $status < 400 && $location !== ''),
            'locationHost' => $location !== '' ? (string) parse_url($location, PHP_URL_HOST) : '',
        );
    }
}

lc_api_success(array(
    'campaign' => array(
        'id'               => $cp_id,
        'code'             => (string) $campaign['cp_code'],
        'name'             => (string) $campaign['cp_name'],
        'status'           => (string) $campaign['cp_status'],
        'trackingBaseUrl'  => $tracking_base,
        'landingUrlStored' => $landing_stored,
        'landingUrlPublic' => $landing_public,
        'hasTrackingDomain'=> $tracking_base !== '',
    ),
    'sampleLink' => $sample,
    'httpProbe'  => $http,
    'verdict'    => array(
        'trackingDomainConfigured' => $tracking_base !== '',
        'partnerLinkWouldUseDomain'=> is_array($sample) ? !empty($sample['usesIndependent']) : false,
        'air911ClickReachable'     => is_array($http) ? (!empty($http['redirectOk']) || (int) ($http['status'] ?? 0) === 200) : false,
    ),
));
