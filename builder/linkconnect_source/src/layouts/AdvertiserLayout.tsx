import React from 'react';
import { Link } from 'react-router-dom';
import { ImpersonateBanner } from '../components/ImpersonateBanner';
import { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';
import { LayoutDashboard, FileText, Target, Wallet, BarChart3, MessageSquare, PhoneCall } from 'lucide-react';
import { MemberAuthMenu } from '../components/MemberAuthMenu';
import { CenterTopBar } from '../components/CenterTopBar';
import { getLcAuth } from '../lib/auth';
import { AiGuideChat } from '../components/AiGuideChat';
import { NotificationCenter } from '../components/NotificationCenter';

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
  const displayCompany = companyName ?? auth.merchantCompany ?? '(주)리드앤솔루션';
  const displayBalance = balance ?? (auth.merchantBalance !== null ? auth.merchantBalance.toLocaleString() : '2,350,000');
  const dbBadge = pendingBadge ?? (auth.dbReady ? undefined : 9);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      {!auth.isImpersonating ? <SuperAdminWidget /> : null}
      <ImpersonateBanner />
      <CenterTopBar center="advertiser" />
      <div className="flex flex-col md:flex-row flex-1">
      {/* Sidebar */}
      <aside className="w-full md:w-64 bg-slate-950 text-slate-300 shrink-0 border-r border-slate-800 overflow-x-auto md:overflow-visible z-10">
        <div className="p-4 md:p-6 flex md:flex-col gap-2 md:gap-0 sticky top-0">
          <div className="hidden md:block text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Advertiser Menu</div>
          <nav className="flex md:flex-col gap-2 md:space-y-1 min-w-max md:min-w-0">
            <NavItem icon={<LayoutDashboard size={20} />} label="대시보드" active={activeMenu === 'dashboard'} to="/advertiser" />
            <NavItem icon={<FileText size={20} />} label="내 광고상품" active={activeMenu === 'campaigns'} to="/advertiser/campaigns" />
            <NavItem icon={<Target size={20} />} label="디비 확인" badge={dbBadge} active={activeMenu === 'db'} to="/advertiser/db" />
            <NavItem icon={<PhoneCall size={20} />} label="콜디비" active={activeMenu === 'call'} to="/advertiser/call" />
            <NavItem icon={<Wallet size={20} />} label="광고비 충전/내역" active={activeMenu === 'billing'} to="/advertiser/billing" />
            <NavItem icon={<BarChart3 size={20} />} label="성과 리포트" active={activeMenu === 'reports'} to="/advertiser/reports" />
            <NavItem icon={<MessageSquare size={20} />} label="문의하기" active={activeMenu === 'support'} to="/advertiser/support" />
          </nav>
          <MemberAuthMenu variant="sidebar" logoutReturnPath="/advertiser" />
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 p-4 md:p-8 overflow-x-hidden bg-slate-50">
        {/* Header */}
        <header className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
            <p className="text-slate-500">{displayCompany}</p>
          </div>
          <div className="flex items-center gap-4">
            <SuperAdminHeaderButton />
            <div className="flex flex-col items-end bg-white px-4 py-1.5 rounded-lg border border-slate-200 shadow-sm">
              <span className="text-xs text-slate-500">현재 광고비 잔액</span>
              <span className="font-bold text-cyan-600">{displayBalance} 원</span>
            </div>
            <NotificationCenter center="merchant" />
            <MemberAuthMenu variant="compact" logoutReturnPath="/advertiser" />
          </div>
        </header>

        {children}
      </main>
      </div>
      <AiGuideChat page="advertiser" role="merchant" />
    </div>
  );
}

function NavItem({ icon, label, active = false, badge, to }: any) {
  return (
    <Link to={to} className={`flex items-center justify-between px-4 py-3 rounded-xl transition-colors ${active ? 'bg-cyan-500/10 text-cyan-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}>
      <div className="flex items-center gap-3">
        {icon}
        <span>{label}</span>
      </div>
      {badge && (
        <span className="bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{badge}</span>
      )}
    </Link>
  );
}
