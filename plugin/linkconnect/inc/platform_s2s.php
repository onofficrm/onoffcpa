<?php
/**
 * Platform CPS S2S auth (TRAFFIC / BACKLINK / CONTENT / DOMAIN / GEO / ONOFFCPA).
 *
 * Machine credential (keyId + HMAC secret). Used for referral bind + payment report.
 * Domain product keeps domain-api for CatchDomain compatibility.
 */
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!defined('LC_PLATFORM_S2S_MAX_SKEW_SECONDS')) {
    define('LC_PLATFORM_S2S_MAX_SKEW_SECONDS', 300);
}

if (!function_exists('lc_platform_s2s_allowed_scopes')) {
    /**
     * @return list<string>
     */
    function lc_platform_s2s_allowed_scopes()
    {
        return array(
            'platform.referral.bind',
            'platform.payment.report',
            'platform.core.ready',
        );
    }
}

if (!function_exists('lc_platform_s2s_allowed_source_services')) {
    /**
     * @return list<string>
     */
    function lc_platform_s2s_allowed_source_services()
    {
        if (function_exists('lc_onoff_core_platform_services')) {
            return lc_onoff_core_platform_services();
        }
        return array('TRAFFIC', 'BACKLINK', 'CONTENT', 'DOMAIN', 'GEO', 'ONOFFCPA');
    }
}

if (!function_exists('lc_platform_s2s_normalize_source_service')) {
    function lc_platform_s2s_normalize_source_service($raw)
    {
        $svc = strtoupper(trim((string) $raw));
        $aliases = array(
            'SEO'        => 'CONTENT',
            'SEO_GEO'    => 'CONTENT',
            'SEO-GEO'    => 'CONTENT',
            'SEOSYSTEM'  => 'CONTENT',
            'CPS-SEO-GEO'=> 'CONTENT',
        );
        if (isset($aliases[$svc])) {
            $svc = $aliases[$svc];
        }
        $allowed = lc_platform_s2s_allowed_source_services();
        if ($svc === '' || !in_array($svc, $allowed, true)) {
            return '';
        }
        return $svc;
    }
}

if (!function_exists('lc_platform_s2s_clients_config_path')) {
    function lc_platform_s2s_clients_config_path()
    {
        return LC_PLUGIN_PATH . '/platform-api/clients.local.php';
    }
}

if (!function_exists('lc_platform_s2s_load_clients_config')) {
    /**
     * @return array{clients:list<array>,maxSkewSeconds:int}
     */
    function lc_platform_s2s_load_clients_config()
    {
        $config = array(
            'clients'        => array(),
            'maxSkewSeconds' => (int) LC_PLATFORM_S2S_MAX_SKEW_SECONDS,
        );

        $path = lc_platform_s2s_clients_config_path();
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

        $env_json = getenv('LC_PLATFORM_S2S_CLIENTS_JSON');
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

if (!function_exists('lc_platform_s2s_find_client')) {
    /**
     * @return array<string,mixed>|null
     */
    function lc_platform_s2s_find_client($key_id)
    {
        $key_id = trim((string) $key_id);
        if ($key_id === '') {
            return null;
        }
        $cfg = lc_platform_s2s_load_clients_config();
        foreach ($cfg['clients'] as $client) {
            if (!is_array($client)) {
                continue;
            }
            if ((string) ($client['keyId'] ?? '') !== $key_id) {
                continue;
            }
            $status = strtolower(trim((string) ($client['status'] ?? 'active')));
            if ($status !== '' && $status !== 'active') {
                return null;
            }
            return $client;
        }
        return null;
    }
}

if (!function_exists('lc_platform_s2s_client_has_scope')) {
    function lc_platform_s2s_client_has_scope(array $client, $scope)
    {
        $scopes = isset($client['scopes']) && is_array($client['scopes']) ? $client['scopes'] : array();
        return in_array((string) $scope, $scopes, true)
            || in_array('platform.*', $scopes, true)
            || in_array('*', $scopes, true);
    }
}

if (!function_exists('lc_platform_s2s_client_allows_source')) {
    function lc_platform_s2s_client_allows_source(array $client, $source_service)
    {
        $source_service = lc_platform_s2s_normalize_source_service($source_service);
        if ($source_service === '') {
            return false;
        }
        if (!isset($client['allowedSourceServices']) || !is_array($client['allowedSourceServices'])) {
            return true;
        }
        $allowed = array();
        foreach ($client['allowedSourceServices'] as $item) {
            $n = lc_platform_s2s_normalize_source_service($item);
            if ($n !== '') {
                $allowed[] = $n;
            }
        }
        if (!$allowed) {
            return true;
        }
        return in_array($source_service, $allowed, true);
    }
}

if (!function_exists('lc_platform_s2s_header')) {
    function lc_platform_s2s_header($name)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? trim((string) $_SERVER[$key]) : '';
    }
}

if (!function_exists('lc_platform_s2s_request_path')) {
    function lc_platform_s2s_request_path()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        }
        return $path;
    }
}

