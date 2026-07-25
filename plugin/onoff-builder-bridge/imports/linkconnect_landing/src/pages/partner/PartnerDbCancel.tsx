import { Search, Filter, AlertCircle, MessageSquare, Info, XCircle, FileWarning, PieChart, ShieldAlert, X } from 'lucide-react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';

const cancelData = [
  { id: 'DB241007-004', date: '2026.10.07 10:05', campaign: '개인회생 상담 DB', name: '최*훈', phone: '010-55**-33**', channel: '유튜브', reason: '연락불가', comment: '3회 통화 시도 모두 부재중', status: '취소/무효' },
  { id: 'DB241006-007', date: '2026.10.06 11:10', campaign: '개인회생 상담 DB', name: '윤*철', phone: '010-99**-88**', channel: '네이버 블로그', reason: '중복디비', comment: '기존 접수 이력 있음 (10.02)', status: '취소/무효' },
  { id: 'DB241005-012', date: '2026.10.05 14:30', campaign: '자동차 장기렌트', name: '이*수', phone: '010-12**-45**', channel: '인스타그램', reason: '조건불일치', comment: '신용불량자로 진행 불가', status: '취소/무효' },
  { id: 'DB241004-009', date: '2026.10.04 16:45', campaign: '어린이 영어캠프', name: '김*영', phone: '010-34**-56**', channel: '맘카페', reason: '장난접수', comment: '상담 의사 없음', status: '취소/무효' },
  { id: 'DB241002-021', date: '2026.10.02 09:20', campaign: '프리미엄 임플란트', name: '박*진', phone: '010-88**-99**', channel: '네이버 카페', reason: '지역불가', comment: '제주 지역 방문 불가', status: '취소/무효' },
  { id: 'DB241001-033', date: '2026.10.01 15:15', campaign: '소상공인 대출', name: '정*환', phone: '010-22**-33**', channel: '네이버 블로그', reason: '허위정보', comment: '사업자 아님', status: '취소/무효' },
];

