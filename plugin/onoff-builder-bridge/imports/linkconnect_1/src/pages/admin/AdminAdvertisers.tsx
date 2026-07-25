import React, { useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Building2, Activity, Database, CreditCard, AlertCircle, Clock,
  Search, Filter, Calendar, ChevronDown, Download, X, ExternalLink, ShieldAlert,
  Briefcase, Wallet, Users
} from 'lucide-react';

const advertiserData = [
  { name: '희망법무법인', manager: '김희망', phone: '010-1111-2222', email: 'hope@law.com', bizNo: '123-45-67890', date: '2025.10.12', campaigns: 3, totalDb: 8500, approvedDb: 6200, canceledDb: 1500, rate: '72.9%', balance: 14500000, holdBalance: 1200000, spend: 31000000, totalCharge: 150000000, totalSpend: 135500000, avgTime: '2.4시간', status: '운영중' },
  { name: '(주)성공대부', manager: '박성공', phone: '010-2222-3333', email: 'success@loan.com', bizNo: '234-56-78901', date: '2025.11.05', campaigns: 2, totalDb: 5200, approvedDb: 3800, canceledDb: 800, rate: '73.0%', balance: 2500000, holdBalance: 800000, spend: 15300000, totalCharge: 80000000, totalSpend: 77500000, avgTime: '1.2시간', status: '운영중' },
  { name: '스피드렌터카', manager: '이렌탈', phone: '010-3333-4444', email: 'speed@rent.com', bizNo: '345-67-89012', date: '2026.01.20', campaigns: 4, totalDb: 3500, approvedDb: 2100, canceledDb: 1100, rate: '60.0%', balance: 120000, holdBalance: 50000, spend: 15600000, totalCharge: 40000000, totalSpend: 39880000, avgTime: '12.5시간', status: '광고비부족' },
  { name: '라이프보험법인', manager: '최라이프', phone: '010-4444-5555', email: 'life@insure.com', bizNo: '456-78-90123', date: '2026.03.15', campaigns: 1, totalDb: 1800, approvedDb: 1400, canceledDb: 200, rate: '77.7%', balance: 8500000, holdBalance: 400000, spend: 12400000, totalCharge: 30000000, totalSpend: 21500000, avgTime: '4.8시간', status: '운영중' },
  { name: '에듀스터디', manager: '정학습', phone: '010-5555-6666', email: 'edu@study.com', bizNo: '567-89-01234', date: '2026.05.10', campaigns: 1, totalDb: 900, approvedDb: 700, canceledDb: 100, rate: '77.7%', balance: 4200000, holdBalance: 150000, spend: 8400000, totalCharge: 15000000, totalSpend: 10800000, avgTime: '0.8시간', status: '운영중' },
  { name: '헬스플러스', manager: '강건강', phone: '010-6666-7777', email: 'health@plus.com', bizNo: '678-90-12345', date: '2026.07.01', campaigns: 0, totalDb: 0, approvedDb: 0, canceledDb: 0, rate: '-', balance: 0, holdBalance: 0, spend: 0, totalCharge: 0, totalSpend: 0, avgTime: '-', status: '승인대기' },
  { name: '투어여행사', manager: '윤투어', phone: '010-7777-8888', email: 'tour@travel.com', bizNo: '789-01-23456', date: '2025.12.01', campaigns: 2, totalDb: 4200, approvedDb: 1200, canceledDb: 2500, rate: '28.5%', balance: 1500000, holdBalance: 0, spend: 250000, totalCharge: 20000000, totalSpend: 18500000, avgTime: '48.2시간', status: '일시중지' },
];

