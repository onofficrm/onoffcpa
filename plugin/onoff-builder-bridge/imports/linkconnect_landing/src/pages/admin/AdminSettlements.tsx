import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Banknote, Calendar, Search, ChevronDown, CheckCircle2, 
  XCircle, Clock, Check, X, FileText, AlertTriangle, Building2, User
} from 'lucide-react';

const settlementsData = [
  { id: 'ST260706-01', date: '2026.07.06 10:30', partnerCode: 'P-10023', partnerName: '김파트너', requestAmount: 3500000, approvedAmount: 3500000, bank: '국민은행', account: '932012-**-***', accountHolder: '김파트너', status: '신청완료' },
  { id: 'ST260706-02', date: '2026.07.06 11:15', partnerCode: 'P-10045', partnerName: '(주)마케팅허브', requestAmount: 12500000, approvedAmount: 12500000, bank: '신한은행', account: '110-345-***', accountHolder: '(주)마케팅허브', status: '승인대기' },
  { id: 'ST260705-08', date: '2026.07.05 16:40', partnerCode: 'P-10008', partnerName: '박마케터', requestAmount: 1800000, approvedAmount: 1800000, bank: '카카오뱅크', account: '3333-01-***', accountHolder: '박마케터', status: '승인완료' },
  { id: 'ST260704-12', date: '2026.07.04 09:20', partnerCode: 'P-10112', partnerName: '리드젠', requestAmount: 5400000, approvedAmount: 0, bank: '우리은행', account: '1002-455-***', accountHolder: '이리드', status: '보류' },
  { id: 'ST260701-05', date: '2026.07.01 14:00', partnerCode: 'P-10033', partnerName: '최파트너', requestAmount: 850000, approvedAmount: 0, bank: '하나은행', account: '456-910-***', accountHolder: '최파트너', status: '반려' },
  { id: 'ST260701-01', date: '2026.07.01 10:00', partnerCode: 'P-10023', partnerName: '김파트너', requestAmount: 4200000, approvedAmount: 4200000, bank: '국민은행', account: '932012-**-***', accountHolder: '김파트너', status: '지급완료' },
];

const settlementDetailsData = [
  { date: '2026.07.05 14:20', campaign: '직장인 신용대출 한도조회', dbCode: 'DB-260705-042', status: '승인완료', revenue: 35000, included: true },
  { date: '2026.07.05 15:10', campaign: '개인회생 무료상담 이벤트', dbCode: 'DB-260705-055', status: '승인완료', revenue: 50000, included: true },
  { date: '2026.07.05 16:05', campaign: '직장인 신용대출 한도조회', dbCode: 'DB-260705-089', status: '취소/무효', revenue: 0, included: false },
  { date: '2026.07.06 09:30', campaign: '개인회생 무료상담 이벤트', dbCode: 'DB-260706-012', status: '검수중', revenue: 0, included: false },
];

