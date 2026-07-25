<?php
require_once dirname(__DIR__) . '/_common.php';

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ($method === 'GET') {
    lc_api_require_method('GET');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        $item = lc_community_get($id, true);
        if (!$item) {
            lc_api_error('게시글을 찾을 수 없습니다.', 'NOT_FOUND', 404);
        }
        lc_api_success(array(
            'item'        => $item,
            'permissions' => lc_community_permissions(),
            'boardReady'  => true,
        ));
    }

    lc_api_success(lc_community_list(array(
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
    $id = isset($body['id']) ? (int) $body['id'] : 0;

    if ($action === 'delete') {
        $result = lc_community_delete($id);
        if (empty($result['ok'])) {
            lc_api_error($result['message'], 'DELETE_FAILED', 400);
        }
        lc_api_success(array('message' => $result['message']));
    }

    $result = lc_community_save($body, $action === 'update' ? $id : 0);
    if (empty($result['ok'])) {
        lc_api_error($result['message'], 'SAVE_FAILED', 400);
    }

    lc_api_success(array(
        'message' => $result['message'],
        'item'    => $result['item'],
    ));
}

lc_api_error('허용되지 않은 HTTP 메서드입니다.', 'METHOD_NOT_ALLOWED', 405);
