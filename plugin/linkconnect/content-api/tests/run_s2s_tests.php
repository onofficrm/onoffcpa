<?php
/**
 * Content S2S bridge — offline unit tests (no production DB writes).
 *
 * Run: php plugin/linkconnect/content-api/tests/run_s2s_tests.php
 */
define('_GNUBOARD_', true);
define('LC_PLUGIN_PATH', dirname(__DIR__, 2));
define('LC_PARTNER_STATUS_ACTIVE', 'active');
define('LC_CONTENT_S2S_MAX_SKEW_SECONDS', 300);
define('LC_CONTENT_S2S_CHANNEL_DEFAULT', 'content');
define('LC_CONTENT_S2S_SUB_ID_MAX', 100);
define('LC_CONTENT_S2S_CHANNEL_MAX', 100);

$nonce_dir = sys_get_temp_dir() . '/lc_content_s2s_test_nonce_' . getmypid();
@mkdir($nonce_dir, 0700, true);
putenv('LC_CONTENT_S2S_CLIENTS_JSON=' . json_encode(array(
    'maxSkewSeconds' => 300,
    'clients' => array(
        array(
            'keyId'  => 'cnt_test_key',
            'secret' => 'test_secret_value_do_not_use_in_prod',
            'ptId'   => 42,
            'status' => 'active',
            'scopes' => array(
                'content.campaigns.read',
                'content.links.read',
                'content.links.create',
                'content.analytics.read',
            ),
        ),
        array(
            'keyId'  => 'cnt_readonly_key',
            'secret' => 'readonly_secret',
            'ptId'   => 42,
            'status' => 'active',
            'scopes' => array(
                'content.campaigns.read',
                'content.links.read',
                'content.analytics.read',
            ),
        ),
    ),
)));

if (!function_exists('lc_get_partner_by_id')) {
    function lc_get_partner_by_id($pt_id)
    {
        if ((int) $pt_id === 42) {
            return array(
                'pt_id'     => 42,
                'pt_code'   => 'PTN-0042',
                'pt_status' => LC_PARTNER_STATUS_ACTIVE,
            );
        }
        return null;
    }
}

if (!function_exists('lc_content_s2s_nonce_dir')) {
    function lc_content_s2s_nonce_dir()
    {
        global $nonce_dir;
        return $nonce_dir;
    }
}

require_once dirname(__DIR__, 2) . '/inc/content_s2s.php';

$passed = 0;
$failed = 0;

function assert_true($cond, $name)
{
    global $passed, $failed;
    if ($cond) {
        echo "PASS  {$name}\n";
        $passed++;
    } else {
        echo "FAIL  {$name}\n";
        $failed++;
    }
}

function sign_request($secret, $method, $path, $ts, $nonce, $body)
{
    $canonical = lc_content_s2s_canonical_string($method, $path, $ts, $nonce, lc_content_s2s_body_hash($body));
    return lc_content_s2s_sign($secret, $canonical);
}

function reset_server($method, $path, $headers)
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['SCRIPT_NAME'] = $path;
    foreach (array(
        'HTTP_X_LC_CONTENT_KEY',
        'HTTP_X_LC_CONTENT_TIMESTAMP',
        'HTTP_X_LC_CONTENT_NONCE',
        'HTTP_X_LC_CONTENT_SIGNATURE',
    ) as $h) {
        unset($_SERVER[$h]);
    }
    foreach ($headers as $k => $v) {
        $_SERVER[$k] = $v;
    }
}

$path = '/plugin/linkconnect/content-api/links.php';
$secret = 'test_secret_value_do_not_use_in_prod';
$ts = (string) time();
$nonce1 = 'nonce-' . bin2hex(random_bytes(8));
$body = '{"campaignId":1,"channel":"content","subId":"content:post-1"}';
$sig = sign_request($secret, 'POST', $path, $ts, $nonce1, $body);

reset_server('POST', $path, array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_test_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => $ts,
    'HTTP_X_LC_CONTENT_NONCE' => $nonce1,
    'HTTP_X_LC_CONTENT_SIGNATURE' => $sig,
));
$auth = lc_content_s2s_authenticate('content.links.create', $body);
assert_true(!empty($auth['ok']) && (int) $auth['partner']['pt_id'] === 42, '1. valid machine auth');

$bad_sig = str_repeat('a', 64);
reset_server('POST', $path, array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_test_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => $ts,
    'HTTP_X_LC_CONTENT_NONCE' => 'nonce-bad-sig-' . bin2hex(random_bytes(4)),
    'HTTP_X_LC_CONTENT_SIGNATURE' => $bad_sig,
));
$auth = lc_content_s2s_authenticate('content.links.create', $body);
assert_true(empty($auth['ok']) && ($auth['code'] ?? '') === 'INVALID_SIGNATURE', '2. invalid signature');

$old_ts = (string) (time() - 900);
$nonce_exp = 'nonce-exp-' . bin2hex(random_bytes(4));
$sig_exp = sign_request($secret, 'POST', $path, $old_ts, $nonce_exp, $body);
reset_server('POST', $path, array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_test_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => $old_ts,
    'HTTP_X_LC_CONTENT_NONCE' => $nonce_exp,
    'HTTP_X_LC_CONTENT_SIGNATURE' => $sig_exp,
));
$auth = lc_content_s2s_authenticate('content.links.create', $body);
assert_true(empty($auth['ok']) && ($auth['code'] ?? '') === 'TIMESTAMP_EXPIRED', '3. expired request');

