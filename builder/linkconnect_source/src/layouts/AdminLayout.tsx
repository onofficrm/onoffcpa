import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { LayoutDashboard, Users, Building2, Briefcase, Database, ShieldAlert, CreditCard, Receipt, Code, MessageSquare, Settings, Search, Menu, ChevronRight, Gift, ScrollText, ClipboardList, AlertTriangle, PhoneCall } from 'lucide-react';
import { MemberAuthMenu } from '../components/MemberAuthMenu';
import { getLcAuth, getMemberDisplayName } from '../lib/auth';
import { queueScrollTo } from '../lib/navigation';
import { g5MemberEditUrl } from '../lib/urls';
import { AiGuideChat } from '../components/AiGuideChat';
import { NotificationCenter } from '../components/NotificationCenter';
import { ImpersonateHistoryBar } from '../components/ImpersonateHistoryBar';

const sidebarMenus = [
  { id: 'dashboard', label: '통합 대시보드', icon: <LayoutDashboard size={20} />, path: '/admin' },
  { id: 'partners', label: '파트너 관리', icon: <Users size={20} />, path: '/admin/partners' },
  { id: 'review', label: '자동 심사 큐', icon: <ClipboardList size={20} />, path: '/admin/review-queue' },
  { id: 'advertisers', label: '광고주 관리', icon: <Building2 size={20} />, path: '/admin/advertisers' },
  { id: 'campaigns', label: '광고상품 관리', icon: <Briefcase size={20} />, path: '/admin/campaigns' },
  { id: 'db', label: '전체 디비 관리', icon: <Database size={20} />, path: '/admin/conversions' },
  { id: 'call', label: '콜디비 관리', icon: <PhoneCall size={20} />, path: '/admin/call' },
  { id: 'inspections', label: '취소/무효 검수', icon: <ShieldAlert size={20} />, path: '/admin/inspections' },
  { id: 'channel_reports', label: '금지 채널 신고', icon: <AlertTriangle size={20} />, path: '/admin/channel-reports' },
  { id: 'billing', label: '광고비 관리', icon: <CreditCard size={20} />, path: '/admin/billing' },
  { id: 'settlements', label: '정산 관리', icon: <Receipt size={20} />, path: '/admin/settlements' },
  { id: 'api', label: 'API 관리', icon: <Code size={20} />, path: '/admin/api' },
  { id: 'events', label: '이벤트/프로모션', icon: <Gift size={20} />, path: '/admin/events' },
  { id: 'logs', label: '작업 로그', icon: <ScrollText size={20} />, path: '/admin/logs' },
  { id: 'support', label: '문의 관리', icon: <MessageSquare size={20} />, path: '/admin/support' },
  { id: 'settings', label: '환경설정', icon: <Settings size={20} />, path: '/admin/settings' },
];

