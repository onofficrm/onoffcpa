import { useState } from 'react';
import { Copy, Sparkles, CheckCircle2 } from 'lucide-react';
import { AiPromoCopy, generatePartnerPromo } from '../lib/api';
import { canAccessPartnerCenter } from '../lib/auth';
import { g5LoginUrl } from '../lib/urls';

export type AiPromoCampaign = {
  id?: number;
  title: string;
  category?: string;
  price?: string;
  approvalRate?: string;
  allowedChannels?: string;
  forbiddenChannels?: string;
  eventTitle?: string;
};

export function AiPromoPanel({ campaign }: { campaign: AiPromoCampaign }) {
  const [copies, setCopies] = useState<AiPromoCopy[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [copiedId, setCopiedId] = useState<string | null>(null);
  const canUse = canAccessPartnerCenter();

  const handleGenerate = async () => {
    if (!canUse) return;
    setLoading(true);
    setError('');
    setNotice('');
    try {
      const data = await generatePartnerPromo({
        campaignId: campaign.id,
        title: campaign.title,
        category: campaign.category,
        price: campaign.price,
        approvalRate: campaign.approvalRate,
        allowedChannels: campaign.allowedChannels,
        forbiddenChannels: campaign.forbiddenChannels,
        eventTitle: campaign.eventTitle,
      });
      setCopies(data.copies);
      if (data.fallback && data.message) setNotice(data.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : '홍보 문구 생성에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleCopy = (copy: AiPromoCopy) => {
    navigator.clipboard.writeText(copy.text).catch(() => {});
    setCopiedId(copy.id);
    window.setTimeout(() => setCopiedId(null), 2000);
  };

  return (
    <div className="mt-4 pt-4 border-t border-slate-200">
      <div className="flex items-center justify-between gap-3 mb-3">
        <div className="flex items-center gap-2 text-sm font-bold text-slate-800">
          <Sparkles size={16} className="text-cyan-500" /> AI 홍보 문구
        </div>
        <button
          type="button"
          onClick={handleGenerate}
          disabled={loading || !canUse}
          className="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold disabled:opacity-60"
        >
          {loading ? '생성 중…' : copies.length ? '다시 생성' : '문구 생성'}
        </button>
      </div>

      {!canUse && (
        <p className="text-xs text-slate-500 mb-2">
          AI 홍보 문구는 파트너 로그인 후 사용할 수 있습니다.{' '}
          <a href={g5LoginUrl('/partner/search')} className="text-cyan-600 font-bold hover:underline">로그인</a>
        </p>
      )}

      {error && <p className="text-xs text-rose-600 mb-2">{error}</p>}
      {notice && <p className="text-xs text-amber-600 mb-2">{notice}</p>}

      {copies.length > 0 && (
        <div className="space-y-2 max-h-64 overflow-y-auto">
          {copies.map((copy) => (
            <div key={copy.id + copy.label} className="bg-slate-50 border border-slate-200 rounded-xl p-3">
              <div className="flex items-center justify-between gap-2 mb-1.5">
                <span className="text-xs font-bold text-cyan-700">{copy.label}</span>
                <button
                  type="button"
                  onClick={() => handleCopy(copy)}
                  className="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 hover:text-slate-800"
                >
                  {copiedId === copy.id ? <CheckCircle2 size={12} className="text-emerald-500" /> : <Copy size={12} />}
                  {copiedId === copy.id ? '복사됨' : '복사'}
                </button>
              </div>
              <p className="text-xs text-slate-600 leading-relaxed whitespace-pre-wrap">{copy.text}</p>
            </div>
          ))}
        </div>
      )}

      {!copies.length && !loading && (
        <p className="text-xs text-slate-400">캠페인 정보를 바탕으로 블로그·SNS·카페용 홍보 문구를 생성합니다.</p>
      )}
    </div>
  );
}
