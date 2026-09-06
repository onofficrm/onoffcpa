<?php
/**
 * /login → 그누보드 회원 로그인으로 리다이렉트
 * (짧은 URL이 게시판 bo_table=login 으로 오인되어 "존재하지 않는 게시판"이 뜨는 문제 방지)
 */
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
header('Location: /bbs/login.php' . $qs, true, 302);
exit;
