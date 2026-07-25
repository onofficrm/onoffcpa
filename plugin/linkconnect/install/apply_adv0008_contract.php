<?php
/**
 * 관리자 1회 적용: ADV-0008(김장수/모두의철거) 첨부 계약서
 *
 * 브라우저: /plugin/linkconnect/install/apply_adv0008_contract.php
 * (최고관리자 로그인 필요)
 */
require_once dirname(__DIR__) . '/_common.php';

if (!function_exists('lc_is_super_admin') || !lc_is_super_admin()) {
    alert('최고관리자만 실행할 수 있습니다.', G5_URL);
}

$force = isset($_GET['force']) && (string) $_GET['force'] === '1';
$do = isset($_POST['do']) && (string) $_POST['do'] === '1';

$result = null;
if ($do) {
    $result = lc_merchant_contract_custom_ensure_adv0008($force || !empty($_POST['force']));
    if (!is_array($result)) {
        $result = lc_merchant_contract_admin_apply_custom_document(array(
            'mtCode'      => 'ADV-0008',
            'documentKey' => 'adv-0008-moduicheolge',
            'force'       => $force || !empty($_POST['force']),
            'partyOverrides' => array(
                'company_name'        => '모두의철거',
                'representative_name' => '김장수',
                'signer_name'         => '김장수',
                'signer_position'     => '대표',
            ),
        ));
    }
}

$merchant = lc_get_merchant_by_code('ADV-0008');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>ADV-0008 첨부 계약서 적용</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Noto Sans KR", sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; color: #0f172a; }
    .card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; background: #fff; }
    .ok { color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px; border-radius: 8px; }
    .err { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; padding: 12px; border-radius: 8px; }
    button { background: #0891b2; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
    label { display: flex; gap: 8px; align-items: center; margin: 12px 0; font-size: 14px; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>ADV-0008 첨부 계약서 적용</h1>
    <p>원본: <code>모두의철거 x 링크커넥트 계약서.docx</code></p>
    <p>광고주:
      <?php if (is_array($merchant)) { ?>
        <strong><?php echo htmlspecialchars((string) ($merchant['mt_company'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
        (<code><?php echo htmlspecialchars((string) ($merchant['mt_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>,
        mb_id=<code><?php echo htmlspecialchars((string) ($merchant['mb_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>)
      <?php } else { ?>
        <span style="color:#b91c1c">ADV-0008 광고주를 찾을 수 없습니다.</span>
      <?php } ?>
    </p>

    <?php if (is_array($result)) { ?>
      <div class="<?php echo !empty($result['ok']) ? 'ok' : 'err'; ?>" style="margin:16px 0;">
        <?php echo htmlspecialchars((string) ($result['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        <?php if (!empty($result['contractCode'])) { ?>
          <div style="margin-top:6px;">계약번호: <code><?php echo htmlspecialchars((string) $result['contractCode'], ENT_QUOTES, 'UTF-8'); ?></code></div>
        <?php } ?>
      </div>
    <?php } ?>

    <?php if (is_array($merchant)) { ?>
      <form method="post">
        <input type="hidden" name="do" value="1">
        <label>
          <input type="checkbox" name="force" value="1" <?php echo $force ? 'checked' : ''; ?>>
          이미 체결된 경우에도 강제로 다시 적용
        </label>
        <button type="submit">첨부 계약서 적용 (체결 완료)</button>
      </form>
    <?php } ?>

    <p style="margin-top:20px;font-size:13px;color:#64748b;">
      관리자 화면: <a href="/admin/contracts">/admin/contracts</a>
    </p>
  </div>
</body>
</html>