export function AdminSettlements() {
  const [selectedSettlement, setSelectedSettlement] = useState<string | null>(null);

  return (
    <AdminLayout activeMenu="settlements" title="정산 관리" description="파트너 수익 정산 신청을 검토하고 지급 상태를 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="정산 신청 대기" value="37" suffix="건" dark icon={<Banknote size={18} />} />
        <SummaryCard title="정산 대기 금액" value="12,800,000" suffix="원" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="오늘 승인 금액" value="4,200,000" suffix="원" color="cyan" highlight icon={<CheckCircle2 size={18} />} />
        <SummaryCard title="이번 달 지급완료" value="86,500,000" suffix="원" color="emerald" highlight icon={<Check size={18} />} />
        <SummaryCard title="보류 건수" value="5" suffix="건" color="orange" icon={<AlertTriangle size={18} />} />
        <SummaryCard title="반려 건수" value="2" suffix="건" color="red" icon={<XCircle size={18} />} />
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        
        {/* Main List */}
        <div className={`flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden ${selectedSettlement ? 'hidden lg:block lg:w-2/3' : 'w-full'}`}>
          <div className="p-6">
            
            {/* Filters */}
            <div className="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b border-slate-100">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="파트너명 또는 코드 검색" 
                    className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              
              <div className="flex flex-wrap items-center gap-2">
                <select className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 상태</option>
                  <option>신청완료</option>
                  <option>승인대기</option>
                  <option>승인완료</option>
                  <option>지급완료</option>
                  <option>보류</option>
                  <option>반려</option>
                </select>
                <div className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>신청일 기준</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
              </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">신청일 / 정산코드</th>
                    <th className="px-4 py-3 font-medium">파트너 정보</th>
                    <th className="px-4 py-3 font-medium text-right">신청금액</th>
                    <th className="px-4 py-3 font-medium">계좌정보</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {settlementsData.map((item, i) => (
                    <tr 
                      key={i} 
                      className={`transition-colors cursor-pointer ${selectedSettlement === item.id ? 'bg-cyan-50' : 'hover:bg-slate-50'}`}
                      onClick={() => setSelectedSettlement(item.id)}
                    >
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-slate-600 mb-0.5">{item.date}</div>
                        <div className="text-xs text-slate-400">{item.id}</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{item.partnerName}</div>
                        <div className="text-xs text-slate-500">{item.partnerCode}</div>
                      </td>
                      <td className="px-4 py-4 text-right whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{item.requestAmount.toLocaleString()}원</div>
                        {item.status === '승인완료' || item.status === '지급완료' ? (
                           <div className="text-xs text-cyan-600 font-medium">승인: {item.approvedAmount.toLocaleString()}원</div>
                        ) : null}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-slate-700 mb-0.5">{item.bank} {item.account}</div>
                        <div className="text-xs text-slate-500">예금주: {item.accountHolder}</div>
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <StatusBadge status={item.status} />
                      </td>
                      <td className="px-4 py-4 text-center whitespace-nowrap">
                        <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">
                          상세보기
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

          </div>
        </div>

        {/* Details Panel */}
        {selectedSettlement && (
          <div className="lg:w-1/3 w-full flex flex-col gap-4">
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col max-h-[calc(100vh-120px)] sticky top-6">
              
              <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <h3 className="font-bold text-slate-900 flex items-center gap-2">
                  <FileText size={18} className="text-slate-400" />
                  정산 상세 내역
                </h3>
                <button 
                  onClick={() => setSelectedSettlement(null)}
                  className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="p-5 overflow-y-auto flex-1 space-y-6">
                
                {/* 1. 파트너 정보 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <User size={16} className="text-cyan-600" /> 파트너 정보
                  </h4>
                  <div className="bg-slate-50 rounded-xl p-4 text-sm space-y-3">
                    <div className="flex justify-between">
                      <span className="text-slate-500">이름/상호명</span>
                      <span className="font-bold text-slate-900">김파트너 (P-10023)</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-500">연락처</span>
                      <span className="font-medium text-slate-700">010-1234-56**</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-500">이메일</span>
                      <span className="font-medium text-slate-700">k*@example.com</span>
                    </div>
                    <div className="pt-3 mt-3 border-t border-slate-200 flex justify-between">
                      <span className="text-slate-500">계좌 정보</span>
                      <span className="font-bold text-slate-800">국민은행 932012-**-*** (김파트너)</span>
                    </div>
                  </div>
                </section>

                {/* 2. 정산 금액 정보 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Banknote size={16} className="text-cyan-600" /> 정산 금액 정보
                  </h4>
                  <div className="bg-slate-50 rounded-xl p-4 text-sm space-y-3">
                    <div className="flex justify-between">
                      <span className="text-slate-500">총 확정수익</span>
                      <span className="font-medium text-slate-700">7,700,000원</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-slate-500">기존 정산완료</span>
                      <span className="font-medium text-slate-700">-4,200,000원</span>
                    </div>
                    <div className="flex justify-between text-cyan-600">
                      <span className="font-bold">현재 정산 가능 금액</span>
                      <span className="font-bold">3,500,000원</span>
                    </div>
                    <div className="pt-3 mt-3 border-t border-slate-200 flex justify-between items-center">
                      <span className="text-slate-500 font-medium">이번 신청 금액</span>
                      <span className="text-lg font-bold text-slate-900">3,500,000원</span>
                    </div>
                  </div>
                </section>

                {/* 3. 수익 내역 */}
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-3">정산 대상 수익 내역 (요약)</h4>
                  <div className="border border-slate-200 rounded-xl overflow-hidden">
                    <table className="w-full text-xs text-left">
                      <thead className="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                          <th className="px-3 py-2 font-medium">광고상품</th>
                          <th className="px-3 py-2 font-medium text-right">수익금</th>
                          <th className="px-3 py-2 font-medium text-center">포함</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {settlementDetailsData.map((detail, idx) => (
                          <tr key={idx} className={!detail.included ? 'bg-slate-50/50' : ''}>
                            <td className="px-3 py-2">
                              <div className={`font-medium truncate max-w-[120px] ${!detail.included ? 'text-slate-400 line-through' : 'text-slate-700'}`}>{detail.campaign}</div>
                              <div className="text-[10px] text-slate-400">{detail.dbCode} ({detail.status})</div>
                            </td>
                            <td className={`px-3 py-2 text-right font-medium ${!detail.included ? 'text-slate-400' : 'text-slate-700'}`}>
                              {detail.revenue > 0 ? detail.revenue.toLocaleString() : 0}원
                            </td>
                            <td className="px-3 py-2 text-center">
                              {detail.included ? (
                                <Check size={14} className="text-cyan-600 mx-auto" />
                              ) : (
                                <X size={14} className="text-slate-300 mx-auto" />
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </section>

                {/* 4. 관리자 처리 */}
                <section className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                  <h4 className="text-sm font-bold text-slate-900 mb-3">관리자 처리</h4>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">승인 금액</label>
                      <div className="relative">
                        <input 
                          type="number" 
                          defaultValue={3500000}
                          className="w-full pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm font-bold focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                        />
                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">원</span>
                      </div>
                    </div>
                    
                    <div>
                      <label className="block text-xs font-medium text-slate-500 mb-1.5">관리자 메모</label>
                      <textarea 
                        placeholder="승인/보류/반려 사유 입력"
                        className="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm h-16 resize-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                      ></textarea>
                    </div>

                    <div className="grid grid-cols-2 gap-2 pt-2">
                      <button className="py-2 bg-slate-800 text-white rounded-lg text-sm font-bold hover:bg-slate-900 transition-colors shadow-sm">
                        승인 보류
                      </button>
                      <button className="py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors shadow-sm">
                        반려 처리
                      </button>
                      <button className="py-2 bg-cyan-600 text-white rounded-lg text-sm font-bold hover:bg-cyan-700 transition-colors shadow-sm col-span-2 flex items-center justify-center gap-1.5">
                        <CheckCircle2 size={16} /> 승인 완료
                      </button>
                      <button className="py-2 bg-emerald-600 text-white rounded-lg text-sm font-bold hover:bg-emerald-700 transition-colors shadow-sm col-span-2 flex items-center justify-center gap-1.5 mt-1">
                        <Banknote size={16} /> 최종 지급완료 처리
                      </button>
                    </div>
                  </div>
                </section>
                
              </div>
            </div>
            
            {/* Notice Box */}
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
              <AlertTriangle size={18} className="text-amber-600 mt-0.5 shrink-0" />
              <div className="text-xs text-amber-800 space-y-1">
                <p className="font-bold mb-1">정산 처리 주의사항</p>
                <p>• <b>승인완료</b>된 수익만 정산 대상입니다.</p>
                <p>• 취소/무효 처리된 디비 수익금은 정산에서 제외됩니다.</p>
                <p>• <b>지급완료</b> 처리 후에는 해당 정산 내역이 잠금 처리되며 수정할 수 없습니다.</p>
              </div>
            </div>
          </div>
        )}

      </div>
    </AdminLayout>
  );
}
