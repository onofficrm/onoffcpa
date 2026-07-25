<?php
/**
 * 콜 녹음 파일 스트리밍 (요청자·관리자만)
 */
require_once dirname(__DIR__) . '/_common.php';

$crr_id = isset($_GET['crrId']) ? (int) $_GET['crrId'] : 0;
if ($crr_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('crrId가 필요합니다.');
}

if (!function_exists('lc_call_recording_request_send_file')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('녹음 모듈을 사용할 수 없습니다.');
}

if (!lc_is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('로그인이 필요합니다.');
}

lc_call_recording_request_send_file($crr_id);
