<?php
if (!defined('_GNUBOARD_')) exit;

include_once G5_SKIN_PATH . '/_inc/onoff-platform.php';
onoff_platform_member_styles($member_skin_url);

$mb_name = get_text($mb['mb_name']);
$home_url = defined('G5_URL') ? rtrim(G5_URL, '/') . '/' : '/';
$center_url = $home_url . '#/select-center';
$partner_url = $home_url . '#/partner';
$advertiser_url = $home_url . '#/advertiser';
?>

<div class="<?php echo onoff_platform_member_shell_class(); ?>" style="max-width:560px">
<?php onoff_platform_member_top_bar(); ?>

<div id="reg_result" class="register onoff-platform__card onoff-reg-result">
    <?php onoff_platform_member_brand('가입 완료'); ?>

    <div class="onoff-reg-result__hero">
        <div class="onoff-reg-result__icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/>
                <path d="M8 12.5l2.5 2.5L16 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 class="onoff-reg-result__title">
            <strong><?php echo $mb_name; ?></strong>님,<br>회원가입을 축하합니다
        </h2>
        <p class="onoff-reg-result__lead">
            링크커넥트 계정이 준비되었습니다.<br>
            파트너센터 또는 광고주센터에서 바로 이용을 시작하세요.
        </p>
    </div>

    <?php if (is_use_email_certify()) { ?>
    <div class="onoff-reg-result__email">
        <p class="onoff-reg-result__email-note">
            입력하신 이메일로 인증 메일을 보냈습니다. 인증을 완료하면 서비스를 원활하게 이용할 수 있습니다.
        </p>
        <dl class="onoff-reg-result__meta">
            <div>
                <dt>아이디</dt>
                <dd><?php echo get_text($mb['mb_id']); ?></dd>
            </div>
            <div>
                <dt>이메일</dt>
                <dd><?php echo get_text($mb['mb_email']); ?></dd>
            </div>
        </dl>
        <p class="onoff-reg-result__hint">이메일 주소가 잘못되었다면 관리자에게 문의해 주세요.</p>
    </div>
    <?php } ?>

    <ul class="onoff-reg-result__notes">
        <li>비밀번호는 암호화되어 저장되며, 분실 시 가입 이메일로 찾을 수 있습니다.</li>
        <li>회원 탈퇴는 언제든지 가능하며, 일정 기간 후 정보는 삭제됩니다.</li>
    </ul>

    <div class="onoff-reg-result__actions">
        <a href="<?php echo htmlspecialchars($center_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn_submit onoff-reg-result__primary">센터 선택하기</a>
        <div class="onoff-reg-result__secondary">
            <a href="<?php echo htmlspecialchars($partner_url, ENT_QUOTES, 'UTF-8'); ?>" class="onoff-platform__outline-btn">파트너센터</a>
            <a href="<?php echo htmlspecialchars($advertiser_url, ENT_QUOTES, 'UTF-8'); ?>" class="onoff-platform__outline-btn">광고주센터</a>
        </div>
        <a href="<?php echo htmlspecialchars($home_url, ENT_QUOTES, 'UTF-8'); ?>" class="onoff-reg-result__home">메인으로</a>
    </div>
</div>

<?php onoff_platform_member_footer(); ?>
</div>
