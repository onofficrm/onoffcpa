<?php
if (!defined('_GNUBOARD_')) {
    exit;
}
lc_ui_partner_intro('취소·무효 처리된 디비와 광고주 코멘트를 확인하고 이의신청할 수 있습니다.');

lc_ui_summary_grid(array(
    array('label' => '취소/무효 DB', 'value' => '211', 'suffix' => '건', 'icon' => '✕', 'tone' => 'red'),
    array('label' => '이의신청 가능', 'value' => '2', 'suffix' => '건', 'icon' => '📝', 'tone' => 'default'),
    array('label' => '이의신청 완료', 'value' => '1', 'suffix' => '건', 'icon' => '⏳', 'tone' => 'default'),
    array('label' => '환수 예상 금액', 'value' => '690,000', 'suffix' => '원', 'icon' => '💰', 'tone' => 'dark'),
), '4');

foreach (lc_sample_partner_canceled() as $row) {
    ?>
<article class="lc-cancel-card">
  <div class="lc-cancel-card__head">
    <div>
      <strong><?php echo lc_h($row['id']); ?></strong>
      <span class="lc-muted" style="margin-left:0.5rem;font-size:0.8rem"><?php echo lc_h($row['date']); ?></span>
    </div>
    <?php echo lc_ui_partner_status_badge($row['status']); ?>
  </div>
  <h3 style="margin:0 0 0.35rem;font-size:1rem"><?php echo lc_h($row['campaign']); ?></h3>
  <p class="lc-muted" style="margin:0 0 0.5rem;font-size:0.85rem">고객: <span class="lc-mask"><?php echo lc_h($row['name']); ?></span> · <span class="lc-mask"><?php echo lc_h($row['phone']); ?></span></p>
  <span class="lc-cancel-card__reason">취소 사유: <?php echo lc_h($row['reason']); ?></span>
  <p class="lc-cancel-card__comment"><strong>광고주 코멘트:</strong> <?php echo lc_h($row['comment']); ?></p>
  <div class="lc-cancel-card__appeal">
    <?php if (!empty($row['appeal'])) { ?>
    <button type="button" class="lc-btn lc-btn--sm lc-btn--primary">이의신청하기</button>
    <button type="button" class="lc-btn lc-btn--sm lc-btn--outline">상세 보기</button>
    <?php } else { ?>
    <span class="lc-muted" style="font-size:0.8rem">이의신청 기간이 만료되었습니다.</span>
    <?php } ?>
  </div>
</article>
    <?php
}
