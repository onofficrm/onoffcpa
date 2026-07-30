import { Link } from 'react-router-dom';
import { ArrowRight, FileText } from 'lucide-react';
import { ContractProcessGuide } from '../contract/ContractProcessGuide';
import { getLcAuth, getMerchantContractPath } from '../../lib/auth';

export function AdvertiserContractGate() {
  const auth = getLcAuth();
  const approvalPending = auth.merchantContractStatus === 'review_pending';
  const rejected = auth.merchantContractStatus === 'rejected';
  const contractPath = getMerchantContractPath(auth);

  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6">
      <div className="max-w-2xl w-full space-y-6">
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
          <div className="mx-auto w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mb-5">
            <FileText className="text-amber-600" size={28} />
          </div>
          <h2 className="text-xl font-bold text-slate-900 mb-3">
            {approvalPending
              ? '계약서 관리자 승인 대기 중입니다'
              : rejected
                ? '계약 승인 요청이 반려되었습니다'
                : 'CPA 광고 제휴 계약 승인이 필요합니다'}
          </h2>
          <p className="text-slate-600 mb-6 leading-relaxed">
            {approvalPending
              ? '관리자가 계약 내용을 검토하고 있습니다. 승인되면 광고를 바로 등록할 수 있습니다.'
              : rejected
                ? '계약 내용을 수정한 뒤 다시 승인 요청해 주세요.'
                : '계약서를 작성·서명하고 관리자 승인을 받으면 광고를 등록할 수 있습니다.'}
            <br />
          </p>
          <Link
            to={contractPath}
            className="inline-flex items-center justify-center gap-2 w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl transition-colors"
          >
            {approvalPending ? '승인 요청 현황 보기' : rejected ? '계약서 수정하기' : '계약서 작성하기'}
            <ArrowRight size={18} />
          </Link>
        </div>
        <ContractProcessGuide audience="advertiser" />
      </div>
    </div>
  );
}
