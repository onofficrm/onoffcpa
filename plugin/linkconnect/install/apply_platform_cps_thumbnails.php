<?php
/**
 * ONOFF 플랫폼 CPS 4종 썸네일 적용 (DOMAIN / SEO-GEO / TRAFFIC / BACKLINK).
 *
 * CLI: php plugin/linkconnect/install/apply_platform_cps_thumbnails.php
 * Web: /plugin/linkconnect/install/apply_platform_cps_thumbnails.php?action=run
 */
require_once dirname(__DIR__) . '/_common.php';

if (!function_exists('lc_campaign_thumbnail_save_binary')) {
    require_once dirname(__DIR__) . '/inc/campaign_thumbnail.php';
}

if (!function_exists('lc_apply_platform_cps_thumbnails')) {
    /**
     * @return array{ok:bool,message:string,items:array<int,array<string,mixed>>}
     */
    function lc_apply_platform_cps_thumbnails()
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB not ready', 'items' => array());
        }

        $asset_dir = dirname(__FILE__) . '/assets';
        $targets = array(
            array('code' => 'CPS-DOMAIN', 'file' => $asset_dir . '/thumb-cps-domain.jpg'),
            array('code' => 'CPS-SEO-GEO', 'file' => $asset_dir . '/thumb-cps-seo-geo.jpg'),
            array('code' => 'CPS-TRAFFIC', 'file' => $asset_dir . '/thumb-cps-traffic.jpg'),
            array('code' => 'CPS-BACKLINK', 'file' => $asset_dir . '/thumb-cps-backlink.jpg'),
        );

        $items = array();
        $all_ok = true;
        $table = lc_table('campaigns');

        foreach ($targets as $target) {
            $code = (string) $target['code'];
            $campaign = lc_sql_fetch(
                " SELECT * FROM `{$table}` WHERE cp_code = '" . lc_sql_escape($code) . "' ORDER BY cp_id DESC LIMIT 1 "
            );
            if (!is_array($campaign) || empty($campaign['cp_id'])) {
                $all_ok = false;
                $items[] = array('code' => $code, 'ok' => false, 'message' => 'campaign not found');
                continue;
            }
            if (!is_file($target['file'])) {
                $all_ok = false;
                $items[] = array(
                    'code' => $code,
                    'ok' => false,
                    'cpId' => (int) $campaign['cp_id'],
                    'message' => 'asset missing: ' . basename($target['file']),
                );
                continue;
            }
            $binary = file_get_contents($target['file']);
            if ($binary === false || $binary === '') {
                $all_ok = false;
                $items[] = array('code' => $code, 'ok' => false, 'message' => 'asset unreadable');
                continue;
            }

            $saved = lc_campaign_thumbnail_save_binary(
                (int) $campaign['cp_id'],
                $binary,
                'image/jpeg',
                basename($target['file'])
            );
            if (empty($saved['ok'])) {
                $all_ok = false;
            }
            $items[] = array(
                'code' => $code,
                'ok' => !empty($saved['ok']),
                'cpId' => (int) $campaign['cp_id'],
                'name' => (string) ($campaign['cp_name'] ?? ''),
                'thumbnailUrl' => (string) ($saved['thumbnailUrl'] ?? ''),
                'message' => (string) ($saved['message'] ?? ''),
            );
        }

        return array(
            'ok' => $all_ok,
            'message' => $all_ok ? 'platform cps thumbnails applied' : 'some thumbnails failed',
            'items' => $items,
        );
    }
}

$is_cli = (php_sapi_name() === 'cli');
$running_as_script = (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__))
    || ($is_cli && isset($_SERVER['argv'][0]) && realpath($_SERVER['argv'][0]) === realpath(__FILE__));

if (!$running_as_script) {
    return;
}

if (!$is_cli) {
    $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
    if ($action !== 'run') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "usage: ?action=run\n";
        exit;
    }
}

$result = lc_apply_platform_cps_thumbnails();
if ($is_cli) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($result['ok']) ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
