<?php
/**
 * Content Platform → ONOFFCPA Partner S2S auth bridge.
 *
 * Machine credential (keyId + HMAC secret) maps to a fixed pt_id.
 * Request body/query pt_id is never treated as authority.
 *
 * No DB migration. Clients load from content-api/clients.local.php (or env).
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!defined('LC_CONTENT_S2S_MAX_SKEW_SECONDS')) {
    define('LC_CONTENT_S2S_MAX_SKEW_SECONDS', 300);
}

if (!defined('LC_CONTENT_S2S_CHANNEL_DEFAULT')) {
    define('LC_CONTENT_S2S_CHANNEL_DEFAULT', 'content');
}

if (!defined('LC_CONTENT_S2S_SUB_ID_MAX')) {
    /** Matches g5_lc_links.lk_sub_id varchar(100) */
    define('LC_CONTENT_S2S_SUB_ID_MAX', 100);
}

if (!defined('LC_CONTENT_S2S_CHANNEL_MAX')) {
    /** Matches g5_lc_links.lk_channel varchar(100) */
    define('LC_CONTENT_S2S_CHANNEL_MAX', 100);
}

if (!function_exists('lc_content_s2s_allowed_scopes')) {
    /**
     * @return list<string>
     */
    function lc_content_s2s_allowed_scopes()
    {
        return array(
            'content.campaigns.read',
            'content.links.read',
            'content.links.create',
            'content.analytics.read',
        );
    }
}

if (!function_exists('lc_content_s2s_clients_config_path')) {
    function lc_content_s2s_clients_config_path()
    {
        return LC_PLUGIN_PATH . '/content-api/clients.local.php';
    }
}

if (!function_exists('lc_content_s2s_load_clients_config')) {
    /**
     * @return array{clients:list<array>,maxSkewSeconds:int}
     */
    function lc_content_s2s_load_clients_config()
    {
        $config = array(
            'clients'         => array(),
            'maxSkewSeconds'  => (int) LC_CONTENT_S2S_MAX_SKEW_SECONDS,
        );

        $path = lc_content_s2s_clients_config_path();
        if (is_file($path)) {
            $loaded = include $path;
            if (is_array($loaded)) {
                if (isset($loaded['clients']) && is_array($loaded['clients'])) {
                    $config['clients'] = $loaded['clients'];
                }
                if (isset($loaded['maxSkewSeconds'])) {
                    $config['maxSkewSeconds'] = max(30, (int) $loaded['maxSkewSeconds']);
                }
            }
        }

        // Optional env override for CI / local fixtures (JSON array of clients).
        $env_json = getenv('LC_CONTENT_S2S_CLIENTS_JSON');
        if (is_string($env_json) && trim($env_json) !== '') {
            $decoded = json_decode($env_json, true);
            if (is_array($decoded)) {
                if (isset($decoded['clients']) && is_array($decoded['clients'])) {
                    $config['clients'] = $decoded['clients'];
                } elseif (array_keys($decoded) === range(0, count($decoded) - 1)) {
                    $config['clients'] = $decoded;
                }
                if (isset($decoded['maxSkewSeconds'])) {
                    $config['maxSkewSeconds'] = max(30, (int) $decoded['maxSkewSeconds']);
                }
            }
        }

        return $config;
    }
}

