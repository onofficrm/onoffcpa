import { Link } from 'react-router-dom';
import { ArrowRight, CheckCircle2, FileSignature, Search, Settings2 } from 'lucide-react';

type ContractProcessGuideProps = {
  audience: 'admin' | 'advertiser';
  className?: string;
};

const advertiserSteps = [
  {
    step: '1',
    title: '광고주 정보 입력',
    desc: '회사명, 사업자번호, 담당자 연락처를 확인합니다.',
  },
  {
    step: '2',
    title: '별도 협의·특별조항',
    desc: '기본 계약 외 협의사항·특별조항이 있으면 입력합니다.',
  },
  {
    step: '3',
    title: '계약서 확인·동의',
    desc: '기본 계약과 추가 조항이 포함된 전문을 읽고 동의합니다.',
  },
  {
    step: '4',
    title: '담당자 서명·승인 요청',
    desc: '전자 서명 후 관리자에게 계약 승인을 요청합니다.',
  },
  {
    step: '5',
    title: '관리자 승인·광고 등록',
    desc: '관리자 승인 즉시 광고를 등록할 수 있습니다.',
  },
];

const adminSteps = [
  {
    step: '1',
    icon: <FileSignature size={16} />,
    title: '광고주가 승인 요청',
    desc: '사이드바의 「계약서 작성」에서 작성·동의·서명 후 승인 요청합니다.',
  },
  {
    step: '2',
    icon: <Search size={16} />,
    title: '관리자가 계약 검토·승인',
    desc: '사이드바 「광고주 계약」에서 승인 대기 건을 선택한 뒤, 상단의 「계약 승인」으로 처리합니다.',
  },
  {
    step: '3',
    icon: <Settings2 size={16} />,
    title: '승인 후 광고 등록',
    desc: '승인 즉시 광고주의 광고 등록 기능이 열립니다.',
  },
];

export function ContractProcessGuide({ audience, className = '' }: ContractProcessGuideProps) {
  if (audience === 'advertiser') {
    return (
      <div className={`rounded-2xl border border-cyan-100 bg-cyan-50/60 p-5 ${className}`}>
        <h3 className="font-bold text-slate-900 mb-1">CPA 계약 체결 절차</h3>
        <p className="text-sm text-slate-600 mb-4">
          광고주가 계약서를 작성·서명해 승인 요청하면 관리자가 검토합니다. 승인 후 광고를 등록할 수 있습니다.
        </p>
        <ol className="grid sm:grid-cols-2 gap-3">
          {advertiserSteps.map((item) => (
            <li key={item.step} className="flex gap-3 bg-white/80 border border-cyan-100 rounded-xl p-3">
              <span className="w-7 h-7 shrink-0 rounded-full bg-cyan-600 text-white text-sm font-bold flex items-center justify-center">
                {item.step}
              </span>
              <div>
                <div className="font-bold text-slate-900 text-sm">{item.title}</div>
                <div className="text-xs text-slate-600 mt-0.5 leading-relaxed">{item.desc}</div>
              </div>
            </li>
          ))}
        </ol>
      </div>
    );
  }

  return (
    <div className={`rounded-2xl border border-slate-200 bg-slate-50 p-5 ${className}`}>
      <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
        <div>
          <h3 className="font-bold text-slate-900 mb-1">광고주 계약 프로세스</h3>
          <p className="text-sm text-slate-600 leading-relaxed">
            광고주가 전자 서명 후 <b>승인 요청</b>하면 관리자가 계약서를 검토하여 승인 또는 반려합니다.
          </p>
        </div>
        <div className="text-xs text-slate-500 bg-white border border-slate-200 rounded-xl px-3 py-2 shrink-0">
          데모: <code className="font-mono">lc_advertiser</code> / <code className="font-mono">demo1234!</code>
        </div>
      </div>
      <ol className="grid md:grid-cols-3 gap-3">
        {adminSteps.map((item) => (
          <li key={item.step} className="bg-white border border-slate-200 rounded-xl p-4">
            <div className="flex items-center gap-2 text-cyan-700 font-bold text-sm mb-2">
              <span className="w-6 h-6 rounded-full bg-cyan-100 flex items-center justify-center text-xs">{item.step}</span>
              {item.icon}
              {item.title}
            </div>
            <p className="text-xs text-slate-600 leading-relaxed">{item.desc}</p>
          </li>
        ))}
      </ol>
      <p className="mt-4 text-xs text-slate-500 flex items-start gap-1.5">
        <CheckCircle2 size={14} className="text-emerald-600 shrink-0 mt-0.5" />
        광고주 체결 경로: 로그인 → 광고주센터 → 사이드바 하단 <Link to="/advertiser/contract" className="text-cyan-700 font-bold hover:underline">계약서 작성</Link>
        <ArrowRight size={12} className="inline shrink-0 mt-0.5" />
        작성·서명·승인 요청
      </p>
    </div>
  );
}
