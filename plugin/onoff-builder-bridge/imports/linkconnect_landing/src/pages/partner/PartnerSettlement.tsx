import { PartnerLayout } from '../../layouts/PartnerLayout';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { CreditCard, Info, CheckCircle2, Clock, Building2, User, FileText, Calculator, Check, AlertCircle } from 'lucide-react';

const settlementHistory = [
  { id: 'ST-241001', date: '2026.10.01 10:15', reqAmount: 3500000, appAmount: 3500000, status: '지급완료', payDate: '2026.10.05', memo: '10월 정기 지급건' },
  { id: 'ST-240901', date: '2026.09.01 11:20', reqAmount: 2800000, appAmount: 2800000, status: '지급완료', payDate: '2026.09.05', memo: '9월 정기 지급건' },
  { id: 'ST-240801', date: '2026.08.01 09:45', reqAmount: 420000, appAmount: 0, status: '반려', payDate: '-', memo: '예금주명 불일치 (수정 후 재신청 요망)' },
  { id: 'ST-240715', date: '2026.07.15 14:30', reqAmount: 420000, appAmount: 420000, status: '지급완료', payDate: '2026.07.20', memo: '7월 중순 수시 지급' },
];

export function PartnerSettlement() {
  return (
    <PartnerLayout activeMenu="settlement" title="정산 신청">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          확정수익을 기준으로 정산 가능 금액을 확인하고 정산을 신청하세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="이번 달 확정수익" value="8,430,000" suffix="원" icon={<Calculator className="text-slate-500" />} />
        <SummaryCard title="정산 완료 금액" value="23,500,000" suffix="원" icon={<CheckCircle2 className="text-blue-500" />} />
        <SummaryCard title="정산 대기 금액" value="1,200,000" suffix="원" icon={<Clock className="text-yellow-500" />} />
        <SummaryCard title="정산 가능 금액" value="7,230,000" suffix="원" icon={<CreditCard className="text-emerald-600" />} highlight />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-10">
        {/* Form Area */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
          <h2 className="text-lg font-bold text-slate-900 mb-6">정산 신청 정보</h2>
          
          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">정산 신청 금액 <span className="text-red-500">*</span></label>
              <div className="relative">
                <input 
                  type="text" 
                  className="w-full pl-4 pr-12 py-3 bg-white border border-slate-200 rounded-xl text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                  defaultValue="7,230,000"
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">원</span>
              </div>
              <div className="mt-2 flex items-center justify-between">
                <p className="text-xs text-slate-500">최소 정산 가능 금액: 50,000원</p>
                <button className="text-xs font-medium text-emerald-600 hover:text-emerald-700">전액 입력</button>
              </div>
            </div>

            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">은행명 <span className="text-red-500">*</span></label>
                <div className="relative">
                  <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                  <select className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 appearance-none">
                    <option>신한은행</option>
                    <option>국민은행</option>
                    <option>우리은행</option>
                    <option>하나은행</option>
                    <option>농협은행</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">예금주 <span className="text-red-500">*</span></label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                  <input 
                    type="text" 
                    className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 focus:outline-none"
                    defaultValue="김파트너"
                    readOnly
                  />
                </div>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">계좌번호 <span className="text-red-500">*</span></label>
              <div className="relative">
                <CreditCard className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input 
                  type="text" 
                  className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                  defaultValue="110-123-456789"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">메모 <span className="text-slate-400 font-normal">(선택)</span></label>
              <div className="relative">
                <FileText className="absolute left-3 top-4 w-5 h-5 text-slate-400" />
                <textarea 
                  rows={3}
                  className="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none"
                  placeholder="관리자에게 남길 메모가 있다면 입력해주세요."
                ></textarea>
              </div>
            </div>
            
            <div className="pt-4">
              <button className="w-full py-4 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm text-lg">
                정산 신청하기
              </button>
            </div>
          </div>
        </div>

        {/* Right Summary */}
        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg sticky top-6">
            <h2 className="text-lg font-bold mb-8 flex items-center gap-2">
              <CreditCard className="text-emerald-400" />
              내 정산 요약
            </h2>
            
            <div className="space-y-5">
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400 text-sm">누적 확정수익</span>
                <span className="font-semibold text-white">31,930,000 원</span>
              </div>
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400 text-sm">기정산 금액</span>
                <span className="font-medium text-slate-300">23,500,000 원</span>
              </div>
              <div className="flex justify-between items-center py-3 border-b border-white/10">
                <span className="text-slate-400 text-sm">정산 대기</span>
                <span className="font-medium text-yellow-400">1,200,000 원</span>
              </div>
              <div className="pt-4">
                <span className="block text-slate-300 text-sm mb-2">현재 신청 가능 금액</span>
                <span className="text-4xl font-bold text-emerald-400 tracking-tight">7,230,000 <span className="text-xl font-normal text-emerald-400/80">원</span></span>
              </div>
            </div>

            <div className="mt-8 bg-white/10 rounded-xl p-4">
              <div className="text-xs text-slate-300 space-y-2">
                <div className="flex items-center gap-1.5"><Check size={14} className="text-emerald-400"/> 최소 정산 기준: 50,000원</div>
                <div className="flex items-center gap-1.5"><Check size={14} className="text-emerald-400"/> 원천징수 3.3% 차감 후 지급</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* History Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div className="p-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
          <h2 className="font-bold text-slate-900">정산 신청 내역</h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-5 py-4 font-medium whitespace-nowrap">신청일시</th>
                <th className="px-5 py-4 font-medium text-right whitespace-nowrap">신청금액</th>
                <th className="px-5 py-4 font-medium text-right whitespace-nowrap">승인금액</th>
                <th className="px-5 py-4 font-medium text-center whitespace-nowrap">상태</th>
                <th className="px-5 py-4 font-medium text-center whitespace-nowrap">지급일</th>
                <th className="px-5 py-4 font-medium whitespace-nowrap">관리자 메모</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {settlementHistory.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-5 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                  <td className="px-5 py-4 text-right font-bold text-slate-900 whitespace-nowrap">{item.reqAmount.toLocaleString()}원</td>
                  <td className="px-5 py-4 text-right font-bold text-emerald-600 whitespace-nowrap">{item.appAmount > 0 ? `${item.appAmount.toLocaleString()}원` : '-'}</td>
                  <td className="px-5 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={item.status} />
                  </td>
                  <td className="px-5 py-4 text-center text-slate-600 whitespace-nowrap">{item.payDate}</td>
                  <td className="px-5 py-4 text-slate-600 min-w-[200px]">{item.memo}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Notice Box */}
      <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <div className="flex items-start gap-4">
          <Info className="w-6 h-6 text-cyan-400 shrink-0 mt-0.5" />
          <div>
            <h3 className="text-lg font-bold mb-4">정산 안내</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                <span>광고주에 의해 <strong>승인 완료된 수익만</strong> 정산 대상에 포함됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <AlertCircle className="w-4 h-4 text-yellow-400 shrink-0 mt-0.5" />
                <span>취소/무효 처리된 디비는 정산에서 제외되며, 이미 정산된 디비가 취소될 경우 다음 정산 금액에서 차감될 수 있습니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                <span>정산 신청 후 관리자 승인 절차가 진행되며, 승인 완료 후 지정된 지급일에 입금됩니다. (영업일 기준 1~3일 소요)</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </PartnerLayout>
  );
}