if (!function_exists('lc_content_s2s_find_client')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_content_s2s_find_client($key_id)
    {
        $key_id = trim((string) $key_id);
        if ($key_id === '') {
            return null;
        }

        $config = lc_content_s2s_load_clients_config();
        foreach ($config['clients'] as $client) {
            if (!is_array($client)) {
                continue;
            }
            $id = trim((string) ($client['keyId'] ?? $client['key_id'] ?? ''));
            // keyId is a public client id (not a secret); exact match is enough.
            if ($id === '' || $id !== $key_id) {
                continue;
            }
            $status = strtolower(trim((string) ($client['status'] ?? 'active')));
            if ($status !== 'active') {
                return null;
            }
            $secret = (string) ($client['secret'] ?? '');
            if ($secret === '') {
                return null;
            }

            $scopes = isset($client['scopes']) && is_array($client['scopes'])
                ? array_values(array_filter(array_map('strval', $client['scopes'])))
                : lc_content_s2s_allowed_scopes();
            $allowed = lc_content_s2s_allowed_scopes();
            $scopes = array_values(array_intersect($scopes, $allowed));

            $pt_id = (int) ($client['ptId'] ?? $client['pt_id'] ?? 0);
            $pt_code = trim((string) ($client['ptCode'] ?? $client['pt_code'] ?? ''));

            return array(
                'keyId'   => $id,
                'secret'  => $secret,
                'ptId'    => $pt_id,
                'ptCode'  => $pt_code,
                'scopes'  => $scopes,
                'name'    => (string) ($client['name'] ?? $id),
            );
        }

        return null;
    }
}

if (!function_exists('lc_content_s2s_resolve_partner')) {
    /**
     * Bind machine client → active partner. Never trust request pt_id.
     *
     * @param array<string,mixed> $client
     * @return array{ok:bool,partner?:array,message?:string,code?:string}
     */
    function lc_content_s2s_resolve_partner(array $client)
    {
        $pt_id = (int) ($client['ptId'] ?? 0);
        $pt_code = trim((string) ($client['ptCode'] ?? ''));

        if ($pt_id <= 0 && $pt_code !== ''
            && function_exists('lc_db_installed') && lc_db_installed()
            && function_exists('lc_sql_fetch') && function_exists('lc_table')
        ) {
            $table = lc_table('partners');
            $by_code = lc_sql_fetch(
                " SELECT * FROM `{$table}` WHERE pt_code = '" . lc_sql_escape($pt_code) . "' LIMIT 1 "
            );
            if (is_array($by_code)) {
                $pt_id = (int) ($by_code['pt_id'] ?? 0);
            }
        }

        if ($pt_id <= 0) {
            return array('ok' => false, 'message' => 'Client has no partner mapping.', 'code' => 'PARTNER_NOT_MAPPED');
        }

        if (!function_exists('lc_get_partner_by_id')) {
            return array('ok' => false, 'message' => 'Partner lookup unavailable.', 'code' => 'PARTNER_LOOKUP_FAILED');
        }

        $partner = lc_get_partner_by_id($pt_id);
        if (!is_array($partner)) {
            return array('ok' => false, 'message' => 'Mapped partner not found.', 'code' => 'PARTNER_NOT_FOUND');
        }

        if (($partner['pt_status'] ?? '') !== LC_PARTNER_STATUS_ACTIVE) {
            return array('ok' => false, 'message' => 'Mapped partner is not active.', 'code' => 'PARTNER_NOT_ACTIVE');
        }

        return array('ok' => true, 'partner' => $partner);
    }
}

if (!function_exists('lc_content_s2s_header')) {
    function lc_content_s2s_header($name)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? trim((string) $_SERVER[$key]) : '';
    }
}

if (!function_exists('lc_content_s2s_request_path')) {
    function lc_content_s2s_request_path()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
            return $script;
        }
        return $path;
    }
}

if (!function_exists('lc_content_s2s_body_hash')) {
    function lc_content_s2s_body_hash($raw_body)
    {
        return hash('sha256', (string) $raw_body);
    }
}

if (!function_exists('lc_content_s2s_canonical_string')) {
    /**
     * METHOD\nPATH\nTIMESTAMP\nNONCE\nBODY_SHA256
     */
    function lc_content_s2s_canonical_string($method, $path, $timestamp, $nonce, $body_hash)
    {
        return strtoupper((string) $method) . "\n"
            . (string) $path . "\n"
            . (string) $timestamp . "\n"
            . (string) $nonce . "\n"
            . (string) $body_hash;
    }
}

