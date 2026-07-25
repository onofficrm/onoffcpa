import { Search, Info, Link as LinkIcon, CheckCircle2, AlertTriangle, XCircle, ShoppingBag, Clock } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchPublicCampaigns, PublicCampaign } from '../../lib/api';

const fallbackCategories = ['전체', '쇼핑몰', '뷰티', '건강', '생활', '기타'];

export function CpsList() {
  const [activeCategory, setActiveCategory] = useState('전체');
  const [searchQuery, setSearchQuery] = useState('');
  const [categories, setCategories] = useState(fallbackCategories);
  const [items, setItems] = useState<PublicCampaign[]>([]);
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
          type: 'cps',
        });
        if (cancelled) return;
        setCategories(data.categories.length ? data.categories : fallbackCategories);
        setItems(data.items);
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : 'CPS 캠페인을 불러오지 못했습니다.');
        }
      } finally {
        if (!cancelled) setLoading(false);
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
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-50 border border-cyan-100 text-cyan-700 text-sm font-bold mb-4">
            <ShoppingBag size={16} /> CPS (Cost Per Sale)
          </div>
          <h1 className="text-3xl font-bold text-slate-900 mb-4">CPS 광고상품</h1>
          <p className="text-slate-600 text-lg max-w-3xl">
            구매·결제가 발생한 실적 기준으로 수수료를 받을 수 있는 CPS 캠페인입니다. 쿠키 기간 내 구매 전환을 추적합니다.
          </p>
        </div>

        <div className="flex flex-wrap gap-2 mb-8">
          {categories.map((cat) => (
            <button
              key={cat}
              type="button"
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
              placeholder="CPS 상품명 검색..."
              className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500"
            />
          </div>
        </div>

        {error && (
          <div className="mb-8 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
        )}

        {recommendedItems.length > 0 && (
          <div className="mb-12">
            <h2 className="text-xl font-bold text-slate-900 mb-6">추천 CPS 캠페인</h2>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {recommendedItems.map((item) => (
                <CpsCard key={`rec-${item.id}`} item={item} />
              ))}
            </div>
          </div>
        )}

        <div className="h-px bg-slate-200 w-full mb-12" />

        <div className="mb-8 flex items-center justify-between">
          <h2 className="text-xl font-bold text-slate-900">전체 CPS <span className="text-cyan-600">({items.length})</span></h2>
        </div>

        {loading ? (
          <div className="py-16 text-center text-slate-500">CPS 캠페인을 불러오는 중...</div>
        ) : items.length === 0 ? (
          <div className="py-16 text-center">
            <p className="text-slate-500 mb-4">표시할 CPS 캠페인이 없습니다.</p>
            <Link to="/cpa-list" className="text-cyan-600 font-bold">CPA 상품 보기</Link>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
            {items.map((item) => (
              <CpsCard key={item.id} item={item} />
            ))}
          </div>
        )}

        <div className="bg-slate-900 rounded-2xl p-8 md:p-10 text-white shadow-xl">
          <div className="flex items-start gap-4">
            <Info className="w-8 h-8 text-cyan-400 shrink-0 mt-1" />
            <div>
              <h3 className="text-xl font-bold mb-6">CPS 상품 홍보 전 꼭 확인해주세요.</h3>
              <ul className="space-y-4">
                <li className="flex items-start gap-3 text-slate-300">
                  <Clock className="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" />
                  <span>쿠키(추적) 기간 내 발생한 구매만 수수료 정산 대상입니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <AlertTriangle className="w-5 h-5 text-yellow-400 shrink-0 mt-0.5" />
                  <span>자사 구매, 허위 클릭, 쿠폰 어뷰징 등 부정 전환은 정산에서 제외될 수 있습니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <XCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
                  <span>브랜드 가이드라인을 위반한 홍보는 캠페인 참여가 제한될 수 있습니다.</span>
                </li>
                <li className="flex items-start gap-3 text-slate-300">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                  <span>확정 수수료는 광고주·쇼핑몰의 <strong>구매 확정 후 정산</strong>됩니다.</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

function CpsCard({ item }: { item: PublicCampaign }) {
  const commission = item.approvalRate || item.priceFormatted;
  const cookie = item.avgTime;

  return (
    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-xl hover:border-cyan-300 transition-all flex flex-col">
      <div className="p-6 flex-1">
        <div className="flex justify-between items-start mb-4">
          <span className="px-3 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-md border border-slate-200">
            {item.category}
          </span>
          {item.badge && (
            <span className="px-2.5 py-1 text-xs font-bold rounded-md bg-cyan-100 text-cyan-800">{item.badge}</span>
          )}
        </div>

        <h3 className="text-xl font-bold text-slate-900 mb-1">{item.title}</h3>
        <div className="text-sm text-slate-500 mb-6">유형: CPS (구매/결제)</div>

        <div className="grid grid-cols-2 gap-4 mb-6">
          <div className="bg-cyan-50 rounded-xl p-3 border border-cyan-100/50">
            <div className="text-xs text-cyan-800 mb-1">수수료율</div>
            <div className="text-lg font-bold text-cyan-600">{commission}</div>
          </div>
          <div className="bg-slate-50 rounded-xl p-3 border border-slate-100">
            <div className="text-xs text-slate-500 mb-1">쿠키 기간</div>
            <div className="text-lg font-bold text-slate-700">{cookie || '-'}</div>
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

      <div className="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
        <Link to="/cps" className="flex-1 py-3 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-medium rounded-xl transition-colors text-sm text-center">
          상세보기
        </Link>
        <Link to="/partner/search" className="flex-1 py-3 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition-colors text-sm flex justify-center items-center gap-2">
          <LinkIcon className="w-4 h-4" />
          홍보하기
        </Link>
      </div>
    </div>
  );
}
