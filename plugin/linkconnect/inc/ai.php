<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('lc_ai_usage_file')) {
    function lc_ai_usage_file()
    {
        $dir = G5_DATA_PATH . '/linkconnect';
        if (!is_dir($dir)) {
            @mkdir($dir, G5_DIR_PERMISSION, true);
        }

        return $dir . '/ai_usage.json';
    }
}

if (!function_exists('lc_ai_usage_read')) {
    function lc_ai_usage_read()
    {
        $file = lc_ai_usage_file();
        if (!is_file($file)) {
            return array();
        }
        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : array();
    }
}

if (!function_exists('lc_ai_usage_write')) {
    function lc_ai_usage_write(array $data)
    {
        file_put_contents(lc_ai_usage_file(), json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}

if (!function_exists('lc_ai_actor_id')) {
    function lc_ai_actor_id()
    {
        global $member;

        if (isset($member['mb_id']) && $member['mb_id'] !== '') {
            return 'mb:' . $member['mb_id'];
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'guest';

        return 'ip:' . $ip;
    }
}

if (!function_exists('lc_ai_rate_limit')) {
    function lc_ai_rate_limit($action, $limit = 0)
    {
        $action = preg_replace('/[^a-z_]/', '', (string) $action);
        if ($limit <= 0) {
            $map = array(
                'chat'    => lc_settings_get_int('aiChatDailyLimit', 30),
                'promo'   => lc_settings_get_int('aiPromoDailyLimit', 20),
                'summary' => lc_settings_get_int('aiSummaryDailyLimit', 10),
                'image'   => lc_settings_get_int('aiImageDailyLimit', 15),
            );
            $limit = isset($map[$action]) ? (int) $map[$action] : 20;
        }

        $actor = lc_ai_actor_id();
        $day = date('Y-m-d');
        $key = $day . '|' . $actor . '|' . $action;

        $usage = lc_ai_usage_read();
        $count = (int) ($usage[$key] ?? 0);
        if ($count >= $limit) {
            return array(
                'ok'      => false,
                'message' => '오늘 AI 사용 한도(' . $limit . '회)에 도달했습니다. 내일 다시 시도해주세요.',
                'remaining' => 0,
                'limit'   => $limit,
            );
        }

        $usage[$key] = $count + 1;
        foreach ($usage as $k => $v) {
            if (strpos($k, $day . '|') !== 0) {
                unset($usage[$k]);
            }
        }
        lc_ai_usage_write($usage);

        return array(
            'ok'        => true,
            'remaining' => max(0, $limit - $count - 1),
            'limit'     => $limit,
        );
    }
}

if (!function_exists('lc_ai_status_for_api')) {
    function lc_ai_status_for_api()
    {
        $enabled = lc_gemini_enabled();
        $actor = lc_ai_actor_id();

        return array(
            'available' => $enabled,
            'model'     => lc_gemini_model(),
            'limits'    => array(
                'chat'    => lc_settings_get_int('aiChatDailyLimit', 30),
                'promo'   => lc_settings_get_int('aiPromoDailyLimit', 20),
                'summary' => lc_settings_get_int('aiSummaryDailyLimit', 10),
                'image'   => lc_settings_get_int('aiImageDailyLimit', 15),
            ),
            'imageModel'=> function_exists('lc_openai_image_model') ? lc_openai_image_model() : '',
            'imageProvider' => 'openai',
            'imageAvailable' => function_exists('lc_openai_image_enabled') ? lc_openai_image_enabled() : false,
            'message'   => $enabled ? '' : '관리자 설정에서 Gemini API 키를 등록해주세요.',
        );
    }
}

if (!function_exists('lc_ai_image_prompt_template_default')) {
    function lc_ai_image_prompt_template_default($kind = 'thumbnail')
    {
        if ($kind === 'promo') {
            return 'Create a clean Korean marketing promotional image for affiliate advertising. '
                . 'Campaign: {title}. Category: {category}. '
                . 'Target size about {width}x{height}px ({aspect}). '
                . 'Mood/atmosphere: {mood}. '
                . 'Modern flat illustration style with soft teal and slate colors. '
                . 'No logos, no watermarks, no phone numbers, no brand marks. '
                . '{text_instruction} '
                . 'Suitable for web banners and blog headers. {extra}';
        }

        return 'Create a clean Korean CPA product list thumbnail. '
            . 'Campaign name: {title}. Category: {category}. '
            . 'Exact composition for {width}x{height}px ({aspect}) landscape. '
            . 'Mood/atmosphere: {mood}. Soft teal accent, slate neutrals. '
            . 'No logos, no watermarks, no phone numbers. '
            . '{text_instruction} '
            . 'Photorealistic or high-quality illustration of a calm consultation atmosphere. {extra}';
    }
}

if (!function_exists('lc_ai_image_prompt_template')) {
    function lc_ai_image_prompt_template($kind = 'thumbnail')
    {
        $kind = $kind === 'promo' ? 'promo' : 'thumbnail';
        $key = $kind === 'promo' ? 'aiImagePromptPromo' : 'aiImagePromptThumbnail';
        $tpl = trim((string) lc_settings_get($key, ''));
        if ($tpl === '') {
            $tpl = lc_ai_image_prompt_template_default($kind);
        }

        return $tpl;
    }
}

if (!function_exists('lc_ai_normalize_image_options')) {
    /**
     * @param array<string,mixed> $input
     * @return array{mood:string,includeText:bool,overlayText:string,extra:string}
     */
    function lc_ai_normalize_image_options(array $input = array())
    {
        $mood = trim((string) ($input['mood'] ?? ''));
        $include_text = !empty($input['includeText']) || !empty($input['include_text']);
        $overlay = trim((string) ($input['overlayText'] ?? $input['overlay_text'] ?? $input['text'] ?? ''));
        $extra = trim((string) ($input['extra'] ?? ''));

        if ($mood === '') {
            $mood = '신뢰감 있는';
        }
        if (!$include_text) {
            $overlay = '';
        }

        return array(
            'mood'        => $mood,
            'includeText' => $include_text,
            'overlayText' => $overlay,
            'extra'       => $extra,
        );
    }
}

if (!function_exists('lc_ai_image_text_instruction')) {
    function lc_ai_image_text_instruction($include_text, $overlay_text)
    {
        $include_text = (bool) $include_text;
        $overlay_text = trim((string) $overlay_text);

        if ($include_text && $overlay_text !== '') {
            $escaped = str_replace(array("\r\n", "\r"), "\n", $overlay_text);
            return 'TEXT REQUIREMENT (critical): Render ONLY the following Korean text exactly, '
                . 'with correct Hangul spelling and clear legible typography. '
                . 'Do not invent, misspell, rearrange, or add any other words, letters, or numbers. '
                . 'Exact text to show (preserve line breaks): <<<' . $escaped . '>>>';
        }

        return 'TEXT REQUIREMENT (critical): Do NOT render any text, Hangul characters, Latin letters, '
            . 'numbers, captions, slogans, watermarks, or logos inside the image. '
            . 'Pure visual imagery only. Leave clean negative space so marketers can overlay text later.';
    }
}

if (!function_exists('lc_ai_build_image_prompt')) {
    /**
     * @param array{title?:string,category?:string,width?:int,height?:int,extra?:string,kind?:string,mood?:string,includeText?:bool,overlayText?:string} $context
     */
    function lc_ai_build_image_prompt(array $context = array())
    {
        $kind = isset($context['kind']) && $context['kind'] === 'promo' ? 'promo' : 'thumbnail';
        $title = trim((string) ($context['title'] ?? ''));
        $category = trim((string) ($context['category'] ?? ''));
        $opts = lc_ai_normalize_image_options($context);
        $extra = $opts['extra'];
        $width = isset($context['width']) ? (int) $context['width'] : 0;
        $height = isset($context['height']) ? (int) $context['height'] : 0;
        $aspect = ($width > 0 && $height > 0 && function_exists('lc_image_aspect_ratio_label'))
            ? lc_image_aspect_ratio_label($width, $height)
            : '16:9';

        if ($title === '') {
            $title = '제휴 마케팅 상담';
        }
        if ($category === '') {
            $category = '일반';
        }

        $text_instruction = lc_ai_image_text_instruction($opts['includeText'], $opts['overlayText']);
        if ($extra === '') {
            $extra = $opts['includeText']
                ? 'Keep composition balanced so the requested text remains readable.'
                : 'Keep the composition uncluttered with generous negative space for optional text overlays later.';
        }

        $tpl = lc_ai_image_prompt_template($kind);
        $map = array(
            '{title}'            => $title,
            '{category}'         => $category,
            '{width}'            => (string) max(0, $width),
            '{height}'           => (string) max(0, $height),
            '{aspect}'           => $aspect,
            '{mood}'             => $opts['mood'],
            '{text_instruction}' => $text_instruction,
            '{extra}'            => $extra,
        );

        return trim(strtr($tpl, $map));
    }
}

if (!function_exists('lc_ai_generate_campaign_image')) {
    /**
     * @return array{ok:bool,message?:string,binary?:string,mime?:string,ext?:string,prompt?:string}
     */
    function lc_ai_generate_campaign_image(array $context = array())
    {
        if (!function_exists('lc_openai_generate_image')) {
            return array('ok' => false, 'message' => 'OpenAI 이미지 생성 모듈을 사용할 수 없습니다.');
        }

        $prompt = lc_ai_build_image_prompt($context);
        $width = isset($context['width']) ? (int) $context['width'] : 0;
        $height = isset($context['height']) ? (int) $context['height'] : 0;

        return lc_openai_generate_image($prompt, array(
            'width'  => $width,
            'height' => $height,
        ));
    }
}

if (!function_exists('lc_ai_require_ready')) {
    function lc_ai_require_ready($action)
    {
        $action = (string) $action;
        if ($action === 'image') {
            if (!function_exists('lc_openai_image_enabled') || !lc_openai_image_enabled()) {
                lc_api_error(
                    '이미지 생성용 OpenAI(ChatGPT) API 키가 설정되지 않았습니다. 관리자 설정을 확인해 주세요.',
                    'AI_IMAGE_NOT_CONFIGURED',
                    503
                );
            }
        } elseif (!lc_gemini_enabled()) {
            lc_api_error('AI 기능이 비활성화되었거나 API 키가 설정되지 않았습니다.', 'AI_NOT_CONFIGURED', 503);
        }

        $rate = lc_ai_rate_limit($action);
        if (!$rate['ok']) {
            lc_api_error($rate['message'], 'AI_RATE_LIMIT', 429, array(
                'remaining' => 0,
                'limit'     => $rate['limit'],
            ));
        }

        return $rate;
    }
}

if (!function_exists('lc_ai_guide_system_prompt')) {
    function lc_ai_guide_system_prompt(array $context = array())
    {
        $site = (string) lc_settings_get('siteName', '링크커넥트');
        $phone = (string) lc_settings_get('supportPhone', '');

        $lines = array(
            '당신은 ' . $site . ' 제휴마케팅 플랫폼의 AI 가이드입니다.',
            '한국어로 친절하고 간결하게 답변하세요. 3~8문장 이내를 권장합니다.',
            'CPA/CPS, 파트너, 광고주, 디비(DB), 승인, 취소, 정산, 이벤트, 랭킹 용어를 사용하세요.',
            '확실하지 않은 정책은 "관리자/고객센터 확인"을 안내하세요. 허위·과장 홍보는 금지입니다.',
            '개인정보(이름, 전화, 계좌)는 요청하지 마세요.',
        );

        if ($phone !== '') {
            $lines[] = '고객센터: ' . $phone;
        }

        if (!empty($context['role'])) {
            $lines[] = '현재 사용자 역할: ' . (string) $context['role'];
        }
        if (!empty($context['page'])) {
            $lines[] = '현재 화면: ' . (string) $context['page'];
        }

        $lines[] = '주요 메뉴: 회사소개(/about), 제휴마케팅이란?(/affiliate), 공지(/notice), CPA(/cpa-list), CPS(/cps), 이벤트(/events), 파트너센터(/partner), 광고주센터(/advertiser), 관리자(/admin).';

        return implode("\n", $lines);
    }
}

if (!function_exists('lc_ai_chat')) {
    function lc_ai_chat($message, array $history = array(), array $context = array())
    {
        $message = trim((string) $message);
        if ($message === '') {
            return array('ok' => false, 'message' => '질문을 입력해주세요.');
        }

        $messages = array();
        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }
            $role = (string) ($item['role'] ?? 'user');
            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $messages[] = array(
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'text' => mb_substr($text, 0, 2000, 'UTF-8'),
            );
        }
        $messages[] = array('role' => 'user', 'text' => mb_substr($message, 0, 2000, 'UTF-8'));

        $result = lc_gemini_chat($messages, array(
            'system'          => lc_ai_guide_system_prompt($context),
            'temperature'     => 0.4,
            'maxOutputTokens' => 1024,
        ));

        if (!$result['ok']) {
            return $result;
        }

        return array(
            'ok'       => true,
            'reply'    => (string) $result['text'],
            'fallback' => false,
        );
    }
}

if (!function_exists('lc_ai_promo_generate')) {
    function lc_ai_promo_generate(array $payload)
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return array('ok' => false, 'message' => '캠페인 제목이 필요합니다.');
        }

        $category = trim((string) ($payload['category'] ?? ''));
        $price = trim((string) ($payload['price'] ?? ''));
        $approval = trim((string) ($payload['approvalRate'] ?? ''));
        $allowed = trim((string) ($payload['allowedChannels'] ?? ''));
        $forbidden = trim((string) ($payload['forbiddenChannels'] ?? ''));
        $channel = trim((string) ($payload['channel'] ?? 'all'));
        $event = trim((string) ($payload['eventTitle'] ?? ''));
        $target = trim((string) ($payload['targetAudience'] ?? ''));

        $prompt = "다음 CPA 캠페인 홍보 문구를 작성하세요.\n\n";
        $prompt .= "캠페인: {$title}\n";
        if ($target !== '') $prompt .= "타겟 고객: {$target}\n";
        if ($category !== '') $prompt .= "카테고리: {$category}\n";
        if ($price !== '') $prompt .= "파트너 단가: {$price}\n";
        if ($approval !== '') $prompt .= "승인율: {$approval}\n";
        if ($allowed !== '') $prompt .= "허용 채널: {$allowed}\n";
        if ($forbidden !== '') $prompt .= "금지 채널: {$forbidden}\n";
        if ($event !== '') $prompt .= "연결 이벤트: {$event}\n";
        $prompt .= "\n요구사항:\n";
        $prompt .= "- 과장·허위 표현 금지, 금융/법률 광고 유의\n";
        $prompt .= "- [링크] 자리표시자 포함\n";
        $prompt .= "- JSON만 출력: {\"copies\":[{\"id\":\"blog_title\",\"label\":\"블로그 제목\",\"text\":\"...\"}, ...]}\n";
        $prompt .= "- id 목록: blog_title, blog_body, cafe, sns, youtube, kakao\n";
        if ($channel !== '' && $channel !== 'all') {
            $prompt .= "- 요청 채널만 생성: {$channel}\n";
        }

        $result = lc_gemini_generate($prompt, array(
            'json'            => true,
            'temperature'     => 0.8,
            'maxOutputTokens' => 2048,
            'system'          => '당신은 한국 CPA 제휴마케팅 카피라이터입니다. 컴플라이언스를 지키며 전환율 높은 문구를 작성합니다.',
        ));

        if (!$result['ok']) {
            return lc_ai_promo_fallback($payload);
        }

        $parsed = lc_ai_parse_promo_json($result['text']);
        if (!$parsed['ok']) {
            return lc_ai_promo_fallback($payload);
        }

        return array(
            'ok'       => true,
            'copies'   => $parsed['copies'],
            'fallback' => false,
        );
    }
}

