import { Link } from 'lucide-react';

export function Footer() {
  return (
    <footer className="bg-slate-950 pt-20 pb-10 px-4 sm:px-6 lg:px-8 border-t border-white/5">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
          <div className="col-span-1 md:col-span-2">
            <div className="flex items-center gap-2 mb-6">
              <Link className="w-6 h-6 text-emerald-400" />
              <span className="text-xl font-bold text-white tracking-tight">
                링크커넥트
              </span>
            </div>
            <p className="text-slate-400 text-sm leading-relaxed max-w-md mb-6">
              클릭을 수익으로, DB를 성과로 연결하는 제휴마케팅 플랫폼입니다. 
              최고의 전환율과 투명한 정산 시스템을 제공합니다.
            </p>
            <div className="text-slate-500 text-sm">
              <p>이메일: support@linkconnect.com</p>
              <p>고객센터: 1588-0000 (평일 10:00 ~ 17:00)</p>
            </div>
          </div>
          
          <div>
            <h4 className="text-white font-semibold mb-4">플랫폼</h4>
            <ul className="space-y-3 text-sm text-slate-400">
              <li><a href="#" className="hover:text-emerald-400 transition-colors">회사소개</a></li>
              <li><a href="#" className="hover:text-emerald-400 transition-colors">공지사항</a></li>
              <li><a href="#cpa" className="hover:text-emerald-400 transition-colors">CPA 상품</a></li>
              <li><a href="#cps" className="hover:text-emerald-400 transition-colors">CPS 상품</a></li>
              <li><a href="#events" className="hover:text-emerald-400 transition-colors">이벤트/프로모션</a></li>
            </ul>
          </div>

          <div>
            <h4 className="text-white font-semibold mb-4">서비스</h4>
            <ul className="space-y-3 text-sm text-slate-400">
              <li><a href="#partner" className="hover:text-cyan-400 transition-colors">파트너센터</a></li>
              <li><a href="#advertiser" className="hover:text-cyan-400 transition-colors">광고주센터</a></li>
              <li><a href="#" className="hover:text-white transition-colors">이용약관</a></li>
              <li><a href="#" className="hover:text-white transition-colors">개인정보처리방침</a></li>
            </ul>
          </div>
        </div>

        <div className="pt-8 border-t border-white/5 text-center text-sm text-slate-600">
          © {new Date().getFullYear()} LinkConnect. All rights reserved.
        </div>
      </div>
    </footer>
  );
}
