<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_gemini_api_key')) {
    function lc_gemini_api_key()
    {
        return trim((string) lc_settings_get('geminiApiKey', ''));
    }
}

if (!function_exists('lc_gemini_enabled')) {
    function lc_gemini_enabled()
    {
        if (!lc_settings_get_bool('geminiEnabled', true)) {
            return false;
        }

        return lc_gemini_api_key() !== '';
    }
}

if (!function_exists('lc_gemini_model')) {
    function lc_gemini_model()
    {
        $model = trim((string) lc_settings_get('geminiModel', 'gemini-2.5-flash'));
        if ($model === '') {
            $model = 'gemini-2.5-flash';
        }

        $deprecated = array(
            'gemini-2.0-flash'           => 'gemini-2.5-flash',
            'gemini-2.0-flash-001'       => 'gemini-2.5-flash',
            'gemini-2.0-flash-lite'      => 'gemini-2.5-flash-lite',
            'gemini-2.0-flash-lite-001' => 'gemini-2.5-flash-lite',
        );
        if (isset($deprecated[$model])) {
            $model = $deprecated[$model];
        }

        return $model;
    }
}

if (!function_exists('lc_gemini_image_model')) {
    function lc_gemini_image_model()
    {
        $model = trim((string) lc_settings_get('geminiImageModel', 'gemini-2.5-flash-image'));
        if ($model === '') {
            $model = 'gemini-2.5-flash-image';
        }

        $deprecated = array(
            'gemini-2.0-flash-preview-image-generation' => 'gemini-2.5-flash-image',
            'gemini-2.0-flash-exp-image-generation'     => 'gemini-2.5-flash-image',
            'imagen-3.0-generate-002'                  => 'gemini-2.5-flash-image',
            'imagen-4.0-generate-001'                  => 'gemini-2.5-flash-image',
            'imagen-4.0-fast-generate-001'             => 'gemini-2.5-flash-image',
            'imagen-4.0-ultra-generate-001'            => 'gemini-2.5-flash-image',
        );
        if (isset($deprecated[$model])) {
            $model = $deprecated[$model];
        }

        return $model;
    }
}

if (!function_exists('lc_gemini_generate')) {
    /**
     * @return array{ok:bool,message?:string,text?:string,raw?:array}
     */
    function lc_gemini_generate($prompt, array $options = array())
    {
        $api_key = lc_gemini_api_key();
        if ($api_key === '') {
            return array('ok' => false, 'message' => 'Gemini API 키가 설정되지 않았습니다.');
        }

        $model = isset($options['model']) ? (string) $options['model'] : lc_gemini_model();
        $temperature = isset($options['temperature']) ? (float) $options['temperature'] : 0.7;
        $max_tokens = isset($options['maxOutputTokens']) ? (int) $options['maxOutputTokens'] : 2048;
        $system = isset($options['system']) ? trim((string) $options['system']) : '';

        $parts = array();
        if ($system !== '') {
            $parts[] = array('text' => $system . "\n\n---\n\n" . (string) $prompt);
        } else {
            $parts[] = array('text' => (string) $prompt);
        }

        $payload = array(
            'contents' => array(
                array('parts' => $parts),
            ),
            'generationConfig' => array(
                'temperature'     => $temperature,
                'maxOutputTokens' => $max_tokens,
            ),
        );

        if (!empty($options['json']) || !empty($options['responseMimeType'])) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        return lc_gemini_request($model, $payload, $api_key);
    }
}

