import React from 'react';
import { TrendingDown, TrendingUp } from 'lucide-react';
import { SummaryCardProps } from './types';
import { Skeleton } from './Skeleton';

const accentStyles: Record<string, { border: string; bg: string; text: string; iconBg: string }> = {
  slate: { border: 'border-slate-200', bg: 'bg-white', text: 'text-slate-900', iconBg: 'bg-slate-100' },
  cyan: { border: 'border-cyan-200/80', bg: 'bg-gradient-to-br from-cyan-50/80 to-white', text: 'text-cyan-700', iconBg: 'bg-cyan-100/80' },
  blue: { border: 'border-blue-200/80', bg: 'bg-gradient-to-br from-blue-50/80 to-white', text: 'text-blue-700', iconBg: 'bg-blue-100/80' },
  emerald: { border: 'border-emerald-200/80', bg: 'bg-gradient-to-br from-emerald-50/80 to-white', text: 'text-emerald-700', iconBg: 'bg-emerald-100/80' },
  rose: { border: 'border-rose-200/80', bg: 'bg-gradient-to-br from-rose-50/80 to-white', text: 'text-rose-700', iconBg: 'bg-rose-100/80' },
  red: { border: 'border-red-200/80', bg: 'bg-gradient-to-br from-red-50/80 to-white', text: 'text-red-700', iconBg: 'bg-red-100/80' },
  yellow: { border: 'border-yellow-200/80', bg: 'bg-gradient-to-br from-yellow-50/80 to-white', text: 'text-yellow-700', iconBg: 'bg-yellow-100/80' },
  purple: { border: 'border-purple-200/80', bg: 'bg-gradient-to-br from-purple-50/80 to-white', text: 'text-purple-700', iconBg: 'bg-purple-100/80' },
  amber: { border: 'border-amber-200/80', bg: 'bg-gradient-to-br from-amber-50/80 to-white', text: 'text-amber-700', iconBg: 'bg-amber-100/80' },
  indigo: { border: 'border-indigo-200/80', bg: 'bg-gradient-to-br from-indigo-50/80 to-white', text: 'text-indigo-700', iconBg: 'bg-indigo-100/80' },
  violet: { border: 'border-violet-200/80', bg: 'bg-gradient-to-br from-violet-50/80 to-white', text: 'text-violet-700', iconBg: 'bg-violet-100/80' },
};

export function SummaryCard({
  title,
  value,
  suffix = '',
  icon,
  highlight = false,
  dark = false,
  color = 'slate',
  subtitle,
  caption,
  trend,
  trendLabel,
  loading = false,
}: SummaryCardProps) {
  if (loading) {
    return (
      <div className="p-5 rounded-2xl border border-slate-200 bg-white h-full">
        <Skeleton className="h-4 w-24 mb-4" />
        <Skeleton className="h-8 w-32 mb-2" />
        <Skeleton className="h-3 w-20" />
      </div>
    );
  }

  if (dark) {
    return (
      <div className="group relative overflow-hidden bg-slate-900 p-5 rounded-2xl shadow-lg flex flex-col justify-between h-full border border-slate-800 hover:border-slate-700 transition-all">
        <div className="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none" />
        <div className="relative flex items-center justify-between mb-4">
          <div className="text-sm font-medium text-slate-400">{title}</div>
          {icon && <div className="p-2.5 rounded-xl bg-slate-800 border border-slate-700 shrink-0">{icon}</div>}
        </div>
        <div className="relative">
          <div className="text-2xl md:text-3xl font-bold text-white tracking-tight">
            {value} {suffix && <span className="text-sm font-normal text-slate-400">{suffix}</span>}
          </div>
          {subtitle && <div className="text-xs text-slate-400 mt-1.5">{subtitle}</div>}
          {caption && <div className="text-xs text-slate-500 mt-1">{caption}</div>}
        </div>
      </div>
    );
  }

  const accent = highlight ? accentStyles[color] ?? accentStyles.slate : accentStyles.slate;
  const valueColor = highlight ? accent.text : 'text-slate-900';

  return (
    <div
      className={`group relative overflow-hidden p-5 rounded-2xl shadow-sm border flex flex-col justify-between h-full transition-all hover:shadow-md hover:-translate-y-0.5 ${
        highlight ? `${accent.border} ${accent.bg}` : 'bg-white border-slate-200'
      }`}
    >
      <div className="flex items-start justify-between mb-3 gap-2">
        <div className="min-w-0">
          <div className="text-sm font-medium text-slate-500 truncate">{title}</div>
          {caption && <div className="text-[11px] text-slate-400 mt-0.5 truncate">{caption}</div>}
        </div>
        {icon && (
          <div className={`p-2.5 rounded-xl shrink-0 border border-white/60 shadow-sm ${highlight ? accent.iconBg : 'bg-slate-50'}`}>
            {icon}
          </div>
        )}
      </div>
      <div>
        <div className={`text-2xl md:text-3xl font-bold tracking-tight ${valueColor}`}>
          {value} {suffix && <span className="text-sm md:text-base font-medium text-slate-400">{suffix}</span>}
        </div>
        <div className="flex items-center gap-2 mt-1.5 flex-wrap">
          {typeof trend === 'number' && (
            <span className={`inline-flex items-center gap-0.5 text-xs font-bold px-1.5 py-0.5 rounded-md ${
              trend >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'
            }`}>
              {trend >= 0 ? <TrendingUp size={12} /> : <TrendingDown size={12} />}
              {trend >= 0 ? '+' : ''}{trend}%
            </span>
          )}
          {trendLabel && <span className="text-xs text-slate-400">{trendLabel}</span>}
          {subtitle && !trendLabel && <span className="text-xs text-slate-400">{subtitle}</span>}
        </div>
      </div>
    </div>
  );
}
