export function StatsCounter() {
  const stats = [
    { label: "누적 캠페인 수", value: "3,420+", suffix: "개" },
    { label: "누적 전환 데이터", value: "12.5M+", suffix: "건" },
    { label: "활동 파트너 수", value: "8,950+", suffix: "명" },
    { label: "광고주 만족도", value: "98.7", suffix: "%" },
  ];

  return (
    <section className="py-20 bg-blue-900 text-white relative overflow-hidden">
      {/* Abstract Background pattern */}
      <div className="absolute inset-0 opacity-10">
        <svg className="h-full w-full" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="grid-pattern" width="40" height="40" patternUnits="userSpaceOnUse">
              <path d="M0 40V0H40" fill="none" stroke="currentColor" strokeWidth="1" />
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#grid-pattern)" />
        </svg>
      </div>

      <div className="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12 text-center">
          {stats.map((stat, idx) => (
            <div key={idx} className="flex flex-col items-center justify-center">
              <div className="text-4xl md:text-5xl font-black mb-2 tracking-tight">
                {stat.value}
                <span className="text-2xl md:text-3xl font-bold text-teal-400 ml-1">{stat.suffix}</span>
              </div>
              <p className="text-sm md:text-base font-medium text-blue-200">{stat.label}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
