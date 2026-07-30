import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  AlertTriangle,
  ArrowLeft,
  Check,
  CheckCircle2,
  Copy,
  Download,
  ExternalLink,
  Link2,
  Loader2,
  Sparkles,
} from 'lucide-react';
import {
  PartnerPromoGuideDetail,
  PartnerPromoGuideImage,
  PublicCampaign,
  buildPartnerCpaShortlink,
  confirmPartnerPromoGuide,
  fetchPartnerPromoGuide,
  fetchPublicCpaCampaign,
} from '../../lib/api';
import { getLcAuth, isLcActivePartner, isLcLoggedIn } from '../../lib/auth';
import { g5LoginUrl, spaReturnUrl } from '../../lib/urls';
import { openLandingPage } from '../../lib/utils';
import { CpsChannelGuide } from '../../components/cps/CpsChannelGuide';
import { CPA_THUMBNAIL_ASPECT_CLASS } from '../../lib/cpaThumbnail';

function GuideList({ title, items, tone = 'slate' }: { title: string; items: string[]; tone?: 'slate' | 'amber' | 'rose' }) {
  if (!items.length) return null;
  const toneClass =
    tone === 'amber'
      ? 'border-amber-100 bg-amber-50/60 text-amber-950'
      : tone === 'rose'
        ? 'border-rose-100 bg-rose-50/60 text-rose-950'
        : 'border-slate-100 bg-slate-50 text-slate-800';
  return (
    <div className={`rounded-xl border px-4 py-3 ${toneClass}`}>
      <h3 className="text-sm font-bold mb-2">{title}</h3>
      <ul className="space-y-1.5 text-sm leading-relaxed">
        {items.map((item) => (
          <li key={item} className="flex gap-2">
            <span className="shrink-0 mt-1.5 w-1 h-1 rounded-full bg-current opacity-50" />
            <span>{item}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function GuideImages({ images }: { images: PartnerPromoGuideImage[] }) {
  if (!images.length) return null;
  return (
    <div className="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
      <h3 className="text-sm font-bold text-slate-900 mb-3">홍보 소재 · 배너 ({images.length})</h3>
      <div className="grid grid-cols-2 gap-3">
        {images.map((img) => {
          const title = img.imageTitle || img.originalFilename || '홍보 이미지';
          return (
            <div key={img.id} className="rounded-xl border border-slate-200 overflow-hidden bg-white">
              <div className="aspect-video bg-slate-100">
                <img src={img.downloadUrl} alt={title} className="w-full h-full object-contain" loading="lazy" />
              </div>
              <div className="p-2 flex items-center justify-between gap-2">
                <p className="text-xs font-medium text-slate-700 truncate">{title}</p>
                <a
                  href={img.downloadUrl}
                  download={img.originalFilename || `promo-${img.id}.jpg`}
                  className="shrink-0 p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                  aria-label={`${title} 다운로드`}
                >
                  <Download size={14} />
                </a>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

export function CpaCampaignDetail() {
  const { code = '' } = useParams<{ code: string }>();
  const [campaign, setCampaign] = useState<PublicCampaign | null>(null);
  const [guideDetail, setGuideDetail] = useState<PartnerPromoGuideDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [guideLoading, setGuideLoading] = useState(false);
  const [linkLoading, setLinkLoading] = useState(false);
  const [confirming, setConfirming] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [promoUrl, setPromoUrl] = useState('');
  const [shortUrl, setShortUrl] = useState('');
  const [landingUrl, setLandingUrl] = useState('');
  const [copied, setCopied] = useState(false);
  const [shortBusy, setShortBusy] = useState(false);

  const auth = getLcAuth();
  const loggedIn = isLcLoggedIn();
  const isActivePartner = isLcActivePartner();
  const detailPath = `/cpa/${encodeURIComponent(code)}`;

  const notify = (msg: string) => {
    setMessage(msg);
    window.setTimeout(() => setMessage(''), 2500);
  };

  const loadPublic = useCallback(() => {
    if (!code.trim()) {
      setError('캠페인 코드가 없습니다.');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    fetchPublicCpaCampaign(code)
      .then((item) => setCampaign(item))
      .catch((e) => {
        setCampaign(null);
        setError(e instanceof Error ? e.message : '캠페인을 불러오지 못했습니다.');
      })
      .finally(() => setLoading(false));
  }, [code]);

  useEffect(() => {
    loadPublic();
  }, [loadPublic]);

  useEffect(() => {
    if (!campaign || !isActivePartner) {
      setGuideDetail(null);
      setPromoUrl('');
      setShortUrl('');
      setLandingUrl('');
      return;
    }

    let cancelled = false;
    setLinkLoading(true);
    setShortUrl('');
    buildPartnerCpaShortlink({ campaignId: campaign.id })
      .then((res) => {
        if (cancelled) return;
        setPromoUrl(res.promoUrl || res.link?.url || '');
        setLandingUrl(res.link?.landingUrl || '');
        if (res.shortUrl) setShortUrl(res.shortUrl);
      })
      .catch(() => {
        if (!cancelled) {
          setPromoUrl('');
          setLandingUrl('');
        }
      })
      .finally(() => {
        if (!cancelled) setLinkLoading(false);
      });

    if (campaign.hasPublishedGuide) {
      setGuideLoading(true);
      fetchPartnerPromoGuide(campaign.id)
        .then((detail) => {
          if (!cancelled) setGuideDetail(detail);
        })
        .catch(() => {
          if (!cancelled) setGuideDetail(null);
        })
        .finally(() => {
          if (!cancelled) setGuideLoading(false);
        });
    } else {
      setGuideDetail(null);
    }

    return () => {
      cancelled = true;
    };
  }, [campaign, isActivePartner]);

  const copyText = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      notify('링크가 복사되었습니다.');
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      notify('복사에 실패했습니다.');
    }
  };

  const handleConfirmGuide = async () => {
    if (!campaign || confirming) return;
    setConfirming(true);
    try {
      const res = await confirmPartnerPromoGuide(campaign.id);
      setGuideDetail((prev) =>
        prev
          ? {
              ...prev,
              confirmation: res.confirmation,
            }
          : prev,
      );
      notify(res.message || '홍보 가이드를 확인했습니다.');
    } catch (e) {
      notify(e instanceof Error ? e.message : '확인 처리에 실패했습니다.');
    } finally {
      setConfirming(false);
    }
  };

  if (loading) {
    return (
      <main className="min-h-[50vh] flex items-center justify-center bg-slate-50">
        <p className="text-slate-400">불러오는 중…</p>
      </main>
    );
  }

  if (error || !campaign) {
    return (
      <main className="min-h-[50vh] flex items-center justify-center bg-slate-50 px-4">
        <div className="max-w-md w-full bg-white border border-slate-200 rounded-2xl p-8 text-center">
          <h1 className="text-xl font-bold text-slate-900 mb-2">캠페인을 찾을 수 없습니다</h1>
          <p className="text-sm text-slate-500 mb-6">{error || '비공개 또는 종료된 캠페인일 수 있습니다.'}</p>
          <Link to="/cpa-list" className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold">
            <ArrowLeft size={16} />
            CPA 목록으로
          </Link>
        </div>
      </main>
    );
  }

  const guide = guideDetail?.guide;
  const confirmation = guideDetail?.confirmation;
  const guideNeedsConfirm = Boolean(campaign.hasPublishedGuide && confirmation && !confirmation.confirmed);
  const displayLanding = (campaign.landingUrl || '').trim();

  return (
    <main className="bg-slate-50 min-h-screen pb-16">
      <section className="bg-slate-950 text-white pt-24 pb-10 px-4 sm:px-6 lg:px-8">
        <div className="max-w-6xl mx-auto">
          <Link to="/cpa-list" className="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-white mb-6 transition-colors">
            <ArrowLeft size={16} />
            CPA 광고상품 목록
          </Link>
          <div className="flex flex-col md:flex-row gap-6 md:items-center">
            <div className={`w-40 ${CPA_THUMBNAIL_ASPECT_CLASS} rounded-2xl bg-white/5 border border-white/10 overflow-hidden shrink-0`}>
              {campaign.thumbnailUrl ? (
                <img src={campaign.thumbnailUrl} alt="" className="w-full h-full object-cover" loading="lazy" />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-xs text-slate-500 font-bold">CPA</div>
              )}
            </div>
            <div className="min-w-0 flex-1">
              <div className="flex flex-wrap items-center gap-2 mb-2">
                {campaign.category ? (
                  <span className="px-2 py-0.5 rounded-md bg-white/10 text-xs font-semibold text-slate-200">{campaign.category}</span>
                ) : null}
                <span className="px-2 py-0.5 rounded-md bg-emerald-500/20 text-xs font-bold text-emerald-300">CPA</span>
                {campaign.recommended || campaign.badge ? (
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-400/20 text-xs font-bold text-amber-200">
                    <Sparkles size={12} />
                    {campaign.badge || '추천'}
                  </span>
                ) : null}
                {campaign.hasPublishedGuide ? (
                  <span className="px-2 py-0.5 rounded-md bg-cyan-500/20 text-xs font-bold text-cyan-300">홍보 가이드</span>
                ) : null}
              </div>
              <h1 className="text-2xl md:text-3xl font-bold truncate">{campaign.title}</h1>
              <p className="text-sm text-slate-400 mt-1 font-mono">{campaign.code}</p>
            </div>
            <div className="flex gap-3 shrink-0">
              <div className="rounded-2xl bg-white/5 border border-white/10 px-4 py-3 text-center min-w-[110px]">
                <div className="text-[11px] text-slate-400 font-bold uppercase tracking-wide">DB당 수익</div>
                <div className="text-xl font-bold text-emerald-300 mt-1 tabular-nums">
                  {Number(campaign.price || 0).toLocaleString()}원
                </div>
              </div>
              <div className="rounded-2xl bg-white/5 border border-white/10 px-4 py-3 text-center min-w-[110px]">
                <div className="text-[11px] text-slate-400 font-bold uppercase tracking-wide">승인율</div>
                <div className="text-xl font-bold text-white mt-1 tabular-nums">{campaign.approvalRate || '-'}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4">
        {message ? (
          <div className="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2">{message}</div>
        ) : null}

        <div className="grid lg:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)] gap-6 items-start">
          <div className="space-y-5">
            <article className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-3">상품 안내</h2>
              <p className="text-slate-600 leading-relaxed whitespace-pre-wrap text-sm md:text-base">
                {campaign.description || '상세 안내가 등록되지 않았습니다.'}
              </p>
              <dl className="mt-5 grid sm:grid-cols-2 gap-3 text-sm">
                <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                  <dt className="text-xs font-bold text-slate-400 mb-0.5">지급 조건</dt>
                  <dd className="text-slate-700">DB 승인 시</dd>
                </div>
                <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2.5">
                  <dt className="text-xs font-bold text-slate-400 mb-0.5">평균 소요</dt>
                  <dd className="text-slate-700">{campaign.avgTime || '-'}</dd>
                </div>
              </dl>
            </article>

            <article className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-4">홍보 채널 · 유의사항</h2>
              <CpsChannelGuide
                allowedChannels={campaign.allowedChannels || ''}
                forbiddenChannels={campaign.forbiddenChannels || ''}
                merchantName={campaign.title}
                expanded
              />
            </article>

            {campaign.hasPublishedGuide ? (
              <article className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">홍보 가이드</h2>
                    <p className="text-xs text-slate-500 mt-1">광고 전 반드시 확인해야 하는 홍보 유의사항입니다.</p>
                  </div>
                  {confirmation?.confirmed ? (
                    <span className="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-lg">
                      <CheckCircle2 size={14} />
                      확인 완료
                    </span>
                  ) : null}
                </div>

                {!loggedIn || !isActivePartner ? (
                  <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex gap-2">
                    <AlertTriangle size={18} className="shrink-0 mt-0.5" />
                    <p>파트너 로그인 후 상세 홍보 가이드(금지 문구·유효 DB 기준 등)를 확인하고 링크를 발급받을 수 있습니다.</p>
                  </div>
                ) : guideLoading ? (
                  <div className="flex items-center gap-2 text-sm text-slate-500 py-6 justify-center">
                    <Loader2 size={16} className="animate-spin" />
                    가이드 불러오는 중…
                  </div>
                ) : guide ? (
                  <div className="space-y-3">
                    <GuideList title="홍보 포인트" items={guide.promotionPoints} />
                    <GuideList title="추천 키워드" items={guide.recommendedKeywords} />
                    <GuideList title="금지 문구" items={guide.forbiddenWords} tone="rose" />
                    <GuideImages images={guide.images ?? []} />
                    <GuideList title="주의사항" items={guide.precautions} tone="amber" />
                    <GuideList title="유효 DB 기준" items={guide.validDbRules} />
                    <GuideList title="무효 DB 기준" items={guide.invalidDbRules} tone="rose" />
                    {!confirmation?.confirmed ? (
                      <button
                        type="button"
                        disabled={confirming}
                        onClick={handleConfirmGuide}
                        className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold disabled:opacity-60 flex items-center justify-center gap-2"
                      >
                        {confirming ? <Loader2 size={16} className="animate-spin" /> : <Check size={16} />}
                        홍보 가이드를 확인했습니다
                      </button>
                    ) : null}
                  </div>
                ) : (
                  <p className="text-sm text-slate-500">공개된 홍보 가이드를 불러오지 못했습니다.</p>
                )}
              </article>
            ) : null}

            {displayLanding ? (
              <button
                type="button"
                onClick={() => openLandingPage(displayLanding)}
                className="inline-flex items-center gap-2 text-sm font-bold text-cyan-700 hover:text-cyan-800"
              >
                <ExternalLink size={16} />
                랜딩페이지 미리보기
              </button>
            ) : null}
          </div>

          <aside className="lg:sticky lg:top-24 space-y-4">
            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-1">내 홍보 링크</h2>
              <p className="text-xs text-slate-500 mb-4">
                파트너별 개별 추적 링크입니다. 이 링크로 유입·DB가 기록됩니다.
              </p>

              {!loggedIn ? (
                <div className="space-y-3">
                  <p className="text-sm text-slate-600 leading-relaxed">
                    로그인 후 파트너 계정으로 개별 홍보 링크를 발급받을 수 있습니다.
                  </p>
                  <a
                    href={g5LoginUrl(spaReturnUrl(detailPath))}
                    className="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition-colors"
                  >
                    <Link2 size={16} />
                    로그인하고 링크 받기
                  </a>
                  <Link to="/partner" className="block text-center text-xs text-slate-500 hover:text-slate-700">
                    파트너 센터 알아보기
                  </Link>
                </div>
              ) : !isActivePartner ? (
                <div className="space-y-3">
                  <p className="text-sm text-slate-600 leading-relaxed">
                    {auth.isPartner
                      ? '파트너 계정 활성화 후 홍보 링크를 발급받을 수 있습니다.'
                      : '파트너 등록 후 이 캠페인의 개별 홍보 링크를 발급받을 수 있습니다.'}
                  </p>
                  <Link
                    to="/partner"
                    className="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold transition-colors"
                  >
                    파트너센터 이동
                  </Link>
                </div>
              ) : linkLoading ? (
                <div className="flex items-center gap-2 text-sm text-slate-500 py-4 justify-center">
                  <Loader2 size={16} className="animate-spin" />
                  홍보 링크 준비 중…
                </div>
              ) : !promoUrl ? (
                <p className="text-sm text-slate-500">홍보 링크를 발급하지 못했습니다. 잠시 후 다시 시도해 주세요.</p>
              ) : (
                <div className="space-y-4">
                  {guideNeedsConfirm ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900 flex gap-2">
                      <AlertTriangle size={16} className="shrink-0 mt-0.5" />
                      <p>광고를 시작하기 전에 왼쪽 홍보 가이드를 확인해 주세요.</p>
                    </div>
                  ) : null}
                  <div>
                    <div className="text-xs font-bold text-slate-500 mb-1.5">대표 홍보 링크</div>
                    <div className="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2.5 text-xs font-mono break-all text-slate-700">
                      {shortUrl || promoUrl}
                    </div>
                    <div className="mt-2 flex flex-col sm:flex-row gap-2">
                      <button
                        type="button"
                        disabled={shortBusy}
                        onClick={async () => {
                          setShortBusy(true);
                          try {
                            const res = await buildPartnerCpaShortlink({ campaignId: campaign.id });
                            setPromoUrl(res.promoUrl || promoUrl);
                            setShortUrl(res.shortUrl);
                            if (res.link?.landingUrl) setLandingUrl(res.link.landingUrl);
                            notify('숏링크로 변환되었습니다.');
                          } catch (e) {
                            notify(e instanceof Error ? e.message : '숏링크 변환에 실패했습니다.');
                          } finally {
                            setShortBusy(false);
                          }
                        }}
                        className="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-cyan-200 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 text-sm font-bold disabled:opacity-50"
                      >
                        <Link2 size={16} />
                        {shortBusy ? '변환 중…' : shortUrl ? '숏링크 다시 변환' : '숏링크 변환'}
                      </button>
                      <button
                        type="button"
                        onClick={() => copyText(shortUrl || promoUrl)}
                        className="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold"
                      >
                        {copied ? <CheckCircle2 size={16} /> : <Copy size={16} />}
                        복사
                      </button>
                    </div>
                    {shortUrl ? (
                      <p className="mt-2 text-[11px] text-slate-400">
                        원본: <span className="font-mono break-all">{promoUrl}</span>
                      </p>
                    ) : null}
                  </div>

                  {(landingUrl || displayLanding) ? (
                    <button
                      type="button"
                      onClick={() => openLandingPage(landingUrl || displayLanding)}
                      className="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50"
                    >
                      <ExternalLink size={16} />
                      상담 랜딩 미리보기
                    </button>
                  ) : null}

                  <Link
                    to="/partner/links"
                    className="block text-center text-xs text-slate-500 hover:text-emerald-700"
                  >
                    내 홍보링크 목록 보기
                  </Link>
                </div>
              )}
            </div>
          </aside>
        </div>
      </section>
    </main>
  );
}
