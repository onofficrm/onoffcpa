<?php
/**
 * LinkConnect 이메일 알림 테스트 발송
 *
 * 브라우저/ curl:
 *   POST /plugin/linkconnect/install/test_email_notify.php
 *     token=...&to=email@example.com
 *
 * token: _site.config.php 의 linkconnect_install_token 또는 linkconnect_seed_token
 *        (비어 있으면 아래 임시 키 — 운영 확인 후 제거 권장)
 */
require_once dirname(__DIR__) . '/_common.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('lc_email_test_token_ok')) {
    function lc_email_test_token_ok()
    {
        $given = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
        if ($given === '') {
            return false;
        }

        $expected = '';
        if (function_exists('g5site_cfg')) {
            $expected = (string) g5site_cfg('linkconnect_seed_token', '');
            if ($expected === '') {
                $expected = (string) g5site_cfg('linkconnect_install_token', '');
            }
        }
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, $given);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['run'])) {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'POST required'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!lc_email_test_token_ok()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'Invalid token'), JSON_UNESCAPED_UNICODE);
    exit;
}

$to = isset($_REQUEST['to']) ? trim((string) $_REQUEST['to']) : '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Valid to email required'), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('lc_email_notify_send')) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'lc_email_notify_send unavailable'), JSON_UNESCAPED_UNICODE);
    exit;
}

global $config;
$site = function_exists('lc_site_name') ? lc_site_name() : 'LinkConnect';
$from = !empty($config['cf_admin_email']) ? (string) $config['cf_admin_email'] : '';
$email_use = !empty($config['cf_email_use']);

$subject = '[' . $site . '] 이메일 알림 테스트';
$body = '<p><strong>링크커넥트 이메일 알림 테스트입니다.</strong></p>'
    . '<p>이 메일이 도착했다면 서버 mailer 및 알림 발송 경로가 정상 동작하는 것입니다.</p>'
    . '<table style="border-collapse:collapse;font-size:14px;">'
    . '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">시각</th>'
    . '<td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '<tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #e2e8f0;">수신</th>'
    . '<td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">' . htmlspecialchars($to, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '</table>';

$sent = lc_email_notify_send($to, $subject, $body);

echo json_encode(array(
    'ok'        => (bool) $sent,
    'message'   => $sent ? '테스트 메일을 발송했습니다.' : '메일 발송에 실패했습니다. cf_email_use / SMTP / 관리자 메일을 확인하세요.',
    'to'        => $to,
    'from'      => $from,
    'emailUse'  => $email_use,
    'mailer'    => function_exists('mailer'),
    'sentAt'    => date('c'),
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
