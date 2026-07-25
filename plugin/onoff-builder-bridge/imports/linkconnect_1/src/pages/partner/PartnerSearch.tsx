import React from "react";
import { Search, Info, Link as LinkIcon, Filter, ChevronDown, CheckCircle2, AlertTriangle, XCircle, TrendingUp, Briefcase, PlusCircle, CheckCircle, DollarSign } from 'lucide-react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';

const categories = ['전체', '금융', '법률', '병원', '교육', '생활서비스', '렌탈', '기타'];

const cpaItems = [
  {
    id: 1,
    title: '개인회생 상담 DB',
    category: '법률',
    description: '개인회생/파산 무료 상담 신청 캠페인입니다.',
    price: '30,000',
    approvalRate: '68%',
    avgTime: '1.8일',
    allowedChannels: '블로그, 카페, 검색광고, SNS',
    forbiddenChannels: '허위광고, 브랜드 사칭, 스팸문자',
    status: '진행중',
    badge: '추천',
  },
  {
    id: 2,
    title: '자동차 장기렌트 특가',
    category: '렌탈',
    description: '신차 장기렌트카 맞춤 견적 상담',
    price: '25,000',
    approvalRate: '72%',
    avgTime: '1.2일',
    allowedChannels: '블로그, SNS, 커뮤니티',
    forbiddenChannels: '보상형 리워드, 강제 가입',
    status: '진행중',
    badge: '인기',
  },
  {
    id: 3,
    title: '어린이 영어캠프 조기등록',
    category: '교육',
    description: '프리미엄 원어민 영어캠프 상담 신청',
    price: '35,000',
    approvalRate: '55%',
    avgTime: '2.5일',
    allowedChannels: '맘카페, 교육 블로그, 인스타그램',
    forbiddenChannels: '과장광고, 관련없는 타겟팅',
    status: '진행중',
    badge: '신규',
  },
  {
    id: 4,
    title: '프리미엄 임플란트 치과',
    category: '병원',
    description: '임플란트 및 치아교정 할인가 상담',
    price: '40,000',
    approvalRate: '45%',
    avgTime: '3.0일',
    allowedChannels: '블로그, 건강카페',
    forbiddenChannels: '의료법 위반 문구, 허위 후기',
    status: '진행중',
  },
  {
    id: 5,
    title: '소상공인 대출 지원센터',
    category: '금융',
    description: '개인사업자 및 소상공인 맞춤 대출 한도조회',
    price: '32,000',
    approvalRate: '60%',
    avgTime: '1.5일',
    allowedChannels: '자영업 커뮤니티, 블로그',
    forbiddenChannels: '불법 스팸, 사칭',
    status: '마감임박',
  },
];

export function PartnerSearch() {
  const [activeCategory, setActiveCategory] = useState('전체');

  return (
    <PartnerLayout activeMenu="search" title="광고상품 찾기">
      <p className="text-slate-500 mb-8 mt(-2)">
        홍보 가능한 CPA 광고상품을 확인하고, 내 채널에 맞는 캠페인을 선택하세요.
      </p>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 CPA 상품" value="128" suffix="개" icon={<Briefcase className="text-slate-500" />} />
        <SummaryCard title="신규 캠페인" value="14" suffix="개" icon={<PlusCircle className="text-blue-500" />} />
        <SummaryCard title="승인율 70% 이상" value="37" suffix="개" icon={<CheckCircle className="text-emerald-500" />} />
        <SummaryCard title="평균 파트너 단가" value="28,000" suffix="원" highlight icon={<DollarSign className="text-cyan-500" />} />
      </div>

      {/* Categories */}
      <div className="flex flex-wrap gap-2 mb-6">
        {categories.map((cat) => (
          <button
            key={cat}
            onClick={() => setActiveCategory(cat)}
            className={`px-5 py-2.5 rounded-full text-sm font-medium transition-colors ${
              activeCategory === cat
                ? 'bg-slate-900 text-white shadow-md'
                : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300 hover:bg-slate-50'
            }`}
          >
            {cat}
          </button>
        ))}
      </div>

      {/* Search & Sort Filters */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 flex flex-col md:flex-row gap-4 justify-between items-center mb-10 shadow-sm">
        <div className="relative w-full md:w-80">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input 
            type="text" 
            placeholder="상품명 검색..." 
            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
          />
        </div>
        <div className="flex flex-wrap gap-2 w-full md:w-auto">
          {['단가 높은순', '승인율 높은순', '신규 캠페인', '인기 캠페인'].map((filter) => (
            <button key={filter} className="px-3 sm:px-4 py-2 bg-white border border-slate-200 rounded-lg text-xs sm:text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2">
              {filter}
            </button>
          ))}
          <button className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2 shadow-sm">
            채널 필터 <Filter className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Recommended Section (First 3) */}
      <div className="mb-12">
        <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
          <TrendingUp className="w-5 h-5 text-emerald-500" />
          추천 CPA 캠페인
        </h2>
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {cpaItems.slice(0, 3).map(item => (
            <CampaignCard key={`rec-${item.id}`} item={item} />
          ))}
        </div>
      </div>

      <div className="h-px bg-slate-200 w-full mb-10"></div>

      {/* Main List */}
      <div className="mb-6 flex items-center justify-between">
         <h2 className="text-lg font-bold text-slate-900">전체 캠페인 <span className="text-emerald-600 font-bold">({cpaItems.length})</span></h2>
      </div>
      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
        {cpaItems.map(item => (
          <CampaignCard key={item.id} item={item} />
        ))}
      </div>

      {/* Notice Section */}
      <div className="bg-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <div className="flex items-start gap-4">
          <Info className="w-6 h-6 text-cyan-400 shrink-0 mt-0.5" />
          <div>
            <h3 className="text-lg font-bold mb-4">광고상품 홍보 전 꼭 확인하세요.</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                <span>허용 채널과 금지 채널을 반드시 확인해주세요. 채널 위반 시 정산이 거절될 수 있습니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <AlertTriangle className="w-4 h-4 text-yellow-400 shrink-0 mt-0.5" />
                <span>허위 또는 과장 홍보로 발생한 디비는 전량 취소될 수 있으며 파트너 자격이 정지될 수 있습니다.</span>
              </li>
              <li className="flex items-start gap-3 text-sm text-slate-300">
                <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                <span>광고주가 정상적인 상담/가입으로 승인 완료한 디비만 확정수익으로 반영됩니다.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </PartnerLayout>
  );
}



