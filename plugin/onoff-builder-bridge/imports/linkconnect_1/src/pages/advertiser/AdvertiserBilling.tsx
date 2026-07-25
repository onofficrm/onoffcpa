import React, { useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { SummaryCard, StatusBadge } from '../../components/advertiser/AdvertiserShared';
import { Wallet, CreditCard, Receipt, AlertTriangle, ArrowRight, Check, History, Download, Filter, Search } from 'lucide-react';

const billingHistory = [
  { id: 'TX-20261007-03', date: '2026.10.07 14:22', type: '가차감', campaign: '개인회생 상담 DB', dbId: 'DB241007-001', amount: -50000, balance: 1900000, status: '차감완료', memo: '신규 DB 접수 가차감' },
  { id: 'TX-20261007-02', date: '2026.10.07 14:10', type: '확정차감', campaign: '어린이 영어캠프', dbId: 'DB241007-002', amount: 0, balance: 1950000, status: '차감완료', memo: 'DB 승인 (가차감 확정)' },
  { id: 'TX-20261007-01', date: '2026.10.07 11:00', type: '환급', campaign: '자동차 렌트 상담', dbId: 'DB241007-004', amount: +45000, balance: 1950000, status: '환급완료', memo: '취소/무효 처리 (연락처 결번)' },
  { id: 'TX-20261006-05', date: '2026.10.06 18:30', type: '가차감', campaign: '소상공인 대출 상담', dbId: 'DB241006-005', amount: -25000, balance: 1905000, status: '차감완료', memo: '신규 DB 접수 가차감' },
  { id: 'TX-20261001-01', date: '2026.10.01 10:00', type: '충전', campaign: '-', dbId: '-', amount: +5000000, balance: 5000000, status: '충전완료', memo: '10월 정기 충전' },
  { id: 'TX-20260930-01', date: '2026.09.30 15:20', type: '조정', campaign: '-', dbId: '-', amount: +50000, balance: 0, status: '충전완료', memo: '이벤트 캐시 지급' },
];

const TypeBadge = ({ type }: { type: string }) => {
  const styles: Record<string, string> = {
    '충전': 'text-cyan-700 bg-cyan-50 border-cyan-200',
    '가차감': 'text-rose-600 bg-rose-50 border-rose-200',
    '확정차감': 'text-slate-100 bg-slate-800 border-slate-900',
    '환급': 'text-emerald-700 bg-emerald-50 border-emerald-200',
    '조정': 'text-purple-700 bg-purple-50 border-purple-200',
  };
  const style = styles[type] || 'text-slate-600 bg-slate-50 border-slate-200';
  
  return (
    <span className={`px-2 py-1 text-xs font-bold rounded-lg border ${style} whitespace-nowrap`}>
      {type}
    </span>
  );
};

export function AdvertiserBilling() {
  const [chargeAmount, setChargeAmount] = useState<number | ''>('');
  const [activeTab, setActiveTab] = useState('전체');
  
  const tabs = ['전체', '충전', '가차감', '확정차감', '환급', '조정'];

  const filteredHistory = activeTab === '전체' ? billingHistory : billingHistory.filter(h => h.type === activeTab);

  const handleQuickAmount = (amount: number) => {
    setChargeAmount(amount);
  };

  return (
    <AdvertiserLayout activeMenu="billing" title="광고비 충전/내역">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          광고비 잔액과 충전, 차감, 환급 내역을 확인하세요.
        </p>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="현재 광고비 잔액" value="2,350,000" suffix="원" highlight />
        <SummaryCard title="가차감 광고비" value="450,000" suffix="원" color="blue" highlight />
        <SummaryCard title="사용 가능 잔액" value="1,900,000" suffix="원" dark />
        <SummaryCard title="이번 달 충전액" value="5,000,000" suffix="원" />
        <SummaryCard title="이번 달 사용액" value="2,650,000" suffix="원" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {/* Charge Request Form */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 md:p-8">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2.5 bg-cyan-100 text-cyan-600 rounded-xl">
              <CreditCard size={24} />
            </div>
            <div>
              <h2 className="text-lg font-bold text-slate-900">광고비 충전 신청</h2>
              <p className="text-sm text-slate-500">무통장 입금으로 광고비를 충전할 수 있습니다.</p>
            </div>
          </div>

          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">충전 신청 금액 *</label>
              <div className="relative">
                <input 
                  type="number" 
                  value={chargeAmount}
                  onChange={(e) => setChargeAmount(Number(e.target.value))}
                  placeholder="금액을 입력하세요" 
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-bold outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow text-right pr-12 text-lg" 
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 font-medium">원</span>
              </div>
              <div className="grid grid-cols-3 md:grid-cols-6 gap-2 mt-3">
                {[100000, 300000, 500000, 1000000, 3000000, 5000000].map(amount => (
                  <button 
                    key={amount}
                    onClick={() => handleQuickAmount(amount)}
                    className="py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-medium rounded-lg transition-colors"
                  >
                    +{(amount/10000).toLocaleString()}만
                  </button>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">입금자명 *</label>
                <input 
                  type="text" 
                  placeholder="입금하시는 분 성함/법인명" 
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow" 
                />
              </div>
              <div className="flex flex-col justify-center pt-2">
                <label className="flex items-center gap-3 cursor-pointer">
                  <input type="checkbox" className="w-5 h-5 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500" />
                  <div>
                    <span className="block text-sm font-bold text-slate-900">세금계산서 발행 요청</span>
                    <span className="block text-xs text-slate-500">사업자등록증 정보로 발행됩니다.</span>
                  </div>
                </label>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-slate-700 mb-2">메모 (선택)</label>
              <input 
                type="text" 
                placeholder="전달하실 내용이 있다면 입력해주세요." 
                className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow" 
              />
            </div>

            <div className="pt-4 border-t border-slate-100">
              <button className="w-full py-4 bg-cyan-600 text-white hover:bg-cyan-700 rounded-xl font-bold text-lg transition-colors shadow-sm shadow-cyan-600/20 flex items-center justify-center gap-2">
                광고비 충전 신청하기 <ArrowRight size={20} />
              </button>
            </div>
          </div>
        </div>

        {/* Current Status Card */}
        <div className="bg-slate-900 rounded-2xl shadow-lg p-6 md:p-8 text-white flex flex-col relative overflow-hidden">
          <div className="absolute top-0 right-0 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
          
          <div className="flex items-center gap-3 mb-8 relative z-10">
            <div className="p-2 bg-slate-800 rounded-xl">
              <Wallet size={24} className="text-cyan-400" />
            </div>
            <h2 className="text-lg font-bold text-white">이번 달 광고비 현황</h2>
          </div>

          <div className="space-y-6 flex-1 relative z-10">
            <div>
              <div className="text-slate-400 text-sm mb-1">이번 달 충전액</div>
              <div className="text-xl font-medium text-white">+5,000,000원</div>
            </div>
            <div>
              <div className="text-slate-400 text-sm mb-1">이번 달 사용액</div>
              <div className="text-xl font-medium text-white">-2,650,000원</div>
            </div>
            <div>
              <div className="text-slate-400 text-sm mb-1 flex items-center gap-1">
                가차감 금액 <span className="w-4 h-4 rounded-full bg-slate-800 text-slate-400 inline-flex items-center justify-center text-[10px] font-bold cursor-help" title="접수된 디비에 대한 임시 차감액">?</span>
              </div>
              <div className="text-xl font-medium text-cyan-400">-450,000원</div>
            </div>
          </div>

          <div className="pt-6 mt-6 border-t border-slate-800 relative z-10">
            <div className="text-slate-400 text-sm mb-2">남은 잔액 (사용 가능)</div>
            <div className="text-3xl font-bold text-white tracking-tight">1,900,000<span className="text-xl font-medium ml-1">원</span></div>
          </div>

          <button onClick={() => window.scrollTo({top: 0, behavior: 'smooth'})} className="w-full mt-8 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2 border border-white/10 relative z-10">
            광고비 충전하기
          </button>
        </div>
      </div>

      {/* History Table Section */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-slate-100 rounded-lg text-slate-700">
              <History size={20} />
            </div>
            <h2 className="text-lg font-bold text-slate-900">광고비 내역</h2>
          </div>
          
          <div className="flex gap-2">
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
              <Filter size={16} /> 필터
            </button>
            <button className="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
              <Download size={16} /> 엑셀 다운로드
            </button>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex overflow-x-auto border-b border-slate-100 hide-scrollbar px-6">
          {tabs.map(tab => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`px-5 py-3.5 text-sm font-bold whitespace-nowrap border-b-2 transition-colors ${
                activeTab === tab 
                  ? 'border-cyan-600 text-cyan-600' 
                  : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300'
              }`}
            >
              {tab}
            </button>
          ))}
        </div>

        {/* Desktop Table */}
        <div className="hidden lg:block overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-6 py-4 font-medium whitespace-nowrap">일시</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap text-center">구분</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">광고상품 / 내역</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">관련 디비</th>
                <th className="px-6 py-4 font-medium text-right whitespace-nowrap">금액</th>
                <th className="px-6 py-4 font-medium text-right whitespace-nowrap">처리 후 잔액</th>
                <th className="px-6 py-4 font-medium text-center whitespace-nowrap">상태</th>
                <th className="px-6 py-4 font-medium whitespace-nowrap">메모</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredHistory.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-6 py-4 text-slate-500 whitespace-nowrap">{item.date}</td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <TypeBadge type={item.type} />
                  </td>
                  <td className="px-6 py-4 font-bold text-slate-900 whitespace-nowrap">{item.campaign !== '-' ? item.campaign : <span className="text-slate-400 font-normal">충전/조정</span>}</td>
                  <td className="px-6 py-4 text-slate-500 font-mono text-xs whitespace-nowrap">{item.dbId}</td>
                  <td className={`px-6 py-4 text-right font-bold whitespace-nowrap ${item.amount > 0 ? 'text-emerald-600' : item.amount < 0 ? 'text-red-600' : 'text-slate-500'}`}>
                    {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                  </td>
                  <td className="px-6 py-4 text-right font-medium text-slate-900 whitespace-nowrap">
                    {item.balance.toLocaleString()}원
                  </td>
                  <td className="px-6 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={item.status} />
                  </td>
                  <td className="px-6 py-4 text-slate-500 truncate max-w-[200px]" title={item.memo}>{item.memo}</td>
                </tr>
              ))}
              {filteredHistory.length === 0 && (
                <tr>
                  <td colSpan={8} className="px-6 py-12 text-center text-slate-500">
                    내역이 없습니다.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Mobile List */}
        <div className="lg:hidden divide-y divide-slate-100">
          {filteredHistory.map((item) => (
            <div key={item.id} className="p-5 flex flex-col gap-3">
              <div className="flex justify-between items-center">
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <span>{item.date}</span>
                </div>
                <TypeBadge type={item.type} />
              </div>
              
              <div>
                <h3 className="font-bold text-slate-900 mb-1">{item.campaign !== '-' ? item.campaign : '충전/조정 내역'}</h3>
                {item.dbId !== '-' && <p className="text-xs text-slate-400 font-mono mb-2">관련 디비: {item.dbId}</p>}
                <p className="text-sm text-slate-600 truncate">{item.memo}</p>
              </div>
              
              <div className="bg-slate-50 p-3 rounded-xl flex justify-between items-center mt-1">
                <div className="flex flex-col">
                  <span className="text-xs text-slate-500 mb-0.5">잔액</span>
                  <span className="text-sm font-medium text-slate-700">{item.balance.toLocaleString()}원</span>
                </div>
                <div className="flex flex-col items-end">
                  <span className="text-xs text-slate-500 mb-0.5">변동금액</span>
                  <span className={`font-bold ${item.amount > 0 ? 'text-emerald-600' : item.amount < 0 ? 'text-red-600' : 'text-slate-500'}`}>
                    {item.amount > 0 ? '+' : ''}{item.amount.toLocaleString()}원
                  </span>
                </div>
              </div>
              <div className="mt-1">
                <StatusBadge status={item.status} />
              </div>
            </div>
          ))}
          {filteredHistory.length === 0 && (
            <div className="p-8 text-center text-slate-500 text-sm">
              내역이 없습니다.
            </div>
          )}
        </div>
      </div>

      {/* Info Box */}
      <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <div className="flex items-start gap-4">
          <AlertTriangle className="w-6 h-6 text-cyan-400 shrink-0 mt-0.5" />
          <div>
            <h3 className="text-lg font-bold mb-4">광고비 운영 안내</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>디비가 접수되면 광고비가 먼저 <strong>가차감</strong>됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"></span>
                <span>승인 완료 시 광고비가 <strong>확정 차감</strong> 처리됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                <span>취소/무효 처리된 디비는 가차감되었던 광고비가 다시 <strong>환급</strong>됩니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                <span>광고비 잔액이 부족해지면 진행 중인 캠페인이 <strong>일시중지</strong>될 수 있으니 잔액을 여유있게 유지해주세요.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </AdvertiserLayout>
  );
}
