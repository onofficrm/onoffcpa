import { Search, Info, Link as LinkIcon, Filter, ChevronDown, CheckCircle2, AlertTriangle, XCircle, TrendingUp, ExternalLink } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchPublicCampaigns, PublicCampaign } from '../../lib/api';
import { openLandingPage } from '../../lib/utils';
import { CPA_THUMBNAIL_LIST_IMG_CLASS, CPA_THUMBNAIL_LIST_MEDIA_CLASS } from '../../lib/cpaThumbnail';
import { cpaCardImageUrl } from '../../lib/optimizedImage';
import { CallDbBadge, CallDbStatsHint } from '../../components/CallDbBadge';

const fallbackCategories = ['전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타'];

type CampaignCardItem = {
  id: number;
  code: string;
  title: string;
  category: string;
  description: string;
  price: string;
  approvalRate: string;
  avgTime: string;
  allowedChannels: string;
  forbiddenChannels: string;
  status: string;
  badge?: string;
  recommended?: boolean;
  landingUrl: string;
  thumbnailUrl: string;
  callEnabled?: boolean;
};

function toCardItem(campaign: PublicCampaign): CampaignCardItem {
  return {
    id: campaign.id,
    code: campaign.code || String(campaign.id),
    title: campaign.title,
    category: campaign.category,
    description: campaign.description || '',
    price: campaign.priceFormatted,
    approvalRate: campaign.approvalRate,
    avgTime: campaign.avgTime,
    allowedChannels: campaign.allowedChannels,
    forbiddenChannels: campaign.forbiddenChannels,
    status: campaign.status,
    badge: campaign.badge || undefined,
    recommended: campaign.recommended,
    landingUrl: campaign.landingUrl || '',
    thumbnailUrl: campaign.thumbnailUrl || '',
    callEnabled: Boolean(campaign.callEnabled),
  };
}