function CampaignCard({ item }: any) {
  return (
    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg hover:border-emerald-300 transition-all flex flex-col">
      <div className="p-6 flex-1">
        <div className="flex justify-between items-start mb-4">
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md border border-slate-200">
              {item.category}
            </span>
            <span className="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md border border-slate-200">
              CPA
            </span>
          </div>
          <div className="flex gap-2">
            {item.badge && (
              <span className={`px-2.5 py-1 text-xs font-bold rounded-md ${
                item.badge === '추천' ? 'bg-emerald-100 text-emerald-800' :
                item.badge === '인기' ? 'bg-blue-100 text-blue-800' :
                'bg-purple-100 text-purple-800'
              }`}>
                {item.badge}
              </span>
            )}
            <span className={`px-2.5 py-1 text-xs font-bold rounded-md border ${
              item.status === '진행중' ? 'bg-white border-emerald-200 text-emerald-600' : 'bg-white border-red-200 text-red-500'
            }`}>
              {item.status}
            </span>
          </div>
        </div>
        
        <h3 className="text-lg font-bold text-slate-900 mb-1">{item.title}</h3>
        <p className="text-sm text-slate-500 mb-5 line-clamp-1">{item.description}</p>

        <div className="grid grid-cols-2 gap-3 mb-5">
          <div className="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100/50">
            <div className="text-xs text-emerald-800 mb-1">파트너 단가</div>
            <div className="text-lg font-bold text-emerald-600">{item.price}<span className="text-sm font-normal ml-0.5">원</span></div>
          </div>
          <div className="bg-slate-50 rounded-xl p-3 border border-slate-100">
            <div className="text-xs text-slate-500 mb-1">승인율 / 평균</div>
            <div className="text-lg font-bold text-slate-700">{item.approvalRate} <span className="text-xs font-normal text-slate-400">({item.avgTime})</span></div>
          </div>
        </div>

        <div className="space-y-2 text-xs">
          <div className="flex items-start">
            <span className="inline-block w-14 text-slate-400 font-medium shrink-0">허용채널</span>
            <span className="text-slate-700 font-medium">{item.allowedChannels}</span>
          </div>
          <div className="flex items-start">
            <span className="inline-block w-14 text-slate-400 font-medium shrink-0">금지채널</span>
            <span className="text-red-500 font-medium">{item.forbiddenChannels}</span>
          </div>
        </div>
      </div>
      
      <div className="p-4 bg-slate-50 border-t border-slate-100 flex gap-2 sm:gap-3">
        <button className="flex-1 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-medium rounded-xl transition-colors text-sm">
          상세보기
        </button>
        <button className="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white font-bold rounded-xl transition-colors text-sm flex justify-center items-center gap-1.5 shadow-sm">
          <LinkIcon className="w-4 h-4" />
          홍보 링크 생성
        </button>
      </div>
    </div>
  );
}