if (!function_exists('lc_platform_s2s_body_hash')) {
    function lc_platform_s2s_body_hash($raw_body)
    {
        return hash('sha256', (string) $raw_body);
    }
}

if (!function_exists('lc_platform_s2s_canonical_string')) {
    function lc_platform_s2s_canonical_string($method, $path, $timestamp, $nonce, $body_hash)
    {
        return strtoupper((string) $method) . "\n"
            . (string) $path . "\n"
            . (string) $timestamp . "\n"
            . (string) $nonce . "\n"
            . (string) $body_hash;
    }
}

if (!function_exists('lc_platform_s2s_sign')) {
    function lc_platform_s2s_sign($secret, $canonical)
    {
        return hash_hmac('sha256', (string) $canonical, (string) $secret);
    }
}

if (!function_exists('lc_platform_s2s_nonce_dir')) {
    function lc_platform_s2s_nonce_dir()
    {
        if (defined('G5_DATA_PATH') && G5_DATA_PATH) {
            $dir = rtrim((string) G5_DATA_PATH, '/') . '/linkconnect/platform_s2s_nonce';
        } else {
            $dir = sys_get_temp_dir() . '/lc_platform_s2s_nonce';
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }
}

if (!function_exists('lc_platform_s2s_nonce_seen')) {
    function lc_platform_s2s_nonce_seen($key_id, $nonce, $ttl_seconds)
    {
        $key_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key_id);
        $nonce = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', (string) $nonce);
        if ($key_id === '' || $nonce === '') {
            return true;
        }

        $dir = lc_platform_s2s_nonce_dir();
        $file = $dir . '/' . $key_id . '_' . hash('sha256', $nonce) . '.nonce';
        $now = time();

        if (is_file($file)) {
            $mtime = (int) @filemtime($file);
            if ($mtime > 0 && ($now - $mtime) < (int) $ttl_seconds) {
                return true;
            }
        }

        @file_put_contents($file, (string) $now, LOCK_EX);
        return false;
    }
}

if (!function_exists('lc_platform_s2s_authenticate')) {
    /**
     * @return array{ok:bool,client?:array,error?:string,http?:int}
     */
    function lc_platform_s2s_authenticate($required_scope, $raw_body = '')
    {
        $key_id = lc_platform_s2s_header('X-LC-Platform-Key');
        $timestamp = lc_platform_s2s_header('X-LC-Platform-Timestamp');
        $nonce = lc_platform_s2s_header('X-LC-Platform-Nonce');
        $signature = strtolower(lc_platform_s2s_header('X-LC-Platform-Signature'));

        if ($key_id === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            return array('ok' => false, 'error' => 'Missing Platform S2S auth headers', 'http' => 401);
        }

        $client = lc_platform_s2s_find_client($key_id);
        if (!$client || empty($client['secret'])) {
            return array('ok' => false, 'error' => 'UNAUTHORIZED', 'http' => 401);
        }

        if (!lc_platform_s2s_client_has_scope($client, $required_scope)) {
            return array('ok' => false, 'error' => 'FORBIDDEN_SCOPE', 'http' => 403);
        }

        if (!ctype_digit((string) $timestamp)) {
            return array('ok' => false, 'error' => 'INVALID_TIMESTAMP', 'http' => 401);
        }
        $ts = (int) $timestamp;
        $cfg = lc_platform_s2s_load_clients_config();
        $skew = (int) $cfg['maxSkewSeconds'];
        if (abs(time() - $ts) > $skew) {
            return array('ok' => false, 'error' => 'TIMESTAMP_SKEW', 'http' => 401);
        }

        if (lc_platform_s2s_nonce_seen($key_id, $nonce, $skew * 2)) {
            return array('ok' => false, 'error' => 'NONCE_REPLAY', 'http' => 401);
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'POST';
        $path = lc_platform_s2s_request_path();
        $body_hash = lc_platform_s2s_body_hash($raw_body);
        $canonical = lc_platform_s2s_canonical_string($method, $path, (string) $ts, $nonce, $body_hash);
        $expected = lc_platform_s2s_sign($client['secret'], $canonical);
        if (!hash_equals($expected, $signature)) {
            return array('ok' => false, 'error' => 'BAD_SIGNATURE', 'http' => 401);
        }

        return array('ok' => true, 'client' => $client);
    }
}

if (!function_exists('lc_platform_s2s_require')) {
    /**
     * @return array<string,mixed>
     */
    function lc_platform_s2s_require($required_scope)
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw)) {
            $raw = '';
        }
        $auth = lc_platform_s2s_authenticate($required_scope, $raw);
        if (empty($auth['ok'])) {
            $http = isset($auth['http']) ? (int) $auth['http'] : 401;
            lc_api_error((string) ($auth['error'] ?? 'UNAUTHORIZED'), 'UNAUTHORIZED', $http);
        }
        $auth['rawBody'] = $raw;
        $auth['body'] = array();
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $auth['body'] = $decoded;
            }
        }
        return $auth;
    }
}
