<?php
/**
 * 공개 별칭: /api/external/linkprice/postback → 플러그인 수신기로 위임
 * api/external/linkprice → 3단계 상위 = 사이트 루트(public_html)
 */
require dirname(__DIR__, 3) . '/plugin/linkconnect/api/lp_postback.php';
