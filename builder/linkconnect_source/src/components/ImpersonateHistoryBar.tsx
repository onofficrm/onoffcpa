import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Clock, Eye } from 'lucide-react';
import { fetchImpersonateHistory, ImpersonateHistoryItem, viewAsMerchant, viewAsPartner } from '../lib/api';

export function ImpersonateHistoryBar() {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState<ImpersonateHistoryItem[]>([]);
  const rootRef = useRef<HTMLDivElement>(null);

  const load = useCallback(() => {
    fetchImpersonateHistory()
      .then((data) => setItems(data.history ?? []))
      .catch(() => setItems([]));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  const handleReopen = async (item: ImpersonateHistoryItem) => {
    try {
      const result =
        item.type === 'merchant'
          ? await viewAsMerchant(item.targetId)
          : await viewAsPartner(item.targetId);
      window.location.href = result.redirect || (item.type === 'merchant' ? '/advertiser' : '/partner');
    } catch {
      // ignore
    }
  };

  if (items.length === 0) {
    return null;
  }

  return (
    <div className="relative" ref={rootRef}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-300 hover:text-white bg-slate-800 border border-slate-700 rounded-lg"
      >
        <Clock size={14} />
        최근 계정 보기
      </button>
      {open ? (
        <div className="absolute right-0 top-full mt-2 w-72 bg-slate-900 border border-slate-700 rounded-xl shadow-xl z-[100] overflow-hidden">
          <div className="px-3 py-2 border-b border-slate-800 text-xs font-bold text-slate-400">최근 전환 기록</div>
          {items.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => handleReopen(item)}
              className="w-full text-left px-3 py-2.5 hover:bg-slate-800 border-b border-slate-800 last:border-b-0"
            >
              <div className="flex items-center gap-2 text-sm text-white font-medium">
                <Eye size={14} className="text-cyan-400 shrink-0" />
                <span className="truncate">{item.label || item.type + ' #' + item.targetId}</span>
              </div>
              <div className="text-[10px] text-slate-500 mt-0.5">{item.startedAt.slice(0, 16)}</div>
            </button>
          ))}
        </div>
      ) : null}
    </div>
  );
}
