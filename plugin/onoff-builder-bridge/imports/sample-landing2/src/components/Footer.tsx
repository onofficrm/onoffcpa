export function Footer() {
  const FOOTER_LINKS = [
    "링크커넥트 소개",
    "공지사항",
    "뉴스",
    "CPA",
    "CPS",
    "이벤트/프로모션",
    "파트너센터",
    "광고주센터",
  ];

  return (
    <footer className="bg-slate-900 py-12 text-slate-400">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <div className="col-span-1 md:col-span-2">
            <div className="flex items-center gap-2 mb-4">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-800 border border-slate-700">
                <span className="text-lg font-bold text-teal-400">L</span>
              </div>
              <span className="text-xl font-bold text-white tracking-tight">링크커넥트</span>
            </div>
            <p className="text-sm leading-relaxed max-w-xs mb-6">
              광고주와 파트너를 가장 투명하고 효율적으로 연결하는 성과형 제휴마케팅 플랫폼.
            </p>
            <div className="flex gap-4 text-sm">
              <a href="#" className="hover:text-white transition-colors">개인정보처리방침</a>
              <a href="#" className="hover:text-white transition-colors">이용약관</a>
            </div>
          </div>

          <div>
            <h3 className="text-white font-medium mb-4">서비스</h3>
            <ul className="space-y-3">
              {FOOTER_LINKS.slice(0, 5).map((link) => (
                <li key={link}>
                  <a href="#" className="text-sm hover:text-white transition-colors">{link}</a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="text-white font-medium mb-4">고객지원</h3>
            <ul className="space-y-3">
              {FOOTER_LINKS.slice(5).map((link) => (
                <li key={link}>
                  <a href="#" className="text-sm hover:text-white transition-colors">{link}</a>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-800 pt-8 text-sm flex flex-col md:flex-row justify-between items-center gap-4">
          <p>© 2026 LinkConnect. All rights reserved.</p>
          <p>사업자등록번호: 000-00-00000 | 대표: 홍길동 | 서울특별시 강남구 테헤란로</p>
        </div>
      </div>
    </footer>
  );
}
