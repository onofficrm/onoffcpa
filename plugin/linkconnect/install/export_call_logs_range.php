<?php
header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode(array('ok'=>false,'error'=>'GONE','code'=>'GONE'), JSON_UNESCAPED_UNICODE);
