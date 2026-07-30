import React from 'react';
import { Link } from 'react-router-dom';
import { Sparkles } from 'lucide-react';
import { CenterAccent } from './types';

type InsightAction = {
  label: string;
  to?: string;
  onClick?: () => void;
  variant?: 'primary' | 'secondary';
};

type InsightBannerProps = {
  message: React.ReactNode;
  subMessage?: string;
  accent?: CenterAccent;
  actions?: InsightAction[];
};

const accentMap: Record<CenterAccent, { wrap: string; icon: string; primary: string; secondary: string }> = {
  cyan: {
    wrap: 'bg-gradient-to-r from-cyan-50 via-white to-blue-50 border-cyan-200/80',
    icon: 'bg-cyan-100 text-cyan-600',
    primary: 'bg-cyan-600 hover:bg-cyan-700 text-white',
    secondary: 'bg-white border-cyan-200 text-cyan-700 hover:bg-cyan-50',
  },
  emerald: {
    wrap: 'bg-gradient-to-r from-emerald-50 via-white to-teal-50 border-emerald-200/80',
    icon: 'bg-emerald-100 text-emerald-600',
    primary: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    secondary: 'bg-white border-emerald-200 text-emerald-700 hover:bg-emerald-50',
  },
  slate: {
    wrap: 'bg-gradient-to-r from-slate-50 via-white to-slate-100 border-slate-200',
    icon: 'bg-slate-200 text-slate-600',
    primary: 'bg-slate-900 hover:bg-slate-800 text-white',
    secondary: 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
  },
};

export function InsightBanner({ message, subMessage, accent = 'slate', actions = [] }: InsightBannerProps) {
  const styles = accentMap[accent];

  return (
    <div className={`mb-6 rounded-2xl border p-4 sm:p-5 shadow-sm ${styles.wrap}`}>
      <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div className="flex items-start gap-3 min-w-0">
          <div className={`p-2.5 rounded-xl shrink-0 ${styles.icon}`}>
            <Sparkles size={18} />
          </div>
          <div className="min-w-0">
            <p className="text-sm sm:text-base font-semibold text-slate-900 leading-relaxed">{message}</p>
            {subMessage && <p className="text-xs sm:text-sm text-slate-500 mt-1">{subMessage}</p>}
          </div>
        </div>
        {actions.length > 0 && (
          <div className="flex flex-wrap gap-2 shrink-0">
            {actions.map((action) => {
              const cls = `px-4 py-2 rounded-xl text-sm font-bold transition-colors border ${
                action.variant === 'secondary' ? styles.secondary : styles.primary
              }`;
              if (action.to) {
                return (
                  <Link key={action.label} to={action.to} className={cls}>
                    {action.label}
                  </Link>
                );
              }
              return (
                <button key={action.label} type="button" onClick={action.onClick} className={cls}>
                  {action.label}
                </button>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