$nonce_replay = 'nonce-replay-' . bin2hex(random_bytes(4));
$sig_r = sign_request($secret, 'POST', $path, (string) time(), $nonce_replay, $body);
$hdr = array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_test_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => (string) time(),
    'HTTP_X_LC_CONTENT_NONCE' => $nonce_replay,
    'HTTP_X_LC_CONTENT_SIGNATURE' => $sig_r,
);
reset_server('POST', $path, $hdr);
$first = lc_content_s2s_authenticate('content.links.create', $body);
// resign with fresh timestamp for second attempt same nonce
$ts2 = (string) time();
$sig_r2 = sign_request($secret, 'POST', $path, $ts2, $nonce_replay, $body);
reset_server('POST', $path, array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_test_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => $ts2,
    'HTTP_X_LC_CONTENT_NONCE' => $nonce_replay,
    'HTTP_X_LC_CONTENT_SIGNATURE' => $sig_r2,
));
$second = lc_content_s2s_authenticate('content.links.create', $body);
assert_true(!empty($first['ok']) && empty($second['ok']) && ($second['code'] ?? '') === 'REPLAY_DETECTED', '4. replay');

$nonce_scope = 'nonce-scope-' . bin2hex(random_bytes(4));
$ts_s = (string) time();
$sig_s = sign_request('readonly_secret', 'POST', $path, $ts_s, $nonce_scope, $body);
reset_server('POST', $path, array(
    'HTTP_X_LC_CONTENT_KEY' => 'cnt_readonly_key',
    'HTTP_X_LC_CONTENT_TIMESTAMP' => $ts_s,
    'HTTP_X_LC_CONTENT_NONCE' => $nonce_scope,
    'HTTP_X_LC_CONTENT_SIGNATURE' => $sig_s,
));
$auth = lc_content_s2s_authenticate('content.links.create', $body);
assert_true(empty($auth['ok']) && ($auth['code'] ?? '') === 'SCOPE_FORBIDDEN', '5. unauthorized scope');

$own = lc_content_s2s_reject_client_pt_override(array('ptId' => 99), 42);
$own_ok = lc_content_s2s_reject_client_pt_override(array('campaignId' => 1), 42);
$own_same = lc_content_s2s_reject_client_pt_override(array('ptId' => 42), 42);
assert_true(empty($own['ok']) && !empty($own_ok['ok']) && !empty($own_same['ok']), '6. partner ownership override rejected');

$sub_ok = lc_content_s2s_normalize_sub_id('content:article-99');
$sub_draft = lc_content_s2s_normalize_sub_id('content:draft:550e8400-e29b-41d4-a716-446655440000');
$sub_bad = lc_content_s2s_normalize_sub_id('blog:1');
assert_true(!empty($sub_ok['ok']) && !empty($sub_draft['ok']) && empty($sub_bad['ok']), '7/8. subId convention (campaign/link create helpers)');

$ch = lc_content_s2s_normalize_channel('');
assert_true($ch === 'content', '8b. default channel=content');

$link = lc_content_s2s_link_response(array(
    'code' => 'abcdef1234',
    'url' => 'https://onoffcpa.icrm.co.kr/r/abcdef1234',
    'landingUrl' => 'https://onoffcpa.icrm.co.kr/c/abcdef1234',
    'id' => 7,
    'campaignId' => 3,
));
assert_true($link['code'] === 'abcdef1234' && $link['shortUrl'] === null && $link['url'] !== '', '8c. link response shape');

$read_scopes = array('content.campaigns.read', 'content.links.read', 'content.analytics.read');
$client_ro = array('scopes' => $read_scopes);
assert_true(
    lc_content_s2s_client_has_scope($client_ro, 'content.campaigns.read')
    && lc_content_s2s_client_has_scope($client_ro, 'content.links.read')
    && lc_content_s2s_client_has_scope($client_ro, 'content.analytics.read')
    && !lc_content_s2s_client_has_scope($client_ro, 'content.links.create'),
    '9/10. campaign/link/analytics read scopes'
);

$write_forbidden = !in_array('content.campaigns.write', lc_content_s2s_allowed_scopes(), true)
    && !in_array('admin', lc_content_s2s_allowed_scopes(), true);
assert_true($write_forbidden, '11. write/admin scopes absent (fail closed allowlist)');

$cps_unavailable = (!function_exists('lc_cps_enabled') || !lc_cps_enabled());
assert_true($cps_unavailable, '12. CPS unavailable (runtime flag off / unset)');

$redacted = lc_content_s2s_redact_for_log(array('secret' => 'x', 'signature' => 'y', 'ok' => true));
assert_true($redacted['secret'] === '[REDACTED]' && $redacted['signature'] === '[REDACTED]' && $redacted['ok'] === true, 'log redaction');

// cleanup nonce dir
foreach (glob($nonce_dir . '/*') ?: array() as $f) {
    @unlink($f);
}
@rmdir($nonce_dir);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
