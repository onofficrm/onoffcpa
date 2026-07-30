import type { PromoGuideFormState, PromoGuideLimits } from './campaignPromoGuide';
import { normalizePromoGuideList, validatePromoGuideForm } from './campaignPromoGuide';

/** 전체 입점 온보딩 체크리스트 (허브) */
export const ADVERTISER_ONBOARDING_PHASES = [
  { id: 'contract', label: 'CPA 계약 체결', description: '회사 정보 확인 · 동의 · 서명' },
  { id: 'campaign', label: '광고상품 확인', description: '운영할 캠페인 준비' },
  { id: 'guide', label: '홍보 가이드 작성', description: '키워드 · 이미지 · 운영 규칙' },
] as const;

export type AdvertiserOnboardingPhaseId = (typeof ADVERTISER_ONBOARDING_PHASES)[number]['id'];

/** 홍보 가이드 위저드 스텝 */
export const PROMO_GUIDE_WIZARD_STEPS = [
  { id: 1, label: '홍보 콘텐츠', description: '포인트 · 추천키워드 · 금지어' },
  { id: 2, label: '소재 업로드', description: '블로그 · 카페 · 웹 사이즈 안내' },
  { id: 3, label: '운영 규칙 · 제출', description: '유의사항 · DB 기준 · 승인 방식' },
] as const;

const STEP_STORAGE_PREFIX = 'lc_promo_guide_step_';

export function readPromoGuideWizardStep(cpId: number): number {
  try {
    const raw = sessionStorage.getItem(`${STEP_STORAGE_PREFIX}${cpId}`);
    const n = Number(raw);
    if (n === 1 || n === 2 || n === 3) return n;
  } catch {
    // ignore
  }
  return 1;
}

export function writePromoGuideWizardStep(cpId: number, step: number) {
  try {
    sessionStorage.setItem(`${STEP_STORAGE_PREFIX}${cpId}`, String(step));
  } catch {
    // ignore
  }
}

export function clearPromoGuideWizardStep(cpId: number) {
  try {
    sessionStorage.removeItem(`${STEP_STORAGE_PREFIX}${cpId}`);
  } catch {
    // ignore
  }
}

export function clampPromoGuideWizardStep(step: number): 1 | 2 | 3 {
  if (step <= 1) return 1;
  if (step >= 3) return 3;
  return step as 2;
}

/** 스텝 이동 시 클라이언트 검증 (임시저장은 검증 생략) */
export function validatePromoGuideWizardStep(
  step: 1 | 2 | 3,
  form: PromoGuideFormState,
  limits: PromoGuideLimits,
): Record<string, string> {
  if (step === 1) {
    const points = normalizePromoGuideList(form.promotionPoints, limits.promotion_points);
    const errors: Record<string, string> = {};
    if (points.length === 0) {
      errors.promotionPoints = '핵심 홍보 포인트를 1개 이상 입력해 주세요.';
    }
    if (form.recommendedKeywords.length > limits.recommended_keywords) {
      errors.recommendedKeywords = `최대 ${limits.recommended_keywords}개까지 등록할 수 있습니다.`;
    }
    if (form.forbiddenWords.length > limits.forbidden_words) {
      errors.forbiddenWords = `최대 ${limits.forbidden_words}개까지 등록할 수 있습니다.`;
    }
    return errors;
  }

  if (step === 2) {
    // 이미지는 선택 — 스킵 가능
    return {};
  }

  return validatePromoGuideForm(form, limits);
}

export function isGuideOnboardingComplete(guideStatus?: string | null): boolean {
  return guideStatus === 'review' || guideStatus === 'published';
}

export function guideNeedsAttention(guideStatus?: string | null): boolean {
  return !guideStatus || guideStatus === 'draft' || guideStatus === 'revision' || guideStatus === 'hidden';
}
