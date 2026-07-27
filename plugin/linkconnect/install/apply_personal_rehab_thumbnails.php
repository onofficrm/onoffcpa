<?php
/**
 * 개인회생 CPA 2곳(banktupt / dasibom) 썸네일 적용.
 *
 * CLI: php plugin/linkconnect/install/apply_personal_rehab_thumbnails.php
 * Web: /plugin/linkconnect/install/apply_personal_rehab_thumbnails.php?action=run
 */
require_once dirname(__DIR__) . '/_common.php';

if (!function_exists('lc_campaign_thumbnail_save_binary')) {
    require_once dirname(__DIR__) . '/inc/campaign_thumbnail.php';
}
if (!function_exists('lc_campaign_find_banktupt') && is_file(dirname(__DIR__) . '/inc/campaign_banktupt.php')) {
    require_once dirname(__DIR__) . '/inc/campaign_banktupt.php';
}
if (!function_exists('lc_campaign_find_dasibom') && is_file(dirname(__DIR__) . '/inc/campaign_dasibom.php')) {
    require_once dirname(__DIR__) . '/inc/campaign_dasibom.php';
}

if (!function_exists('lc_campaign_find_by_code')) {
    function lc_campaign_find_by_code($code)
    {
        $code = trim((string) $code);
        if ($code === '' || !lc_db_installed()) {
            return null;
        }
        $table = lc_table('campaigns');
        $row = sql_fetch(" SELECT * FROM `{$table}` WHERE cp_code = '" . lc_sql_escape($code) . "' ORDER BY cp_id DESC LIMIT 1 ");

        return is_array($row) && !empty($row['cp_id']) ? $row : null;
    }
}

if (!function_exists('lc_apply_personal_rehab_thumbnails')) {
    /**
     * @return array{ok:bool,message:string,items:array<int,array<string,mixed>>}
     */
    function lc_apply_personal_rehab_thumbnails()
    {
        if (!lc_db_installed()) {
            return array('ok' => false, 'message' => 'DB not ready', 'items' => array());
        }

        $asset_dir = dirname(__FILE__) . '/assets';
        $targets = array(
            array(
                'key' => 'banktupt',
                'file' => $asset_dir . '/thumb-banktupt.jpg',
                'codes' => array('CPA-BANKTUPT'),
                'finder' => 'lc_campaign_find_banktupt',
            ),
            array(
                'key' => 'dasibom',
                'file' => $asset_dir . '/thumb-dasibom.jpg',
                'codes' => array('CPA-DASIBOM', 'CPA-00011'),
                'finder' => 'lc_campaign_find_dasibom',
            ),
        );

        $items = array();
        $all_ok = true;

        foreach ($targets as $target) {
            $campaign = null;
            if (!empty($target['finder']) && function_exists($target['finder'])) {
                $campaign = call_user_func($target['finder']);
            }
            if (!is_array($campaign)) {
                foreach ($target['codes'] as $code) {
                    $campaign = lc_campaign_find_by_code($code);
                    if (is_array($campaign)) {
                        break;
                    }
                }
            }

            if (!is_array($campaign) || empty($campaign['cp_id'])) {
                $all_ok = false;
                $items[] = array(
                    'key' => $target['key'],
                    'ok' => false,
                    'message' => 'campaign not found',
                );
                continue;
            }

            if (!is_file($target['file'])) {
                $all_ok = false;
                $items[] = array(
                    'key' => $target['key'],
                    'ok' => false,
                    'cpId' => (int) $campaign['cp_id'],
                    'message' => 'asset missing: ' . basename($target['file']),
                );
                continue;
            }

            $binary = file_get_contents($target['file']);
            if ($binary === false || $binary === '') {
                $all_ok = false;
                $items[] = array(
                    'key' => $target['key'],
                    'ok' => false,
                    'cpId' => (int) $campaign['cp_id'],
                    'message' => 'asset unreadable',
                );
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
                'key' => $target['key'],
                'ok' => !empty($saved['ok']),
                'cpId' => (int) $campaign['cp_id'],
                'code' => (string) ($campaign['cp_code'] ?? ''),
                'name' => (string) ($campaign['cp_name'] ?? ''),
                'thumbnailUrl' => (string) ($saved['thumbnailUrl'] ?? ''),
                'message' => (string) ($saved['message'] ?? ''),
            );
        }

        return array(
            'ok' => $all_ok,
            'message' => $all_ok ? 'personal rehab thumbnails applied' : 'some thumbnails failed',
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

$result = lc_apply_personal_rehab_thumbnails();
if ($is_cli) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($result['ok']) ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