export function AdminLayout({ children, activeMenu, title, description }: { children: React.ReactNode, activeMenu: string, title: string, description?: string }) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const auth = getLcAuth();
  const displayName = getMemberDisplayName();
  const memberEmail = auth.memberEmail ?? '';
  const memberInitials = displayName.slice(0, 2).toUpperCase();

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
      {/* Top Header */}
      <header className="h-16 bg-slate-950 border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 fixed top-0 w-full z-50 text-white">
        <div className="flex items-center gap-6">
          <div className="flex items-center gap-3">
            <button 
              className="lg:hidden p-2 -ml-2 text-slate-400 hover:text-white transition-colors"
              onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            >
              <Menu size={24} />
            </button>
            <Link to="/admin" className="text-xl font-bold tracking-tight text-white flex items-center gap-2">
              <div className="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-black text-sm">LC</span>
              </div>
              <span className="hidden sm:inline">링크커넥트 <span className="text-cyan-400 text-xs font-medium ml-1 px-1.5 py-0.5 bg-cyan-950 rounded-md border border-cyan-800/50">ADMIN</span></span>
            </Link>
          </div>
          
          <nav className="hidden xl:flex items-center gap-1 text-sm font-medium ml-8">
            <Link to="/" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">홈페이지</Link>
            <div className="w-1 h-1 bg-slate-700 rounded-full mx-1"></div>
            <Link to="/cpa-list" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">CPA</Link>
            <Link to="/cps" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">CPS</Link>
            <Link to="/events" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">이벤트/프로모션</Link>
            <div className="w-1 h-1 bg-slate-700 rounded-full mx-1"></div>
            <Link to="/partner" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">파트너센터</Link>
            <Link to="/advertiser" className="px-3 py-2 text-slate-300 hover:text-white transition-colors">광고주센터</Link>
          </nav>
        </div>

        <div className="flex items-center gap-2 sm:gap-4">
          <div className="hidden md:flex relative">
            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
            <input 
              type="text" 
              placeholder="통합 검색 (파트너/광고주/디비)" 
              className="pl-9 pr-4 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 w-64 transition-all"
            />
          </div>
          <NotificationCenter center="admin" variant="dark" />
          <ImpersonateHistoryBar />
          <a
            href={g5MemberEditUrl()}
            className="p-2 text-slate-400 hover:text-white transition-colors"
            title="회원정보 수정"
          >
            <Settings size={20} />
          </a>
          <div className="h-6 w-px bg-slate-800 mx-1 sm:mx-2"></div>
          <div className="flex items-center gap-3">
            <div className="hidden sm:flex flex-col items-end">
              <span className="text-sm font-bold">{displayName}</span>
              {memberEmail ? (
                <span className="text-xs text-slate-400">{memberEmail}</span>
              ) : null}
            </div>
            <a
              href={g5MemberEditUrl()}
              className="w-9 h-9 bg-slate-800 rounded-full flex items-center justify-center border border-slate-700 text-cyan-400 font-bold text-xs"
              title="회원정보 수정"
            >
              {memberInitials}
            </a>
          </div>
        </div>
      </header>

      <div className="flex flex-1 pt-16 h-screen">
        {/* Sidebar */}
        <aside className={`
          fixed lg:static inset-y-0 left-0 z-40
          w-64 bg-slate-950 border-r border-slate-800
          transform transition-transform duration-300 ease-in-out lg:translate-x-0
          flex flex-col
          ${isSidebarOpen ? 'translate-x-0 pt-16' : '-translate-x-full'}
        `}>
          <div className="flex-1 overflow-y-auto py-6 px-4 space-y-1 hide-scrollbar">
            {sidebarMenus.map((menu) => (
              <Link
                key={menu.id}
                to={menu.path}
                className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${
                  activeMenu === menu.id 
                    ? 'bg-cyan-500/10 text-cyan-400 font-bold border border-cyan-500/20' 
                    : 'text-slate-400 hover:text-white hover:bg-slate-900 font-medium'
                }`}
              >
                {menu.icon}
                <span>{menu.label}</span>
              </Link>
            ))}
          </div>
          
          <div className="p-4 border-t border-slate-800">
            <MemberAuthMenu variant="sidebar" logoutReturnPath="/admin" />
          </div>
        </aside>

        {/* Backdrop for mobile */}
        {isSidebarOpen && (
          <div 
            className="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-30 lg:hidden"
            onClick={() => setIsSidebarOpen(false)}
          />
        )}

        {/* Main Content */}
        <main className="flex-1 overflow-y-auto bg-slate-50 relative">
          <div className="p-4 sm:p-8 max-w-[1600px] mx-auto min-h-full flex flex-col">
            <div className="mb-8">
              <div className="flex items-center gap-2 text-sm text-slate-500 mb-2 font-medium">
                <span>관리자센터</span>
                <ChevronRight size={14} />
                <span className="text-slate-900">{title}</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight mb-2">{title}</h1>
              {description && <p className="text-slate-500">{description}</p>}
            </div>
            
            <div className="flex-1 flex flex-col">
              {children}
            </div>
          </div>
        </main>
      </div>
      <AiGuideChat page="admin" role="admin" />
    </div>
  );
}
