import React from 'react';
import { ImpersonateBanner } from '../components/ImpersonateBanner';
import { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';
import { LayoutDashboard, FileText, Target, Wallet, BarChart3, MessageSquare, PhoneCall, Megaphone, ScrollText, ClipboardPen } from 'lucide-react';
import { MemberAuthMenu } from '../components/MemberAuthMenu';
import { CenterTopBar } from '../components/CenterTopBar';
import {
  getLcAuth,
  getMerchantContractMenuLabel,
  getMerchantContractPath,
  shouldShowMerchantAdApplyMenu,
  shouldShowMerchantContractMenu,
  shouldShowMerchantContractNotice,
} from '../lib/auth';
import { AiGuideChat } from '../components/AiGuideChat';
import { AdvertiserContractNotice } from '../components/advertiser/AdvertiserContractNotice';
import { NotificationCenter } from '../components/NotificationCenter';
import { CenterNavItem } from '../components/center-ui';

export function AdvertiserLayout({
  children,
  activeMenu,
  title,
  companyName,
  balance,
  pendingBadge,
}: {
  children: React.ReactNode;
  activeMenu: string;
  title: string;
  companyName?: string;
  balance?: string;
  pendingBadge?: number;
}) {
  const auth = getLcAuth();
  const showContractGraceBanner = shouldShowMerchantContractNotice(auth) && auth.merchantContractGraceActive;
  const showContractMenu = shouldShowMerchantContractMenu(auth);
  const showAdApplyMenu = shouldShowMerchantAdApplyMenu(auth);
  const contractLabel = getMerchantContractMenuLabel(auth);
  const contractPath = getMerchantContractPath(auth);
  const contractBadge =
    auth.merchantContractStatus === 'review_pending'
      ? '대기'
      : auth.merchantContractRequires && !auth.merchantContractSigned
        ? '필수'
        : undefined;
  const displayCompany = companyName ?? auth.merchantCompany ?? '광고주';
  const displayBalance = balance ?? (auth.merchantBalance !== null && auth.merchantBalance !== undefined
    ? auth.merchantBalance.toLocaleString()
    : '0');
  const dbBadge = pendingBadge;

  return (
    <div className="min-h-screen bg-slate-50/80 flex flex-col">
      {!auth.isImpersonating ? <SuperAdminWidget /> : null}
      <ImpersonateBanner />
      <CenterTopBar center="advertiser" />
      <div className="flex flex-col md:flex-row flex-1">
      <aside className="w-full md:w-64 bg-slate-950 text-slate-300 shrink-0 border-r border-slate-800/80 overflow-x-auto md:overflow-visible z-10">
        <div className="p-4 md:p-5 flex md:flex-col gap-2 md:gap-0 sticky top-0">
          <div className="hidden md:block text-[11px] font-bold text-slate-500 uppercase tracking-[0.14em] mb-4 px-1">Advertiser</div>
          <nav className="flex md:flex-col gap-1.5 min-w-max md:min-w-0">
            <CenterNavItem icon={<LayoutDashboard size={20} />} label="대시보드" active={activeMenu === 'dashboard'} to="/advertiser" accent="cyan" />
            <CenterNavItem icon={<FileText size={20} />} label="내 광고상품" active={activeMenu === 'campaigns'} to="/advertiser/campaigns" accent="cyan" />
            {showContractMenu ? (
              <CenterNavItem
                icon={<ScrollText size={20} />}
                label={contractLabel}
                badge={contractBadge}
                active={activeMenu === 'contract'}
                to={contractPath}
                accent="cyan"
              />
            ) : null}
            {showAdApplyMenu ? (
              <CenterNavItem
                icon={<ClipboardPen size={20} />}
                label="광고 등록 신청하기"
                active={activeMenu === 'ad-apply'}
                to="/advertiser/ad-apply"
                accent="cyan"
              />
            ) : null}
            <CenterNavItem icon={<Target size={20} />} label="디비 확인" badge={dbBadge} active={activeMenu === 'db'} to="/advertiser/db" accent="cyan" />
            <CenterNavItem icon={<PhoneCall size={20} />} label="콜디비" active={activeMenu === 'call'} to="/advertiser/call" accent="cyan" />
            <CenterNavItem icon={<Wallet size={20} />} label="광고비 충전/내역" active={activeMenu === 'billing'} to="/advertiser/billing" accent="cyan" />
            <CenterNavItem icon={<Megaphone size={20} />} label="마케팅 성과" active={activeMenu === 'marketing'} to="/advertiser/marketing" accent="cyan" />
            <CenterNavItem icon={<BarChart3 size={20} />} label="성과 리포트" active={activeMenu === 'reports'} to="/advertiser/reports" accent="cyan" />
            <CenterNavItem icon={<MessageSquare size={20} />} label="문의하기" active={activeMenu === 'support'} to="/advertiser/support" accent="cyan" />
          </nav>
          <div className="mt-4 pt-4 border-t border-slate-800/80">
            <MemberAuthMenu
              variant="sidebar"
              logoutReturnPath="/advertiser"
              activeMenu={activeMenu}
            />
          </div>
        </div>
      </aside>

      <main className="flex-1 p-4 md:p-8 overflow-x-hidden bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-100 via-slate-50 to-slate-50">
        <header className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wider text-cyan-600 mb-1">Advertiser Center</p>
            <h1 className="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{title}</h1>
            <p className="text-slate-500 mt-1">{displayCompany}</p>
          </div>
          <div className="flex items-center gap-3 sm:gap-4">
            <SuperAdminHeaderButton />
            <div className="flex flex-col items-end bg-white/90 backdrop-blur px-4 py-2 rounded-xl border border-slate-200/80 shadow-sm">
              <span className="text-[11px] text-slate-500">현재 광고비 잔액</span>
              <span className="font-bold text-cyan-600 tabular-nums">{displayBalance} 원</span>
            </div>
            <NotificationCenter center="merchant" />
            <MemberAuthMenu variant="compact" logoutReturnPath="/advertiser" />
          </div>
        </header>

        {showContractGraceBanner ? <AdvertiserContractNotice variant="banner" /> : null}

        {children}
      </main>
      </div>
      <AiGuideChat page="advertiser" role="merchant" />
    </div>
  );
}
