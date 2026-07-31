<?php
/**
 * 홍보 가이드 DB 점검·복구 (관리자)
 *
 * 점검: /plugin/linkconnect/install/inspect_promo_guide.php?cpCode=CPA-HASUGU
 * 복구: ...?cpCode=CPA-HASUGU&action=recover
 * 미리보기: ...?cpCode=CPA-HASUGU&action=preview
 * 광고주 전체: ...?mtCode=ADV-0007&action=scan_merchant
 */
require_once dirname(__DIR__) . '/_common.php';

if (!lc_is_super_admin() && php_sapi_name() !== 'cli') {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

lc_campaign_promo_guide_db_ensure_schema();
if (function_exists('lc_merchant_ad_apply_db_ensure_schema')) {
    lc_merchant_ad_apply_db_ensure_schema();
}

header('Content-Type: application/json; charset=utf-8');

$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : 'inspect';
$cp_code = isset($_REQUEST['cpCode']) ? trim((string) $_REQUEST['cpCode']) : 'CPA-HASUGU';
$cp_id = isset($_REQUEST['cpId']) ? (int) $_REQUEST['cpId'] : 0;
$mt_code = isset($_REQUEST['mtCode']) ? trim((string) $_REQUEST['mtCode']) : '';
$mt_id_req = isset($_REQUEST['mtId']) ? (int) $_REQUEST['mtId'] : 0;

$encode = function ($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
};

if ($action === 'scan_merchant') {
    $merchant = null;
    if ($mt_id_req > 0 && function_exists('lc_get_merchant_by_id')) {
        $merchant = lc_get_merchant_by_id($mt_id_req);
    } elseif ($mt_code !== '') {
        $mt_table = lc_table('merchants');
        $code_esc = lc_sql_escape($mt_code);
        $merchant = lc_sql_fetch(" SELECT * FROM `{$mt_table}` WHERE mt_code = '{$code_esc}' LIMIT 1 ", false);
    } else {
        $mt_code = 'ADV-0007';
        $mt_table = lc_table('merchants');
        $code_esc = lc_sql_escape($mt_code);
        $merchant = lc_sql_fetch(" SELECT * FROM `{$mt_table}` WHERE mt_code = '{$code_esc}' LIMIT 1 ", false);
    }

    if (!is_array($merchant)) {
        $encode(array('ok' => false, 'error' => '광고주를 찾을 수 없습니다.', 'mtCode' => $mt_code));
    }

    $mt_id = (int) $merchant['mt_id'];
    $guide_table = lc_campaign_promo_guide_table();
    $cp_table = lc_table('campaigns');
    $guides = array();
    $result = lc_sql_query(
        " SELECT g.*, c.cp_code, c.cp_name
          FROM `{$guide_table}` g
          LEFT JOIN `{$cp_table}` c ON c.cp_id = g.cpg_cp_id
          WHERE g.cpg_mt_id = '{$mt_id}'
          ORDER BY g.cpg_updated_at DESC, g.cpg_id DESC ",
        false
    );
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $guides[] = array(
                'cpg_id' => (int) $row['cpg_id'],
                'cp_id' => (int) $row['cpg_cp_id'],
                'cp_code' => (string) ($row['cp_code'] ?? ''),
                'cp_name' => (string) ($row['cp_name'] ?? ''),
                'status' => (string) ($row['cpg_status'] ?? ''),
                'score' => lc_campaign_promo_guide_content_score($row),
                'points' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_promotion_points'] ?? '')),
                'keywords' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_recommended_keywords'] ?? '')),
                'forbidden' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_forbidden_words'] ?? '')),
                'precautions' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_precautions'] ?? '')),
                'updated_at' => (string) ($row['cpg_updated_at'] ?? ''),
            );
        }
    }

    $apply = function_exists('lc_merchant_ad_apply_get_latest_for_merchant')
        ? lc_merchant_ad_apply_get_latest_for_merchant($mt_id)
        : null;

    $encode(array(
        'ok' => true,
        'action' => 'scan_merchant',
        'merchant' => array(
            'mt_id' => $mt_id,
            'mt_code' => (string) ($merchant['mt_code'] ?? ''),
            'company' => (string) ($merchant['mt_company'] ?? $merchant['mt_name'] ?? ''),
        ),
        'guideCount' => count($guides),
        'guides' => $guides,
        'adApply' => is_array($apply) ? array(
            'maa_id' => (int) ($apply['maa_id'] ?? 0),
            'status' => (string) ($apply['maa_status'] ?? ''),
            'campaignTitle' => (string) ($apply['maa_campaign_title'] ?? ''),
            'sellingPoints' => (string) ($apply['maa_selling_points'] ?? ''),
            'intro' => (string) ($apply['maa_intro'] ?? ''),
            'recommendedKeywords' => (string) ($apply['maa_recommended_keywords'] ?? ''),
            'forbiddenKeywords' => (string) ($apply['maa_forbidden_keywords'] ?? ''),
            'precautions' => (string) ($apply['maa_precautions'] ?? ''),
            'updated_at' => (string) ($apply['maa_updated_at'] ?? ''),
        ) : null,
    ));
}

