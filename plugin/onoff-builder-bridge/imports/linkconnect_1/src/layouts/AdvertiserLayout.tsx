import React from 'react';
import { Link } from 'react-router-dom';
import { SuperAdminWidget, SuperAdminHeaderButton } from '../components/SuperAdminWidget';
import { LayoutDashboard, FileText, Target, Wallet, BarChart3, MessageSquare, Bell, Building2 } from 'lucide-react';

export function AdvertiserLayout({ children, activeMenu, title, companyName = "(주)리드앤솔루션", balance = "2,350,000" }: { children: React.ReactNode, activeMenu: string, title: string, companyName?: string, balance?: string }) {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col pt-20">
      <SuperAdminWidget />
      <div className="flex flex-col md:flex-row flex-1">
      {/* Sidebar */}
      <aside className="w-full md:w-64 bg-slate-950 text-slate-300 shrink-0 border-r border-slate-800 overflow-x-auto md:overflow-visible z-10">
        <div className="p-4 md:p-6 flex md:flex-col gap-2 md:gap-0 sticky top-0">
          <div className="hidden md:block text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Advertiser Menu</div>
          <nav className="flex md:flex-col gap-2 md:space-y-1 min-w-max md:min-w-0">
            <NavItem icon={<LayoutDashboard size={20} />} label="대시보드" active={activeMenu === 'dashboard'} to="/advertiser" />
            <NavItem icon={<FileText size={20} />} label="내 광고상품" active={activeMenu === 'campaigns'} to="/advertiser/campaigns" />
            <NavItem icon={<Target size={20} />} label="디비 확인" badge={9} active={activeMenu === 'db'} to="/advertiser/db" />
            <NavItem icon={<Wallet size={20} />} label="광고비 충전/내역" active={activeMenu === 'billing'} to="/advertiser/billing" />
            <NavItem icon={<BarChart3 size={20} />} label="성과 리포트" active={activeMenu === 'reports'} to="/advertiser/reports" />
            <NavItem icon={<MessageSquare size={20} />} label="문의하기" active={activeMenu === 'support'} to="/advertiser/support" />
          </nav>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 p-4 md:p-8 overflow-x-hidden bg-slate-50">
        {/* Header */}
        <header className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
            <p className="text-slate-500">{companyName}</p>
          </div>
          <div className="flex items-center gap-4">
            <SuperAdminHeaderButton />
            <div className="flex flex-col items-end bg-white px-4 py-1.5 rounded-lg border border-slate-200 shadow-sm">
              <span className="text-xs text-slate-500">현재 광고비 잔액</span>
              <span className="font-bold text-cyan-600">{balance} 원</span>
            </div>
            <button className="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 shadow-sm relative">
              <Bell size={20} />
              <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            <button className="w-10 h-10 rounded-full bg-cyan-100 text-cyan-700 flex items-center justify-center border border-cyan-200 shadow-sm">
              <Building2 size={20} />
            </button>
          </div>
        </header>

        {children}
      </main>
      </div>
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
