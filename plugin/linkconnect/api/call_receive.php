<?php
/**
 * @deprecated 콜디비는 수동 운영(엑셀 업로드)만 지원합니다. 이 웹훅 엔드포인트는 사용하지 않습니다.
 */
require_once dirname(__DIR__) . '/_common.php';

lc_api_json(array(
    'success' => false,
    'error'   => '콜디비 API 연동이 비활성화되었습니다. 관리자 화면에서 통화내역 엑셀 업로드를 이용해 주세요.',
    'code'    => 'CALL_API_DISABLED',
), 410);
