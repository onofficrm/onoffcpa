import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  AlertTriangle,
  Check,
  CheckCircle2,
  Copy,
  Download,
  ExternalLink,
  Loader2,
  X,
} from 'lucide-react';
import {
  PartnerCampaign,
  PartnerPromoGuideDetail,
  PartnerPromoGuideImage,
  confirmPartnerPromoGuide,
  fetchPartnerPromoGuide,
} from '../../lib/api';
import { promoGuideApprovalPartnerMessage } from '../../lib/campaignPromoGuide';
import { openLandingPage } from '../../lib/utils';

type DetailTab = 'intro' | 'guide' | 'assets';

type Props = {
  campaign: PartnerCampaign | null;
  initialTab?: DetailTab;
  open: boolean;
  onClose: () => void;
  onConfirmationChange?: (campaignId: number, confirmed: boolean) => void;
};

async function copyText(text: string) {
  await navigator.clipboard.writeText(text);
}

export function PartnerCampaignDetailModal({
  campaign,
  initialTab = 'intro',
  open,
  onClose,
  onConfirmationChange,
}: Props) {
  const [tab, setTab] = useState<DetailTab>(initialTab);
  const [loading, setLoading] = useState(false);
  const [confirming, setConfirming] = useState(false);
  const [error, setError] = useState('');
  const [copyNotice, setCopyNotice] = useState('');
  const [detail, setDetail] = useState<PartnerPromoGuideDetail | null>(null);

  const cpId = campaign?.id ?? 0;
  const hasGuide = Boolean(campaign?.hasPublishedGuide);

  const loadGuide = useCallback(async () => {
    if (!cpId || !hasGuide) {
      setDetail(null);
      return;
    }
    setLoading(true);
    setError('');
    try {
      const data = await fetchPartnerPromoGuide(cpId);
      setDetail(data);
    } catch (err) {
      setDetail(null);
      setError(err instanceof Error ? err.message : '홍보 가이드를 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  }, [cpId, hasGuide]);

  useEffect(() => {
    if (!open) return;
    setTab(initialTab);
    setCopyNotice('');
    if (initialTab === 'guide' || initialTab === 'assets' || hasGuide) {
      loadGuide();
    } else {
      setDetail(null);
    }
  }, [open, initialTab, hasGuide, loadGuide]);

  useEffect(() => {
    if (!open) return;
    if ((tab === 'guide' || tab === 'assets') && hasGuide && !detail && !loading) {
      loadGuide();
    }
  }, [tab, open, hasGuide, detail, loading, loadGuide]);

  const guide = detail?.guide;
  const confirmation = detail?.confirmation;
  const images = guide?.images ?? [];

  const overviewItems = useMemo(() => {
    if (!campaign) return [];
    const items: Array<{ label: string; value: string }> = [];
    if (campaign.priceFormatted) {
      items.push({ label: 'CPA 단가', value: `${campaign.priceFormatted}원` });
    }
    if (guide?.approvalType) {
      items.push({
        label: '광고물 승인 방식',
        value:
          guide.approvalType === 'free'
            ? '자유 홍보'
            : guide.approvalType === 'first_review'
              ? '최초만 승인'
              : '사전 승인 필수',
      });
    }
    return items;
  }, [campaign, guide?.approvalType]);

  const handleCopyKeyword = async (keyword: string) => {
    try {
      await copyText(keyword);
      setCopyNotice(`「${keyword}」 복사됨`);
      window.setTimeout(() => setCopyNotice(''), 2000);
    } catch {
      setCopyNotice('복사에 실패했습니다.');
    }
  };

  const handleCopyAllKeywords = async () => {
    if (!guide?.recommendedKeywords?.length) return;
    try {
      await copyText(guide.recommendedKeywords.join(', '));
      setCopyNotice('추천 키워드 전체가 복사되었습니다.');
      window.setTimeout(() => setCopyNotice(''), 2000);
    } catch {
      setCopyNotice('복사에 실패했습니다.');
    }
  };

  const handleConfirm = async () => {
    if (!cpId || confirming) return;
    setConfirming(true);
    setError('');
    try {
      const res = await confirmPartnerPromoGuide(cpId);
      setDetail((prev) =>
        prev
          ? {
              ...prev,
              confirmation: res.confirmation,
            }
          : prev,
      );
      onConfirmationChange?.(cpId, res.confirmation.confirmed);
      setCopyNotice(res.message);
      window.setTimeout(() => setCopyNotice(''), 2500);
    } catch (err) {
      setError(err instanceof Error ? err.message : '확인 처리에 실패했습니다.');
    } finally {
      setConfirming(false);
    }
  };

  if (!open || !campaign) return null;

  const tabs: Array<{ id: DetailTab; label: string; show: boolean }> = [
    { id: 'intro', label: '상품소개', show: true },
    { id: 'guide', label: '홍보 가이드', show: hasGuide },
    { id: 'assets', label: '홍보자료', show: hasGuide },
  ];

  return (
    <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/50 backdrop-blur-sm">
      <div className="bg-white w-full sm:max-w-3xl max-h-[92vh] sm:rounded-3xl shadow-xl flex flex-col overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 shrink-0">
          <div className="min-w-0">
            <p className="text-xs font-semibold text-emerald-600 mb-1">{campaign.category} · CPA</p>
            <h2 className="text-lg font-bold text-slate-900 truncate">{campaign.title}</h2>
          </div>
          <button type="button" onClick={onClose} className="p-2 rounded-lg text-slate-400 hover:bg-slate-100" aria-label="닫기">
            <X size={20} />
          </button>
        </div>

        <div className="px-5 pt-3 border-b border-slate-100 flex gap-2 overflow-x-auto shrink-0">
          {tabs
            .filter((t) => t.show)
            .map((t) => (
              <button
                key={t.id}
                type="button"
                onClick={() => setTab(t.id)}
                className={`px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-colors ${
                  tab === t.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                {t.label}
              </button>
            ))}
        </div>

        <div className="flex-1 overflow-y-auto p-5 space-y-5">
          {copyNotice ? (
            <div className="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-2.5">{copyNotice}</div>
          ) : null}
          {error ? (
            <div className="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-2.5">{error}</div>
          ) : null}

          {tab === 'intro' && (
            <IntroTab campaign={campaign} overviewItems={overviewItems} onOpenGuide={hasGuide ? () => setTab('guide') : undefined} />
          )}

          {(tab === 'guide' || tab === 'assets') && (
            <>
              {loading ? (
                <div className="flex items-center justify-center py-16 text-slate-500 gap-2">
                  <Loader2 className="animate-spin" size={20} /> 불러오는 중...
                </div>
              ) : !guide ? (
                <p className="text-sm text-slate-500 py-8 text-center">공개된 홍보 가이드가 없습니다.</p>
              ) : tab === 'assets' ? (
                <AssetsTab images={images} />
              ) : (
                <GuideTab
                  guide={guide}
                  overviewItems={overviewItems}
                  onCopyKeyword={handleCopyKeyword}
                  onCopyAllKeywords={handleCopyAllKeywords}
                />
              )}
            </>
          )}
        </div>

        {tab === 'guide' && guide ? (
          <div className="px-5 py-4 border-t border-slate-100 bg-slate-50 shrink-0 space-y-3">
            {confirmation && !confirmation.confirmed ? (
              <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                광고를 시작하기 전에 최신 홍보 가이드를 확인해 주세요.
              </p>
            ) : confirmation?.confirmed ? (
              <p className="text-xs text-emerald-700 flex items-center gap-1.5">
                <CheckCircle2 size={14} /> 최신 홍보 가이드를 확인했습니다.
              </p>
            ) : null}
            <button
              type="button"
              disabled={confirming || confirmation?.confirmed}
              onClick={handleConfirm}
              className="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {confirming ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />}
              {confirmation?.confirmed ? '확인 완료' : '홍보 가이드를 확인했습니다'}
            </button>
          </div>
        ) : null}
      </div>
    </div>
  );
}

function IntroTab({
  campaign,
  overviewItems,
  onOpenGuide,
}: {
  campaign: PartnerCampaign;
  overviewItems: Array<{ label: string; value: string }>;
  onOpenGuide?: () => void;
}) {
  return (
    <div className="space-y-5">
      {overviewItems.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
          {overviewItems.map((item) => (
            <div key={item.label} className="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <p className="text-xs text-slate-500 mb-1">{item.label}</p>
              <p className="text-sm font-bold text-slate-900">{item.value}</p>
            </div>
          ))}
        </div>
      ) : null}

      {campaign.description ? (
        <section>
          <h3 className="text-sm font-bold text-slate-900 mb-2">상품 소개</h3>
          <p className="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{campaign.description}</p>
        </section>
      ) : null}

      <div className="grid sm:grid-cols-2 gap-3 text-sm">
        {campaign.allowedChannels ? (
          <div className="rounded-xl border border-slate-200 p-3">
            <p className="text-xs text-slate-500 mb-1">허용 채널</p>
            <p className="text-slate-800">{campaign.allowedChannels}</p>
          </div>
        ) : null}
        {campaign.forbiddenChannels ? (
          <div className="rounded-xl border border-amber-100 bg-amber-50/50 p-3">
            <p className="text-xs text-amber-700 mb-1">금지 채널</p>
            <p className="text-amber-900">{campaign.forbiddenChannels}</p>
          </div>
        ) : null}
      </div>

      {(() => {
        const isCps = campaign.campaignType === 'cps' || campaign.type === 'cps';
        if (isCps) {
          return campaign.avgTime ? (
            <div className="flex flex-wrap gap-4 text-sm text-slate-600">
              <span>쿠키 기간 {campaign.avgTime}</span>
            </div>
          ) : null;
        }
        if (!campaign.approvalRate && !campaign.avgTime) return null;
        return (
          <div className="flex flex-wrap gap-4 text-sm text-slate-600">
            {campaign.approvalRate ? <span>승인율 {campaign.approvalRate}</span> : null}
            {campaign.avgTime ? <span>평균 처리 {campaign.avgTime}</span> : null}
          </div>
        );
      })()}

      <div className="flex flex-wrap gap-2">
        {onOpenGuide ? (
          <button
            type="button"
            onClick={onOpenGuide}
            className="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-500"
          >
            홍보 가이드 보기
          </button>
        ) : null}
        {campaign.landingUrl ? (
          <button
            type="button"
            onClick={() => openLandingPage(campaign.landingUrl)}
            className="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 inline-flex items-center gap-1.5"
          >
            <ExternalLink size={16} /> 랜딩페이지
          </button>
        ) : null}
      </div>
    </div>
  );
}

function GuideTab({
  guide,
  overviewItems,
  onCopyKeyword,
  onCopyAllKeywords,
}: {
  guide: PartnerPromoGuideDetail['guide'];
  overviewItems: Array<{ label: string; value: string }>;
  onCopyKeyword: (kw: string) => void;
  onCopyAllKeywords: () => void;
}) {
  return (
    <div className="space-y-6">
      {overviewItems.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
          {overviewItems.map((item) => (
            <div key={item.label} className="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
              <p className="text-[11px] text-emerald-800/70">{item.label}</p>
              <p className="text-sm font-bold text-emerald-900">{item.value}</p>
            </div>
          ))}
        </div>
      ) : null}

      {guide.promotionPoints?.length > 0 ? (
        <section>
          <h3 className="text-sm font-bold text-slate-900 mb-3">핵심 홍보 포인트</h3>
          <div className="grid gap-2 sm:grid-cols-3">
            {guide.promotionPoints.map((point, i) => (
              <div key={i} className="rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-4 text-sm font-semibold leading-snug shadow-sm">
                {point}
              </div>
            ))}
          </div>
        </section>
      ) : null}

      {guide.recommendedKeywords?.length > 0 ? (
        <section>
          <div className="flex items-center justify-between gap-2 mb-3">
            <h3 className="text-sm font-bold text-slate-900">추천키워드</h3>
            <button
              type="button"
              onClick={onCopyAllKeywords}
              className="text-xs font-semibold text-cyan-700 hover:text-cyan-900 inline-flex items-center gap-1"
            >
              <Copy size={14} /> 전체 복사
            </button>
          </div>
          <div className="flex flex-wrap gap-2">
            {guide.recommendedKeywords.map((kw) => (
              <button
                key={kw}
                type="button"
                onClick={() => onCopyKeyword(kw)}
                className="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-cyan-50 hover:text-cyan-800 text-sm font-medium text-slate-700 border border-slate-200 transition-colors"
              >
                {kw}
              </button>
            ))}
          </div>
        </section>
      ) : null}

      {guide.forbiddenWords?.length > 0 ? (
        <section className="rounded-xl border border-rose-200 bg-rose-50/60 p-4">
          <h3 className="text-sm font-bold text-rose-900 mb-2 flex items-center gap-1.5">
            <AlertTriangle size={16} /> 사용 금지어
          </h3>
          <div className="flex flex-wrap gap-2">
            {guide.forbiddenWords.map((word) => (
              <span key={word} className="px-2.5 py-1 rounded-md bg-white border border-rose-200 text-rose-800 text-sm">
                {word}
              </span>
            ))}
          </div>
        </section>
      ) : null}

      {guide.images?.length > 0 ? (
        <section>
          <h3 className="text-sm font-bold text-slate-900 mb-3">홍보 이미지 · 배너</h3>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {guide.images.map((img) => (
              <ImageCard key={img.id} image={img} compact />
            ))}
          </div>
        </section>
      ) : null}

      {guide.precautions?.length > 0 ? (
        <section>
          <h3 className="text-sm font-bold text-slate-900 mb-2">홍보 시 유의사항</h3>
          <ul className="space-y-2">
            {guide.precautions.map((item, i) => (
              <li key={i} className="flex items-start gap-2 text-sm text-slate-700">
                <CheckCircle2 size={16} className="text-emerald-500 shrink-0 mt-0.5" />
                <span>{item}</span>
              </li>
            ))}
          </ul>
        </section>
      ) : null}

      {(guide.validDbRules?.length > 0 || guide.invalidDbRules?.length > 0) && (
        <section className="grid sm:grid-cols-2 gap-4">
          {guide.validDbRules?.length > 0 ? (
            <div className="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
              <h3 className="text-sm font-bold text-emerald-900 mb-2">유효 DB</h3>
              <ul className="space-y-1.5 text-sm text-emerald-900/90">
                {guide.validDbRules.map((r, i) => (
                  <li key={i}>· {r}</li>
                ))}
              </ul>
            </div>
          ) : null}
          {guide.invalidDbRules?.length > 0 ? (
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <h3 className="text-sm font-bold text-slate-800 mb-2">무효 DB</h3>
              <ul className="space-y-1.5 text-sm text-slate-700">
                {guide.invalidDbRules.map((r, i) => (
                  <li key={i}>· {r}</li>
                ))}
              </ul>
            </div>
          ) : null}
        </section>
      )}

      {guide.approvalType ? (
        <section className="rounded-xl border-2 border-cyan-200 bg-cyan-50 p-4">
          <h3 className="text-sm font-bold text-cyan-900 mb-1">광고물 승인 안내</h3>
          <p className="text-sm text-cyan-900/90 leading-relaxed">{promoGuideApprovalPartnerMessage(guide.approvalType)}</p>
        </section>
      ) : null}
    </div>
  );
}

function AssetsTab({ images }: { images: PartnerPromoGuideImage[] }) {
  if (images.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
        광고주가 등록한 홍보 소재(배너·이미지)가 아직 없습니다.
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
      {images.map((img) => (
        <ImageCard key={img.id} image={img} />
      ))}
    </div>
  );
}

function ImageCard({ image, compact = false }: { image: PartnerPromoGuideImage; compact?: boolean }) {
  const title = image.imageTitle || image.originalFilename || '홍보 이미지';
  const downloadName = image.originalFilename || `promo-${image.id}.jpg`;

  return (
    <div className="rounded-xl border border-slate-200 overflow-hidden bg-white">
      <div className={`bg-slate-100 ${compact ? 'aspect-video' : 'aspect-[4/3]'}`}>
        <img src={image.downloadUrl} alt={title} className="w-full h-full object-contain" loading="lazy" />
      </div>
      <div className="p-3 flex items-center justify-between gap-2">
        <p className="text-sm font-medium text-slate-800 truncate">{title}</p>
        <a
          href={image.downloadUrl}
          download={downloadName}
          className="shrink-0 p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
          aria-label="다운로드"
        >
          <Download size={16} />
        </a>
      </div>
    </div>
  );
}
