import { Search, Filter, Download, CheckCircle2, Clock, XCircle, MessageSquare, Info, Target, DollarSign, ListOrdered } from 'lucide-react';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { useEffect, useState } from 'react';
import { fetchPartnerConversions, PartnerConversion } from '../../lib/api';

export function PartnerDbStatus() {
  const [items, setItems] = useState<PartnerConversion[]>([]);
  const [summary, setSummary] = useState({ total: 0, pending: 0, approved: 0, rejected: 0, estRevenue: 0, confRevenue: 0 });
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState('');

  useEffect(() => {
    setLoading(true);
    fetchPartnerConversions({ q })
      .then((data) => {
        setItems(data.items);
        setSummary(data.summary);
      })
      .catch(() => setItems([]))
      .finally(() => setLoading(false));
  }, [q]);

  return (
    <PartnerLayout activeMenu="db-status" title="디비 현황">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">
          접수된 디비의 상태와 수익 반영 여부를 확인할 수 있습니다.
        </p>
      </div>

      {/* Overview Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 접수 DB" value={String(summary.total)} suffix="건" icon={<ListOrdered className="text-slate-500" />} />
        <SummaryCard title="승인대기" value={String(summary.pending)} suffix="건" icon={<Clock className="text-blue-500" />} />
        <SummaryCard title="승인완료" value={String(summary.approved)} suffix="건" highlight icon={<CheckCircle2 className="text-emerald-500" />} />
        <SummaryCard title="취소/무효" value={String(summary.rejected)} suffix="건" icon={<XCircle className="text-red-500" />} />
        <SummaryCard title="예상수익" value={summary.estRevenue.toLocaleString()} suffix="원" icon={<DollarSign className="text-slate-500" />} />
        <SummaryCard title="확정수익" value={summary.confRevenue.toLocaleString()} suffix="원" highlight icon={<Target className="text-emerald-600" />} />
      </div>

      {/* Filter Bar */}
      <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap gap-4 items-center mb-6 shadow-sm">
        <div className="flex items-center gap-2">
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-01" />
          <span className="text-slate-400">~</span>
          <input type="date" className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500" defaultValue="2026-10-07" />
        </div>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[140px] flex-1 md:flex-none">
          <option>전체 캠페인</option>
          <option>개인회생 상담 DB</option>
          <option>어린이 영어캠프</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[120px] flex-1 md:flex-none">
          <option>상태 전체</option>
          <option>접수완료</option>
          <option>검수중</option>
          <option>승인완료</option>
          <option>취소/무효</option>
          <option>확정완료</option>
          <option>정산완료</option>
        </select>
        <select className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500 min-w-[120px] flex-1 md:flex-none">
          <option>전체 채널</option>
          <option>네이버 블로그</option>
          <option>인스타그램</option>
          <option>유튜브</option>
        </select>
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input 
            type="text" 
            placeholder="고객명, 연락처 검색" 
            value={q}
            onChange={(e) => setQ(e.target.value)}
            className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
          />
        </div>
        <button className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm">
          <Filter size={16} /> 조회
        </button>
      </div>

      {/* DB List */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col mb-8">
        <div className="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <div className="text-sm text-slate-600 font-medium">총 <span className="text-emerald-600 font-bold">{items.length}</span>건의 디비가 조회되었습니다.</div>
          <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors">
            <Download size={14} /> 엑셀 다운로드
          </button>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-4 py-4 font-medium whitespace-nowrap">접수일</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">광고상품</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">고객명</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">연락처</th>
                <th className="px-4 py-4 font-medium whitespace-nowrap">유입경로</th>
                <th className="px-4 py-4 font-medium text-center whitespace-nowrap">상태</th>
                <th className="px-4 py-4 font-medium text-right whitespace-nowrap">단가</th>
                <th className="px-4 py-4 font-medium text-right whitespace-nowrap">예상수익</th>
                <th className="px-4 py-4 font-medium text-right whitespace-nowrap">확정수익</th>
                <th className="px-4 py-4 font-medium text-center whitespace-nowrap">코멘트</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr><td colSpan={10} className="px-4 py-12 text-center text-slate-500">불러오는 중...</td></tr>
              ) : items.length > 0 ? items.map((db) => (
                <tr key={db.id} className={`transition-colors ${db.status === '취소/무효' ? 'bg-red-50/30 hover:bg-red-50/50' : 'hover:bg-slate-50'}`}>
                  <td className="px-4 py-4 text-slate-500 whitespace-nowrap">{db.date}</td>
                  <td className="px-4 py-4 font-medium text-slate-900 min-w-[140px]">{db.campaign}</td>
                  <td className="px-4 py-4 text-slate-700 whitespace-nowrap">{db.name}</td>
                  <td className="px-4 py-4 font-mono text-slate-600 whitespace-nowrap">{db.phone}</td>
                  <td className="px-4 py-4 text-slate-600 whitespace-nowrap">{db.channel}</td>
                  <td className="px-4 py-4 text-center whitespace-nowrap">
                    <StatusBadge status={db.status} />
                  </td>
                  <td className="px-4 py-4 text-right text-slate-600 whitespace-nowrap">{db.price.toLocaleString()}</td>
                  <td className="px-4 py-4 text-right font-medium whitespace-nowrap">
                    <span className={db.estRevenue > 0 ? 'text-blue-600' : 'text-slate-400'}>{db.estRevenue.toLocaleString()}</span>
                  </td>
                  <td className="px-4 py-4 text-right font-bold whitespace-nowrap">
                    <span className={db.confRevenue > 0 ? 'text-emerald-600' : 'text-slate-400'}>{db.confRevenue.toLocaleString()}</span>
                  </td>
                  <td className="px-4 py-4 text-center">
                    {db.comment ? (
                      <div className="group relative inline-flex justify-center">
                        <button type="button" className="text-slate-400 hover:text-emerald-500 transition-colors">
                          <MessageSquare size={18} />
                        </button>
                        <div className="absolute bottom-full mb-2 hidden group-hover:block w-48 p-2 bg-slate-800 text-white text-xs rounded-lg shadow-lg z-10 break-keep">
                          {db.comment}
                        </div>
                      </div>
                    ) : (
                      <span className="text-slate-300">-</span>
                    )}
                  </td>
                </tr>
              )) : (
                <tr><td colSpan={10} className="px-4 py-12 text-center text-slate-500">접수된 디비가 없습니다.</td></tr>
              )}
            </tbody>
          </table>
        </div>
        
        {/* Pagination */}
        <div className="p-4 border-t border-slate-100 flex items-center justify-center gap-1">
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50" disabled>&lt;</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-500 text-white font-bold shadow-sm">1</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">2</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">3</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm">...</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">12</button>
          <button className="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50">&gt;</button>
        </div>
      </div>

      {/* Info Box */}
      <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg">
        <div className="flex items-center gap-2 mb-4">
          <Info className="text-cyan-400" size={20} />
          <h3 className="font-bold text-lg">상태 안내</h3>
        </div>
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-slate-300">
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-slate-400 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">접수완료:</strong> 디비가 정상 접수된 상태입니다.</div>
          </div>
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-blue-400 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">검수중:</strong> 광고주가 유효성 검토를 진행 중입니다.</div>
          </div>
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-emerald-400 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">승인완료:</strong> 유효 디비로 인정되었습니다.</div>
          </div>
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-red-400 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">취소/무효:</strong> 중복, 결번 등으로 무효 처리되었습니다.</div>
          </div>
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">확정완료:</strong> 수익 정산이 확정된 상태입니다.</div>
          </div>
          <div className="flex items-start gap-2">
            <span className="inline-block w-2 h-2 rounded-full bg-purple-400 mt-1.5 shrink-0"></span>
            <div><strong className="text-white">정산완료:</strong> 파트너에게 수익 지급이 완료되었습니다.</div>
          </div>
        </div>
      </div>

    </PartnerLayout>
  );
}




