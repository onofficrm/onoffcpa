<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_openai_api_key')) {
    function lc_openai_api_key()
    {
        return trim((string) lc_settings_get('openaiApiKey', ''));
    }
}

if (!function_exists('lc_openai_image_enabled')) {
    function lc_openai_image_enabled()
    {
        if (!lc_settings_get_bool('openaiImageEnabled', true)) {
            return false;
        }

        return lc_openai_api_key() !== '';
    }
}

if (!function_exists('lc_openai_image_model')) {
    function lc_openai_image_model()
    {
        $model = trim((string) lc_settings_get('openaiImageModel', 'gpt-image-2'));
        if ($model === '') {
            $model = 'gpt-image-2';
        }

        // Legacy / mistaken video labels → GPT Image (still images for banners).
        $aliases = array(
            'sora'           => 'gpt-image-2',
            'sora-2'         => 'gpt-image-2',
            'chatgpt-sora'   => 'gpt-image-2',
            'dall-e-3'       => 'gpt-image-2',
            'gpt-image-1'    => 'gpt-image-2',
            'gpt-image-1.5'  => 'gpt-image-2',
        );
        $key = strtolower($model);
        if (isset($aliases[$key])) {
            $model = $aliases[$key];
        }

        return $model;
    }
}

if (!function_exists('lc_openai_image_quality')) {
    function lc_openai_image_quality()
    {
        $quality = strtolower(trim((string) lc_settings_get('openaiImageQuality', 'medium')));
        $allowed = array('low', 'medium', 'high', 'auto');
        if (!in_array($quality, $allowed, true)) {
            $quality = 'medium';
        }

        return $quality;
    }
}

if (!function_exists('lc_openai_mask_key')) {
    function lc_openai_mask_key($key)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return '';
        }
        if (strlen($key) <= 8) {
            return '********';
        }

        return str_repeat('*', max(8, strlen($key) - 4)) . substr($key, -4);
    }
}

if (!function_exists('lc_openai_round_multiple')) {
    function lc_openai_round_multiple($value, $multiple = 16)
    {
        $multiple = max(1, (int) $multiple);
        $value = (int) $value;
        if ($value <= 0) {
            return $multiple;
        }

        return max($multiple, (int) (round($value / $multiple) * $multiple));
    }
}

if (!function_exists('lc_openai_request_size')) {
    /**
     * Map target WxH to a gpt-image-2-safe size string (multiples of 16, ratio ≤ 3:1, min pixels).
     */
    function lc_openai_request_size($width, $height)
    {
        $width = (int) $width;
        $height = (int) $height;
        if ($width <= 0 || $height <= 0) {
            return '1536x1024';
        }

        $ratio = $width / max(1, $height);
        if ($ratio > 3.0) {
            $ratio = 3.0;
        } elseif ($ratio < (1 / 3)) {
            $ratio = 1 / 3;
        }

        $long = 1536;
        if ($ratio >= 1.0) {
            $w = $long;
            $h = (int) round($long / $ratio);
        } else {
            $h = $long;
            $w = (int) round($long * $ratio);
        }

        $w = lc_openai_round_multiple($w, 16);
        $h = lc_openai_round_multiple($h, 16);

        $min_pixels = 655360;
        $guard = 0;
        while (($w * $h) < $min_pixels && $guard < 12) {
            $w = lc_openai_round_multiple((int) round($w * 1.15), 16);
            $h = lc_openai_round_multiple((int) round($h * 1.15), 16);
            $guard++;
        }

        $max_edge = 3840;
        if ($w > $max_edge || $h > $max_edge) {
            $scale = min($max_edge / max(1, $w), $max_edge / max(1, $h));
            $w = lc_openai_round_multiple((int) floor($w * $scale), 16);
            $h = lc_openai_round_multiple((int) floor($h * $scale), 16);
        }

        return $w . 'x' . $h;
    }
}

if (!function_exists('lc_openai_http_json')) {
    /**
     * @return array{ok:bool,message?:string,data?:array,http?:int}
     */
    function lc_openai_http_json($method, $path, array $payload = array(), array $options = array())
    {
        $api_key = lc_openai_api_key();
        if ($api_key === '') {
            return array('ok' => false, 'message' => 'OpenAI API 키가 설정되지 않았습니다.');
        }

        $url = 'https://api.openai.com/v1/' . ltrim((string) $path, '/');
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 120;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return array('ok' => false, 'message' => '요청 JSON 인코딩에 실패했습니다.');
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        );

        $raw = '';
        $http = 0;
        $curl_err = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper((string) $method));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, max(30, $timeout));
            $raw = (string) curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = (string) curl_error($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method'  => strtoupper((string) $method),
                    'header'  => implode("\r\n", $headers),
                    'content' => $body,
                    'timeout' => max(30, $timeout),
                    'ignore_errors' => true,
                ),
            ));
            $raw = (string) @file_get_contents($url, false, $context);
            if (function_exists('http_get_last_response_headers')) {
                $resp_headers = http_get_last_response_headers();
                if (is_array($resp_headers) && isset($resp_headers[0]) && preg_match('/\s(\d{3})\s/', (string) $resp_headers[0], $m)) {
                    $http = (int) $m[1];
                }
            }
        }

        if ($raw === '' && $curl_err !== '') {
            return array('ok' => false, 'message' => 'OpenAI 요청 실패: ' . $curl_err, 'http' => $http);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return array('ok' => false, 'message' => 'OpenAI 응답을 해석할 수 없습니다.', 'http' => $http);
        }

        if ($http >= 400 || isset($decoded['error'])) {
            $msg = '';
            if (isset($decoded['error']['message'])) {
                $msg = (string) $decoded['error']['message'];
            } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
                $msg = (string) $decoded['error'];
            }
            if ($msg === '') {
                $msg = 'OpenAI 이미지 생성에 실패했습니다. (HTTP ' . $http . ')';
            }

            return array('ok' => false, 'message' => $msg, 'http' => $http, 'data' => $decoded);
        }

        return array('ok' => true, 'data' => $decoded, 'http' => $http);
    }
}