if (!function_exists('lc_ai_parse_promo_json')) {
    function lc_ai_parse_promo_json($text)
    {
        $text = trim((string) $text);
        $text = preg_replace('/^```json\s*|\s*```$/u', '', $text);
        $decoded = json_decode($text, true);
        if (!is_array($decoded) || empty($decoded['copies']) || !is_array($decoded['copies'])) {
            return array('ok' => false);
        }

        $copies = array();
        foreach ($decoded['copies'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $copy_text = trim((string) ($item['text'] ?? ''));
            if ($copy_text === '') {
                continue;
            }
            $copies[] = array(
                'id'    => (string) ($item['id'] ?? 'custom'),
                'label' => (string) ($item['label'] ?? '홍보 문구'),
                'text'  => $copy_text,
            );
        }

        if (empty($copies)) {
            return array('ok' => false);
        }

        return array('ok' => true, 'copies' => $copies);
    }
}

if (!function_exists('lc_ai_promo_fallback')) {
    function lc_ai_promo_fallback(array $payload)
    {
        $title = trim((string) ($payload['title'] ?? 'CPA 캠페인'));
        $price = trim((string) ($payload['price'] ?? ''));
        $bonus = $price !== '' ? " 이벤트 단가 {$price}!" : '';

        $samples = function_exists('lc_sample_promo_copy_tabs') ? lc_sample_promo_copy_tabs() : array();
        $copies = array();
        foreach ($samples as $tab) {
            foreach ($tab['copies'] ?? array() as $copy) {
                $text = str_replace('개인회생', $title, (string) ($copy['text'] ?? ''));
                $copies[] = array(
                    'id'    => (string) ($tab['id'] ?? 'custom'),
                    'label' => (string) ($tab['label'] ?? '홍보'),
                    'text'  => $text . $bonus . ' [링크]',
                );
            }
        }

        if (empty($copies)) {
            $copies[] = array(
                'id'    => 'sns',
                'label' => 'SNS 문구',
                'text'  => "🔥 {$title}{$bonus} 안정적인 승인율의 CPA 캠페인입니다. 지금 무료 상담 신청 ▶ [링크]",
            );
        }

        return array(
            'ok'       => true,
            'copies'   => array_slice($copies, 0, 6),
            'fallback' => true,
            'message'  => 'AI 응답을 사용할 수 없어 샘플 문구를 제공합니다.',
        );
    }
}

if (!function_exists('lc_ai_admin_summary_data')) {
    function lc_ai_admin_summary_data()
    {
        if (lc_db_installed() && function_exists('lc_admin_dashboard_data')) {
            $data = lc_admin_dashboard_data();
            if (is_array($data)) {
                return $data;
            }
        }

        return array(
            'summary' => array(
                'todayReceived' => 248,
                'todayApproved' => 173,
                'todayRejected' => 42,
                'todayRate'     => 69.7,
                'todayRevenue'  => 8650000,
            ),
            'chart7d' => function_exists('lc_sample_admin_chart_7d') ? lc_sample_admin_chart_7d() : array(),
        );
    }
}

if (!function_exists('lc_ai_merchant_summary_data')) {
    function lc_ai_merchant_summary_data($mt_id)
    {
        if (function_exists('lc_conversion_merchant_report_for_api')) {
            return lc_conversion_merchant_report_for_api((int) $mt_id);
        }

        return array('summary' => array(), 'campaigns' => array(), 'partners' => array());
    }
}

if (!function_exists('lc_ai_build_summary_prompt')) {
    function lc_ai_build_summary_prompt(array $report, $role = 'admin')
    {
        $json = json_encode($report, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }

        $role_label = $role === 'merchant' ? '광고주' : '플랫폼 관리자';

        return "다음은 {$role_label}용 성과 데이터(JSON)입니다. 5~7줄 한국어 브리핑을 작성하세요.\n"
            . "- 핵심 수치(접수/승인/취소/승인율/매출) 요약\n"
            . "- 전주 대비 또는 추세 코멘트 1줄\n"
            . "- 주의/개선 포인트 1~2줄\n"
            . "- 불릿 목록 형식, 마크다운 헤더 없이 plain text\n\n"
            . $json;
    }
}

if (!function_exists('lc_ai_report_summary')) {
    function lc_ai_report_summary(array $report, $role = 'admin')
    {
        $result = lc_gemini_generate(lc_ai_build_summary_prompt($report, $role), array(
            'system'          => '당신은 제휴마케팅 데이터 분석가입니다. 간결하고 실행 가능한 인사이트를 제공합니다.',
            'temperature'     => 0.3,
            'maxOutputTokens' => 1024,
        ));

        if (!$result['ok']) {
            return lc_ai_report_summary_fallback($report, $role);
        }

        return array(
            'ok'       => true,
            'summary'  => (string) $result['text'],
            'fallback' => false,
        );
    }
}

if (!function_exists('lc_ai_report_summary_fallback')) {
    function lc_ai_report_summary_fallback(array $report, $role = 'admin')
    {
        $s = isset($report['summary']) && is_array($report['summary']) ? $report['summary'] : array();
        $received = (int) ($s['todayReceived'] ?? ($s['total'] ?? 0));
        $approved = (int) ($s['todayApproved'] ?? ($s['approved'] ?? 0));
        $rejected = (int) ($s['todayRejected'] ?? ($s['rejected'] ?? 0));
        $rate = (float) ($s['todayRate'] ?? ($s['avgRate'] ?? 0));

        $text = "• 접수 {$received}건 / 승인 {$approved}건 / 취소·무효 {$rejected}건\n";
        $text .= '• 승인율 약 ' . round($rate, 1) . "%\n";
        $text .= '• AI 요약을 생성할 수 없어 기본 통계만 표시합니다. Gemini API 키를 확인해주세요.';

        return array(
            'ok'       => true,
            'summary'  => $text,
            'fallback' => true,
        );
    }
}
