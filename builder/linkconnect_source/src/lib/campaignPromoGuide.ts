export type PromoGuideLimits = {
  promotion_points: number;
  recommended_keywords: number;
  forbidden_words: number;
  precautions: number;
  valid_db_rules: number;
  invalid_db_rules: number;
  images: number;
};

export type PromoGuideApprovalType = 'free' | 'first_review' | 'all_review';

export type PromoGuideStatus = 'draft' | 'review' | 'revision' | 'published' | 'hidden';

export type PromoGuideImage = {
  id: number;
  assetId: number;
  originalFilename: string;
  mimeType: string;
  fileSize: number;
  imageTitle: string;
  sortOrder: number;
  downloadUrl: string;
};

export type PromoGuideFormState = {
  promotionPoints: string[];
  recommendedKeywords: string[];
  forbiddenWords: string[];
  precautions: string[];
  validDbRules: string[];
  invalidDbRules: string[];
  approvalType: PromoGuideApprovalType;
};

export const EMPTY_PROMO_GUIDE_FORM: PromoGuideFormState = {
  promotionPoints: [''],
  recommendedKeywords: [],
  forbiddenWords: [],
  precautions: [''],
  validDbRules: [''],
  invalidDbRules: [''],
  approvalType: 'free',
};

export const PROMO_GUIDE_LIMITS_DEFAULT: PromoGuideLimits = {
  promotion_points: 3,
  recommended_keywords: 10,
  forbidden_words: 10,
  precautions: 5,
  valid_db_rules: 5,
  invalid_db_rules: 5,
  images: 10,
};

export function promoGuideStatusLabel(status: PromoGuideStatus | string): string {
  const map: Record<string, string> = {
    draft: '작성 중',
    review: '검토 대기',
    revision: '수정 요청',
    published: '파트너 공개 중',
    hidden: '비공개',
  };
  return map[status] ?? status;
}

export function promoGuideStatusStyle(status: PromoGuideStatus | string): string {
  const map: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 border-slate-200',
    review: 'bg-amber-50 text-amber-800 border-amber-200',
    revision: 'bg-orange-50 text-orange-800 border-orange-200',
    published: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    hidden: 'bg-rose-50 text-rose-700 border-rose-200',
  };
  return map[status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
}

export function promoGuideApprovalLabel(type: PromoGuideApprovalType): string {
  const map: Record<PromoGuideApprovalType, string> = {
    free: '가이드 안에서 자유롭게 홍보 가능',
    first_review: '최초 광고물만 승인 필요',
    all_review: '모든 광고물 사전 승인 필요',
  };
  return map[type];
}

export function promoGuideApprovalPartnerMessage(type: PromoGuideApprovalType): string {
  const map: Record<PromoGuideApprovalType, string> = {
    free: '가이드 안에서 자유롭게 홍보할 수 있습니다.',
    first_review: '최초 광고물은 광고주의 승인을 받은 후 홍보해 주세요.',
    all_review: '모든 광고물은 광고주의 사전 승인이 필요합니다.',
  };
  return map[type];
}

export function normalizePromoGuideList(items: string[], max: number): string[] {
  const result: string[] = [];
  items.forEach((raw) => {
    const text = raw.trim();
    if (text === '') return;
    if (result.length >= max) return;
    result.push(text);
  });
  return result;
}

export function normalizePromoGuideTags(items: string[], max: number): string[] {
  const seen = new Set<string>();
  const result: string[] = [];
  items.forEach((raw) => {
    const text = raw.trim();
    if (text === '') return;
    const key = text.toLowerCase();
    if (seen.has(key)) return;
    if (result.length >= max) return;
    seen.add(key);
    result.push(text);
  });
  return result;
}

