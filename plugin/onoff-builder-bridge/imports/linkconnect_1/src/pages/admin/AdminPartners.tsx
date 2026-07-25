import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Users, Activity, Database, CreditCard, Receipt, 
  Search, Filter, Calendar, ChevronDown, Download, AlertCircle, X, ExternalLink, ShieldAlert
} from 'lucide-react';

const partnerData = [
  { code: 'PT-8832', name: '김마케터', id: 'marketer_kim', date: '2026.01.15', links: 12, totalDb: 1450, approvedDb: 1050, canceledDb: 250, rate: '72.4%', expectedProfit: 4500000, confirmedProfit: 38500000, settlement: 1200000, status: '활성' },
  { code: 'PT-1029', name: '박제휴', id: 'aff_park', date: '2026.02.20', links: 8, totalDb: 890, approvedDb: 620, canceledDb: 150, rate: '69.6%', expectedProfit: 2100000, confirmedProfit: 18400000, settlement: 0, status: '활성' },
  { code: 'PT-5591', name: '이수익', id: 'profit_lee', date: '2026.04.10', links: 5, totalDb: 420, approvedDb: 290, canceledDb: 80, rate: '69.0%', expectedProfit: 1250000, confirmedProfit: 8500000, settlement: 500000, status: '검수필요' },
  { code: 'PT-2248', name: '최블로그', id: 'blog_choi', date: '2026.05.05', links: 3, totalDb: 150, approvedDb: 110, canceledDb: 20, rate: '73.3%', expectedProfit: 450000, confirmedProfit: 3200000, settlement: 450000, status: '활성' },
  { code: 'PT-7731', name: '정카페', id: 'cafe_jung', date: '2026.06.12', links: 2, totalDb: 45, approvedDb: 25, canceledDb: 15, rate: '55.5%', expectedProfit: 120000, confirmedProfit: 450000, settlement: 0, status: '승인대기' },
  { code: 'PT-9912', name: '어뷰징의심', id: 'abuser_01', date: '2026.06.28', links: 4, totalDb: 120, approvedDb: 10, canceledDb: 105, rate: '8.3%', expectedProfit: 50000, confirmedProfit: 150000, settlement: 0, status: '차단' },
  { code: 'PT-3345', name: '송휴면', id: 'sleep_song', date: '2025.11.20', links: 1, totalDb: 12, approvedDb: 8, canceledDb: 2, rate: '66.6%', expectedProfit: 0, confirmedProfit: 240000, settlement: 0, status: '휴면' },
];