if (!function_exists('lc_gemini_chat')) {
    /**
     * @param array<int,array{role:string,text:string}> $messages
     * @return array{ok:bool,message?:string,text?:string,raw?:array}
     */
    function lc_gemini_chat(array $messages, array $options = array())
    {
        $api_key = lc_gemini_api_key();
        if ($api_key === '') {
            return array('ok' => false, 'message' => 'Gemini API 키가 설정되지 않았습니다.');
        }

        $model = isset($options['model']) ? (string) $options['model'] : lc_gemini_model();
        $system = isset($options['system']) ? trim((string) $options['system']) : '';
        $temperature = isset($options['temperature']) ? (float) $options['temperature'] : 0.5;
        $max_tokens = isset($options['maxOutputTokens']) ? (int) $options['maxOutputTokens'] : 2048;

        $contents = array();
        if ($system !== '') {
            $contents[] = array(
                'role'  => 'user',
                'parts' => array(array('text' => '[시스템 지침] ' . $system)),
            );
            $contents[] = array(
                'role'  => 'model',
                'parts' => array(array('text' => '네, 링크커넥트 AI 가이드로서 안내하겠습니다.')),
            );
        }

        foreach ($messages as $msg) {
            $role = isset($msg['role']) && $msg['role'] === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($msg['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $contents[] = array(
                'role'  => $role,
                'parts' => array(array('text' => $text)),
            );
        }

        if (empty($contents)) {
            return array('ok' => false, 'message' => '메시지가 비어 있습니다.');
        }

        $payload = array(
            'contents'         => $contents,
            'generationConfig' => array(
                'temperature'     => $temperature,
                'maxOutputTokens' => $max_tokens,
            ),
        );

        return lc_gemini_request($model, $payload, $api_key);
    }
}

if (!function_exists('lc_gemini_request')) {
    /**
     * @return array{ok:bool,message?:string,text?:string,images?:array<int,array{mime:string,binary:string}>,raw?:array}
     */
    function lc_gemini_request($model, array $payload, $api_key, array $options = array())
    {
        $model = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $model);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($api_key);
        $timeout = isset($options['timeout']) ? max(30, (int) $options['timeout']) : 60;
        $allow_empty_text = !empty($options['allowEmptyText']);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return array('ok' => false, 'message' => '요청 JSON 생성에 실패했습니다.');
        }

        $response = null;
        $http_code = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
            ));
            $response = curl_exec($ch);
            $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === false) {
                $err = curl_error($ch);
                curl_close($ch);

                return array('ok' => false, 'message' => 'Gemini API 연결 실패: ' . $err);
            }
            curl_close($ch);
        } else {
            $ctx = stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\n",
                    'content' => $json,
                    'timeout' => $timeout,
                ),
            ));
            $response = @file_get_contents($url, false, $ctx);
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $http_code = (int) $m[1];
            }
        }

        if ($response === false || $response === null || $response === '') {
            return array('ok' => false, 'message' => 'Gemini API 응답이 비어 있습니다.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return array('ok' => false, 'message' => 'Gemini API 응답 파싱 실패');
        }

        if ($http_code >= 400 || isset($decoded['error'])) {
            $msg = isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : 'Gemini API 오류 (HTTP ' . $http_code . ')';

            return array('ok' => false, 'message' => $msg, 'raw' => $decoded);
        }

        $text = '';
        $images = array();
        if (isset($decoded['candidates'][0]['content']['parts']) && is_array($decoded['candidates'][0]['content']['parts'])) {
            foreach ($decoded['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $text .= (string) $part['text'];
                }
                $inline = null;
                if (isset($part['inlineData']) && is_array($part['inlineData'])) {
                    $inline = $part['inlineData'];
                } elseif (isset($part['inline_data']) && is_array($part['inline_data'])) {
                    $inline = $part['inline_data'];
                }
                if (is_array($inline)) {
                    $mime = isset($inline['mimeType']) ? (string) $inline['mimeType'] : (isset($inline['mime_type']) ? (string) $inline['mime_type'] : '');
                    $data = isset($inline['data']) ? (string) $inline['data'] : '';
                    if ($mime !== '' && $data !== '') {
                        $binary = base64_decode($data, true);
                        if ($binary !== false && $binary !== '') {
                            $images[] = array(
                                'mime'   => $mime,
                                'binary' => $binary,
                            );
                        }
                    }
                }
            }
        }

        $text = trim($text);
        if ($text === '' && empty($images) && !$allow_empty_text) {
            return array('ok' => false, 'message' => 'Gemini가 빈 응답을 반환했습니다.', 'raw' => $decoded);
        }

        return array(
            'ok'     => true,
            'text'   => $text,
            'images' => $images,
            'raw'    => $decoded,
        );
    }
}

if (!function_exists('lc_gemini_generate_image')) {
    /**
     * Gemini 이미지 생성 모델로 이미지 바이너리 생성.
     *
     * @return array{ok:bool,message?:string,binary?:string,mime?:string,ext?:string,prompt?:string}
     */
    function lc_gemini_generate_image($prompt, array $options = array())
    {
        $api_key = lc_gemini_api_key();
        if ($api_key === '') {
            return array('ok' => false, 'message' => 'Gemini API 키가 설정되지 않았습니다.');
        }

        $prompt = trim((string) $prompt);
        if ($prompt === '') {
            return array('ok' => false, 'message' => '이미지 생성 프롬프트가 비어 있습니다.');
        }

        $model = isset($options['model']) ? (string) $options['model'] : lc_gemini_image_model();
        $aspect = isset($options['aspectRatio']) ? (string) $options['aspectRatio'] : '16:9';
        $width = isset($options['width']) ? (int) $options['width'] : 0;
        $height = isset($options['height']) ? (int) $options['height'] : 0;
        if ($width > 0 && $height > 0 && function_exists('lc_image_aspect_ratio_label')) {
            $aspect = lc_image_aspect_ratio_label($width, $height);
        }

        $payload = array(
            'contents' => array(
                array(
                    'role'  => 'user',
                    'parts' => array(array('text' => $prompt)),
                ),
            ),
            'generationConfig' => array(
                'responseModalities' => array('TEXT', 'IMAGE'),
                'imageConfig'        => array(
                    'aspectRatio' => $aspect,
                ),
            ),
        );

        $result = lc_gemini_request($model, $payload, $api_key, array(
            'timeout'        => 120,
            'allowEmptyText' => true,
        ));
        if (empty($result['ok'])) {
            return array(
                'ok'      => false,
                'message' => isset($result['message']) ? (string) $result['message'] : '이미지 생성에 실패했습니다.',
            );
        }

        $images = isset($result['images']) && is_array($result['images']) ? $result['images'] : array();
        if (empty($images[0]['binary'])) {
            return array('ok' => false, 'message' => 'Gemini가 이미지를 반환하지 않았습니다. 이미지 모델(gemini-2.5-flash-image)과 API 권한을 확인해 주세요.');
        }

        $mime = isset($images[0]['mime']) ? (string) $images[0]['mime'] : 'image/png';
        $binary = $images[0]['binary'];
        $ext = function_exists('lc_image_mime_to_ext') ? lc_image_mime_to_ext($mime) : 'png';
        if ($ext === '') {
            $ext = 'png';
            $mime = 'image/png';
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

if (!function_exists('lc_gemini_mask_key')) {
    function lc_gemini_mask_key($key)
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
