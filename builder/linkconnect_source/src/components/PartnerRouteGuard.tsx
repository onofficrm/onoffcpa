import { useEffect, useState } from 'react';
import { Link, Outlet } from 'react-router-dom';
import { applyPartner, type PartnerApplyPayload } from '../lib/api';
import { canAccessPartnerCenter, getLcAuth } from '../lib/auth';
import { currentSpaReturnUrl, g5LoginUrl } from '../lib/urls';

type EntityType = 'individual' | 'business';

function formatResidentNo(value: string) {
  const digits = value.replace(/\D/g, '').slice(0, 13);
  if (digits.length <= 6) return digits;
  return `${digits.slice(0, 6)}-${digits.slice(6)}`;
}

function formatBusinessNumber(value: string) {
  const digits = value.replace(/\D/g, '').slice(0, 10);
  if (digits.length <= 3) return digits;
  if (digits.length <= 5) return `${digits.slice(0, 3)}-${digits.slice(3)}`;
  return `${digits.slice(0, 3)}-${digits.slice(3, 5)}-${digits.slice(5)}`;
}

function validatePartnerApply(form: {
  entityType: EntityType | '';
  residentNo: string;
  companyName: string;
  businessNumber: string;
  representativeName: string;
  companyAddress: string;
}) {
  const errors: Record<string, string> = {};
  if (form.entityType !== 'individual' && form.entityType !== 'business') {
    errors.entityType = '개인 또는 사업자를 선택해 주세요.';
  }
  if (form.entityType === 'individual') {
    if (!/^\d{6}-\d{7}$/.test(form.residentNo)) {
      errors.residentNo = '주민등록번호 13자리를 입력해 주세요.';
    }
  }
  if (form.entityType === 'business') {
    if (!form.companyName.trim()) errors.companyName = '회사명을 입력해 주세요.';
    if (!/^\d{3}-\d{2}-\d{5}$/.test(form.businessNumber)) {
      errors.businessNumber = '사업자등록번호는 000-00-00000 형식으로 입력해 주세요.';
    }
    if (!form.representativeName.trim()) errors.representativeName = '대표자명을 입력해 주세요.';
    if (!form.companyAddress.trim()) errors.companyAddress = '사업장 주소를 입력해 주세요.';
  }
  return errors;
}

