import { useEffect, useState } from 'react';
import { Sparkles, RefreshCw } from 'lucide-react';

type AiReportInsightProps = {
  title?: string;
  fetchSummary: () => Promise<{ summary: string; fallback: boolean }>;
};

export function AiReportInsight({ title = 'AI 성과 브리핑', fetchSummary }: AiReportInsightProps) {
  const [summary, setSummary] = useState('');
  const [fallback, setFallback] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [loaded, setLoaded] = useState(false);

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchSummary();
      setSummary(data.summary);
      setFallback(data.fallback);
      setLoaded(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'AI 요약을 생성하지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  return (
    <div className="bg-gradient-to-br from-slate-900 via-slate-800 to-cyan-950 rounded-2xl p-5 md:p-6 text-white shadow-lg border border-slate-700/50 mb-8">
      <div className="flex items-start justify-between gap-4 mb-4">
        <div className="flex items-center gap-2">
          <Sparkles size={18} className="text-cyan-400" />
          <h3 className="font-bold text-base md:text-lg">{title}</h3>
        </div>
        <button
          type="button"
          onClick={load}
          disabled={loading}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-xs font-bold border border-white/10 disabled:opacity-50"
        >
          <RefreshCw size={14} className={loading ? 'animate-spin' : ''} />
          {loading ? '분석 중…' : '새로고침'}
        </button>
      </div>

      {error && <p className="text-sm text-rose-300">{error}</p>}

      {!error && !loaded && loading && (
        <p className="text-sm text-slate-300">Gemini가 리포트 데이터를 분석하고 있습니다…</p>
      )}

      {!error && summary && (
        <>
          <div className="text-sm text-slate-200 leading-relaxed whitespace-pre-wrap">{summary}</div>
          {fallback && (
            <p className="text-[11px] text-amber-300/90 mt-3">※ API 연결 문제로 기본 요약이 표시되었습니다.</p>
          )}
        </>
      )}
    </div>
  );
}
