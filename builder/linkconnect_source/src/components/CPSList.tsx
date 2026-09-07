import { ArrowRight, Clock, ShoppingBag, TrendingUp, ExternalLink } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { fetchPublicCampaigns, PublicCampaign } from '../lib/api';
import { cn, openLandingPage } from '../lib/utils';
import { CPA_THUMBNAIL_LIST_IMG_CLASS, CPA_THUMBNAIL_LIST_MEDIA_CLASS } from '../lib/cpaThumbnail';
import { cpaCardImageUrl } from '../lib/optimizedImage';
import { HomeSectionAnchor } from './HomeSectionAnchor';

function isPlatformCps(item: PublicCampaign) {
  const code = (item.code || '').toUpperCase();
  if (code.startsWith('CPS-')) return true;
  const platform = String((item as { platformService?: string }).platformService || '').toUpperCase();
  return ['DOMAIN', 'CONTENT', 'TRAFFIC', 'BACKLINK', 'GEO', 'ONOFFCPA'].includes(platform);
}

export function CPSList() {
  const [items, setItems] = useState<PublicCampaign[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    fetchPublicCampaigns({ type: 'cps' })
      .then((data) => {
        if (cancelled) return;
        const platform = data.items.filter(isPlatformCps);
        const preferred = (platform.length ? platform : data.items)
          .slice()
          .sort((a, b) => Number(b.recommended) - Number(a.recommended));
        setItems(preferred.slice(0, 4));
      })
      .catch(() => {
        if (!cancelled) setItems([]);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <section className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900 border-t border-slate-800 relative">
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-cyan-500/5 blur-[120px] rounded-full pointer-events-none" />
      <div className="max-w-7xl mx-auto relative z-10">
        <HomeSectionAnchor id="cps" />
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
            구매·이용이 발생하면 수익이 쌓이는 <span className="text-cyan-400">CPS 상품</span>
          </h2>
          <p className="text-slate-400 max-w-2xl mx-auto">
            결제·구매 확정 기준으로 수수료가 지급되는 CPS 캠페인입니다. ONOFF 플랫폼 상품은 Core 수수료 규칙을 따릅니다.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          {loading ? (
            <div className="col-span-full py-16 text-center text-slate-400">CPS 상품을 불러오는 중...</div>
          ) : items.length === 0 ? (
            <div className="col-span-full py-16 text-center text-slate-400">현재 진행 중인 CPS 상품이 없습니다.</div>
          ) : (
            items.map((item) => {
              const commission = item.approvalRate || item.priceFormatted || '-';
              const cookie = item.avgTime || '-';
              return (
                <div
                  key={item.id}
                  className="bg-slate-800/50 border border-slate-700 rounded-2xl overflow-hidden hover:border-cyan-500/50 hover:bg-slate-800 transition-all flex flex-col shadow-md"
                >
                  {item.thumbnailUrl ? (
                    <div className={cn(CPA_THUMBNAIL_LIST_MEDIA_CLASS, 'rounded-none border-0 border-b border-slate-700')}>
                      <img
                        src={cpaCardImageUrl(item.thumbnailUrl)}
                        alt={item.title}
                        className={CPA_THUMBNAIL_LIST_IMG_CLASS}
                        loading="lazy"
                      />
                    </div>
                  ) : null}
                  <div className="p-5 flex-1 flex flex-col">
                    <div className="flex justify-between items-start mb-3 gap-2">
                      <span className="text-xs font-medium px-2.5 py-1 rounded-md bg-slate-900/80 text-slate-200 border border-slate-700">
                        {item.category}
                      </span>
                      {item.badge ? (
                        <span className="text-xs font-bold px-2.5 py-1 rounded-md bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                          {item.badge}
                        </span>
                      ) : null}
                    </div>
                    <h3 className="text-lg font-bold text-white mb-2 line-clamp-1">{item.title}</h3>
                    <p className="text-sm text-slate-400 mb-6 line-clamp-2 min-h-[40px]">
                      {item.description || `${item.title} CPS 캠페인`}
                    </p>
                    <div className="space-y-3 mb-6 bg-slate-900/50 p-3 rounded-xl border border-slate-700/50 mt-auto">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-400 flex items-center gap-1.5">
                          <ShoppingBag className="w-4 h-4" />
                          수익 조건
                        </span>
                        <span className="text-slate-200 font-medium">구매·이용 확정</span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-400 flex items-center gap-1.5">
                          <TrendingUp className="w-4 h-4" />
                          수수료
                        </span>
                        <span className="text-cyan-400 font-bold text-base">{commission}</span>
                      </div>
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-slate-400 flex items-center gap-1.5">
                          <Clock className="w-4 h-4" />
                          추적 기간
                        </span>
                        <span className="text-slate-300 font-medium">{cookie}</span>
                      </div>
                    </div>
                    <div className="grid grid-cols-1 gap-2">
                      {(item.landingUrl || '').trim() ? (
                        <button
                          type="button"
                          onClick={() => openLandingPage(item.landingUrl)}
                          className={cn(
                            'w-full py-2.5 text-sm font-medium rounded-lg transition-colors border flex justify-center items-center gap-1.5',
                            'bg-slate-700 hover:bg-slate-600 text-white border-slate-600',
                          )}
                        >
                          <ExternalLink className="w-4 h-4" />
                          랜딩페이지 보기
                        </button>
                      ) : null}
                      <Link
                        to="/partner/search?type=cps"
                        className="w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-slate-900 text-sm font-bold rounded-lg transition-colors shadow-md shadow-cyan-500/20 text-center"
                      >
                        홍보하기
                      </Link>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        <div className="text-center">
          <Link
            to="/cps"
            className="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-cyan-500/30 text-cyan-400 hover:bg-cyan-500/10 transition-colors font-medium bg-slate-800 shadow-sm"
          >
            CPS 전체 상품 보기
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>
      </div>
    </section>
  );
}
