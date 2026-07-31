<?php
/**
 * 홍보 가이드 DB 점검 (관리자)
 *
 * 브라우저: /plugin/linkconnect/install/inspect_promo_guide.php?cpCode=CPA-HASUGU
 * 또는 ?cpId=12
 */
require_once dirname(__DIR__) . '/_common.php';

if (!lc_is_super_admin() && php_sapi_name() !== 'cli') {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

lc_campaign_promo_guide_db_ensure_schema();

$cp_code = isset($_REQUEST['cpCode']) ? trim((string) $_REQUEST['cpCode']) : 'CPA-HASUGU';
$cp_id = isset($_REQUEST['cpId']) ? (int) $_REQUEST['cpId'] : 0;

$campaign = null;
if ($cp_id > 0) {
    $campaign = lc_campaign_get_by_id($cp_id);
} elseif ($cp_code !== '') {
    $table = lc_table('campaigns');
    $code_esc = lc_sql_escape($cp_code);
    $campaign = lc_sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '{$code_esc}' LIMIT 1 ");
}

header('Content-Type: application/json; charset=utf-8');

if (!is_array($campaign)) {
    echo json_encode(array('ok' => false, 'error' => '캠페인을 찾을 수 없습니다.', 'cpCode' => $cp_code, 'cpId' => $cp_id), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$cp_id = (int) $campaign['cp_id'];
$mt_id = (int) $campaign['mt_id'];
$guide_table = lc_campaign_promo_guide_table();
$asset_table = lc_campaign_promo_guide_asset_table();

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
            'points' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_promotion_points'] ?? '')),
            'keywords' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_recommended_keywords'] ?? '')),
            'forbidden' => lc_campaign_promo_guide_decode_json_list((string) ($row['cpg_forbidden_words'] ?? '')),
            'updated_at' => (string) ($row['cpg_updated_at'] ?? ''),
            'published_at' => (string) ($row['cpg_published_at'] ?? ''),
            'assets' => $assets,
            'api' => lc_campaign_promo_guide_to_api($row, null, true),
        );
    }
}

$latest = lc_campaign_promo_guide_get_by_cp_id($cp_id);
$summary = function_exists('lc_campaign_promo_guide_summaries_for_cp_ids')
    ? lc_campaign_promo_guide_summaries_for_cp_ids(array($cp_id))
    : array();

echo json_encode(array(
    'ok' => true,
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
        'points' => lc_campaign_promo_guide_decode_json_list((string) ($latest['cpg_promotion_points'] ?? '')),
    ) : null,
    'summary' => isset($summary[$cp_id]) ? $summary[$cp_id] : null,
    'guides' => $guides,
    'hint' => count($guides) === 0
        ? 'DB에 가이드 행이 없습니다. 광고주 화면에서 내용을 입력하고 저장하세요.'
        : ((string) ($latest['cpg_status'] ?? '') !== 'published'
            ? '가이드는 있으나 published가 아닙니다. 관리자 검수에서 파트너 공개가 필요합니다.'
            : 'published 상태입니다. 파트너에게 보여야 합니다.'),
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