$campaign = null;
if ($cp_id > 0) {
    $campaign = lc_campaign_get_by_id($cp_id);
} elseif ($cp_code !== '') {
    $table = lc_table('campaigns');
    $code_esc = lc_sql_escape($cp_code);
    $campaign = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
}

if (!is_array($campaign)) {
    $encode(array('ok' => false, 'error' => '캠페인을 찾을 수 없습니다.', 'cpCode' => $cp_code, 'cpId' => $cp_id));
}

$cp_id = (int) $campaign['cp_id'];
$mt_id = (int) $campaign['mt_id'];
$guide_table = lc_campaign_promo_guide_table();
$asset_table = lc_campaign_promo_guide_asset_table();

if ($action === 'preview' || $action === 'recover') {
    $dry = ($action === 'preview');
    $result = lc_campaign_promo_guide_recover_content($mt_id, $cp_id, $dry);
    $guide = isset($result['guide']) && is_array($result['guide']) ? $result['guide'] : lc_campaign_promo_guide_get_by_cp_id($cp_id);
    $encode(array(
        'ok' => !empty($result['ok']),
        'action' => $action,
        'message' => $result['message'] ?? '',
        'recovered' => !empty($result['recovered']),
        'sources' => $result['sources'] ?? array(),
        'preview' => $result['preview'] ?? null,
        'guide' => is_array($guide) ? array(
            'cpg_id' => (int) $guide['cpg_id'],
            'status' => (string) ($guide['cpg_status'] ?? ''),
            'score' => lc_campaign_promo_guide_content_score($guide),
            'points' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_promotion_points'] ?? '')),
            'keywords' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_recommended_keywords'] ?? '')),
            'forbidden' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_forbidden_words'] ?? '')),
            'precautions' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_precautions'] ?? '')),
            'validDbRules' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_valid_db_rules'] ?? '')),
            'invalidDbRules' => lc_campaign_promo_guide_decode_json_list((string) ($guide['cpg_invalid_db_rules'] ?? '')),
        ) : null,
        'campaign' => array(
            'cp_id' => $cp_id,
            'cp_code' => (string) ($campaign['cp_code'] ?? ''),
            'cp_name' => (string) ($campaign['cp_name'] ?? ''),
            'mt_id' => $mt_id,
        ),
    ));
}

