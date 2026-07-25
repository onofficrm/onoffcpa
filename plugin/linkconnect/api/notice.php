<?php
require_once dirname(__DIR__) . '/_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    lc_api_require_method('GET');

    $wr_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($wr_id > 0) {
        $item = lc_notice_board_get($wr_id, true);
        if (!$item) {
            lc_api_error('공지사항을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }

        lc_api_success(array(
            'item'        => $item,
            'permissions' => lc_notice_board_permissions(),
            'boardReady'  => lc_notice_board_ready(),
        ));
    }

    lc_api_success(lc_notice_board_list(array(
        'page'    => isset($_GET['page']) ? (int) $_GET['page'] : 1,
        'q'       => isset($_GET['q']) ? (string) $_GET['q'] : '',
        'perPage' => isset($_GET['perPage']) ? (int) $_GET['perPage'] : 15,
    )));
}

if ($method === 'POST') {
    lc_api_require_method('POST');
    lc_api_require_login();

    $body = lc_api_read_json_body();
    $action = isset($body['action']) ? (string) $body['action'] : 'save';
    $wr_id = isset($body['id']) ? (int) $body['id'] : 0;

    if ($action === 'delete') {
        if ($wr_id <= 0) {
            lc_api_error('게시글 ID가 필요합니다.', 'INVALID_REQUEST', 400);
        }

        $result = lc_notice_board_delete($wr_id);
        if (!$result['ok']) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }

        lc_api_success(array('message' => $result['message']));
    }

    $result = lc_notice_board_save($body, $action === 'update' ? $wr_id : 0);
    if (!$result['ok']) {
        lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'item'    => $result['item'],
    ));
}

lc_api_error('허용되지 않은 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
