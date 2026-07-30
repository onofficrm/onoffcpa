export type LcAuth = {
  loggedIn: boolean;
  isSuperAdmin: boolean;
  isImpersonating?: boolean;
  impersonateType?: string | null;
  impersonateId?: number | null;
  impersonateLabel?: string;
  isPartner: boolean;
  isActivePartner: boolean;
  partnerId: number | null;
  partnerCode: string | null;
  partnerName: string | null;
  partnerStatus: string | null;
  isMerchant: boolean;
  isActiveMerchant: boolean;
  merchantId: number | null;
  merchantCode: string | null;
  merchantCompany: string | null;
  merchantStatus: string | null;
  merchantBalance: number | null;
  isLinkconnectAdmin: boolean;
  canAccessAdmin: boolean;
  memberId: string | null;
  memberName: string | null;
  memberNick: string | null;
  memberEmail: string | null;
  bbsUrl: string;
  siteUrl?: string;
  dbReady: boolean;
  merchantContractApplies?: boolean;
  merchantContractSigned?: boolean;
  merchantContractBlocked?: boolean;
  merchantContractGraceActive?: boolean;
  merchantContractRequires?: boolean;
  merchantContractHasSignedHistory?: boolean;
  merchantContractViewable?: boolean;
  merchantContractStatus?: string;
};

declare global {
  interface Window {
    __LC_AUTH__?: LcAuth;
  }
}

const defaultAuth: LcAuth = {
  loggedIn: false,
  isSuperAdmin: false,
  isImpersonating: false,
  impersonateType: null,
  impersonateId: null,
  impersonateLabel: '',
  isPartner: false,
  isActivePartner: false,
  partnerId: null,
  partnerCode: null,
  partnerName: null,
  partnerStatus: null,
  isMerchant: false,
  isActiveMerchant: false,
  merchantId: null,
  merchantCode: null,
  merchantCompany: null,
  merchantStatus: null,
  merchantBalance: null,
  isLinkconnectAdmin: false,
  canAccessAdmin: false,
  memberId: null,
  memberName: null,
  memberNick: null,
  memberEmail: null,
  bbsUrl: '/bbs',
  siteUrl: '',
  dbReady: false,
  merchantContractApplies: false,
  merchantContractSigned: true,
  merchantContractBlocked: false,
  merchantContractGraceActive: false,
  merchantContractRequires: false,
  merchantContractHasSignedHistory: false,
  merchantContractViewable: false,
  merchantContractStatus: '',
};

export function getLcAuth(): LcAuth {
  if (typeof window === 'undefined') {
    return defaultAuth;
  }
  return { ...defaultAuth, ...window.__LC_AUTH__ };
}

export function isLcLoggedIn(): boolean {
  return getLcAuth().loggedIn;
}

export function isLcSuperAdmin(): boolean {
  return getLcAuth().isSuperAdmin;
}

export function isLcPartner(): boolean {
  return getLcAuth().isPartner;
}

export function isLcActivePartner(): boolean {
  return getLcAuth().isActivePartner;
}

export function canAccessPartnerCenter(): boolean {
  const auth = getLcAuth();
  if (auth.isImpersonating && auth.impersonateType === 'partner') {
    return true;
  }
  if (auth.isSuperAdmin) {
    return true;
  }
  if (!auth.dbReady) {
    return true;
  }
  return auth.isActivePartner;
}

export function isLcMerchant(): boolean {
  return getLcAuth().isMerchant;
}

export function isLcActiveMerchant(): boolean {
  return getLcAuth().isActiveMerchant;
}

export function canAccessAdvertiserCenter(): boolean {
  const auth = getLcAuth();
  if (auth.isImpersonating && auth.impersonateType === 'merchant') {
    return true;
  }
  if (auth.isSuperAdmin) {
    return true;
  }
  if (!auth.dbReady) {
    return true;
  }
  return auth.isActiveMerchant;
}

/** 계약 접근 제어가 적용되는 광고주 세션인지 */
export function shouldEnforceMerchantContract(auth: LcAuth = getLcAuth()): boolean {
  if (!auth.dbReady) {
    return false;
  }
  if (auth.isSuperAdmin && !(auth.isImpersonating && auth.impersonateType === 'merchant')) {
    return false;
  }
  return Boolean(auth.merchantContractApplies);
}

/** 유예 종료 후 미체결 — 주요 기능 차단 */
export function isMerchantContractBlocked(auth: LcAuth = getLcAuth()): boolean {
  return shouldEnforceMerchantContract(auth) && Boolean(auth.merchantContractBlocked);
}

/** 미체결이나 유예 중 안내 표시 */
export function shouldShowMerchantContractNotice(auth: LcAuth = getLcAuth()): boolean {
  return shouldEnforceMerchantContract(auth) && Boolean(auth.merchantContractRequires);
}

/** 광고주 CPA 계약 화면 경로 (미체결 → 작성, 체결·이력 → 열람) */
export function getMerchantContractPath(auth: LcAuth = getLcAuth()): string {
  if (auth.merchantContractStatus === 'review_pending') {
    return '/advertiser/contract/complete';
  }
  if (shouldEnforceMerchantContract(auth) && auth.merchantContractViewable) {
    return '/advertiser/contract/view';
  }
  return '/advertiser/contract';
}

/**
 * 사이드바 계약 메뉴 라벨
 * - 체결 전: 계약서 체결하기
 * - 체결 후: 계약 정보
 */
export function getMerchantContractMenuLabel(auth: LcAuth = getLcAuth()): string {
  if (auth.merchantContractStatus === 'review_pending') {
    return '계약 승인 대기';
  }
  if (auth.merchantContractStatus === 'rejected') {
    return '계약서 재작성';
  }
  if (auth.merchantContractSigned || auth.merchantContractViewable) {
    return '계약 정보';
  }
  if (auth.merchantContractRequires || shouldEnforceMerchantContract(auth)) {
    return '계약서 체결하기';
  }
  return '계약 정보';
}

/** 계약 체결 후 광고등록 신청 메뉴 표시 */
export function shouldShowMerchantAdApplyMenu(auth: LcAuth = getLcAuth()): boolean {
  if (!auth.isMerchant && !auth.isActiveMerchant) {
    return false;
  }
  return Boolean(auth.merchantContractSigned);
}

/** 사이드바 CPA 계약 메뉴 표시 여부 */
export function shouldShowMerchantContractMenu(auth: LcAuth = getLcAuth()): boolean {
  return shouldEnforceMerchantContract(auth);
}

export async function refreshLcAuthFromServer(): Promise<LcAuth> {
  const { fetchMerchantMe } = await import('./api');
  const data = await fetchMerchantMe();
  if (typeof window !== 'undefined' && data.auth) {
    window.__LC_AUTH__ = { ...defaultAuth, ...data.auth };
  }
  return getLcAuth();
}

export function canAccessAdmin(): boolean {
  const auth = getLcAuth();
  return Boolean(auth.canAccessAdmin || auth.isSuperAdmin || auth.isLinkconnectAdmin);
}

export function getMemberDisplayName(): string {
  const auth = getLcAuth();
  if (auth.memberNick) {
    return auth.memberNick;
  }
  if (auth.memberName) {
    return auth.memberName;
  }
  if (auth.memberId) {
    return auth.memberId;
  }
  return '회원';
}