export function PartnerRouteGuard() {
  const auth = getLcAuth();
  const [applying, setApplying] = useState(false);
  const [applyError, setApplyError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [redirecting, setRedirecting] = useState(!auth.loggedIn);
  const [entityType, setEntityType] = useState<EntityType | ''>('');
  const [residentNo, setResidentNo] = useState('');
  const [companyName, setCompanyName] = useState('');
  const [businessNumber, setBusinessNumber] = useState('');
  const [representativeName, setRepresentativeName] = useState('');
  const [companyAddress, setCompanyAddress] = useState('');

  useEffect(() => {
    if (!auth.loggedIn) {
      setRedirecting(true);
      window.location.replace(g5LoginUrl(currentSpaReturnUrl('/partner')));
    }
  }, [auth.loggedIn]);

  if (!auth.loggedIn) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
        <p className="text-slate-600">{redirecting ? '로그인 페이지로 이동 중...' : '로그인이 필요합니다.'}</p>
      </div>
    );
  }

  if (canAccessPartnerCenter()) {
    return <Outlet />;
  }

  const handleApply = async () => {
    const errors = validatePartnerApply({
      entityType,
      residentNo,
      companyName,
      businessNumber,
      representativeName,
      companyAddress,
    });
    setFieldErrors(errors);
    if (Object.keys(errors).length > 0) {
      setApplyError('입력 내용을 확인해 주세요.');
      return;
    }

    const payload: PartnerApplyPayload =
      entityType === 'individual'
        ? { entityType: 'individual', residentNo }
        : {
            entityType: 'business',
            companyName: companyName.trim(),
            businessNumber,
            representativeName: representativeName.trim(),
            companyAddress: companyAddress.trim(),
          };

    setApplying(true);
    setApplyError('');
    try {
      await applyPartner(payload);
      window.location.reload();
    } catch (error) {
      const maybe = error as { fieldErrors?: Record<string, string>; message?: string };
      if (maybe.fieldErrors) {
        setFieldErrors(maybe.fieldErrors);
      }
      setApplyError(error instanceof Error ? error.message : '신청에 실패했습니다.');
    } finally {
      setApplying(false);
    }
  };

  const isPending = auth.isPartner && auth.partnerStatus === 'pending';
  const isSuspended = auth.isPartner && auth.partnerStatus === 'suspended';

  const fieldClass =
    'w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100';

  const identityForm = !isSuspended ? (
    <div className="mb-6 space-y-4">
      <div>
        <p className="text-sm font-semibold text-slate-800 mb-2">가입 유형</p>
        <div className="grid grid-cols-2 gap-2">
          <button
            type="button"
            onClick={() => {
              setEntityType('individual');
              setFieldErrors((prev) => {
                const next = { ...prev };
                delete next.entityType;
                return next;
              });
            }}
            className={`py-2.5 rounded-xl border text-sm font-semibold transition-colors ${
              entityType === 'individual'
                ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
            }`}
          >
            개인
          </button>
          <button
            type="button"
            onClick={() => {
              setEntityType('business');
              setFieldErrors((prev) => {
                const next = { ...prev };
                delete next.entityType;
                return next;
              });
            }}
            className={`py-2.5 rounded-xl border text-sm font-semibold transition-colors ${
              entityType === 'business'
                ? 'border-emerald-600 bg-emerald-50 text-emerald-800'
                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
            }`}
          >
            사업자
          </button>
        </div>
        {fieldErrors.entityType ? <p className="mt-1.5 text-xs text-red-600">{fieldErrors.entityType}</p> : null}
      </div>

      {entityType === 'individual' ? (
        <div>
          <label className="block text-sm font-semibold text-slate-800 mb-1.5" htmlFor="partner-resident-no">
            주민등록번호
          </label>
          <input
            id="partner-resident-no"
            type="text"
            inputMode="numeric"
            autoComplete="off"
            placeholder="000000-0000000"
            value={residentNo}
            onChange={(e) => setResidentNo(formatResidentNo(e.target.value))}
            className={fieldClass}
          />
          {fieldErrors.residentNo ? <p className="mt-1.5 text-xs text-red-600">{fieldErrors.residentNo}</p> : null}
          <p className="mt-1.5 text-xs text-slate-500">정산·세무 처리에 사용되며 관리자에게만 표시됩니다.</p>
        </div>
      ) : null}

      {entityType === 'business' ? (
        <div className="space-y-3">
          <div>
            <label className="block text-sm font-semibold text-slate-800 mb-1.5" htmlFor="partner-company-name">
              회사명
            </label>
            <input
              id="partner-company-name"
              type="text"
              value={companyName}
              onChange={(e) => setCompanyName(e.target.value)}
              className={fieldClass}
              placeholder="상호명"
            />
            {fieldErrors.companyName ? <p className="mt-1.5 text-xs text-red-600">{fieldErrors.companyName}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-800 mb-1.5" htmlFor="partner-business-number">
              사업자등록번호
            </label>
            <input
              id="partner-business-number"
              type="text"
              inputMode="numeric"
              value={businessNumber}
              onChange={(e) => setBusinessNumber(formatBusinessNumber(e.target.value))}
              className={fieldClass}
              placeholder="000-00-00000"
            />
            {fieldErrors.businessNumber ? (
              <p className="mt-1.5 text-xs text-red-600">{fieldErrors.businessNumber}</p>
            ) : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-800 mb-1.5" htmlFor="partner-rep-name">
              대표자명
            </label>
            <input
              id="partner-rep-name"
              type="text"
              value={representativeName}
              onChange={(e) => setRepresentativeName(e.target.value)}
              className={fieldClass}
            />
            {fieldErrors.representativeName ? (
              <p className="mt-1.5 text-xs text-red-600">{fieldErrors.representativeName}</p>
            ) : null}
          </div>
          <div>
            <label className="block text-sm font-semibold text-slate-800 mb-1.5" htmlFor="partner-company-address">
              사업장 주소
            </label>
            <input
              id="partner-company-address"
              type="text"
              value={companyAddress}
              onChange={(e) => setCompanyAddress(e.target.value)}
              className={fieldClass}
            />
            {fieldErrors.companyAddress ? (
              <p className="mt-1.5 text-xs text-red-600">{fieldErrors.companyAddress}</p>
            ) : null}
          </div>
        </div>
      ) : null}
    </div>
  ) : null;

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
      <div className="max-w-lg w-full bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        {isPending ? (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 등록 확인</h1>
            <p className="text-slate-600 mb-4">
              이전에 신청하신 내역이 있습니다. 개인/사업자 정보를 입력한 뒤 활성화해 주세요.
            </p>
            {auth.partnerCode && (
              <p className="text-sm text-slate-500 mb-4">
                파트너 코드: <span className="font-mono font-semibold text-slate-800">{auth.partnerCode}</span>
              </p>
            )}
            {identityForm}
            {applyError && <p className="text-sm text-red-600 mb-4">{applyError}</p>}
            <button
              type="button"
              onClick={handleApply}
              disabled={applying}
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold rounded-xl transition-colors"
            >
              {applying ? '활성화 중...' : '파트너 활성화하기'}
            </button>
          </>
        ) : isSuspended ? (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 계정 정지</h1>
            <p className="text-slate-600">계정이 정지되었습니다. 고객센터로 문의해 주세요.</p>
          </>
        ) : (
          <>
            <h1 className="text-2xl font-bold text-slate-900 mb-3">파트너 등록이 필요합니다</h1>
            <p className="text-slate-600 mb-6">
              개인 또는 사업자를 선택한 뒤 필수 정보를 입력하면 즉시 파트너센터를 이용할 수 있습니다.
            </p>
            {identityForm}
            {applyError && <p className="text-sm text-red-600 mb-4">{applyError}</p>}
            <button
              type="button"
              onClick={handleApply}
              disabled={applying}
              className="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold rounded-xl transition-colors"
            >
              {applying ? '등록 중...' : '파트너 등록하기'}
            </button>
          </>
        )}

        <div className="mt-6 flex gap-3">
          <Link to="/" className="text-sm text-slate-500 hover:text-emerald-600">
            홈으로
          </Link>
          <Link to="/select-center" className="text-sm text-slate-500 hover:text-emerald-600">
            센터 선택
          </Link>
        </div>
      </div>
    </div>
  );
}
