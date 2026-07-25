import { Link as LinkIcon } from 'lucide-react';
import { Link } from 'react-router-dom';
import { campaignNavItems, centerNavItems, companyNavItems } from '../lib/publicNav';
import { MemberAuthMenu } from './MemberAuthMenu';
import { g5BbsUrl } from '../lib/urls';

export function Footer() {
  return (
    <footer className="bg-slate-950 pt-20 pb-10 px-4 sm:px-6 lg:px-8 border-t border-white/5">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
          <div className="col-span-1 md:col-span-2">
            <Link to="/" className="flex items-center gap-2 mb-6">
              <LinkIcon className="w-6 h-6 text-emerald-400" />
              <span className="text-xl font-bold text-white tracking-tight">
                링크커넥트
              </span>
            </Link>
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
            <h4 className="text-white font-semibold mb-4">회사소개</h4>
            <ul className="space-y-3 text-sm text-slate-400">
              {companyNavItems.map((item) => (
                <li key={item.to}>
                  <Link to={item.to} className="hover:text-emerald-400 transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-white font-semibold mb-4">캠페인 · 서비스</h4>
            <ul className="space-y-3 text-sm text-slate-400">
              {campaignNavItems.map((item) => (
                <li key={item.to}>
                  <Link to={item.to} className="hover:text-emerald-400 transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
              {centerNavItems.map((item) => (
                <li key={item.to}>
                  <Link to={item.to} className="hover:text-cyan-400 transition-colors">
                    {item.label}
                  </Link>
                </li>
              ))}
              <MemberAuthMenu variant="footer" />
              <li><a href={g5BbsUrl('content.php?co_id=provision')} className="hover:text-cyan-400 transition-colors">이용약관</a></li>
              <li><a href={g5BbsUrl('content.php?co_id=privacy')} className="hover:text-cyan-400 transition-colors">개인정보처리방침</a></li>
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
