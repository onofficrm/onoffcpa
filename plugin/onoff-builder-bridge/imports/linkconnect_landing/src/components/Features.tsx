import { Activity, BadgeDollarSign, ShieldCheck, Zap } from 'lucide-react';

const features = [
  {
    icon: <Zap className="w-8 h-8 text-amber-500" />,
    title: '다양한 CPA/CPS 상품',
    desc: '모든 트래픽에 맞는 최적의 캠페인 매칭'
  },
  {
    icon: <Activity className="w-8 h-8 text-cyan-500" />,
    title: '실시간 전환 추적',
    desc: '누락 없는 강력한 자체 트래킹 시스템'
  },
  {
    icon: <ShieldCheck className="w-8 h-8 text-emerald-500" />,
    title: '투명한 승인/반려 확인',
    desc: '명확한 사유 제공으로 신뢰할 수 있는 데이터'
  },
  {
    icon: <BadgeDollarSign className="w-8 h-8 text-indigo-500" />,
    title: '빠르고 정확한 수익 정산',
    desc: '주급/월급 정산 등 파트너 맞춤형 시스템'
  }
];

export function Features() {
  return (
    <section className="py-24 bg-white border-b border-slate-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 className="text-3xl font-bold text-slate-900 mb-16">
          왜 <span className="text-emerald-500">링크커넥트</span>인가?
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {features.map((feature, i) => (
            <div key={i} className="bg-slate-50 border border-slate-100 p-8 rounded-2xl hover:border-slate-200 hover:shadow-lg transition-all text-center">
              <div className="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center mx-auto mb-6 border border-slate-100">
                {feature.icon}
              </div>
              <h3 className="text-lg font-bold text-slate-900 mb-3">{feature.title}</h3>
              <p className="text-slate-600 text-sm leading-relaxed">{feature.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
