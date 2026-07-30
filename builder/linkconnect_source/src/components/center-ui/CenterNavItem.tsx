import React from 'react';
import { Link } from 'react-router-dom';
import { CenterAccent } from './types';

type CenterNavItemProps = {
  icon: React.ReactNode;
  label: string;
  to: string;
  active?: boolean;
  badge?: string | number;
  accent?: CenterAccent;
  onClick?: () => void;
};

const accentStyles: Record<CenterAccent, { active: string; bar: string; badge: string }> = {
  cyan: {
    active: 'bg-cyan-500/10 text-cyan-300 font-semibold border-cyan-500/20 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]',
    bar: 'bg-cyan-400',
    badge: 'bg-rose-500',
  },
  emerald: {
    active: 'bg-emerald-500/10 text-emerald-300 font-semibold border-emerald-500/20 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]',
    bar: 'bg-emerald-400',
    badge: 'bg-rose-500',
  },
  slate: {
    active: 'bg-white/5 text-white font-semibold border-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]',
    bar: 'bg-cyan-400',
    badge: 'bg-rose-500',
  },
};

export function CenterNavItem({
  icon,
  label,
  to,
  active = false,
  badge,
  accent = 'slate',
  onClick,
}: CenterNavItemProps) {
  const styles = accentStyles[accent];

  return (
    <Link
      to={to}
      onClick={onClick}
      className={`relative flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-transparent transition-all ${
        active
          ? styles.active
          : 'text-slate-400 hover:text-white hover:bg-white/5 font-medium'
      }`}
    >
      {active && (
        <span className={`absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full ${styles.bar}`} />
      )}
      <div className="flex items-center gap-3 min-w-0 pl-1">
        <span className={`shrink-0 ${active ? 'opacity-100' : 'opacity-80'}`}>{icon}</span>
        <span className="truncate">{label}</span>
      </div>
      {badge !== undefined && badge !== null && badge !== '' && (
        <span className={`${styles.badge} text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0`}>
          {badge}
        </span>
      )}
    </Link>
  );
}