export function validatePromoGuideForm(
  form: PromoGuideFormState,
  limits: PromoGuideLimits,
): Record<string, string> {
  const errors: Record<string, string> = {};

  const points = normalizePromoGuideList(form.promotionPoints, limits.promotion_points);
  if (points.length === 0) {
    errors.promotionPoints = '핵심 홍보 포인트를 1개 이상 입력해 주세요.';
  } else if (form.promotionPoints.filter((v) => v.trim()).length > limits.promotion_points) {
    errors.promotionPoints = `최대 ${limits.promotion_points}개까지 입력할 수 있습니다.`;
  }

  const keywords = normalizePromoGuideTags(form.recommendedKeywords, limits.recommended_keywords);
  if (form.recommendedKeywords.length > limits.recommended_keywords) {
    errors.recommendedKeywords = `최대 ${limits.recommended_keywords}개까지 등록할 수 있습니다.`;
  }

  const forbidden = normalizePromoGuideTags(form.forbiddenWords, limits.forbidden_words);
  if (form.forbiddenWords.length > limits.forbidden_words) {
    errors.forbiddenWords = `최대 ${limits.forbidden_words}개까지 등록할 수 있습니다.`;
  }

  const precautions = normalizePromoGuideList(form.precautions, limits.precautions);
  if (form.precautions.filter((v) => v.trim()).length > limits.precautions) {
    errors.precautions = `최대 ${limits.precautions}개까지 입력할 수 있습니다.`;
  }

  const validRules = normalizePromoGuideList(form.validDbRules, limits.valid_db_rules);
  if (form.validDbRules.filter((v) => v.trim()).length > limits.valid_db_rules) {
    errors.validDbRules = `최대 ${limits.valid_db_rules}개까지 입력할 수 있습니다.`;
  }

  const invalidRules = normalizePromoGuideList(form.invalidDbRules, limits.invalid_db_rules);
  if (form.invalidDbRules.filter((v) => v.trim()).length > limits.invalid_db_rules) {
    errors.invalidDbRules = `최대 ${limits.invalid_db_rules}개까지 입력할 수 있습니다.`;
  }

  if (keywords.length === 0 && !errors.recommendedKeywords) {
    // keywords optional
  }

  return errors;
}

export function buildPromoGuidePayload(form: PromoGuideFormState, limits: PromoGuideLimits) {
  return {
    promotionPoints: normalizePromoGuideList(form.promotionPoints, limits.promotion_points),
    recommendedKeywords: normalizePromoGuideTags(form.recommendedKeywords, limits.recommended_keywords),
    forbiddenWords: normalizePromoGuideTags(form.forbiddenWords, limits.forbidden_words),
    precautions: normalizePromoGuideList(form.precautions, limits.precautions),
    validDbRules: normalizePromoGuideList(form.validDbRules, limits.valid_db_rules),
    invalidDbRules: normalizePromoGuideList(form.invalidDbRules, limits.invalid_db_rules),
    approvalType: form.approvalType,
  };
}

export function formFromPromoGuideApi(data: {
  promotionPoints?: string[];
  recommendedKeywords?: string[];
  forbiddenWords?: string[];
  precautions?: string[];
  validDbRules?: string[];
  invalidDbRules?: string[];
  approvalType?: PromoGuideApprovalType;
}): PromoGuideFormState {
  const ensureList = (items: string[] | undefined, withBlank: boolean) => {
    const list = Array.isArray(items) ? items.filter((v) => v.trim()) : [];
    if (withBlank && list.length === 0) return [''];
    return withBlank ? list : list;
  };

  return {
    promotionPoints: ensureList(data.promotionPoints, true),
    recommendedKeywords: ensureList(data.recommendedKeywords, false),
    forbiddenWords: ensureList(data.forbiddenWords, false),
    precautions: ensureList(data.precautions, true),
    validDbRules: ensureList(data.validDbRules, true),
    invalidDbRules: ensureList(data.invalidDbRules, true),
    approvalType: data.approvalType ?? 'free',
  };
}

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes}B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)}KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)}MB`;
}