export function AdminAdvertisers() {
  const [selectedAdvertiser, setSelectedAdvertiser] = useState<any>(null);

  const handleRowClick = (advertiser: any) => {
    setSelectedAdvertiser(advertiser);
  };

  return (
    <AdminLayout activeMenu="advertisers" title="광고주 관리" description="광고주별 광고상품, 광고비, 디비 처리 현황을 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 광고주" value="186" suffix="곳" icon={<Building2 size={18} />} />
        <SummaryCard title="운영중 광고주" value="142" suffix="곳" color="emerald" highlight icon={<Activity size={18} />} />
        <SummaryCard title="광고비 부족" value="7" suffix="곳" color="red" highlight icon={<AlertCircle size={18} />} />
        <SummaryCard title="이번 달 사용 광고비" value="243,800,000" suffix="원" dark icon={<CreditCard size={18} />} />
        <SummaryCard title="승인대기 DB" value="418" suffix="건" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="취소율 높은 광고주" value="12" suffix="곳" color="orange" highlight icon={<ShieldAlert size={18} />} />
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
                    placeholder="회사명, 담당자명 검색" 
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              <div className="flex items-center gap-2">
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>전체 상태</option>
                  <option>운영중</option>
                  <option>승인대기</option>
                  <option>일시중지</option>
                  <option>차단</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>광고비 상태 (전체)</option>
                  <option>충분</option>
                  <option>부족</option>
                  <option>충전대기</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>사용 광고비 높은순</option>
                  <option>가입일 최신순</option>
                  <option>취소율 높은순</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
              <div className="text-sm font-medium text-slate-600">총 <strong className="text-cyan-600">186</strong>곳의 광고주</div>
              <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                <Download size={14} /> 엑셀 다운로드
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-white border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium">광고주명/담당자</th>
                    <th className="px-4 py-3 font-medium text-center">운영상품</th>
                    <th className="px-4 py-3 font-medium text-right">접수/승인/취소 DB</th>
                    <th className="px-4 py-3 font-medium text-right">승인율</th>
                    <th className="px-4 py-3 font-medium text-right">광고비 잔액</th>
                    <th className="px-4 py-3 font-medium text-right">이번 달 사용액</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {advertiserData.map((advertiser, i) => (
                    <tr 
                      key={i} 
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedAdvertiser?.name === advertiser.name ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => handleRowClick(advertiser)}
                    >
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{advertiser.name}</div>
                        <div className="text-xs text-slate-500 flex items-center gap-1.5">
                          <span>{advertiser.manager}</span>
                          <span className="w-0.5 h-0.5 bg-slate-300 rounded-full"></span>
                          <span>{advertiser.phone}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center font-medium text-slate-600 whitespace-nowrap">{advertiser.campaigns}개</td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-medium text-slate-900">{advertiser.totalDb.toLocaleString()}건</div>
                        <div className="text-xs mt-0.5">
                          <span className="text-emerald-600 font-bold">{advertiser.approvedDb.toLocaleString()}</span>
                          <span className="text-slate-300 mx-1">/</span>
                          <span className="text-red-500">{advertiser.canceledDb.toLocaleString()}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-700 whitespace-nowrap">
                        <span className={parseFloat(advertiser.rate) < 50 && advertiser.rate !== '-' ? 'text-red-600' : 'text-slate-900'}>{advertiser.rate}</span>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className={`font-bold ${advertiser.balance < 500000 ? 'text-red-600' : 'text-cyan-700'}`}>{advertiser.balance.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right font-medium text-slate-600 whitespace-nowrap">
                        {advertiser.spend.toLocaleString()}원
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={advertiser.status} />
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
              관리자 확인 필요
            </h3>
            <div className="space-y-3">
              <div className="bg-white p-3 rounded-xl border border-red-100 text-sm cursor-pointer hover:border-red-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-red-700 transition-colors">스피드렌터카</span>
                  <StatusBadge status="광고비부족" />
                </div>
                <p className="text-red-600 text-xs">광고비 부족으로 캠페인 중지 임박 (잔액 12만원)</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-orange-700 transition-colors">투어여행사</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">최근 7일 취소율 71.5% 이상. 점검 요망.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-yellow-100 text-sm cursor-pointer hover:border-yellow-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-yellow-700 transition-colors">에듀스터디</span>
                  <StatusBadge status="지연" />
                </div>
                <p className="text-yellow-700 text-xs">48시간 이상 미검수 DB 28건 발생.</p>
              </div>
            </div>
          </div>

          {/* Detail Panel */}
          {selectedAdvertiser ? (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <button 
                onClick={() => setSelectedAdvertiser(null)}
                className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors z-10"
              >
                <X size={18} />
              </button>
              
              <div className="p-6 border-b border-slate-200 bg-slate-50 relative overflow-hidden">
                <div className="absolute -right-4 -top-4 w-24 h-24 bg-cyan-100 rounded-full opacity-50 blur-xl"></div>
                <div className="flex items-center gap-2 mb-2 relative z-10">
                  <span className="text-xs font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded">ADV-{Math.floor(Math.random() * 9000) + 1000}</span>
                  <StatusBadge status={selectedAdvertiser.status} />
                </div>
                <h2 className="text-2xl font-bold text-slate-900 relative z-10">{selectedAdvertiser.name}</h2>
                <div className="text-sm text-slate-500 mt-1 relative z-10">가입일: {selectedAdvertiser.date}</div>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Building2 size={16} className="text-slate-500" /> 기본 정보
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">담당자</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.manager}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">연락처</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.phone}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">이메일</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.email}</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">사업자번호</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.bizNo}</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Wallet size={16} className="text-slate-500" /> 광고비 요약
                  </h3>
                  <div className="bg-slate-900 rounded-xl p-4 mb-3 flex justify-between items-center text-white">
                    <span className="text-sm font-medium text-slate-400">현재 잔액</span>
                    <span className={`text-xl font-bold ${selectedAdvertiser.balance < 500000 ? 'text-red-400' : 'text-cyan-400'}`}>{selectedAdvertiser.balance.toLocaleString()}원</span>
                  </div>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">가차감 광고비 (검수대기)</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.holdBalance.toLocaleString()}원</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500 font-bold">사용 가능 잔액</span>
                      <span className="font-bold text-cyan-600">{(selectedAdvertiser.balance - selectedAdvertiser.holdBalance).toLocaleString()}원</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100 mt-2">
                      <span className="text-slate-500">총 충전액</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.totalCharge.toLocaleString()}원</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">총 사용액</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.totalSpend.toLocaleString()}원</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Activity size={16} className="text-slate-500" /> 운영 성과
                  </h3>
                  <div className="bg-slate-50 rounded-xl p-4 grid grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 접수 DB</div>
                      <div className="font-bold text-slate-900">{selectedAdvertiser.totalDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">평균 처리시간</div>
                      <div className="font-bold text-slate-900">{selectedAdvertiser.avgTime}</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 승인 DB</div>
                      <div className="font-bold text-emerald-600">{selectedAdvertiser.approvedDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 취소 DB</div>
                      <div className="font-bold text-red-600">{selectedAdvertiser.canceledDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">승인율</div>
                      <div className={`font-bold ${parseFloat(selectedAdvertiser.rate) < 50 && selectedAdvertiser.rate !== '-' ? 'text-red-600' : 'text-slate-900'}`}>{selectedAdvertiser.rate}</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">취소율</div>
                      <div className="font-bold text-slate-900">
                        {selectedAdvertiser.totalDb > 0 ? ((selectedAdvertiser.canceledDb / selectedAdvertiser.totalDb) * 100).toFixed(1) + '%' : '-'}
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    관리자 메모
                  </h3>
                  <textarea 
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm resize-none h-24 outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                    placeholder="광고주 특이사항을 기록하세요..."
                    defaultValue={selectedAdvertiser.name === '스피드렌터카' ? '광고비 부족 알림 발송 완료 (7/5). 7/8일까지 미충전시 캠페인 자동 중지 예정.' : ''}
                  ></textarea>
                </div>
              </div>
              
              <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                  <Briefcase size={14} /> 광고상품
                </button>
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                  <Wallet size={14} /> 광고비
                </button>
                <button className="py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                  <Database size={14} /> 디비보기
                </button>
                <button className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors shadow-sm">
                  상태 변경
                </button>
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm flex-1 flex flex-col items-center justify-center text-slate-500 p-8 text-center min-h-[400px]">
              <Building2 size={48} className="text-slate-300 mb-4" />
              <p className="font-medium text-slate-900 mb-1">선택된 광고주가 없습니다.</p>
              <p className="text-sm">좌측 목록에서 광고주를 선택하면<br />상세 정보가 표시됩니다.</p>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