if (!function_exists('lc_openai_generate_image')) {
    /**
     * OpenAI GPT Image (ChatGPT Images) — Korean text rendering is stronger than Gemini.
     *
     * @return array{ok:bool,message?:string,binary?:string,mime?:string,ext?:string,prompt?:string}
     */
    function lc_openai_generate_image($prompt, array $options = array())
    {
        if (!lc_openai_image_enabled()) {
            return array('ok' => false, 'message' => 'OpenAI 이미지 생성이 비활성화되었거나 API 키가 없습니다.');
        }

        $prompt = trim((string) $prompt);
        if ($prompt === '') {
            return array('ok' => false, 'message' => '이미지 생성 프롬프트가 비어 있습니다.');
        }

        $model = isset($options['model']) ? (string) $options['model'] : lc_openai_image_model();
        $quality = isset($options['quality']) ? (string) $options['quality'] : lc_openai_image_quality();
        $width = isset($options['width']) ? (int) $options['width'] : 0;
        $height = isset($options['height']) ? (int) $options['height'] : 0;
        $size = isset($options['size']) ? (string) $options['size'] : lc_openai_request_size($width, $height);

        $payload = array(
            'model'   => $model,
            'prompt'  => $prompt,
            'size'    => $size,
            'quality' => $quality,
            'n'       => 1,
        );

        // Prefer jpeg for smaller payload when resizing to campaign assets.
        if (!isset($options['output_format'])) {
            $payload['output_format'] = 'jpeg';
        } else {
            $payload['output_format'] = (string) $options['output_format'];
        }

        $result = lc_openai_http_json('POST', 'images/generations', $payload, array(
            'timeout' => isset($options['timeout']) ? (int) $options['timeout'] : 180,
        ));
        if (empty($result['ok'])) {
            return array(
                'ok'      => false,
                'message' => isset($result['message']) ? (string) $result['message'] : 'OpenAI 이미지 생성 실패',
            );
        }

        $data = isset($result['data']['data'][0]) && is_array($result['data']['data'][0])
            ? $result['data']['data'][0]
            : array();
        $b64 = isset($data['b64_json']) ? (string) $data['b64_json'] : '';
        if ($b64 === '' && !empty($data['url'])) {
            $fetched = @file_get_contents((string) $data['url']);
            if ($fetched === false || $fetched === '') {
                return array('ok' => false, 'message' => 'OpenAI 이미지 URL을 내려받지 못했습니다.');
            }
            $binary = $fetched;
            $mime = 'image/png';
        } else {
            if ($b64 === '') {
                return array('ok' => false, 'message' => 'OpenAI가 이미지 데이터를 반환하지 않았습니다. 모델·결제·권한을 확인해 주세요.');
            }
            $binary = base64_decode($b64, true);
            if ($binary === false || $binary === '') {
                return array('ok' => false, 'message' => 'OpenAI 이미지 디코딩에 실패했습니다.');
            }
            $fmt = strtolower((string) ($payload['output_format'] ?? 'png'));
            $mime = $fmt === 'jpeg' || $fmt === 'jpg' ? 'image/jpeg' : ($fmt === 'webp' ? 'image/webp' : 'image/png');
        }

        $ext = function_exists('lc_image_mime_to_ext') ? lc_image_mime_to_ext($mime) : 'jpg';
        if ($ext === '') {
            $ext = 'jpg';
            $mime = 'image/jpeg';
        }

        if ($width > 0 && $height > 0 && function_exists('lc_image_resize_cover')) {
            $resized = lc_image_resize_cover($binary, $width, $height, 'image/jpeg');
            if (!empty($resized['ok'])) {
                return array(
                    'ok'     => true,
                    'binary' => $resized['binary'],
                    'mime'   => $resized['mime'],
                    'ext'    => $resized['ext'],
                    'prompt' => $prompt,
                );
            }
        }

        return array(
            'ok'     => true,
            'binary' => $binary,
            'mime'   => $mime,
            'ext'    => $ext,
            'prompt' => $prompt,
        );
    }
}
