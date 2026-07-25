import { Link } from 'react-router-dom';
import { Home } from 'lucide-react';

type CenterHomeLinkProps = {
  accent?: 'emerald' | 'cyan' | 'slate';
  className?: string;
};

const accentClass: Record<NonNullable<CenterHomeLinkProps['accent']>, string> = {
  emerald: 'text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 border-emerald-200',
  cyan: 'text-cyan-600 hover:text-cyan-700 hover:bg-cyan-50 border-cyan-200',
  slate: 'text-slate-300 hover:text-white hover:bg-slate-800 border-slate-700',
};

export function CenterHomeLink({ accent = 'emerald', className = '' }: CenterHomeLinkProps) {
  return (
    <Link
      to="/"
      className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border bg-white text-sm font-medium transition-colors shadow-sm ${accentClass[accent]} ${className}`}
    >
      <Home size={16} />
      홈페이지
    </Link>
  );
}
