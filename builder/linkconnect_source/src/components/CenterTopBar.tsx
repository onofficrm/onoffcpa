import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Menu, X } from 'lucide-react';

type CenterTopBarProps = {
  center: 'partner' | 'advertiser';
};

const centerConfig = {
  partner: {
    home: '/partner',
    badge: 'PARTNER',
    badgeClass: 'text-emerald-400 bg-emerald-950 border-emerald-800/50',
    logoGradient: 'from-emerald-500 to-teal-600',
    activeNavClass: 'text-emerald-400',
  },
  advertiser: {
    home: '/advertiser',
    badge: 'ADVERTISER',
    badgeClass: 'text-cyan-400 bg-cyan-950 border-cyan-800/50',
    logoGradient: 'from-cyan-500 to-blue-600',
    activeNavClass: 'text-cyan-400',
  },
};

const navLinks = [
  { to: '/', label: '홈페이지' },
  { to: '/cpa-list', label: 'CPA' },
  { to: '/cps', label: 'CPS' },
  { to: '/events', label: '이벤트/프로모션' },
  { to: '/notice', label: '공지사항' },
  { to: '/partner', label: '파트너센터', center: 'partner' as const },
  { to: '/advertiser', label: '광고주센터', center: 'advertiser' as const },
];

export function CenterTopBar({ center }: CenterTopBarProps) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const config = centerConfig[center];

  return (
    <header className="h-14 bg-slate-950 border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 sticky top-0 w-full z-40 text-white">
      <div className="flex items-center gap-4 sm:gap-6 min-w-0">
        <button
          type="button"
          className="xl:hidden p-2 -ml-2 text-slate-400 hover:text-white transition-colors shrink-0"
          onClick={() => setMobileOpen((v) => !v)}
          aria-label="메뉴"
        >
          {mobileOpen ? <X size={22} /> : <Menu size={22} />}
        </button>

        <Link to={config.home} className="text-lg font-bold tracking-tight text-white flex items-center gap-2 shrink-0">
          <div className={`w-8 h-8 bg-gradient-to-br ${config.logoGradient} rounded-lg flex items-center justify-center`}>
            <span className="text-white font-black text-sm">LC</span>
          </div>
          <span className="hidden sm:inline">
            링크커넥트{' '}
            <span className={`text-xs font-medium ml-1 px-1.5 py-0.5 rounded-md border ${config.badgeClass}`}>
              {config.badge}
            </span>
          </span>
        </Link>

        <nav className="hidden xl:flex items-center gap-1 text-sm font-medium">
          {navLinks.map((item, index) => {
            const isActive = item.center === center;
            const showDivider = index === 0 || index === 4;

            return (
              <React.Fragment key={item.to + item.label}>
                {showDivider && index > 0 ? <div className="w-1 h-1 bg-slate-700 rounded-full mx-1" /> : null}
                <Link
                  to={item.to}
                  className={`px-3 py-2 transition-colors whitespace-nowrap ${
                    isActive ? `${config.activeNavClass} font-bold` : 'text-slate-300 hover:text-white'
                  }`}
                >
                  {item.label}
                </Link>
              </React.Fragment>
            );
          })}
        </nav>
      </div>

      {mobileOpen ? (
        <div className="absolute top-14 left-0 right-0 bg-slate-950 border-b border-slate-800 xl:hidden z-50 py-2 px-2 shadow-xl">
          {navLinks.map((item) => {
            const isActive = item.center === center;
            return (
              <Link
                key={item.to + item.label}
                to={item.to}
                onClick={() => setMobileOpen(false)}
                className={`block px-4 py-2.5 rounded-lg text-sm font-medium ${
                  isActive ? config.activeNavClass : 'text-slate-300 hover:text-white hover:bg-slate-900'
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </div>
      ) : null}
    </header>
  );
}
