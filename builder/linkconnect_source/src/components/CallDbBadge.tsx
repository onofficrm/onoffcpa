import { Phone, PhoneCall } from 'lucide-react';
import { cn } from '../lib/utils';

/** 제목 옆 인라인 칩 */
export function CallDbBadge({ className }: { className?: string }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-0.5 shrink-0 px-1.5 py-0.5 rounded-md text-[10px] font-bold tracking-tight',
        'bg-violet-100 text-violet-700 border border-violet-200',
        className,
      )}
      title="콜디비 가능 — 가상번호 배정 후 통화 DB"
    >
      <Phone className="w-3 h-3" aria-hidden />
      콜디비
    </span>
  );
}

/** 카드 통계 박스용 한 줄 안내 */
export function CallDbStatsHint({ className }: { className?: string }) {
  return (
    <div
      className={cn(
        'flex items-center gap-1.5 text-xs text-violet-700 font-medium pb-1 border-b border-violet-100/80',
        className,
      )}
    >
      <PhoneCall className="w-3.5 h-3.5 shrink-0" aria-hidden />
      <span>가상번호 배정 · 통화 DB</span>
    </div>
  );
}
