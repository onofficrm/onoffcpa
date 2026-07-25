import { ChevronDown, Link as LinkIcon, Menu, ShieldCheck, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { canAccessAdmin } from '../lib/auth';
import {
  adminNavItem,
  campaignNavItems,
  centerNavItems,
  companySubItems,
  isCompanyNavActive,
  type NavLinkItem,
} from '../lib/publicNav';
import { MemberAuthMenu, MemberAuthMenuMobile } from './MemberAuthMenu';

function navLinkClass(active: boolean, accent?: NavLinkItem['accent']) {
  if (accent === 'emerald') {
    return `whitespace-nowrap text-sm xl:text-base font-medium transition-colors ${active ? 'text-emerald-400' : 'text-slate-300 hover:text-emerald-400'}`;
  }
  if (accent === 'cyan') {
    return `whitespace-nowrap text-sm xl:text-base font-medium transition-colors ${active ? 'text-cyan-400' : 'text-slate-300 hover:text-cyan-400'}`;
  }
  return `whitespace-nowrap text-sm xl:text-base font-medium transition-colors ${active ? 'text-white' : 'text-slate-300 hover:text-white'}`;
}

function isActive(pathname: string, to: string) {
  return pathname === to || pathname.startsWith(`${to}/`);
}

function CompanyNavDropdown({ onNavigate }: { onNavigate?: () => void }) {
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const active = isCompanyNavActive(location.pathname);

  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  return (
    <div
      ref={ref}
      className="relative"
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
    >
      <button
        type="button"
        className={`inline-flex items-center gap-1 ${navLinkClass(active)}`}
        aria-expanded={open}
        aria-haspopup="true"
        onClick={() => setOpen((v) => !v)}
      >
        회사소개
        <ChevronDown className={`w-4 h-4 transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>

      {open && (
        <div className="absolute top-full left-0 pt-1 w-52 z-50">
          <div className="py-2 bg-slate-900 border border-white/10 rounded-xl shadow-2xl">
            {companySubItems.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                onClick={() => {
                  setOpen(false);
                  onNavigate?.();
                }}
                className={`block px-4 py-2.5 text-sm font-medium transition-colors ${
                  isActive(location.pathname, item.to)
                    ? 'text-emerald-400 bg-white/5'
                    : 'text-slate-300 hover:text-white hover:bg-white/5'
                }`}
              >
                {item.label}
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

function AdminCenterBadge({ onNavigate, className = '' }: { onNavigate?: () => void; className?: string }) {
  if (!canAccessAdmin()) return null;

  return (
    <Link
      to={adminNavItem.to}
      onClick={onNavigate}
      className={`inline-flex items-center gap-1.5 shrink-0 bg-cyan-600 hover:bg-cyan-500 text-white px-3.5 py-2 rounded-lg text-xs xl:text-sm font-bold transition-colors shadow-sm border border-cyan-400/30 ${className}`}
    >
      <ShieldCheck className="w-4 h-4" />
      관리자센터
    </Link>
  );
}

export function Header() {
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const closeMobile = () => setIsMobileMenuOpen(false);

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-slate-950/90 backdrop-blur-md border-b border-white/10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center h-20">
          {/* 로고 */}
          <Link to="/" className="flex items-center gap-2 shrink-0 mr-6">
            <LinkIcon className="w-7 h-7 text-cyan-400" />
            <span className="text-xl xl:text-2xl font-bold text-white tracking-tight">링크커넥트</span>
          </Link>

          {/* 메인 메뉴 */}
          <nav className="hidden md:flex items-center gap-4 lg:gap-6 xl:gap-8 flex-1 min-w-0 overflow-x-auto" aria-label="주요 메뉴">
            <CompanyNavDropdown />
            {campaignNavItems.map((item) => (
              <Link key={item.to} to={item.to} className={navLinkClass(isActive(location.pathname, item.to))}>
                {item.label}
              </Link>
            ))}
            {centerNavItems.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                className={navLinkClass(isActive(location.pathname, item.to), item.accent)}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          {/* 우측: 로그인 · 회원가입 · 관리자센터(맨 끝) */}
          <div className="hidden md:flex items-center gap-2 lg:gap-3 shrink-0 ml-auto pl-4 lg:pl-6 border-l border-white/10">
            <MemberAuthMenu variant="header-dark" onNavigate={closeMobile} />
            <AdminCenterBadge />
          </div>

          {/* 모바일 햄버거 */}
          <button
            type="button"
            className="md:hidden ml-auto text-slate-300 hover:text-white p-2"
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            aria-expanded={isMobileMenuOpen}
            aria-label="메뉴"
          >
            {isMobileMenuOpen ? <X className="w-7 h-7" /> : <Menu className="w-7 h-7" />}
          </button>
        </div>
      </div>

      {isMobileMenuOpen && (
        <div className="md:hidden bg-slate-900 border-b border-white/10 px-4 pt-2 pb-6 shadow-2xl max-h-[80vh] overflow-y-auto">
          <p className="px-3 pt-2 pb-1 text-xs font-bold text-slate-500 uppercase tracking-wider">회사소개</p>
          {companySubItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              onClick={closeMobile}
              className={`block px-3 py-2.5 pl-5 text-base font-medium rounded-lg ${
                isActive(location.pathname, item.to)
                  ? 'text-emerald-400 bg-white/5'
                  : 'text-slate-300 hover:text-white hover:bg-white/5'
              }`}
            >
              {item.label}
            </Link>
          ))}

          <p className="px-3 pt-4 pb-1 text-xs font-bold text-slate-500 uppercase tracking-wider">캠페인</p>
          {campaignNavItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              onClick={closeMobile}
              className="block px-3 py-2.5 pl-5 text-base font-medium text-slate-300 hover:text-white hover:bg-white/5 rounded-lg"
            >
              {item.label}
            </Link>
          ))}

          <p className="px-3 pt-4 pb-1 text-xs font-bold text-slate-500 uppercase tracking-wider">센터</p>
          {centerNavItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              onClick={closeMobile}
              className={`block px-3 py-2.5 pl-5 text-base font-medium rounded-lg ${
                item.accent === 'emerald' ? 'text-emerald-400 hover:bg-white/5' : 'text-cyan-400 hover:bg-white/5'
              }`}
            >
              {item.label}
            </Link>
          ))}

          <div className="pt-4 mt-2 border-t border-white/10 flex flex-col gap-3">
            <MemberAuthMenuMobile onNavigate={closeMobile} />
            <AdminCenterBadge onNavigate={closeMobile} className="w-full justify-center py-3" />
          </div>
        </div>
      )}
    </header>
  );
}