if (!function_exists('lc_content_s2s_sign')) {
    function lc_content_s2s_sign($secret, $canonical)
    {
        return hash_hmac('sha256', (string) $canonical, (string) $secret);
    }
}

if (!function_exists('lc_content_s2s_nonce_dir')) {
    function lc_content_s2s_nonce_dir()
    {
        if (defined('G5_DATA_PATH') && G5_DATA_PATH) {
            $dir = rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/content_s2s_nonce';
        } else {
            $dir = sys_get_temp_dir() . '/lc_content_s2s_nonce';
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }
}

if (!function_exists('lc_content_s2s_nonce_seen')) {
    /**
     * Replay prevention via filesystem nonce marker (no DB).
     */
    function lc_content_s2s_nonce_seen($key_id, $nonce, $ttl_seconds)
    {
        $key_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key_id);
        $nonce = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $nonce);
        if ($key_id === '' || $nonce === '') {
            return true;
        }

        $dir = lc_content_s2s_nonce_dir();
        $file = $dir . '/' . $key_id . '_' . hash('sha256', $nonce) . '.nonce';
        $now = time();

        if (is_file($file)) {
            $mtime = (int) @filemtime($file);
            if ($mtime > 0 && ($now - $mtime) < (int) $ttl_seconds) {
                return true;
            }
        }

        $written = @file_put_contents($file, (string) $now, LOCK_EX);
        return $written === false;
    }
}