export function AdminPartners() {
  const [selectedPartner, setSelectedPartner] = useState<any>(null);

  const handleRowClick = (partner: any) => {
    setSelectedPartner(partner);
  };

  return (
    <AdminLayout activeMenu="partners" title="파트너 관리" description="파트너별 유입 성과, 수익, 정산 상태를 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 파트너" value="1,284" suffix="명" icon={<Users size={18} />} />
        <SummaryCard title="활성 파트너" value="842" suffix="명" color="emerald" highlight icon={<Activity size={18} />} />
        <SummaryCard title="오늘 활동 파트너" value="126" suffix="명" color="cyan" highlight icon={<Calendar size={18} />} />
        <SummaryCard title="이번 달 접수 DB" value="6,430" suffix="건" icon={<Database size={18} />} />
        <SummaryCard title="이번 달 파트너 수익" value="128,400,000" suffix="원" dark icon={<CreditCard size={18} />} />
        <SummaryCard title="정산 대기 파트너" value="37" suffix="명" color="yellow" highlight icon={<Receipt size={18} />} />
      </div>

      <div className="grid lg:grid-cols-4 gap-6 mb-8">
        <div className="lg:col-span-3 flex flex-col">
          {/* Filter Area */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="파트너 코드, 이름, 아이디 검색" 
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              <div className="flex items-center gap-2">
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 상태</option>
                  <option>활성</option>
                  <option>승인대기</option>
                  <option>차단</option>
                  <option>휴면</option>
                </select>
                <div className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>전체 기간</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>가입일 최신순</option>
                  <option>승인율 높은순</option>
                  <option>수익 높은순</option>
                  <option>취소율 높은순</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
              <div className="text-sm font-medium text-slate-600">총 <strong className="text-cyan-600">1,284</strong>명의 파트너</div>
              <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                <Download size={14} /> 엑셀 다운로드
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-white border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">파트너 정보</th>
                    <th className="px-4 py-3 font-medium text-center">링크</th>
                    <th className="px-4 py-3 font-medium text-right">접수/승인/취소 DB</th>
                    <th className="px-4 py-3 font-medium text-right">승인율</th>
                    <th className="px-4 py-3 font-medium text-right">예상/확정 수익</th>
                    <th className="px-4 py-3 font-medium text-right">정산대기</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {partnerData.map((partner, i) => (
                    <tr 
                      key={i} 
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedPartner?.code === partner.code ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => handleRowClick(partner)}
                    >
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="flex items-center gap-2 mb-0.5">
                          <span className="font-bold text-slate-900">{partner.name}</span>
                          <span className="text-xs text-slate-500">({partner.id})</span>
                        </div>
                        <div className="text-xs text-slate-400 flex items-center gap-1.5">
                          <span>{partner.code}</span>
                          <span className="w-0.5 h-0.5 bg-slate-300 rounded-full"></span>
                          <span>{partner.date} 가입</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center font-medium text-slate-600 whitespace-nowrap">{partner.links}</td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-medium text-slate-900">{partner.totalDb.toLocaleString()}건</div>
                        <div className="text-xs mt-0.5">
                          <span className="text-emerald-600 font-bold">{partner.approvedDb.toLocaleString()}</span>
                          <span className="text-slate-300 mx-1">/</span>
                          <span className="text-red-500">{partner.canceledDb.toLocaleString()}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-700 whitespace-nowrap">
                        <span className={parseFloat(partner.rate) < 50 ? 'text-red-600' : 'text-slate-900'}>{partner.rate}</span>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="text-xs text-slate-500 mb-0.5">{partner.expectedProfit.toLocaleString()}원</div>
                        <div className="font-bold text-cyan-700">{partner.confirmedProfit.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                        {partner.settlement > 0 ? (
                          <span className="text-yellow-600">{partner.settlement.toLocaleString()}원</span>
                        ) : (
                          <span className="text-slate-400">-</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={partner.status === '활성' ? '정상' : partner.status === '차단' ? '정지' : partner.status === '검수필요' ? '경고' : partner.status} />
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <button className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">상세</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar / Detail Panel */}
        <div className="lg:col-span-1 flex flex-col gap-6">
          {/* Alert Card */}
          <div className="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm">
            <h3 className="text-red-900 font-bold mb-4 flex items-center gap-2">
              <ShieldAlert size={18} />
              주의 파트너 알림
            </h3>
            <div className="space-y-3">
              <div className="bg-white p-3 rounded-xl border border-red-100 text-sm cursor-pointer hover:border-red-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>어뷰징의심 (PT-9912)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-red-600 text-xs">최근 7일 취소율 88% 초과. 부정 트래픽 의심.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>단기급증 (PT-1052)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">특정 IP 대역에서 짧은 시간 다량 접수 발생.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>이수익 (PT-5591)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">금지 채널(당근마켓) 유입 의심 내역 확인됨.</p>
              </div>
            </div>
          </div>

          {/* Detail Panel */}
          {selectedPartner ? (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <button 
                onClick={() => setSelectedPartner(null)}
                className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors z-10"
              >
                <X size={18} />
              </button>
              
              <div className="p-6 border-b border-slate-200 bg-slate-50 relative overflow-hidden">
                <div className="absolute -right-4 -top-4 w-24 h-24 bg-cyan-100 rounded-full opacity-50 blur-xl"></div>
                <div className="flex items-center gap-2 mb-2 relative z-10">
                  <span className="text-xs font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded">{selectedPartner.code}</span>
                  <StatusBadge status={selectedPartner.status === '활성' ? '정상' : selectedPartner.status === '차단' ? '정지' : selectedPartner.status === '검수필요' ? '경고' : selectedPartner.status} />
                </div>
                <h2 className="text-2xl font-bold text-slate-900 relative z-10">{selectedPartner.name} <span className="text-lg font-normal text-slate-500 ml-1">({selectedPartner.id})</span></h2>
                <div className="text-sm text-slate-500 mt-1 relative z-10">가입일: {selectedPartner.date}</div>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    기본 정보
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">연락처</span>
                      <span className="font-medium text-slate-900">010-12**-56**</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">이메일</span>
                      <span className="font-medium text-slate-900">user****@gmail.com</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">주요 채널</span>
                      <span className="font-medium text-slate-900">네이버 블로그, 인스타그램</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">정산 계좌</span>
                      <span className="font-medium text-slate-900">국민 123456-**-***</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    누적 성과 요약
                  </h3>
                  <div className="bg-slate-50 rounded-xl p-4 grid grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 접수 DB</div>
                      <div className="font-bold text-slate-900">{selectedPartner.totalDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">승인율</div>
                      <div className={`font-bold ${parseFloat(selectedPartner.rate) < 50 ? 'text-red-600' : 'text-slate-900'}`}>{selectedPartner.rate}</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 승인 DB</div>
                      <div className="font-bold text-emerald-600">{selectedPartner.approvedDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 취소 DB</div>
                      <div className="font-bold text-red-600">{selectedPartner.canceledDb.toLocaleString()}건</div>
                    </div>
                  </div>
                  <div className="bg-slate-900 rounded-xl p-4 mt-3 flex justify-between items-center text-white">
                    <span className="text-sm font-medium text-slate-400">누적 확정수익</span>
                    <span className="text-lg font-bold text-cyan-400">{selectedPartner.confirmedProfit.toLocaleString()}원</span>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    관리자 메모
                  </h3>
                  <textarea 
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm resize-none h-24 outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                    placeholder="파트너 특이사항을 기록하세요..."
                    defaultValue={selectedPartner.code === 'PT-9912' ? '어뷰징 의심. 6/28 유입건 전체 검수 진행중. 계정 정지 처리.' : ''}
                  ></textarea>
                </div>
              </div>
              
              <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                  <ExternalLink size={14} /> 수익/정산
                </button>
                <button className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm">
                  상태 변경
                </button>
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm flex-1 flex flex-col items-center justify-center text-slate-500 p-8 text-center min-h-[400px]">
              <Users size={48} className="text-slate-300 mb-4" />
              <p className="font-medium text-slate-900 mb-1">선택된 파트너가 없습니다.</p>
              <p className="text-sm">좌측 목록에서 파트너를 선택하면<br />상세 정보가 표시됩니다.</p>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