$guides = array();
$result = lc_sql_query(" SELECT * FROM `{$guide_table}` WHERE cpg_cp_id = '{$cp_id}' ORDER BY cpg_id DESC ", false);
if ($result) {
    while ($row = sql_fetch_array($result)) {
        $cpg_id = (int) $row['cpg_id'];
        $assets = array();
        $ar = lc_sql_query(" SELECT cpga_id, cpga_mt_id, cpga_image_title, cpga_is_active, cpga_file_path, cpga_stored_filename FROM `{$asset_table}` WHERE cpga_cpg_id = '{$cpg_id}' ORDER BY cpga_sort_order ASC, cpga_id ASC ", false);
        if ($ar) {
            while ($a = sql_fetch_array($ar)) {
                $assets[] = $a;
            }
        }
        $guides[] = array(
            'cpg_id' => $cpg_id,
            'cpg_mt_id' => (int) ($row['cpg_mt_id'] ?? 0),
            'cpg_status' => (string) ($row['cpg_status'] ?? ''),
            'mt_match' => ((int) ($row['cpg_mt_id'] ?? 0) === $mt_id),
            'score' => lc_campaign_promo_guide_content_score($row),
            'points' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_promotion_points'] ?? '')),
            'keywords' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_recommended_keywords'] ?? '')),
            'forbidden' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_forbidden_words'] ?? '')),
            'precautions' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_precautions'] ?? '')),
            'updated_at' => (string) ($row['cpg_updated_at'] ?? ''),
            'published_at' => (string) ($row['cpg_published_at'] ?? ''),
            'assets' => $assets,
            'api' => lc_campaign_promo_guide_to_api($row, null, true),
        );
    }
}

$latest = lc_campaign_promo_guide_get_by_cp_id($cp_id);
$apply = function_exists('lc_merchant_ad_apply_get_latest_for_merchant')
    ? lc_merchant_ad_apply_get_latest_for_merchant($mt_id)
    : null;

$encode(array(
    'ok' => true,
    'action' => 'inspect',
    'campaign' => array(
        'cp_id' => $cp_id,
        'cp_code' => (string) ($campaign['cp_code'] ?? ''),
        'cp_name' => (string) ($campaign['cp_name'] ?? ''),
        'mt_id' => $mt_id,
        'cp_status' => (string) ($campaign['cp_status'] ?? ''),
    ),
    'guideCount' => count($guides),
    'latestByGet' => is_array($latest) ? array(
        'cpg_id' => (int) $latest['cpg_id'],
        'cpg_mt_id' => (int) ($latest['cpg_mt_id'] ?? 0),
        'cpg_status' => (string) ($latest['cpg_status'] ?? ''),
        'score' => lc_campaign_promo_guide_content_score($latest),
        'points' => lc_campaign_promo_guide_decode_json_list((string) ($latest['cpg_promotion_points'] ?? '')),
    ) : null,
    'guides' => $guides,
    'adApply' => is_array($apply) ? array(
        'maa_id' => (int) ($apply['maa_id'] ?? 0),
        'sellingPoints' => (string) ($apply['maa_selling_points'] ?? ''),
        'recommendedKeywords' => (string) ($apply['maa_recommended_keywords'] ?? ''),
        'forbiddenKeywords' => (string) ($apply['maa_forbidden_keywords'] ?? ''),
        'precautions' => (string) ($apply['maa_precautions'] ?? ''),
    ) : null,
    'recoverUrl' => LC_PLUGIN_URL . '/install/inspect_promo_guide.php?cpCode=' . rawurlencode((string) ($campaign['cp_code'] ?? $cp_code)) . '&action=recover',
    'hint' => count($guides) === 0
        ? 'DB에 가이드 행이 없습니다. action=recover 로 광고신청서 복구를 시도하거나 광고주 화면에서 다시 저장하세요.'
        : (lc_campaign_promo_guide_content_score($latest ?: array()) === 0
            ? '가이드 행은 있으나 내용이 비어 있습니다. action=recover 로 복구를 시도하세요.'
            : (((string) ($latest['cpg_status'] ?? '') !== 'published')
                ? '내용은 있습니다. 파트너에게 보이려면 관리자에서 파트너 공개가 필요합니다.'
                : 'published 상태입니다. 파트너에게 보여야 합니다.')),
));
