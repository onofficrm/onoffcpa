<?php
/**
 * LinkConnect 플러그인 — 그누보드 안전 부트스트랩
 *
 * - bbs/_common.php 경유 금지 (서브폴더 CWD 깨짐 방지)
 * - 그누보드 루트 common.php 만 직접 로드
 */
if (defined('LC_COMMON_LOADED')) {
    return;
}

define('LC_COMMON_LOADED', true);

$lc_g5_root = realpath(dirname(__FILE__) . '/../..');
if ($lc_g5_root === false || !is_file($lc_g5_root . '/common.php')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('LinkConnect: GNUBoard common.php를 찾을 수 없습니다.');
}

if (!defined('_GNUBOARD_')) {
    include_once $lc_g5_root . '/common.php';
}

if (!defined('_GNUBOARD_')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('LinkConnect: GNUBoard 초기화에 실패했습니다.');
}

if (is_file(G5_PATH . '/_site.config.php')) {
    include_once G5_PATH . '/_site.config.php';
}

require_once dirname(__FILE__) . '/config.php';

if (is_file(LC_PLUGIN_PATH . '/inc/sample_data.php')) {
    require_once LC_PLUGIN_PATH . '/inc/sample_data.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/db.php')) {
    require_once LC_PLUGIN_PATH . '/inc/db.php';
    if (function_exists('lc_db_installed') && lc_db_installed() && function_exists('lc_db_run_migrations')) {
        lc_db_run_migrations();
    }
}
if (is_file(LC_PLUGIN_PATH . '/inc/partner.php')) {
    require_once LC_PLUGIN_PATH . '/inc/partner.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_config.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_config.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_body.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_body.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_pdf.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_pdf.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_access.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_access.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_read.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_read.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_addendum.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_addendum.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_admin.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_admin.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_contract_custom.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_contract_custom.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_ad_apply.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_ad_apply.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/wallet.php')) {
    require_once LC_PLUGIN_PATH . '/inc/wallet.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/conversion.php')) {
    require_once LC_PLUGIN_PATH . '/inc/conversion.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/admin.php')) {
    require_once LC_PLUGIN_PATH . '/inc/admin.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/auth_bootstrap.php')) {
    require_once LC_PLUGIN_PATH . '/inc/auth_bootstrap.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign_thumbnail.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign_thumbnail.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign_promo_guide.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign_promo_guide.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign_banktupt.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign_banktupt.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign_dasibom.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign_dasibom.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/campaign_hasugu_cpa.php')) {
    require_once LC_PLUGIN_PATH . '/inc/campaign_hasugu_cpa.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/settlement.php')) {
    require_once LC_PLUGIN_PATH . '/inc/settlement.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/inquiry.php')) {
    require_once LC_PLUGIN_PATH . '/inc/inquiry.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/email_notify.php')) {
    require_once LC_PLUGIN_PATH . '/inc/email_notify.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/settings.php')) {
    require_once LC_PLUGIN_PATH . '/inc/settings.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/api_client.php')) {
    require_once LC_PLUGIN_PATH . '/inc/api_client.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/event.php')) {
    require_once LC_PLUGIN_PATH . '/inc/event.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/link.php')) {
    require_once LC_PLUGIN_PATH . '/inc/link.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/partner_analytics.php')) {
    require_once LC_PLUGIN_PATH . '/inc/partner_analytics.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/merchant_marketing.php')) {
    require_once LC_PLUGIN_PATH . '/inc/merchant_marketing.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/api.php')) {
    require_once LC_PLUGIN_PATH . '/inc/api.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/ui.php')) {
    require_once LC_PLUGIN_PATH . '/inc/ui.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/page.php')) {
    require_once LC_PLUGIN_PATH . '/inc/page.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/notice_board.php')) {
    require_once LC_PLUGIN_PATH . '/inc/notice_board.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/community_board.php')) {
    require_once LC_PLUGIN_PATH . '/inc/community_board.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/image_process.php')) {
    require_once LC_PLUGIN_PATH . '/inc/image_process.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/gemini.php')) {
    require_once LC_PLUGIN_PATH . '/inc/gemini.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/openai.php')) {
    require_once LC_PLUGIN_PATH . '/inc/openai.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/ai.php')) {
    require_once LC_PLUGIN_PATH . '/inc/ai.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/notification.php')) {
    require_once LC_PLUGIN_PATH . '/inc/notification.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/admin_log.php')) {
    require_once LC_PLUGIN_PATH . '/inc/admin_log.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/impersonate.php')) {
    require_once LC_PLUGIN_PATH . '/inc/impersonate.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/partner_tier.php')) {
    require_once LC_PLUGIN_PATH . '/inc/partner_tier.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/admin_ops.php')) {
    require_once LC_PLUGIN_PATH . '/inc/admin_ops.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/abuse.php')) {
    require_once LC_PLUGIN_PATH . '/inc/abuse.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/settlement_risk.php')) {
    require_once LC_PLUGIN_PATH . '/inc/settlement_risk.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/event_roi.php')) {
    require_once LC_PLUGIN_PATH . '/inc/event_roi.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/channel_report.php')) {
    require_once LC_PLUGIN_PATH . '/inc/channel_report.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/call.php')) {
    require_once LC_PLUGIN_PATH . '/inc/call.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/call_recording_request.php')) {
    require_once LC_PLUGIN_PATH . '/inc/call_recording_request.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/linkprice.php')) {
    require_once LC_PLUGIN_PATH . '/inc/linkprice.php';
}
if (is_file(LC_PLUGIN_PATH . '/inc/landing.php')) {
    require_once LC_PLUGIN_PATH . '/inc/landing.php';
}

if (function_exists('lc_link_enforce_tracking_host_gate')) {
    lc_link_enforce_tracking_host_gate();
}
