import { ArrowRight, CheckCircle2, DollarSign, Target } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { fetchPublicCampaigns, PublicCampaign } from '../lib/api';
import { cn } from '../lib/utils';

const filters = ['전체', '고수익', '신규', '승인율 높은 상품', '법률', '병원', '보험', '교육', '부동산'];

export function CPAList() {
  const [items, setItems] = useState<PublicCampaign[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    fetchPublicCampaigns({ type: 'cpa' })
      .then((data) => {
        if (!cancelled) {
          setItems(data.items.slice(0, 4));
        }
      })
      .catch(() => {
        if (!cancelled) {
          setItems([]);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <section id="cpa" className="py-20 px-4 sm:px-6 lg:px-8 bg-slate-50 border-t border-slate-100">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4">
            지금 바로 홍보 가능한 <span className="text-emerald-500">CPA 상품</span>
          </h2>
          <p className="text-slate-600 max-w-2xl mx-auto">
            상담신청, 견적문의, 예약신청, 회원가입 등 DB가 발생하면 수익이 지급되는 CPA 캠페인입니다.
          </p>
        </div>

        <div className="flex flex-wrap justify-center gap-2 mb-10">
          {filters.map((filter, i) => (
            <button
              key={filter}
              className={cn(
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors border shadow-sm',
                i === 0
                  ? 'bg-emerald-500 text-white border-emerald-500'
                  : 'bg-white border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300 hover:bg-slate-50',
              )}
            >
              {filter}
            </button>
          ))}
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          {loading ? (
            <div className="col-span-full py-16 text-center text-slate-500">CPA 상품을 불러오는 중...</div>
          ) : items.length === 0 ? (
            <div className="col-span-full py-16 text-center text-slate-500">현재 진행 중인 CPA 상품이 없습니다.</div>
          ) : items.map((item) => (
            <div key={item.id} className="bg-white border border-slate-200 rounded-2xl overflow-hidden hover:border-emerald-300 hover:shadow-xl transition-all group relative flex flex-col shadow-sm">
              <div className="aspect-square overflow-hidden relative group bg-gradient-to-br from-emerald-500/10 to-cyan-500/10">
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent" />
                <div className="absolute top-3 left-3 right-3 flex justify-between items-start">
                  <span className="text-xs font-medium px-2.5 py-1 rounded-md bg-white/90 backdrop-blur-sm text-slate-700 shadow-sm">
                    {item.category}
                  </span>
                  {item.badge && (
                    <span className={cn(
                      'text-xs font-bold px-2.5 py-1 rounded-md shadow-sm backdrop-blur-sm',
                      item.badge === '고수익' ? 'bg-yellow-100/90 text-yellow-800' :
                      item.badge === '신규' ? 'bg-cyan-100/90 text-cyan-800' :
                      item.badge === '승인율 높음' ? 'bg-emerald-100/90 text-emerald-800' :
                      'bg-white/90 text-slate-700',
                    )}>
                      {item.badge}
                    </span>
                  )}
                </div>
              </div>
              <div className="p-5 flex-1 flex flex-col">
                <h3 className="text-lg font-bold text-slate-900 mb-2 line-clamp-1">{item.title}</h3>
                <p className="text-sm text-slate-600 mb-6 line-clamp-2 min-h-[40px]">{item.description || `${item.title} 캠페인`}</p>

                <div className="space-y-3 mb-6 bg-slate-50 p-3 rounded-xl border border-slate-100 mt-auto">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><Target className="w-4 h-4" />지급 조건</span>
                    <span className="text-slate-700 font-medium">DB 승인 시</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><DollarSign className="w-4 h-4" />DB당 수익금</span>
                    <span className="text-emerald-600 font-bold text-base">{item.price.toLocaleString()}원</span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-slate-500 flex items-center gap-1.5"><CheckCircle2 className="w-4 h-4" />예상 승인율</span>
                    <span className="text-cyan-600 font-medium">{item.approvalRate || '-'}</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <Link to="/cpa-list" className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors border border-slate-200 text-center">
                    상세보기
                  </Link>
                  <Link to="/partner/search" className="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold rounded-lg transition-colors shadow-md shadow-emerald-500/20 text-center">
                    참여하기
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="text-center">
          <Link to="/cpa-list" className="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-emerald-200 text-emerald-600 hover:bg-emerald-50 transition-colors font-medium bg-white shadow-sm">
            CPA 전체 상품 보기
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>
      </div>
    </section>
  );
}
