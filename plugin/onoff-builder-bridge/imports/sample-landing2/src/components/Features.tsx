import { Activity, Database, ShoppingBag } from "lucide-react";

export function Features() {
  const features = [
    {
      title: "CPA 캠페인",
      description: "DB 수집, 상담신청, 견적문의, 회원가입 등 성과 기반 광고 운영으로 확실한 잠재고객을 확보하세요.",
      icon: <Database className="h-6 w-6 text-blue-600" />,
      bg: "bg-blue-50",
      border: "border-blue-100",
    },
    {
      title: "CPS 캠페인",
      description: "구매, 결제, 주문 발생 기준으로 수익을 정산하는 매출형 제휴마케팅으로 실질적인 매출 증대를 이끌어냅니다.",
      icon: <ShoppingBag className="h-6 w-6 text-teal-600" />,
      bg: "bg-teal-50",
      border: "border-teal-100",
    },
    {
      title: "실시간 성과 분석",
      description: "클릭, 전환, 승인, 정산 내역을 대시보드에서 한눈에 확인하고 투명하게 데이터를 관리하세요.",
      icon: <Activity className="h-6 w-6 text-indigo-600" />,
      bg: "bg-indigo-50",
      border: "border-indigo-100",
    },
  ];

  return (
    <section className="py-24 bg-white">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl font-bold text-slate-900 mb-4 tracking-tight">비즈니스 성장을 위한 <br className="sm:hidden" />핵심 서비스</h2>
          <p className="text-lg text-slate-500">
            광고주와 파트너 모두가 윈윈(Win-Win)할 수 있는 최적의 시스템을 제공합니다.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {features.map((feature, index) => (
            <div 
              key={index}
              className={`rounded-2xl border ${feature.border} bg-white p-8 shadow-sm transition-all hover:shadow-md hover:-translate-y-1`}
            >
              <div className={`mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl ${feature.bg}`}>
                {feature.icon}
              </div>
              <h3 className="mb-3 text-xl font-bold text-slate-900">{feature.title}</h3>
              <p className="text-slate-600 leading-relaxed">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
