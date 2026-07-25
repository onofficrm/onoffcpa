import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { Target, CheckCircle2, PlayCircle, PauseCircle, ChevronRight, BarChart3, Edit3, Pause , Wand2, Loader2 } from 'lucide-react';
import { useState } from 'react';

const mockCampaigns = [
  { id: 1, name: '개인회생 무료상담', type: 'CPA', status: '진행중', budget: 1500000, spend: 450000, dbCount: 15, cpa: 30000 },
  { id: 2, name: '어린이 화상영어 1개월 무료체험', type: 'CPA', status: '진행중', budget: 2000000, spend: 1050000, dbCount: 30, cpa: 35000 },
  { id: 3, name: '소상공인 정책자금 상담', type: 'CPA', status: '종료', budget: 1000000, spend: 1000000, dbCount: 40, cpa: 25000 },
  { id: 4, name: '프리미엄 건강검진 센터 예약', type: 'CPA', status: '대기중', budget: 500000, spend: 0, dbCount: 0, cpa: 40000 },
];

export function AdvertiserCampaigns() {
  const [isGenerating, setIsGenerating] = useState(false);
  const handleAIGenerate = () => {
    setIsGenerating(true);
    setTimeout(() => {
      setIsGenerating(false);
      alert('AI 배너 및 홍보 문구가 성공적으로 생성되었습니다.');
    }, 2000);
  };
  return (
    <AdvertiserLayout activeMenu="campaigns" title="내 광고상품">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          운영 중인 광고 캠페인을 관리하고 실시간 성과를 확인하세요.
        </p>
      </div>

      
      {/* AI Banner Generator */}
      <div className="mb-8 bg-gradient-to-r from-indigo-900 to-purple-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div className="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div className="relative z-10">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/20 border border-white/10 text-xs font-bold mb-3 backdrop-blur-sm">
            <Wand2 size={12} className="text-purple-300" /> AI 베타
          </div>
          <h3 className="text-xl font-bold mb-2">원클릭 AI 썸네일 & 배너 생성</h3>
          <p className="text-indigo-200 text-sm max-w-md">상품명과 타겟만 입력하면, AI가 전환율이 높은 광고 이미지와 홍보 문구를 자동으로 생성해 드립니다.</p>
        </div>
        <button onClick={handleAIGenerate} disabled={isGenerating} className="relative z-10 shrink-0 bg-white text-indigo-900 hover:bg-indigo-50 px-6 py-3 rounded-xl font-bold text-sm shadow-[0_0_20px_rgba(255,255,255,0.3)] transition-all hover:scale-105 flex items-center gap-2 disabled:opacity-50 disabled:hover:scale-100">
          {isGenerating ? <Loader2 size={18} className="animate-spin" /> : <Wand2 size={18} />} {isGenerating ? "생성 중..." : "지금 AI로 만들기"}
        </button>
      </div>

      {/* Summary Cards */}

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 광고상품" value="4" suffix="개" icon={<Target size={20} />} />
        <SummaryCard title="진행중" value="2" suffix="개" highlight color="cyan" icon={<PlayCircle size={20} />} />
        <SummaryCard title="대기중" value="1" suffix="개" color="yellow" icon={<PauseCircle size={20} />} />
        <SummaryCard title="종료" value="1" suffix="개" color="slate" icon={<CheckCircle2 size={20} />} />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="p-4 border-b border-slate-100 flex flex-wrap gap-4 justify-between items-center bg-slate-50">
          <div className="flex items-center gap-2">
            <select className="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:border-cyan-500 min-w-[120px]">
              <option>상태 전체</option>
              <option>진행중</option>
              <option>대기중</option>
              <option>종료</option>
            </select>
          </div>
          <button className="px-5 py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-bold hover:bg-cyan-700 transition-colors shadow-sm">
            + 새 광고상품 등록
          </button>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4 font-medium whitespace-nowrap">상품명</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">유형</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">상태</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">단가 (CPA)</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">총 예산</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">소진 금액</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-right">수집 DB</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {mockCampaigns.map((camp) => (
                <tr key={camp.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-5 py-4 text-slate-900 font-bold whitespace-nowrap">{camp.name}</td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <span className="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-bold">{camp.type}</span>
                  </td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={camp.status} />
                  </td>
                  <td className="px-5 py-4 text-right font-medium text-slate-700 whitespace-nowrap">{camp.cpa.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-right font-medium text-slate-700 whitespace-nowrap">{camp.budget.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-right whitespace-nowrap">
                    <span className="font-bold text-cyan-600">{camp.spend.toLocaleString()}원</span>
                  </td>
                  <td className="px-5 py-4 text-right font-bold text-slate-900 whitespace-nowrap">{camp.dbCount}건</td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <div className="flex items-center justify-center gap-2">
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
