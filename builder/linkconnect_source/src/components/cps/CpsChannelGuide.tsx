import { useEffect, useState } from 'react';
import { AlertTriangle, CheckCircle2, X } from 'lucide-react';

type CpsChannelGuideProps = {
  allowedChannels?: string;
  forbiddenChannels?: string;
  merchantName?: string;
  compact?: boolean;
  /** 상세페이지 등 — 모달 없이 항목을 바로 나열 */
  expanded?: boolean;
};

/** 채널·제한 문구를 한 줄 덩어리가 아닌 항목 목록으로 분리 */
export function parseChannelItems(raw?: string): string[] {
  let text = (raw || '').trim();
  if (!text || text === '-') return [];

  text = text
    .replace(/\r\n/g, '\n')
    .replace(/[※＊*•∙ㆍ●○]/g, '\n')
    .replace(/\s*[;/|]\s*/g, '\n')
    .replace(/\s{2,}/g, '\n');

  let parts = text
    .split('\n')
    .map((s) => s.replace(/^[\s.\-–—)\]>]+/, '').replace(/[\s.\-–—]+$/, '').trim())
    .filter(Boolean);

  if (parts.length === 1) {
    const byComma = parts[0]
      .split(/[,，、]/)
      .map((s) => s.trim())
      .filter(Boolean);
    if (byComma.length >= 2 && byComma.every((p) => p.length <= 48)) {
      parts = byComma;
    }
  }

  const seen = new Set<string>();
  const out: string[] = [];
  for (const part of parts) {
    const key = part.toLowerCase();
    if (seen.has(key)) continue;
    seen.add(key);
    out.push(part);
  }
  return out;
}

export function CpsChannelGuide({
  allowedChannels,
  forbiddenChannels,
  merchantName,
  compact = false,
  expanded = false,
}: CpsChannelGuideProps) {
  const [open, setOpen] = useState(false);
  const allowedItems = parseChannelItems(allowedChannels);
  const forbiddenItems = parseChannelItems(forbiddenChannels);
  const hasForbidden = forbiddenItems.length > 0;

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setOpen(false);
    };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [open]);

  if (expanded) {
    return (
      <div className="space-y-5">
        <div>
          <div className="flex items-center gap-2 mb-2.5">
            <CheckCircle2 size={16} className="text-emerald-500 shrink-0" />
            <h3 className="text-sm font-bold text-slate-900">허용 채널</h3>
          </div>
          {allowedItems.length > 0 ? (
            <ul className="flex flex-wrap gap-2">
              {allowedItems.map((item) => (
                <li
                  key={item}
                  className="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-sm font-medium text-emerald-800"
                >
                  {item}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-slate-400">안내된 허용 채널이 없습니다.</p>
          )}
        </div>

        <div>
          <div className="flex items-center gap-2 mb-2.5">
            <AlertTriangle size={16} className="text-rose-500 shrink-0" />
            <h3 className="text-sm font-bold text-slate-900">금지 채널 · 제한 사항</h3>
          </div>
          {hasForbidden ? (
            <ul className="space-y-2">
              {forbiddenItems.map((item, index) => (
                <li
                  key={`${index}-${item.slice(0, 24)}`}
                  className="flex gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-3.5 py-2.5"
                >
                  <span className="mt-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-rose-100 text-[11px] font-bold text-rose-700">
                    {index + 1}
                  </span>
                  <p className="text-sm text-rose-950 leading-relaxed break-keep">{item}</p>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-slate-400 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5">
              등록된 금지 채널이 없습니다.
            </p>
          )}
        </div>
      </div>
    );
  }

  return (
    <>
      <div className={`space-y-1.5 ${compact ? 'text-[11px]' : 'text-xs'}`}>
        <div className="flex items-start gap-1.5 text-slate-600">
          <CheckCircle2 size={compact ? 12 : 13} className="text-emerald-500 shrink-0 mt-0.5" />
          <p className="leading-relaxed line-clamp-2">
            <span className="font-semibold text-slate-500">허용 </span>
            {allowedItems.length > 0 ? allowedItems.join(', ') : '—'}
          </p>
        </div>
        {hasForbidden ? (
          <button
            type="button"
            onClick={() => setOpen(true)}
            className="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-rose-50 border border-rose-100 text-rose-700 text-[11px] font-semibold hover:bg-rose-100 hover:border-rose-200 transition-colors"
          >
            <AlertTriangle size={12} className="shrink-0" />
            금지 채널 안내
          </button>
        ) : (
          <p className="text-slate-400 pl-0.5">금지 채널 없음</p>
        )}
      </div>

      {open && hasForbidden ? (
        <div
          className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="cps-forbidden-title"
        >
          <button
            type="button"
            className="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"
            aria-label="닫기"
            onClick={() => setOpen(false)}
          />
          <div className="relative w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in slide-in-from-bottom-4 sm:zoom-in-95 duration-200">
            <div className="flex items-start justify-between gap-3 px-5 py-4 border-b border-slate-100 bg-rose-50/60">
              <div>
                <p className="text-[11px] font-bold uppercase tracking-wide text-rose-600 mb-0.5">금지 채널 안내</p>
                <h3 id="cps-forbidden-title" className="font-bold text-slate-900 text-base">
                  {merchantName || '광고주'}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white/80 transition-colors"
                aria-label="닫기"
              >
                <X size={18} />
              </button>
            </div>
            <div className="px-5 py-4 max-h-[60vh] overflow-y-auto">
              <ul className="space-y-2">
                {forbiddenItems.map((item, index) => (
                  <li
                    key={`${index}-${item.slice(0, 24)}`}
                    className="flex gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-3.5 py-2.5"
                  >
                    <span className="mt-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-rose-100 text-[11px] font-bold text-rose-700">
                      {index + 1}
                    </span>
                    <p className="text-sm text-rose-950 leading-relaxed break-keep">{item}</p>
                  </li>
                ))}
              </ul>
            </div>
            <div className="px-5 py-3 border-t border-slate-100 bg-slate-50">
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition-colors"
              >
                확인
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
