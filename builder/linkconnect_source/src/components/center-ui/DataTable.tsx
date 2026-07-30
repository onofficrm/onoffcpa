import React from 'react';
import { CenterAccent } from './types';
import { EmptyState } from './EmptyState';

type TableSectionProps = {
  title: string;
  icon?: React.ReactNode;
  action?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
};

export function TableSection({ title, icon, action, children, className = '' }: TableSectionProps) {
  return (
    <div className={`bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${className}`}>
      <div className="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
          {icon}
          {title}
        </h2>
        {action}
      </div>
      {children}
    </div>
  );
}

export function RankBadge({ rank }: { rank: number }) {
  if (rank > 3) return null;
  const styles = [
    'bg-amber-100 text-amber-700 border-amber-200',
    'bg-slate-200 text-slate-700 border-slate-300',
    'bg-orange-100 text-orange-700 border-orange-200',
  ];
  const labels = ['1위', '2위', '3위'];
  return (
    <span className={`inline-flex items-center px-1.5 py-0.5 text-[10px] font-black rounded border mr-2 ${styles[rank - 1]}`}>
      {labels[rank - 1]}
    </span>
  );
}

export function ProgressBar({
  value,
  max = 100,
  accent = 'cyan',
  showLabel = true,
  size = 'sm',
}: {
  value: number;
  max?: number;
  accent?: CenterAccent;
  showLabel?: boolean;
  size?: 'sm' | 'md';
}) {
  const pct = max > 0 ? Math.min(100, Math.round((value / max) * 100)) : 0;
  const barColors: Record<CenterAccent, string> = {
    cyan: 'bg-cyan-500',
    emerald: 'bg-emerald-500',
    slate: 'bg-slate-600',
  };
  const h = size === 'md' ? 'h-2.5' : 'h-2';

  return (
    <div className="min-w-[80px]">
      <div className={`w-full bg-slate-100 rounded-full overflow-hidden ${h}`}>
        <div className={`${h} rounded-full transition-all ${barColors[accent]}`} style={{ width: `${pct}%` }} />
      </div>
      {showLabel && <div className="text-[11px] text-slate-400 mt-1">{pct}%</div>}
    </div>
  );
}

export function tableRowClass(rank?: number, highlight?: boolean) {
  const base = 'transition-colors hover:bg-slate-50/80';
  if (rank === 1) return `${base} bg-amber-50/40`;
  if (rank === 2) return `${base} bg-slate-50/80`;
  if (rank === 3) return `${base} bg-orange-50/30`;
  if (highlight) return `${base} bg-blue-50/40`;
  return base;
}

type DataTableEmptyProps = {
  colSpan: number;
  title: string;
  description?: string;
  actionLabel?: string;
  actionTo?: string;
};

export function DataTableEmpty({ colSpan, title, description, actionLabel, actionTo }: DataTableEmptyProps) {
  return (
    <tr>
      <td colSpan={colSpan}>
        <EmptyState title={title} description={description} actionLabel={actionLabel} actionTo={actionTo} />
      </td>
    </tr>
  );
}
