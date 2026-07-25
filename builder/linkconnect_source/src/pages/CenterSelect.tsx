import { ArrowRight, BarChart3, LogIn, Settings, Target } from 'lucide-react';
import { Link } from 'react-router-dom';
import { MemberAuthMenu } from '../components/MemberAuthMenu';
import { currentSpaReturnUrl } from '../lib/urls';

export function CenterSelect() {
  const loginReturnUrl = currentSpaReturnUrl('/select-center');
  const logoutReturnPath = '/select-center';

  return (
    <div className="pt-32 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div className="text-center mb-10">
        <h1 className="text-3xl md:text-4xl font-bold text-slate-900 mb-4 tracking-tight">링크커넥트 센터 바로가기</h1>
        <p className="text-lg text-slate-600">로그인 후 이용하실 센터를 선택해주세요.</p>
      </div>

      <div className="max-w-xl mx-auto mb-12 bg-slate-900 text-white rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
        <div className="flex items-center gap-3 text-left">
          <div className="w-11 h-11 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
            <LogIn className="w-5 h-5 text-emerald-400" />
          </div>
          <div>
            <p className="font-bold">GNUBoard 회원 로그인</p>
            <p className="text-sm text-slate-400 mt-0.5">로그인 완료 후 이 페이지로 돌아옵니다.</p>
          </div>
        </div>
        <MemberAuthMenu
          variant="card"
          loginReturnUrl={loginReturnUrl}
          logoutReturnPath={logoutReturnPath}
        />
      </div>

      <div className="grid md:grid-cols-3 gap-6">
        <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl hover:border-emerald-300 transition-all group flex flex-col">
          <div className="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-6">
            <Target className="w-7 h-7" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900 mb-3">파트너센터</h2>
          <p className="text-slate-600 mb-6 flex-1">홍보 링크를 생성하고, 유입된 디비와 수익을 확인할 수 있습니다.</p>

          <ul className="space-y-2 mb-8 text-sm text-slate-700">
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>CPA 광고상품 찾기</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>홍보 링크 생성</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>유입 분석</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>예상수익 / 확정수익 확인</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>정산 신청</li>
          </ul>

          <Link to="/partner" className="w-full py-4 bg-emerald-50 text-emerald-700 font-bold rounded-xl flex items-center justify-center gap-2 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
            파트너센터 바로가기 <ArrowRight className="w-5 h-5" />
          </Link>
        </div>

        <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl hover:border-cyan-300 transition-all group flex flex-col">
          <div className="w-14 h-14 bg-cyan-50 text-cyan-600 rounded-xl flex items-center justify-center mb-6">
            <BarChart3 className="w-7 h-7" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900 mb-3">광고주센터</h2>
          <p className="text-slate-600 mb-6 flex-1">접수된 디비를 확인하고, 승인/취소 및 광고비를 관리할 수 있습니다.</p>

          <ul className="space-y-2 mb-8 text-sm text-slate-700">
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>디비 확인</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>승인 / 취소 처리</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>광고비 충전</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>광고비 차감 내역</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-cyan-400"></div>성과 리포트</li>
          </ul>

          <Link to="/advertiser" className="w-full py-4 bg-cyan-50 text-cyan-700 font-bold rounded-xl flex items-center justify-center gap-2 group-hover:bg-cyan-500 group-hover:text-white transition-colors">
            광고주센터 바로가기 <ArrowRight className="w-5 h-5" />
          </Link>
        </div>

        <div className="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm hover:shadow-xl hover:border-purple-300 transition-all group flex flex-col">
          <div className="w-14 h-14 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-6">
            <Settings className="w-7 h-7" />
          </div>
          <h2 className="text-2xl font-bold text-slate-900 mb-3">관리자센터</h2>
          <p className="text-slate-600 mb-6 flex-1">전체 파트너, 광고주, 디비, 정산, API 연동 상태를 관리합니다.</p>

          <ul className="space-y-2 mb-8 text-sm text-slate-700">
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-purple-400"></div>전체 디비 관리</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-purple-400"></div>광고상품 관리</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-purple-400"></div>파트너/광고주 관리</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-purple-400"></div>정산 관리</li>
            <li className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-purple-400"></div>API 관리</li>
          </ul>

          <Link to="/admin" className="w-full py-4 bg-purple-50 text-purple-700 font-bold rounded-xl flex items-center justify-center gap-2 group-hover:bg-slate-900 group-hover:text-white transition-colors">
            관리자센터 바로가기 <ArrowRight className="w-5 h-5" />
          </Link>
        </div>
      </div>
    </div>
  );
}
