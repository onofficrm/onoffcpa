import { Bell, Calendar, Gift, Trophy } from "lucide-react";

const EVENTS = [
  {
    icon: <Gift className="h-5 w-5 text-rose-500" />,
    badge: "이벤트",
    badgeColor: "bg-rose-100 text-rose-700",
    title: "신규 파트너 가입 환영! 첫 DB 발생 시 보너스 5만원 지급",
    date: "2026.06.01 ~ 2026.06.30",
    isNew: true,
  },
  {
    icon: <Trophy className="h-5 w-5 text-amber-500" />,
    badge: "프로모션",
    badgeColor: "bg-amber-100 text-amber-700",
    title: "여름맞이 다이어트/헬스 캠페인 수수료 30% 추가 인상 프로모션",
    date: "2026.06.15 ~ 2026.07.15",
    isNew: true,
  },
  {
    icon: <Bell className="h-5 w-5 text-blue-500" />,
    badge: "공지",
    badgeColor: "bg-blue-100 text-blue-700",
    title: "광고주 광고비 첫 충전 시 10% 추가 포인트 적립 안내",
    date: "상시 진행",
    isNew: false,
  },
  {
    icon: <Calendar className="h-5 w-5 text-teal-500" />,
    badge: "이벤트",
    badgeColor: "bg-teal-100 text-teal-700",
    title: "5월 월간 우수 파트너 리워드 당첨자 발표 및 상금 지급 안내",
    date: "2026.06.05",
    isNew: false,
  },
];

export function EventsBoard() {
  return (
    <section className="py-24 bg-white border-b border-slate-200">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
          <div>
            <h2 className="text-3xl font-bold text-slate-900 mb-2 tracking-tight">이벤트/프로모션</h2>
            <p className="text-slate-500">수익을 극대화할 수 있는 다양한 혜택을 놓치지 마세요.</p>
          </div>
          <button className="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            게시판 더보기 +
          </button>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
          <ul className="divide-y divide-slate-100">
            {EVENTS.map((item, idx) => (
              <li key={idx} className="hover:bg-slate-50 transition-colors group cursor-pointer">
                <div className="flex items-center p-4 sm:p-6 gap-4 sm:gap-6">
                  <div className="hidden sm:flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 border border-slate-100 group-hover:bg-white group-hover:border-slate-200 transition-colors">
                    {item.icon}
                  </div>
                  
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1.5">
                      <span className={`inline-flex px-2 py-0.5 rounded text-[11px] font-bold ${item.badgeColor}`}>
                        {item.badge}
                      </span>
                      {item.isNew && (
                        <span className="inline-flex px-1.5 py-0.5 rounded text-[10px] font-black bg-rose-500 text-white leading-none">
                          N
                        </span>
                      )}
                    </div>
                    <p className="text-base font-semibold text-slate-900 truncate group-hover:text-blue-700 transition-colors">
                      {item.title}
                    </p>
                  </div>

                  <div className="text-right whitespace-nowrap">
                    <span className="text-sm text-slate-400 font-medium">{item.date}</span>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </section>
  );
}
