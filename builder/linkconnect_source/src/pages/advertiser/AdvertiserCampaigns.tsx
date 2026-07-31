import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { AdvertiserContractNotice } from '../../components/advertiser/AdvertiserContractNotice';
import { Target, CheckCircle2, PlayCircle, PauseCircle, BarChart3, Edit3, Pause, FileText } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchMerchantCampaigns, MerchantCampaign, PartnerApiError } from '../../lib/api';
import { getLcAuth, shouldShowMerchantContractNotice } from '../../lib/auth';

export function AdvertiserCampaigns() {
  const auth = getLcAuth();
  const showContractNotice = shouldShowMerchantContractNotice(auth);
  const [campaigns, setCampaigns] = useState<MerchantCampaign[]>([]);
  const [summary, setSummary] = useState({ total: 0, active: 0, pending: 0, ended: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [contractRequired, setContractRequired] = useState(false);

  useEffect(() => {
    let cancelled = false;

    fetchMerchantCampaigns()
      .then((data) => {
        if (!cancelled) {
          setCampaigns(data.items);
          setSummary(data.summary);
        }
      })
      .catch((err) => {
        if (!cancelled) {
          if (err instanceof PartnerApiError && err.code === 'CONTRACT_REQUIRED') {
            setContractRequired(true);
            setError('');
            return;
          }
          setContractRequired(false);
          setError(err instanceof Error ? err.message : '캠페인을 불러오지 못했습니다.');
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
    <AdvertiserLayout activeMenu="campaigns" title="내 광고상품">
      <div className="mb-8 -mt-2">
        <p className="text-slate-500">
          운영 중인 광고 캠페인을 관리하고 실시간 성과를 확인하세요.
        </p>
      </div>

      {showContractNotice ? <AdvertiserContractNotice /> : null}

      {contractRequired ? <AdvertiserContractNotice /> : null}

      {error && (
        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      )}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 광고상품" value={summary.total.toLocaleString()} suffix="개" icon={<Target size={20} />} />
        <SummaryCard title="진행중" value={summary.active.toLocaleString()} suffix="개" highlight color="cyan" icon={<PlayCircle size={20} />} />
        <SummaryCard title="대기중" value={summary.pending.toLocaleString()} suffix="개" color="yellow" icon={<PauseCircle size={20} />} />
        <SummaryCard title="종료" value={summary.ended.toLocaleString()} suffix="개" color="slate" icon={<CheckCircle2 size={20} />} />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="p-4 border-b border-slate-100 flex flex-wrap gap-4 justify-between items-center bg-slate-50">
          <div className="text-sm text-slate-500">
            {loading ? '불러오는 중...' : `총 ${campaigns.length}개의 광고상품`}
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4 font-medium whitespace-nowrap">상품명</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">유형</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">상태</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">단가 (CPA)</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">소진 금액</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">수집 DB</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">계약</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td colSpan={8} className="px-5 py-10 text-center text-slate-500">불러오는 중...</td>
                </tr>
              ) : campaigns.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-5 py-10 text-center text-slate-500">등록된 광고상품이 없습니다.</td>
                </tr>
              ) : campaigns.map((camp) => (
                <tr key={camp.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-5 py-4 text-slate-900 font-bold whitespace-nowrap">{camp.name}</td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <span className="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold">{camp.type}</span>
                  </td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={camp.status} />
                  </td>
                  <td className="px-5 py-4 text-right font-medium text-slate-700 whitespace-nowrap">{camp.cpa.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-right whitespace-nowrap">
                    <span className="font-bold text-cyan-600">{camp.spend.toLocaleString()}원</span>
                  </td>
                  <td className="px-5 py-4 text-right font-bold text-slate-900 whitespace-nowrap">{camp.dbCount}건</td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    {camp.contractViewable ? (
                      <Link
                        to={`/advertiser/contract/view?cpId=${camp.id}`}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors"
                        title="광고상품별 계약서 보기"
                      >
                        <FileText size={14} />
                        계약서
                      </Link>
                    ) : (
                      <Link
                        to="/advertiser/contract"
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors"
                        title="계약 체결 필요"
                      >
                        <FileText size={14} />
                        미체결
                      </Link>
                    )}
                  </td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <div className="flex items-center justify-center gap-2">
                      <Link
                        to={`/advertiser/campaigns/${camp.id}/guide`}
                        className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 rounded-lg transition-colors"
                        title="홍보 가이드 관리"
                      >
                        <BookOpen size={14} />
                        홍보 가이드 관리
                      </Link>
                      <button className="p-1.5 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors" title="리포트">
                        <BarChart3 size={18} />
                      </button>
                      <button className="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="수정">
                        <Edit3 size={18} />
                      </button>
                      <button className="p-1.5 text-slate-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="일시정지">
                        <Pause size={18} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AdvertiserLayout>
  );
}
