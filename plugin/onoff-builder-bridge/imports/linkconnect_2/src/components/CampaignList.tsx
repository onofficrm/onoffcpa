import { useState } from "react";
import { ArrowRight, Laptop, Smartphone, Flame, Sparkles } from "lucide-react";

const CPA_CAMPAIGNS = [
  {
    id: "cpa-1",
    category: "법률",
    isHot: true,
    isNew: false,
    devices: ["PC", "MOBILE"],
    title: "A법무법인 개인회생/파산 무료상담",
    desc: "신용회복, 개인회생, 파산면책 무료 법률상담 캠페인입니다.",
    condition: "유효 DB 승인 시",
    reward: "45,000",
    imageBg: "bg-blue-900",
    imageInitial: "법률",
    imageUrl: "https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cpa-2",
    category: "병원",
    isHot: false,
    isNew: true,
    devices: ["MOBILE"],
    title: "강남 B성형외과 모발이식 상담",
    desc: "탈모 고민 해결! 모발이식 전문 병원 방문 상담 캠페인",
    condition: "방문 상담 완료 시",
    reward: "80,000",
    imageBg: "bg-teal-700",
    imageInitial: "병원",
    imageUrl: "https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cpa-3",
    category: "교육",
    isHot: true,
    isNew: true,
    devices: ["PC", "MOBILE"],
    title: "C원격평생교육원 보육교사 취득과정",
    desc: "단기간 100% 온라인 취득 가능한 보육교사 2급 수강생 모집",
    condition: "전화 상담 완료 시",
    reward: "25,000",
    imageBg: "bg-indigo-700",
    imageInitial: "교육",
    imageUrl: "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cpa-4",
    category: "다이어트",
    isHot: false,
    isNew: false,
    devices: ["PC", "MOBILE"],
    title: "D다이어트 한의원 체질맞춤 감량",
    desc: "요요 없는 한방 다이어트 1개월 프로그램 무료 상담",
    condition: "상담 완료 시",
    reward: "35,000",
    imageBg: "bg-rose-700",
    imageInitial: "뷰티",
    imageUrl: "https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cpa-5",
    category: "재무설계",
    isHot: true,
    isNew: false,
    devices: ["MOBILE"],
    title: "E자산관리 1:1 맞춤 재무설계",
    desc: "직장인, 신혼부부, 사회초년생을 위한 무료 포트폴리오 제공",
    condition: "대면 상담 완료 시",
    reward: "70,000",
    imageBg: "bg-slate-800",
    imageInitial: "금융",
    imageUrl: "https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cpa-6",
    category: "렌탈",
    isHot: false,
    isNew: true,
    devices: ["PC", "MOBILE"],
    title: "F정수기/공기청정기 결합 렌탈 특가",
    desc: "인기 생활가전 렌탈 및 사은품 증정 프로모션",
    condition: "설치 완료 시",
    reward: "120,000",
    imageBg: "bg-sky-700",
    imageInitial: "렌탈",
    imageUrl: "https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&q=80&w=800",
  }
];

const CPS_CAMPAIGNS = [
  {
    id: "cps-1",
    category: "쇼핑몰",
    isHot: true,
    isNew: false,
    devices: ["PC", "MOBILE"],
    title: "G패션 종합몰 신규가입 및 첫구매",
    desc: "트렌디한 2030 여성 의류 쇼핑몰 전 상품 대상",
    condition: "결제 완료 시",
    reward: "15%",
    imageBg: "bg-fuchsia-700",
    imageInitial: "쇼핑",
    imageUrl: "https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cps-2",
    category: "건강식품",
    isHot: false,
    isNew: true,
    devices: ["MOBILE"],
    title: "H건강 프리미엄 홍삼세트 특가전",
    desc: "명절 선물세트 기획전 최대 50% 할인 상품 판매",
    condition: "배송 완료 시",
    reward: "20%",
    imageBg: "bg-amber-600",
    imageInitial: "식품",
    imageUrl: "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&q=80&w=800",
  },
  {
    id: "cps-3",
    category: "생활서비스",
    isHot: true,
    isNew: false,
    devices: ["PC", "MOBILE"],
    title: "I홈케어 에어컨/세탁기 청소 서비스",
    desc: "여름철 필수! 전문가 방문 에어컨 분해 청소 예약",
    condition: "서비스 완료 시",
    reward: "30,000원",
    imageBg: "bg-emerald-600",
    imageInitial: "생활",
    imageUrl: "https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=800",
  },
];