if (!function_exists('lc_content_s2s_client_has_scope')) {
    function lc_content_s2s_client_has_scope(array $client, $scope)
    {
        $scope = (string) $scope;
        $scopes = isset($client['scopes']) && is_array($client['scopes']) ? $client['scopes'] : array();
        foreach ($scopes as $s) {
            if (hash_equals((string) $s, $scope)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('lc_content_s2s_reject_client_pt_override')) {
    /**
     * Fail closed if caller tries to assert a different partner identity.
     *
     * @param array<string,mixed> $payload
     * @param int $authorized_pt_id
     */
    function lc_content_s2s_reject_client_pt_override(array $payload, $authorized_pt_id)
    {
        $authorized_pt_id = (int) $authorized_pt_id;
        $keys = array('ptId', 'pt_id', 'partnerId', 'partner_id');
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $given = (int) $payload[$key];
            if ($given > 0 && $given !== $authorized_pt_id) {
                return array(
                    'ok'      => false,
                    'message' => 'pt_id override is not allowed.',
                    'code'    => 'PARTNER_OVERRIDE_FORBIDDEN',
                );
            }
        }

        $code_keys = array('ptCode', 'pt_code', 'partnerCode', 'partner_code');
        foreach ($code_keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $given_code = trim((string) $payload[$key]);
            if ($given_code === '') {
                continue;
            }
            if (function_exists('lc_get_partner_by_id')) {
                $partner = lc_get_partner_by_id($authorized_pt_id);
                $auth_code = is_array($partner) ? trim((string) ($partner['pt_code'] ?? '')) : '';
                if ($auth_code !== '' && !hash_equals($auth_code, $given_code)) {
                    return array(
                        'ok'      => false,
                        'message' => 'pt_code override is not allowed.',
                        'code'    => 'PARTNER_OVERRIDE_FORBIDDEN',
                    );
                }
            }
        }

        return array('ok' => true);
    }
}

if (!function_exists('lc_content_s2s_authenticate')) {
    /**
     * @param string $required_scope
     * @param string $raw_body Raw request body used for signature (GET: empty string)
     * @return array{ok:bool,client?:array,partner?:array,message?:string,code?:string,http?:int}
     */
    function lc_content_s2s_authenticate($required_scope, $raw_body = '')
    {
        $key_id = lc_content_s2s_header('X-LC-Content-Key');
        $timestamp = lc_content_s2s_header('X-LC-Content-Timestamp');
        $nonce = lc_content_s2s_header('X-LC-Content-Nonce');
        $signature = strtolower(lc_content_s2s_header('X-LC-Content-Signature'));

        if ($key_id === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            return array(
                'ok'      => false,
                'message' => 'Missing Content S2S auth headers.',
                'code'    => 'UNAUTHORIZED',
                'http'    => 401,
            );
        }

        $client = lc_content_s2s_find_client($key_id);
        if (!$client) {
            return array(
                'ok'      => false,
                'message' => 'Invalid Content machine credential.',
                'code'    => 'UNAUTHORIZED',
                'http'    => 401,
            );
        }

        if (!ctype_digit($timestamp) && !preg_match('/^-?\d+$/', $timestamp)) {
            return array(
                'ok'      => false,
                'message' => 'Invalid timestamp.',
                'code'    => 'INVALID_TIMESTAMP',
                'http'    => 401,
            );
        }

        $ts = (int) $timestamp;
        $config = lc_content_s2s_load_clients_config();
        $skew = (int) ($config['maxSkewSeconds'] ?? LC_CONTENT_S2S_MAX_SKEW_SECONDS);
        if (abs(time() - $ts) > $skew) {
            return array(
                'ok'      => false,
                'message' => 'Request timestamp expired.',
                'code'    => 'TIMESTAMP_EXPIRED',
                'http'    => 401,
            );
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $path = lc_content_s2s_request_path();
        $body_hash = lc_content_s2s_body_hash($raw_body);
        $canonical = lc_content_s2s_canonical_string($method, $path, (string) $ts, $nonce, $body_hash);
        $expected = lc_content_s2s_sign($client['secret'], $canonical);

        if (!hash_equals($expected, $signature)) {
            return array(
                'ok'      => false,
                'message' => 'Invalid signature.',
                'code'    => 'INVALID_SIGNATURE',
                'http'    => 401,
            );
        }

        // Replay after signature OK.
        if (lc_content_s2s_nonce_seen($client['keyId'], $nonce, $skew * 2)) {
            return array(
                'ok'      => false,
                'message' => 'Replay detected.',
                'code'    => 'REPLAY_DETECTED',
                'http'    => 401,
            );
        }

        if (!lc_content_s2s_client_has_scope($client, $required_scope)) {
            return array(
                'ok'      => false,
                'message' => 'Scope not authorized: ' . $required_scope,
                'code'    => 'SCOPE_FORBIDDEN',
                'http'    => 403,
            );
        }

        $resolved = lc_content_s2s_resolve_partner($client);
        if (empty($resolved['ok'])) {
            return array(
                'ok'      => false,
                'message' => (string) ($resolved['message'] ?? 'Partner mapping failed.'),
                'code'    => (string) ($resolved['code'] ?? 'PARTNER_NOT_MAPPED'),
                'http'    => 403,
            );
        }

        // Drop secret from in-memory client before returning.
        unset($client['secret']);

        return array(
            'ok'      => true,
            'client'  => $client,
            'partner' => $resolved['partner'],
        );
    }
}

if (!function_exists('lc_content_s2s_require')) {
    /**
     * Authenticate and exit with JSON error on failure.
     *
     * @return array{client:array,partner:array,pt_id:int,raw_body:string}
     */
    function lc_content_s2s_require($required_scope)
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        $raw_body = '';
        if ($method !== 'GET' && $method !== 'HEAD') {
            $raw_body = (string) file_get_contents('php://input');
        }

        $auth = lc_content_s2s_authenticate($required_scope, $raw_body);
        if (empty($auth['ok'])) {
            lc_api_error(
                (string) ($auth['message'] ?? 'Unauthorized'),
                (string) ($auth['code'] ?? 'UNAUTHORIZED'),
                (int) ($auth['http'] ?? 401)
            );
        }

        $partner = $auth['partner'];
        $pt_id = (int) ($partner['pt_id'] ?? 0);

        return array(
            'client'   => $auth['client'],
            'partner'  => $partner,
            'pt_id'    => $pt_id,
            'raw_body' => $raw_body,
        );
    }
}

if (!function_exists('lc_content_s2s_normalize_channel')) {
    function lc_content_s2s_normalize_channel($channel)
    {
        $channel = trim((string) $channel);
        if ($channel === '') {
            $channel = LC_CONTENT_S2S_CHANNEL_DEFAULT;
        }
        if (function_exists('mb_substr')) {
            $channel = mb_substr($channel, 0, LC_CONTENT_S2S_CHANNEL_MAX);
        } else {
            $channel = substr($channel, 0, LC_CONTENT_S2S_CHANNEL_MAX);
        }
        return $channel;
    }
}

if (!function_exists('lc_content_s2s_normalize_sub_id')) {
    /**
     * Content convention: content:{id} | content:draft:{uuid}
     * Enforced to varchar(100).
     *
     * @return array{ok:bool,subId?:string,message?:string,code?:string}
     */
    function lc_content_s2s_normalize_sub_id($sub_id)
    {
        $sub_id = trim((string) $sub_id);
        if ($sub_id === '') {
            return array('ok' => false, 'message' => 'subId is required.', 'code' => 'INVALID_SUB_ID');
        }

        if (!preg_match('/^content:(draft:)?[A-Za-z0-9._\-]{1,80}$/', $sub_id)) {
            return array(
                'ok'      => false,
                'message' => 'subId must match content:{id} or content:draft:{uuid}.',
                'code'    => 'INVALID_SUB_ID',
            );
        }

        if (strlen($sub_id) > LC_CONTENT_S2S_SUB_ID_MAX) {
            return array(
                'ok'      => false,
                'message' => 'subId exceeds ' . LC_CONTENT_S2S_SUB_ID_MAX . ' characters.',
                'code'    => 'INVALID_SUB_ID',
            );
        }

        return array('ok' => true, 'subId' => $sub_id);
    }
}

if (!function_exists('lc_content_s2s_link_response')) {
    /**
     * Normalize partner link DTO for Content BFF.
     *
     * @param array<string,mixed> $link
     * @return array<string,mixed>
     */
    function lc_content_s2s_link_response(array $link)
    {
        return array(
            'code'       => (string) ($link['code'] ?? ''),
            'url'        => (string) ($link['url'] ?? ''),
            'landingUrl' => (string) ($link['landingUrl'] ?? ''),
            'shortUrl'   => isset($link['shortUrl']) ? $link['shortUrl'] : null,
            // Compatibility fields from lc_link_to_api
            'id'         => (int) ($link['id'] ?? 0),
            'campaignId' => (int) ($link['campaignId'] ?? 0),
            'campaign'   => (string) ($link['campaign'] ?? ''),
            'channel'    => (string) ($link['channel'] ?? ''),
            'subId'      => (string) ($link['subId'] ?? ''),
            'status'     => (string) ($link['status'] ?? ''),
            'statusCode' => (string) ($link['statusCode'] ?? ''),
            'createdAt'  => (string) ($link['createdAt'] ?? ''),
        );
    }
}

if (!function_exists('lc_content_s2s_redact_for_log')) {
    /**
     * @param mixed $value
     * @return mixed
     */
    function lc_content_s2s_redact_for_log($value)
    {
        if (is_array($value)) {
            $out = array();
            foreach ($value as $k => $v) {
                $lk = strtolower((string) $k);
                if (strpos($lk, 'secret') !== false
                    || strpos($lk, 'signature') !== false
                    || strpos($lk, 'token') !== false
                    || $lk === 'authorization'
                ) {
                    $out[$k] = '[REDACTED]';
                } else {
                    $out[$k] = lc_content_s2s_redact_for_log($v);
                }
            }
            return $out;
        }
        return $value;
    }
}
