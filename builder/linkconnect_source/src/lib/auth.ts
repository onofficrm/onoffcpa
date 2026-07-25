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
  dbReady: boolean;
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
  dbReady: false,
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

export function canAccessAdmin(): boolean {
  return getLcAuth().canAccessAdmin;
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