export function CpaList() {
  const [activeCategory, setActiveCategory] = useState('전체');
  const [searchQuery, setSearchQuery] = useState('');
  const [categories, setCategories] = useState(fallbackCategories);
  const [items, setItems] = useState<CampaignCardItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      setLoading(true);
      setError('');
      try {
        const data = await fetchPublicCampaigns({
          category: activeCategory,
          q: searchQuery,
          type: 'cpa',
        });
        if (cancelled) {
          return;
        }
        setCategories(data.categories.length ? data.categories : fallbackCategories);
        setItems(data.items.map(toCardItem));
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : '캠페인을 불러오지 못했습니다.');
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    const timer = window.setTimeout(load, 250);
    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [activeCategory, searchQuery]);

  const recommendedItems = useMemo(
    () => items.filter((item) => item.recommended || item.badge).slice(0, 3),
    [items],
  );

  return (
    <div className="bg-slate-50 min-h-screen pt-32 pb-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-10">
          <h1 className="text-3xl font-bold text-slate-900 mb-4">CPA 광고상품</h1>
          <p className="text-slate-600 text-lg max-w-3xl">
            상담 신청, 회원가입, 견적 문의 등 성과가 발생한 디비 기준으로 수익을 받을 수 있는 CPA 캠페인입니다.
          </p>
        </div>

        <div className="flex flex-wrap gap-2 mb-8">
          {categories.map((cat) => (
            <button
              key={cat}
              onClick={() => setActiveCategory(cat)}
              className={`px-5 py-2.5 rounded-full text-sm font-medium transition-colors ${
                activeCategory === cat
                  ? 'bg-slate-900 text-white shadow-md'
                  : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300 hover:bg-slate-50'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 flex flex-col md:flex-row gap-4 justify-between items-center mb-10 shadow-sm">
          <div className="relative w-full md:w-96">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="광고상품명 검색..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
            />
          </div>
        </div>

        {error && (
          <div className="mb-8 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        )}

        {recommendedItems.length > 0 && (
          <div className="mb-12">
            <h2 className="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
              <TrendingUp className="w-6 h-6 text-emerald-500" />
              추천 CPA 캠페인
            </h2>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {recommendedItems.map((item) => (
                <CampaignCard key={`rec-${item.id}`} item={item} />
              ))}
            </div>
          </div>
        )}

        <div className="h-px bg-slate-200 w-full mb-12" />

        <div className="mb-8 flex items-center justify-between">
          <h2 className="text-xl font-bold text-slate-900">전체 캠페인 <span className="text-emerald-600">({items.length})</span></h2>
        </div>

        {loading ? (
          <div className="py-16 text-center text-slate-500">캠페인을 불러오는 중...</div>
        ) : items.length === 0 ? (
          <div className="py-16 text-center text-slate-500">표시할 CPA 캠페인이 없습니다.</div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
            {items.map((item) => (
              <CampaignCard key={item.id} item={item} />
            ))}
          </div>
        )}

        <div className="bg-slate-900 rounded-2xl p-8 md:p-10 text-white shadow-xl">
          <div className="flex items-start gap-4">
            <Info className="w-8 h-8 text-cyan-400 shrink-0 mt-1" />
            <div>
              <h3 className="text-xl font-bold mb-6">CPA 상품 홍보 전 꼭 확인해주세요.</h3>
              <ul className="space-y-4">
                <li className="flex items-start gap-3 text-slate-300">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                  <span>허용 채널과 금지 채널을 반드시 확인해주세요. 채널 위반 시 정산이 거절될 수 있습니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <AlertTriangle className="w-5 h-5 text-yellow-400 shrink-0 mt-0.5" />
                  <span>허위광고, 과장광고로 발생한 디비는 전량 취소될 수 있으며 파트너 자격이 정지될 수 있습니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <XCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                  <span>중복 디비, 장난 신청, 연락 불가(결번 등) 디비는 무효 처리될 수 있습니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                  <span>광고주가 정상적인 상담/가입으로 <strong>승인한 디비만 확정수익으로 반영</strong>됩니다.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function CampaignCard({ item }: { item: CampaignCardItem }) {
  const hasLandingUrl = item.landingUrl.trim().length > 0;

  return (
    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl hover:border-emerald-300 transition-all flex flex-col">
      <div className={`${CPA_THUMBNAIL_LIST_MEDIA_CLASS} bg-slate-100`}>
        {item.thumbnailUrl ? (
          <img
            src={cpaCardImageUrl(item.thumbnailUrl)}
            alt={item.title}
            className={CPA_THUMBNAIL_LIST_IMG_CLASS}
            loading="lazy"
            decoding="async"
          />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-slate-300 text-sm font-medium bg-gradient-to-br from-slate-50 to-slate-100">
            No Image
          </div>
        )}
      </div>
      <div className="p-6 flex-1">
        <div className="flex justify-between items-start mb-4">
          <span className="px-3 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-md border border-slate-200">
            {item.category}
          </span>
          <div className="flex gap-2">
            {item.badge && (
              <span className={`px-2.5 py-1 text-xs font-bold rounded-md ${
                item.badge === '추천' ? 'bg-emerald-100 text-emerald-800' :
                item.badge === '인기' ? 'bg-cyan-100 text-cyan-800' :
                'bg-purple-100 text-purple-800'
              }`}>
                {item.badge}
              </span>
            )}
            <span className={`px-2.5 py-1 text-xs font-bold rounded-md border ${
              item.status === '진행중' || item.status === '마감임박' ? 'bg-white border-emerald-200 text-emerald-600' : 'bg-white border-red-200 text-red-500'
            }`}>
              {item.status}
            </span>
          </div>
        </div>

        <h3 className="text-xl font-bold text-slate-900 mb-1 flex items-center gap-1.5 min-w-0">
          <span className="min-w-0 truncate">{item.title}</span>
          {item.callEnabled ? <CallDbBadge /> : null}
        </h3>
        <div className="text-sm text-slate-500 mb-6 line-clamp-2 min-h-[2.5rem]">
          {item.description || '유형: CPA (DB접수)'}
        </div>

        {item.callEnabled ? (
          <div className="mb-4 rounded-xl border border-violet-100 bg-violet-50/60 px-3 py-2">
            <CallDbStatsHint className="border-0 pb-0" />
          </div>
        ) : null}

        <div className="grid grid-cols-2 gap-4 mb-6">
          <div className="bg-emerald-50 rounded-xl p-3 border border-emerald-100/50">
            <div className="text-xs text-emerald-800 mb-1">파트너 단가</div>
            <div className="text-lg font-bold text-emerald-600">{item.price}<span className="text-sm font-normal ml-1">원</span></div>
          </div>
          <div className="bg-slate-50 rounded-xl p-3 border border-slate-100">
            <div className="text-xs text-slate-500 mb-1">승인율 / 평균</div>
            <div className="text-lg font-bold text-slate-700">{item.approvalRate} <span className="text-sm font-normal text-slate-400">({item.avgTime})</span></div>
          </div>
        </div>

        <div className="space-y-3 text-sm">
          <div>
            <span className="inline-block w-16 text-slate-400 font-medium">허용채널</span>
            <span className="text-slate-700">{item.allowedChannels || '-'}</span>
          </div>
          <div>
            <span className="inline-block w-16 text-slate-400 font-medium">금지채널</span>
            <span className="text-red-500">{item.forbiddenChannels || '-'}</span>
          </div>
        </div>
      </div>

      <div className="p-4 bg-slate-50 border-t border-slate-100 flex flex-col gap-2">
        {hasLandingUrl && (
          <button
            type="button"
            onClick={() => openLandingPage(item.landingUrl)}
            className="w-full py-2.5 bg-white border border-cyan-200 hover:bg-cyan-50 text-cyan-700 font-medium rounded-xl transition-colors text-sm flex justify-center items-center gap-1.5"
          >
            <ExternalLink className="w-4 h-4" />
            랜딩페이지 보기
          </button>
        )}
        <Link
          to={`/cpa/${encodeURIComponent(item.code || String(item.id))}`}
          className="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition-colors text-sm flex justify-center items-center gap-2"
        >
          <LinkIcon className="w-4 h-4" />
          참여하기
        </Link>
      </div>
    </div>
  );
}
