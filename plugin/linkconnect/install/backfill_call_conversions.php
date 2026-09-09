<?php
/**
 * One-shot endpoint retired. Use admin Call DB rematch (createConversions)
 * or admin API action=backfill_conversions.
 */
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
    'ok' => false,
    'error' => 'GONE',
    'message' => '일회 백필 엔드포인트는 종료되었습니다. 관리자 콜디비 재매칭을 사용하세요.',
), JSON_UNESCAPED_UNICODE);
