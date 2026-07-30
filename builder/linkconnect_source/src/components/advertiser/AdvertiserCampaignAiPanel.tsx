import { useState } from 'react';
import { CheckCircle2, Copy, Loader2, Sparkles, Wand2 } from 'lucide-react';
import { AiPromoCopy, generateMerchantCampaignPromo } from '../../lib/api';

type Props = {
  cpId: number;
  campaignName: string;
  readOnly?: boolean;
  onApplyPromotionPoints?: (points: string[]) => void;
};

export function AdvertiserCampaignAiPanel({
  cpId,
  campaignName,
  readOnly = false,
  onApplyPromotionPoints,
}: Props) {
  const [targetAudience, setTargetAudience] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [copies, setCopies] = useState<AiPromoCopy[]>([]);
  const [copiedId, setCopiedId] = useState<string | null>(null);

  const handleGenerate = async () => {
    if (readOnly) return;
    const target = targetAudience.trim();
    if (!target) {
      setError('타겟 고객을 입력해 주세요.');
      return;
    }

    setLoading(true);
    setError('');
    setNotice('');
    try {
      const data = await generateMerchantCampaignPromo({
        cpId,
        title: campaignName,
        targetAudience: target,
      });
      setCopies(data.copies);
      if (data.fallback && data.message) setNotice(data.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'AI 생성에 실패했습니다.');
    } finally {
      setLoading(false);
    }
  };

  const handleCopy = (copy: AiPromoCopy) => {
    navigator.clipboard.writeText(copy.text).catch(() => {});
    setCopiedId(copy.id);
    window.setTimeout(() => setCopiedId(null), 2000);
  };

  const handleApplyPoints = () => {
    if (!onApplyPromotionPoints || copies.length === 0) return;
    const points = copies
      .slice(0, 3)
      .map((c) => c.text.split('\n')[0].trim())
      .filter(Boolean)
      .map((line) => (line.length > 80 ? `${line.slice(0, 77)}...` : line));
    if (points.length === 0) return;
    onApplyPromotionPoints(points);
    setNotice('생성된 문구에서 핵심 포인트를 반영했습니다. 아래에서 수정할 수 있습니다.');
  };

  return (
    <div className="mb-5 rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-950 via-purple-900 to-indigo-900 p-5 text-white relative overflow-hidden">
      <div className="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4" aria-hidden />
      <div className="relative z-10">
        <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/15 border border-white/10 text-xs font-bold mb-3">
          <Wand2 size={12} className="text-purple-200" /> AI 베타
        </div>
        <h4 className="text-lg font-bold mb-1">원클릭 AI 썸네일 & 배너 문구 생성</h4>
        <p className="text-indigo-200 text-sm mb-4">
          이 상품의 이름과 타겟만 입력하면, 파트너 홍보에 쓸 수 있는 문구를 자동 생성합니다.
          배너 이미지는 생성 문구를 참고해 아래 업로드 영역에 등록해 주세요.
        </p>

        <div className="grid sm:grid-cols-2 gap-3 mb-3">
          <div>
            <label className="block text-xs font-bold text-indigo-200 mb-1">상품명</label>
            <input
              type="text"
              value={campaignName}
              readOnly
              className="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/15 text-white text-sm"
            />
          </div>
          <div>
            <label className="block text-xs font-bold text-indigo-200 mb-1">타겟 고객 *</label>
            <input
              type="text"
              value={targetAudience}
              onChange={(e) => setTargetAudience(e.target.value)}
              disabled={readOnly || loading}
              placeholder="예: 30대 직장인, 저신용 대출 상담 희망자"
              className="w-full px-3 py-2.5 rounded-xl bg-white text-slate-900 border border-white/20 text-sm placeholder:text-slate-400 disabled:opacity-60"
            />
          </div>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={handleGenerate}
            disabled={readOnly || loading}
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-indigo-900 font-bold text-sm hover:bg-indigo-50 disabled:opacity-50"
          >
            {loading ? <Loader2 size={16} className="animate-spin" /> : <Sparkles size={16} />}
            {loading ? '생성 중...' : copies.length ? '다시 생성' : 'AI로 만들기'}
          </button>
          {copies.length > 0 && onApplyPromotionPoints ? (
            <button
              type="button"
              onClick={handleApplyPoints}
              disabled={readOnly}
              className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-white/25 text-white font-bold text-sm hover:bg-white/10 disabled:opacity-50"
            >
              핵심 포인트에 반영
            </button>
          ) : null}
        </div>

        {error ? <p className="mt-3 text-sm text-rose-200">{error}</p> : null}
        {notice ? <p className="mt-3 text-sm text-amber-200">{notice}</p> : null}

        {copies.length > 0 ? (
          <div className="mt-4 space-y-2 max-h-56 overflow-y-auto pr-1">
            {copies.map((copy) => (
              <div key={`${copy.id}-${copy.label}`} className="rounded-xl bg-white/10 border border-white/10 p-3">
                <div className="flex items-center justify-between gap-2 mb-1">
                  <span className="text-xs font-bold text-purple-200">{copy.label}</span>
                  <button
                    type="button"
                    onClick={() => handleCopy(copy)}
                    className="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-100 hover:text-white"
                  >
                    {copiedId === copy.id ? <CheckCircle2 size={12} /> : <Copy size={12} />}
                    {copiedId === copy.id ? '복사됨' : '복사'}
                  </button>
                </div>
                <p className="text-xs text-indigo-50 leading-relaxed whitespace-pre-wrap">{copy.text}</p>
              </div>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}
