export interface NavLinkItem {
  to: string;
  label: string;
  accent?: 'emerald' | 'cyan';
}

/** 회사소개 드롭다운 하위 메뉴 */
export const companySubItems: NavLinkItem[] = [
  { to: '/about', label: '회사소개' },
  { to: '/affiliate', label: '제휴마케팅이란?' },
  { to: '/notice', label: '공지사항' },
];

/** @deprecated Footer 등 — companySubItems 사용 */
export const companyNavItems = companySubItems;

/** 캠페인·프로모션 */
export const campaignNavItems: NavLinkItem[] = [
  { to: '/cpa-list', label: 'CPA' },
  { to: '/cps', label: 'CPS' },
  { to: '/events', label: '이벤트/프로모션' },
];

/** 센터 */
export const centerNavItems: NavLinkItem[] = [
  { to: '/partner', label: '파트너센터', accent: 'emerald' },
  { to: '/advertiser', label: '광고주센터', accent: 'cyan' },
];

/** 관리자 (권한 있을 때만 표시) */
export const adminNavItem: NavLinkItem = {
  to: '/admin',
  label: '관리자센터',
  accent: 'cyan',
};

export function isCompanyNavActive(pathname: string): boolean {
  return companySubItems.some((item) => pathname === item.to || pathname.startsWith(`${item.to}/`));
}