export function CampaignList() {
  const [activeTab, setActiveTab] = useState<"CPA" | "CPS">("CPA");
  const campaigns = activeTab === "CPA" ? CPA_CAMPAIGNS : CPS_CAMPAIGNS;

  return (
    <section className="py-24 bg-white border-y border-slate-200">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
          <div>
            <h2 className="text-3xl font-bold text-slate-900 mb-2 tracking-tight">
              실시간 인기 캠페인 리스트
            </h2>
            <p className="text-slate-500">
              링크커넥트의 검증된 고단가 광고주 리스트입니다.
            </p>
          </div>

          {/* Tabs */}
          <div className="inline-flex bg-slate-100 p-1 rounded-lg">
            <button
              onClick={() => setActiveTab("CPA")}
              className={`px-6 py-2 text-sm font-bold rounded-md transition-all ${
                activeTab === "CPA"
                  ? "bg-white text-blue-700 shadow-sm border border-slate-200/50"
                  : "text-slate-500 hover:text-slate-700"
              }`}
            >
              CPA (DB/상담)
            </button>
            <button
              onClick={() => setActiveTab("CPS")}
              className={`px-6 py-2 text-sm font-bold rounded-md transition-all ${
                activeTab === "CPS"
                  ? "bg-white text-blue-700 shadow-sm border border-slate-200/50"
                  : "text-slate-500 hover:text-slate-700"
              }`}
            >
              CPS (판매/결제)
            </button>
          </div>
        </div>

        {/* Grid List */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {campaigns.map((campaign) => (
            <div
              key={campaign.id}
              className="group bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-xl hover:border-blue-300 transition-all duration-300 flex flex-col h-full cursor-pointer"
            >
              {/* Thumbnail / Image Area */}
              <div className={`relative h-40 ${campaign.imageBg} flex items-center justify-center overflow-hidden`}>
                {campaign.imageUrl && (
                  <img src={campaign.imageUrl} alt={campaign.title} referrerPolicy="no-referrer" className="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" />
                )}
                <div className="absolute inset-0 bg-black/30 group-hover:bg-black/10 transition-colors duration-500 z-0"></div>
                {!campaign.imageUrl && (
                  <span className="text-3xl font-black text-white/80 tracking-widest relative z-10">{campaign.imageInitial}</span>
                )}
                
                {/* Badges */}
                <div className="absolute top-3 left-3 flex flex-col gap-1.5 z-10">
                  {campaign.isHot && (
                    <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-rose-500 text-white text-[10px] font-bold tracking-wider shadow-sm">
                      <Flame className="h-3 w-3" /> HOT
                    </span>
                  )}
                  {campaign.isNew && (
                    <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-blue-500 text-white text-[10px] font-bold tracking-wider shadow-sm">
                      <Sparkles className="h-3 w-3" /> NEW
                    </span>
                  )}
                </div>

                {/* Device Icons */}
                <div className="absolute bottom-3 right-3 flex gap-1.5">
                  {campaign.devices.includes("PC") && (
                    <div className="h-7 w-7 rounded bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/20">
                      <Laptop className="h-4 w-4" />
                    </div>
                  )}
                  {campaign.devices.includes("MOBILE") && (
                    <div className="h-7 w-7 rounded bg-white/20 backdrop-blur-sm flex items-center justify-center text-white border border-white/20">
                      <Smartphone className="h-4 w-4" />
                    </div>
                  )}
                </div>
              </div>

              {/* Content Area */}
              <div className="p-5 flex flex-col flex-1">
                <div className="mb-3">
                  <span className="inline-block px-2 py-1 rounded bg-slate-100 text-slate-600 text-[11px] font-semibold mb-2">
                    {campaign.category}
                  </span>
                  <h3 className="text-base font-bold text-slate-900 leading-snug group-hover:text-blue-700 transition-colors line-clamp-1">
                    {campaign.title}
                  </h3>
                  <p className="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                    {campaign.desc}
                  </p>
                </div>
                
                <div className="mt-auto pt-4 border-t border-slate-100 border-dashed">
                  <div className="flex justify-between items-center mb-1">
                    <span className="text-[11px] font-medium text-slate-500 bg-slate-50 px-2 py-0.5 rounded">
                      {campaign.condition}
                    </span>
                    <span className="text-[11px] font-bold text-slate-400">수익금</span>
                  </div>
                  <div className="text-right">
                    <span className="text-xl font-black text-blue-600 group-hover:text-blue-700 tracking-tight">
                      {activeTab === 'CPA' ? `₩ ${campaign.reward}` : campaign.reward}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
        
        <div className="mt-12 text-center">
          <button className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
            전체 캠페인 더보기 <ArrowRight className="h-4 w-4" />
          </button>
        </div>
      </div>
    </section>
  );
}