export function PartnerDbCancel() {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedDb, setSelectedDb] = useState<any>(null);

  const openAppealModal = (db: any) => {
    setSelectedDb(db);
    setIsModalOpen(true);
  };

  return (
    <PartnerLayout activeMenu="db-cancel" title="취소/무효 디비">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          취소 또는 무효 처리된 디비의 사유와 광고주 코멘트를 확인하세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 취소/무효" value="211" suffix="건" icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="이번 주 취소" value="14" suffix="건" icon={<FileWarning className="text-orange-500" />} />
        <SummaryCard title="이번 달 취소율" value="16.5" suffix="%" icon={<AlertCircle className="text-yellow-500" />} />
        <SummaryCard title="최다 취소 사유" value="연락불가" suffix="" icon={<MessageSquare className="text-slate-500" />} />
      </div>

      <div className="grid lg:grid-cols-4 gap-8 mb-8">
        <div className="lg:col-span-3 space-y-6">
          {/* Filters */}
          <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center shadow-sm">
            <div className="flex items-center gap-2">
              <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-01" />
              <span className="text-slate-400">~</span>
              <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-07" />
            </div>
            <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
              <option>전체 광고상품</option>
              <option>개인회생 상담 DB</option>
            </select>
            <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[120px] flex-1 md:flex-none">
              <option>취소 사유 전체</option>
              <option>연락불가</option>
              <option>중복디비</option>
              <option>조건불일치</option>
              <option>기타</option>
            </select>
            <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[120px] flex-1 md:flex-none">
              <option>전체 채널</option>
              <option>네이버 블로그</option>
              <option>유튜브</option>
            </select>
            <div className="relative flex-1 min-w-[150px]">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input 
                type="text" 
                placeholder="검색어 입력" 
                className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
              />
            </div>
            <button className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm">
              <Filter size={16} /> 조회
            </button>
          </div>

          {/* Cancel List Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">접수일</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">광고상품 / 유입경로</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">고객명 / 연락처</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">취소 사유</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap min-w-[200px]">광고주 코멘트</th>
                    <th className="px-4 py-4 font-medium text-center whitespace-nowrap">상태</th>
                    <th className="px-4 py-4 font-medium text-center whitespace-nowrap">이의신청</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {cancelData.map((db) => (
                    <tr key={db.id} className="hover:bg-slate-50 transition-colors bg-red-50/10">
                      <td className="px-4 py-4 text-slate-500 whitespace-nowrap">{db.date}</td>
                      <td className="px-4 py-4">
                        <div className="flex flex-col min-w-[140px]">
                          <span className="font-bold text-slate-900">{db.campaign}</span>
                          <span className="text-xs text-slate-500 mt-1">{db.channel}</span>
                        </div>
                      </td>
                      <td className="px-4 py-4">
                        <div className="flex flex-col">
                          <span className="text-slate-700 font-medium">{db.name}</span>
                          <span className="font-mono text-slate-500 text-xs mt-1">{db.phone}</span>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <ReasonBadge reason={db.reason} />
                      </td>
                      <td className="px-4 py-4">
                        <div className="flex items-start gap-2 bg-white border border-slate-200 p-2.5 rounded-lg text-slate-600">
                          <MessageSquare className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                          <span className="text-xs leading-relaxed">{db.comment}</span>
                        </div>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <span className="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-md bg-red-50 text-red-600 border border-red-200">
                          {db.status}
                        </span>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <button 
                          onClick={() => openAppealModal(db)}
                          className="px-3 py-1.5 bg-white border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors"
                        >
                          이의신청
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            
            {/* Pagination */}
            <div className="p-4 border-t border-slate-100 flex items-center justify-center gap-1 bg-white">
              <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50" disabled>&lt;</button>
              <button className="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-500 text-white font-bold shadow-sm">1</button>
              <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">2</button>
              <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50">&gt;</button>
            </div>
          </div>
        </div>

        {/* Right Sidecards */}
        <div className="lg:col-span-1 space-y-6">
          <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div className="flex items-center gap-2 mb-6">
              <PieChart className="text-emerald-500" size={20} />
              <h3 className="font-bold text-lg text-slate-900">취소 사유 분포</h3>
            </div>
            <div className="space-y-4">
              <DistributionRow label="연락불가" percentage={35} color="bg-orange-500" />
              <DistributionRow label="중복디비" percentage={25} color="bg-blue-500" />
              <DistributionRow label="조건불일치" percentage={18} color="bg-purple-500" />
              <DistributionRow label="기타" percentage={22} color="bg-slate-400" />
            </div>
          </div>

          <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg sticky top-6">
            <div className="flex items-center gap-2 mb-4">
              <ShieldAlert className="text-yellow-400" size={20} />
              <h3 className="font-bold text-lg">취소 디비 운영 팁</h3>
            </div>
            <ul className="space-y-3 text-sm text-slate-300">
              <li className="flex items-start gap-2">
                <div className="w-1.5 h-1.5 rounded-full bg-yellow-400 mt-1.5 shrink-0"></div>
                <p>각 캠페인의 <strong>금지 채널</strong>을 다시 한번 확인해주세요.</p>
              </li>
              <li className="flex items-start gap-2">
                <div className="w-1.5 h-1.5 rounded-full bg-yellow-400 mt-1.5 shrink-0"></div>
                <p>허위·과장 홍보 문구로 유입된 고객은 대부분 조건불일치나 취소로 이어집니다. <strong>문구를 점검하세요.</strong></p>
              </li>
              <li className="flex items-start gap-2">
                <div className="w-1.5 h-1.5 rounded-full bg-yellow-400 mt-1.5 shrink-0"></div>
                <p>유입 분석 메뉴에서 <strong>승인율이 높은 캠페인과 채널</strong>에 집중적으로 트래픽을 몰아보세요.</p>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Appeal Modal */}
      {isModalOpen && selectedDb && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
              <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                <Info size={18} className="text-blue-500" />
                이의신청
              </h3>
              <button onClick={() => setIsModalOpen(false)} className="text-slate-400 hover:text-slate-600 transition-colors">
                <X size={20} />
              </button>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 text-sm">
                <div className="flex justify-between mb-1">
                  <span className="text-slate-500">고객명</span>
                  <span className="font-bold text-slate-900">{selectedDb.name} ({selectedDb.phone})</span>
                </div>
                <div className="flex justify-between mb-1">
                  <span className="text-slate-500">캠페인</span>
                  <span className="font-medium text-slate-900">{selectedDb.campaign}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">광고주 코멘트</span>
                  <span className="font-medium text-red-500">{selectedDb.comment}</span>
                </div>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">이의신청 사유</label>
                <textarea 
                  rows={4} 
                  className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none"
                  placeholder="광고주가 확인할 수 있도록 구체적인 이의제기 사유를 작성해주세요."
                ></textarea>
                <p className="text-xs text-slate-400 mt-2">
                  * 제출된 이의신청은 관리자 검토 후 광고주에게 전달됩니다.
                </p>
              </div>
            </div>
            <div className="px-6 py-5 border-t border-slate-100 flex gap-3">
              <button onClick={() => setIsModalOpen(false)} className="flex-1 py-2.5 bg-white border border-slate-200 text-slate-700 font-medium rounded-xl hover:bg-slate-100 transition-colors">
                취소
              </button>
              <button onClick={() => setIsModalOpen(false)} className="flex-1 py-2.5 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm">
                제출하기
              </button>
            </div>
          </div>
        </div>
      )}
    </PartnerLayout>
  );
}



function ReasonBadge({ reason }: { reason: string }) {
  const styles: Record<string, string> = {
    '연락불가': 'bg-orange-50 text-orange-600 border-orange-200',
    '중복디비': 'bg-blue-50 text-blue-600 border-blue-200',
    '조건불일치': 'bg-purple-50 text-purple-600 border-purple-200',
    '장난접수': 'bg-red-50 text-red-600 border-red-200',
    '지역불가': 'bg-slate-100 text-slate-600 border-slate-200',
    '허위정보': 'bg-pink-50 text-pink-600 border-pink-200',
  };
  
  const defaultStyle = 'bg-slate-100 text-slate-600 border-slate-200';
  
  return (
    <span className={`inline-flex items-center px-2 py-1 text-xs font-bold rounded-md border ${styles[reason] || defaultStyle}`}>
      {reason}
    </span>
  );
}

function DistributionRow({ label, percentage, color }: { label: string, percentage: number, color: string }) {
  return (
    <div className="flex items-center justify-between">
      <div className="flex items-center gap-2 flex-1">
        <div className={`w-2.5 h-2.5 rounded-full ${color}`}></div>
        <span className="text-sm text-slate-600 font-medium">{label}</span>
      </div>
      <div className="flex items-center gap-3">
        <div className="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
          <div className={`h-full ${color}`} style={{ width: `${percentage}%` }}></div>
        </div>
        <span className="text-sm font-bold text-slate-900 w-8 text-right">{percentage}%</span>
      </div>
    </div>
  );
}
