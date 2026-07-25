import { Search, Copy, ExternalLink, Link as LinkIcon, Plus, MousePointerClick, Target, CheckCircle2, DollarSign, Info, X } from 'lucide-react';
import { SummaryCard, StatusBadge } from '../../components/partner/PartnerShared';
import { useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';

const mockLinks = [
  {
    id: 'l_001',
    campaign: '개인회생 상담 DB',
    channel: '네이버 블로그',
    subId: 'blog_01',
    url: 'https://linkconnect.co.kr/r/abc123',
    clicks: 1248,
    received: 32,
    approved: 21,
    canceled: 4,
    estRevenue: 960000,
    confRevenue: 630000,
    status: '운영중',
  },
  {
    id: 'l_002',
    campaign: '자동차 장기렌트 특가',
    channel: '인스타그램',
    subId: 'insta_bio',
    url: 'https://linkconnect.co.kr/r/xyz789',
    clicks: 850,
    received: 12,
    approved: 8,
    canceled: 2,
    estRevenue: 300000,
    confRevenue: 200000,
    status: '운영중',
  },
  {
    id: 'l_003',
    campaign: '어린이 영어캠프 조기등록',
    channel: '맘카페',
    subId: 'cafe_event',
    url: 'https://linkconnect.co.kr/r/qwe456',
    clicks: 320,
    received: 5,
    approved: 2,
    canceled: 0,
    estRevenue: 175000,
    confRevenue: 70000,
    status: '중지',
  },
  {
    id: 'l_004',
    campaign: '소상공인 대출 지원센터',
    channel: '유튜브',
    subId: 'yt_desc',
    url: 'https://linkconnect.co.kr/r/rty234',
    clicks: 2100,
    received: 45,
    approved: 30,
    canceled: 8,
    estRevenue: 1440000,
    confRevenue: 960000,
    status: '만료',
  }
];

export function PartnerLinks() {
  const [isModalOpen, setIsModalOpen] = useState(false);

  return (
    <PartnerLayout activeMenu="links" title="내 홍보 링크">
      <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 mt(-2)">
        <p className="text-slate-500">
          생성한 홍보 링크를 관리하고, 채널별 성과를 확인하세요.
        </p>
        <button 
          onClick={() => setIsModalOpen(true)}
          className="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-sm"
        >
          <Plus size={18} /> 새 홍보 링크 만들기
        </button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <SummaryCard title="전체 홍보 링크 수" value="12" suffix="개" icon={<LinkIcon className="text-slate-500" />} />
        <SummaryCard title="오늘 클릭 수" value="1,248" suffix="회" icon={<MousePointerClick className="text-blue-500" />} />
        <SummaryCard title="오늘 접수 DB" value="32" suffix="건" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title="승인완료 DB" value="21" suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} />
        <SummaryCard title="확정수익" value="630,000" suffix="원" highlight icon={<DollarSign className="text-emerald-600" />} />
      </div>

      <div className="grid lg:grid-cols-4 gap-8">
        {/* Main List Area */}
        <div className="lg:col-span-3 space-y-6">
          {/* Filters */}
          <div className="bg-white p-4 rounded-2xl border border-slate-200 flex flex-col md:flex-row gap-4 justify-between items-center shadow-sm">
            <div className="flex flex-wrap gap-2 w-full md:w-auto">
              <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-emerald-500">
                <option>전체 상품</option>
                <option>개인회생 상담 DB</option>
                <option>자동차 장기렌트</option>
              </select>
              <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-emerald-500">
                <option>전체 채널</option>
                <option>네이버 블로그</option>
                <option>인스타그램</option>
                <option>맘카페</option>
              </select>
              <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-emerald-500">
                <option>상태 전체</option>
                <option>운영중</option>
                <option>중지</option>
                <option>만료</option>
              </select>
              <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-emerald-500">
                <option>최근 7일</option>
                <option>최근 30일</option>
                <option>이번 달</option>
              </select>
            </div>
            <div className="relative w-full md:w-64">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input 
                type="text" 
                placeholder="sub_id 검색..." 
                className="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
              />
            </div>
          </div>

          {/* Links Table */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">광고상품 / 채널명</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">sub_id</th>
                    <th className="px-4 py-4 font-medium whitespace-nowrap">홍보 링크</th>
                    <th className="px-4 py-4 font-medium text-right whitespace-nowrap">클릭 수</th>
                    <th className="px-4 py-4 font-medium text-right whitespace-nowrap">접수/승인/취소</th>
                    <th className="px-4 py-4 font-medium text-right whitespace-nowrap">예상/확정수익</th>
                    <th className="px-4 py-4 font-medium text-center whitespace-nowrap">상태</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {mockLinks.map((link) => (
                    <tr key={link.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-4">
                        <div className="flex flex-col min-w-[140px]">
                          <span className="font-bold text-slate-900 line-clamp-1">{link.campaign}</span>
                          <span className="text-xs text-slate-500 mt-1">{link.channel}</span>
                        </div>
                      </td>
                      <td className="px-4 py-4 font-medium text-slate-600">{link.subId}</td>
                      <td className="px-4 py-4">
                        <div className="flex items-center gap-2">
                          <div className="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg font-mono text-xs border border-slate-200 max-w-[150px] truncate">
                            {link.url}
                          </div>
                          <button className="p-1.5 text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors" title="링크 복사">
                            <Copy size={16} />
                          </button>
                          <button className="p-1.5 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="새 창으로 열기">
                            <ExternalLink size={16} />
                          </button>
                        </div>
                      </td>
                      <td className="px-4 py-4 text-right font-bold text-slate-700">{link.clicks.toLocaleString()}</td>
                      <td className="px-4 py-4 text-right">
                        <div className="flex items-center justify-end gap-2 text-xs">
                          <span className="font-medium text-slate-600" title="접수 DB">{link.received}</span>
                          <span className="text-slate-300">/</span>
                          <span className="font-bold text-emerald-600" title="승인 DB">{link.approved}</span>
                          <span className="text-slate-300">/</span>
                          <span className="font-medium text-red-500" title="취소 DB">{link.canceled}</span>
                        </div>
                      </td>
                      <td className="px-4 py-4 text-right">
                        <div className="flex flex-col items-end">
                          <span className="text-xs text-slate-400 line-through mb-0.5">{link.estRevenue.toLocaleString()}</span>
                          <span className="font-bold text-slate-900">{link.confRevenue.toLocaleString()}원</span>
                        </div>
                      </td>
                      <td className="px-4 py-4 text-center">
                        <StatusBadge status={link.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar Guide */}
        <div className="lg:col-span-1">
          <div className="bg-slate-900 rounded-2xl p-6 text-white shadow-lg sticky top-6">
            <div className="flex items-center gap-2 mb-4">
              <Info className="text-cyan-400" size={20} />
              <h3 className="font-bold text-lg">채널별 성과 팁</h3>
            </div>
            <div className="space-y-4 text-sm text-slate-300">
              <p>
                <strong className="text-emerald-400 font-semibold">sub_id</strong>를 구분해서 생성하면 채널별 성과를 쉽게 비교할 수 있습니다.
              </p>
              <div className="h-px bg-white/10 my-2"></div>
              <p>
                블로그, SNS, 유튜브 등 트래픽이 발생하는 <strong>채널별로 홍보 링크를 분리해</strong> 운영해보세요. 어떤 채널의 전환율이 좋은지 분석할 수 있습니다.
              </p>
              <div className="bg-white/10 rounded-xl p-4 mt-4">
                <div className="text-xs text-slate-400 mb-2">예시</div>
                <div className="font-mono text-xs text-emerald-300 mb-1">?sub_id=blog_review</div>
                <div className="font-mono text-xs text-emerald-300">?sub_id=insta_bio</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Create Link Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <h3 className="text-lg font-bold text-slate-900">새 홍보 링크 만들기</h3>
              <button onClick={() => setIsModalOpen(false)} className="text-slate-400 hover:text-slate-600 transition-colors">
                <X size={20} />
              </button>
            </div>
            <div className="p-6 space-y-5">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">광고상품 선택</label>
                <select className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                  <option>개인회생 상담 DB</option>
                  <option>어린이 영어캠프 조기등록</option>
                  <option>자동차 장기렌트 특가</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">채널명 입력</label>
                <input type="text" placeholder="예) 네이버 블로그" className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  sub_id <span className="text-slate-400 font-normal">(선택)</span>
                </label>
                <input type="text" placeholder="영문, 숫자 혼용 (예: blog_01)" className="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
              </div>
            </div>
            <div className="px-6 py-5 bg-slate-50 border-t border-slate-100 flex gap-3">
              <button onClick={() => setIsModalOpen(false)} className="flex-1 py-3 bg-white border border-slate-200 text-slate-700 font-medium rounded-xl hover:bg-slate-100 transition-colors">
                취소
              </button>
              <button onClick={() => setIsModalOpen(false)} className="flex-1 py-3 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-400 transition-colors shadow-sm">
                링크 생성하기
              </button>
            </div>
          </div>
        </div>
      )}

    </PartnerLayout>
  );
}





