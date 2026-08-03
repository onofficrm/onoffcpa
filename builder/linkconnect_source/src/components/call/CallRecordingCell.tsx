import React, { useState } from 'react';
import { Headphones, Loader2, PlayCircle } from 'lucide-react';
import type { CallRecordingRequestMeta } from '../../lib/api';

type Props = {
  meta?: CallRecordingRequestMeta;
  onRequest: (memo?: string) => Promise<void>;
  compact?: boolean;
};

const statusCls: Record<string, string> = {
  pending: 'bg-amber-50 text-amber-700 border-amber-200',
  fulfilled: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  rejected: 'bg-slate-100 text-slate-500 border-slate-200',
};

export function CallRecordingCell({ meta, onRequest, compact }: Props) {
  const [busy, setBusy] = useState(false);
  const [showMemo, setShowMemo] = useState(false);
  const [memo, setMemo] = useState('');

  const handleRequest = async () => {
    setBusy(true);
    try {
      await onRequest(memo.trim() || undefined);
      setShowMemo(false);
      setMemo('');
    } finally {
      setBusy(false);
    }
  };

  if (meta?.canPlay && meta.playUrl) {
    return (
      <div className="flex flex-col items-center gap-1">
        <a
          href={meta.playUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1 text-cyan-600 font-bold text-xs hover:text-cyan-700"
        >
          <PlayCircle size={compact ? 14 : 16} />
          재생
        </a>
        {!compact && (
          <audio controls preload="none" src={meta.playUrl} className="h-8 w-36 max-w-full" />
        )}
      </div>
    );
  }

  if (meta?.status === 'pending') {
    const cls = statusCls.pending;
    return (
      <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold border ${cls}`}>
        <Loader2 size={12} className="animate-spin" />
        {meta.statusLabel || '요청 대기'}
      </span>
    );
  }

  if (meta?.status === 'rejected') {
    return (
      <div className="flex flex-col items-center gap-1">
        <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-bold border ${statusCls.rejected}`}>
          {meta.statusLabel || '반려'}
        </span>
        {meta.canRequest && (
          <button
            type="button"
            onClick={() => setShowMemo((v) => !v)}
            disabled={busy}
            className="text-xs font-bold text-cyan-600 hover:text-cyan-700 disabled:opacity-50"
          >
            다시 요청
          </button>
        )}
        {showMemo && (
          <RequestMemoForm
            memo={memo}
            busy={busy}
            onMemoChange={setMemo}
            onSubmit={handleRequest}
            onCancel={() => setShowMemo(false)}
          />
        )}
      </div>
    );
  }

  if (meta?.canRequest !== false) {
    return (
      <div className="flex flex-col items-center gap-1">
        {!showMemo ? (
          <button
            type="button"
            onClick={() => setShowMemo(true)}
            disabled={busy}
            className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-violet-50 text-violet-700 border border-violet-200 rounded-lg hover:bg-violet-100 disabled:opacity-50"
          >
            <Headphones size={13} />
            녹음 요청
          </button>
        ) : (
          <RequestMemoForm
            memo={memo}
            busy={busy}
            onMemoChange={setMemo}
            onSubmit={handleRequest}
            onCancel={() => setShowMemo(false)}
          />
        )}
      </div>
    );
  }

  return <span className="text-slate-300 text-xs">—</span>;
}

function RequestMemoForm({
  memo,
  busy,
  onMemoChange,
  onSubmit,
  onCancel,
}: {
  memo: string;
  busy: boolean;
  onMemoChange: (v: string) => void;
  onSubmit: () => void;
  onCancel: () => void;
}) {
  return (
    <div className="w-44 space-y-1.5">
      <input
        type="text"
        value={memo}
        onChange={(e) => onMemoChange(e.target.value)}
        placeholder="요청 메모 (선택)"
        className="w-full px-2 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg"
      />
      <div className="flex gap-1 justify-center">
        <button
          type="button"
          onClick={onCancel}
          className="px-2 py-0.5 text-xs text-slate-500"
        >
          취소
        </button>
        <button
          type="button"
          onClick={onSubmit}
          disabled={busy}
          className="px-2 py-0.5 text-xs font-bold bg-cyan-500 text-white rounded disabled:opacity-50"
        >
          {busy ? '요청 중…' : '요청'}
        </button>
      </div>
    </div>
  );
}
