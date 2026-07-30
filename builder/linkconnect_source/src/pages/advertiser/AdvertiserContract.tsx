import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { AdvertiserContractLayout } from '../../layouts/AdvertiserContractLayout';
import { ContractDocumentViewer } from '../../components/contract/ContractDocumentViewer';
import { ContractProcessGuide } from '../../components/contract/ContractProcessGuide';
import { SignatureCanvas } from '../../components/advertiser/contract/SignatureCanvas';
import {
  CONTRACT_REQUIRED_AGREEMENTS,
  ContractFormState,
  ContractStepIndicator,
  EMPTY_CONTRACT_FORM,
  FieldError,
  TextField,
  draftStorageKey,
  formatBusinessNumber,
  validateStep1,
  validateStep2,
  validateStep3,
  validateStep4,
} from '../../components/advertiser/contract/contractForm';
import {
  fetchMerchantContract,
  saveMerchantContractDraft,
  uploadMerchantContractSignature,
  signMerchantContract,
  PartnerApiError,
  type MerchantContractState,
} from '../../lib/api';
import { getLcAuth, isLcLoggedIn } from '../../lib/auth';
import { g5LoginUrl, spaReturnUrl } from '../../lib/urls';

function mapStateToForm(state: MerchantContractState): ContractFormState {
  return {
    companyName: state.form.companyName ?? '',
    representativeName: state.form.representativeName ?? '',
    businessNumber: state.form.businessNumber ?? '',
    companyAddress: state.form.companyAddress ?? '',
    companyPhone: state.form.companyPhone ?? '',
    contactName: state.form.contactName ?? '',
    contactPhone: state.form.contactPhone ?? '',
    contactEmail: state.form.contactEmail ?? '',
    signerName: state.form.signerName ?? '',
    signerPosition: state.form.signerPosition ?? '',
    signerPhone: state.form.signerPhone ?? '',
    signerEmail: state.form.signerEmail ?? '',
    negotiatedTerms: state.form.negotiatedTerms ?? '',
    specialClauses: state.form.specialClauses ?? '',
    agreements: {
      readAll: Boolean(state.form.agreements?.readAll),
      hasAuthority: Boolean(state.form.agreements?.hasAuthority),
      electronic: Boolean(state.form.agreements?.electronic),
      noModify: Boolean(state.form.agreements?.noModify),
    },
    step: Math.min(4, Math.max(1, Number(state.form.step) || 1)),
  };
}

function formToPayload(form: ContractFormState, csrfToken: string, step: number) {
  return {
    action: 'draft' as const,
    csrfToken,
    step,
    companyName: form.companyName,
    representativeName: form.representativeName,
    businessNumber: form.businessNumber,
    companyAddress: form.companyAddress,
    companyPhone: form.companyPhone,
    contactName: form.contactName,
    contactPhone: form.contactPhone,
    contactEmail: form.contactEmail,
    signerName: form.signerName,
    signerPosition: form.signerPosition,
    signerPhone: form.signerPhone,
    signerEmail: form.signerEmail,
    negotiatedTerms: form.negotiatedTerms,
    specialClauses: form.specialClauses,
    agreements: form.agreements,
  };
}

export function AdvertiserContract() {
  const auth = getLcAuth();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [state, setState] = useState<MerchantContractState | null>(null);
  const [form, setForm] = useState<ContractFormState>(EMPTY_CONTRACT_FORM);
  const [step, setStep] = useState(1);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [missingSummary, setMissingSummary] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [loadError, setLoadError] = useState('');
  const [hasSignature, setHasSignature] = useState(false);
  const [signatureDataUrl, setSignatureDataUrl] = useState<string | null>(null);
  const [submitError, setSubmitError] = useState('');

  const storageKey = useMemo(() => draftStorageKey(auth.merchantId), [auth.merchantId]);

  useEffect(() => {
    if (!isLcLoggedIn()) {
      window.location.replace(g5LoginUrl(spaReturnUrl('/advertiser/contract')));
    }
  }, []);

  const persistLocalDraft = useCallback(
    (nextForm: ContractFormState, nextStep: number) => {
      try {
        localStorage.setItem(
          storageKey,
          JSON.stringify({ form: { ...nextForm, step: nextStep }, savedAt: Date.now() }),
        );
      } catch {
        // ignore quota errors
      }
    },
    [storageKey],
  );

  const loadContract = useCallback(async () => {
    setLoading(true);
    setLoadError('');
    try {
      const data = await fetchMerchantContract();
      if (data.isSigned || data.isApprovalPending) {
        setState(data);
        setLoading(false);
        return;
      }

      let nextForm = mapStateToForm(data);
      let nextStep = nextForm.step;

      try {
        const raw = localStorage.getItem(storageKey);
        if (raw) {
          const parsed = JSON.parse(raw) as { form?: ContractFormState; savedAt?: number };
          if (parsed.form) {
            nextForm = { ...nextForm, ...parsed.form, agreements: { ...nextForm.agreements, ...parsed.form.agreements } };
            nextStep = Math.min(3, Math.max(1, parsed.form.step || nextStep));
          }
        }
      } catch {
        // ignore corrupt local draft
      }

      setState(data);
      setForm(nextForm);
      setStep(nextStep);
      setHasSignature(Boolean(data.hasSignature));
    } catch (error) {
      setLoadError(error instanceof Error ? error.message : '계약 정보를 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  }, [storageKey]);

  useEffect(() => {
    if (auth.isMerchant) {
      void loadContract();
    } else {
      setLoading(false);
    }
  }, [auth.isMerchant, loadContract]);

  const updateForm = (patch: Partial<ContractFormState>) => {
    setForm((prev) => {
      const next = { ...prev, ...patch };
      persistLocalDraft(next, step);
      return next;
    });
  };

  const saveDraft = async (nextForm: ContractFormState, nextStep: number) => {
    if (!state?.csrfToken) return;
    setSaving(true);
    try {
      const result = await saveMerchantContractDraft(formToPayload(nextForm, state.csrfToken, nextStep));
      setState(result.state);
      setHasSignature(Boolean(result.state.hasSignature));
      persistLocalDraft(nextForm, nextStep);
    } catch (error) {
      if (error instanceof PartnerApiError && error.code === 'VALIDATION') {
        // step transition validation may fail silently on partial draft
      }
    } finally {
      setSaving(false);
    }
  };

  const goNext = async () => {
    setSubmitError('');
    if (step === 1) {
      const stepErrors = validateStep1(form);
      setErrors(stepErrors);
      const missing = Object.values(stepErrors);
      setMissingSummary(missing);
      if (Object.keys(stepErrors).length > 0) return;
      const nextStep = 2;
      setStep(nextStep);
      persistLocalDraft(form, nextStep);
      await saveDraft(form, nextStep);
      return;
    }
    if (step === 2) {
      const stepErrors = validateStep2(form);
      setErrors(stepErrors);
      if (Object.keys(stepErrors).length > 0) return;
      const nextStep = 3;
      setStep(nextStep);
      persistLocalDraft(form, nextStep);
      await saveDraft(form, nextStep);
      return;
    }
    if (step === 3) {
      const stepErrors = validateStep3(form);
      setErrors(stepErrors);
      if (Object.keys(stepErrors).length > 0) return;
      const nextStep = 4;
      setStep(nextStep);
      persistLocalDraft(form, nextStep);
      await saveDraft(form, nextStep);
    }
  };

  const goPrev = () => {
    setErrors({});
    setMissingSummary([]);
    const nextStep = Math.max(1, step - 1);
    setStep(nextStep);
    persistLocalDraft(form, nextStep);
  };

  const handleSignatureChange = (hasStroke: boolean, dataUrl: string | null) => {
    setHasSignature(hasStroke);
    setSignatureDataUrl(dataUrl);
    if (errors.signature) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next.signature;
        return next;
      });
    }
  };

  const uploadSignatureIfNeeded = async () => {
    if (!state?.csrfToken || !signatureDataUrl) return false;
    await uploadMerchantContractSignature({
      csrfToken: state.csrfToken,
      signatureDataUrl,
    });
    setHasSignature(true);
    return true;
  };

  const handleSignSubmit = async () => {
    setSubmitError('');
    const stepErrors = validateStep4(form, hasSignature);
    setErrors(stepErrors);
    if (Object.keys(stepErrors).length > 0) {
      setShowConfirm(false);
      return;
    }

    setSubmitting(true);
    try {
      if (signatureDataUrl) {
        await uploadSignatureIfNeeded();
      }
      const payload = {
        ...formToPayload(form, state!.csrfToken, 4),
        action: 'sign' as const,
        signatureDataUrl: signatureDataUrl ?? undefined,
      };
      const result = await signMerchantContract(payload);
      setShowConfirm(false);
      if (result.alreadySigned) {
        navigate('/advertiser/contract/view', { replace: true });
        return;
      }
      navigate('/advertiser/contract/complete', {
        replace: true,
        state: { contract: result.contract },
      });
      try {
        localStorage.removeItem(storageKey);
      } catch {
        // ignore
      }
    } catch (error) {
      if (error instanceof PartnerApiError) {
        if (error.code === 'CONTRACT_SIGNED') {
          navigate('/advertiser/contract/view', { replace: true });
          return;
        }
        setSubmitError(error.message);
      } else {
        setSubmitError('계약 체결 중 오류가 발생했습니다.');
      }
      setShowConfirm(false);
    } finally {
      setSubmitting(false);
    }
  };

  useEffect(() => {
    if (!signatureDataUrl || !state?.csrfToken || step !== 3) return;
    const timer = window.setTimeout(() => {
      void uploadMerchantContractSignature({
        csrfToken: state.csrfToken,
        signatureDataUrl,
      })
        .then((result) => {
          setState(result.state);
          setHasSignature(Boolean(result.state.hasSignature));
        })
        .catch(() => {
          // 서명 임시 저장 실패는 제출 시 재시도
        });
    }, 800);
    return () => window.clearTimeout(timer);
  }, [signatureDataUrl, state?.csrfToken, step]);

  if (!auth.loggedIn) {
    return (
      <AdvertiserContractLayout title="CPA 계약서 작성">
        <p className="text-slate-600">로그인 페이지로 이동 중...</p>
      </AdvertiserContractLayout>
    );
  }

  if (!auth.isMerchant) {
    return (
      <AdvertiserContractLayout title="접근 제한">
        <div className="bg-white border border-slate-200 rounded-2xl p-6">
          <p className="text-slate-700">광고주 계정으로만 계약서를 작성할 수 있습니다.</p>
        </div>
      </AdvertiserContractLayout>
    );
  }

  if (!loading && state?.isSigned) {
    return <Navigate to="/advertiser/contract/view" replace />;
  }
  if (!loading && state?.isApprovalPending) {
    return <Navigate to="/advertiser/contract/complete" replace />;
  }

  if (loading) {
    return (
      <AdvertiserContractLayout title="CPA 계약서 작성" subtitle="계약 정보를 불러오는 중입니다.">
        <div className="flex items-center gap-2 text-slate-600">
          <Loader2 className="animate-spin" size={18} />
          불러오는 중...
        </div>
      </AdvertiserContractLayout>
    );
  }

  if (loadError) {
    return (
      <AdvertiserContractLayout title="CPA 계약서 작성">
        <div className="bg-white border border-slate-200 rounded-2xl p-6">
          <p className="text-slate-700 mb-4">{loadError}</p>
          <button
            type="button"
            onClick={() => void loadContract()}
            className="px-4 py-2 rounded-xl bg-cyan-600 text-white font-semibold"
          >
            다시 시도
          </button>
        </div>
      </AdvertiserContractLayout>
    );
  }

  const partyB = state?.partyB;

  return (
    <AdvertiserContractLayout
      title="CPA 계약서 작성"
      subtitle={`계약서 버전 ${state?.contractVersion ?? ''} · 작성·서명 후 관리자 승인을 요청합니다.`}
    >
      <ContractProcessGuide audience="advertiser" className="mb-6" />
      {state?.isRejected ? (
        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 space-y-1">
          <p className="font-semibold">계약 승인 요청이 반려되었습니다. 내용을 수정한 뒤 다시 승인 요청해 주세요.</p>
          {state.rejectionReason ? <p>반려 사유: {state.rejectionReason}</p> : null}
        </div>
      ) : null}
      <ContractStepIndicator currentStep={step} />

      {step === 1 ? (
        <section className="bg-white border border-slate-200 rounded-2xl p-5 md:p-8 shadow-sm space-y-8">
          <div>
            <h2 className="text-lg font-bold text-slate-900 mb-1">1단계: 광고주 정보 확인</h2>
            <p className="text-sm text-slate-500">
              아래 정보는 계약 체결용으로 저장되며, 기존 회원정보 수정 화면과는 별도로 관리됩니다.
            </p>
          </div>

          {missingSummary.length > 0 ? (
            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
              <p className="font-semibold mb-1">다음 항목을 입력해 주세요.</p>
              <ul className="list-disc pl-5 space-y-0.5">
                {missingSummary.map((item) => (
                  <li key={item}>{item}</li>
                ))}
              </ul>
            </div>
          ) : null}

          <div className="grid md:grid-cols-2 gap-4">
            <TextField label="회사명 *" value={form.companyName} onChange={(v) => updateForm({ companyName: v })} error={errors.companyName} />
            <TextField label="대표자명 *" value={form.representativeName} onChange={(v) => updateForm({ representativeName: v })} error={errors.representativeName} />
            <TextField
              label="사업자등록번호 *"
              value={form.businessNumber}
              onChange={(v) => updateForm({ businessNumber: formatBusinessNumber(v) })}
              error={errors.businessNumber}
              placeholder="000-00-00000"
            />
            <TextField label="회사 연락처 *" value={form.companyPhone} onChange={(v) => updateForm({ companyPhone: v })} error={errors.companyPhone} />
            <div className="md:col-span-2">
              <TextField label="사업장 주소 *" value={form.companyAddress} onChange={(v) => updateForm({ companyAddress: v })} error={errors.companyAddress} />
            </div>
            <TextField label="담당자명 *" value={form.contactName} onChange={(v) => updateForm({ contactName: v })} error={errors.contactName} />
            <TextField label="담당자 연락처 *" value={form.contactPhone} onChange={(v) => updateForm({ contactPhone: v })} error={errors.contactPhone} />
            <div className="md:col-span-2">
              <TextField label="담당자 이메일 *" type="email" value={form.contactEmail} onChange={(v) => updateForm({ contactEmail: v })} error={errors.contactEmail} />
            </div>
          </div>

          <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 className="text-sm font-bold text-slate-800 mb-2">을 (운영사) 정보</h3>
            <dl className="grid sm:grid-cols-2 gap-2 text-sm text-slate-700">
              <div><dt className="text-slate-500">회사명</dt><dd>{partyB?.companyName}</dd></div>
              <div><dt className="text-slate-500">대표자</dt><dd>{partyB?.representativeName}</dd></div>
              <div><dt className="text-slate-500">사업자등록번호</dt><dd>{partyB?.businessNumber}</dd></div>
              <div><dt className="text-slate-500">연락처</dt><dd>{partyB?.companyPhone}</dd></div>
              <div className="sm:col-span-2"><dt className="text-slate-500">주소</dt><dd>{partyB?.companyAddress}</dd></div>
            </dl>
          </div>
        </section>
      ) : null}

      {step === 2 ? (
        <section className="bg-white border border-slate-200 rounded-2xl p-5 md:p-8 shadow-sm space-y-6">
          <div>
            <h2 className="text-lg font-bold text-slate-900 mb-1">2단계: 별도 협의·특별조항</h2>
            <p className="text-sm text-slate-500">
              기본 계약서 외에 합의할 내용이 있으면 입력하세요. 없으면 비워 두고 다음으로 진행할 수 있습니다.
              입력한 내용은 다음 단계의 계약서 전문(제11·12조)에 반영됩니다.
            </p>
          </div>

          <label className="block space-y-1.5">
            <span className="text-sm font-semibold text-slate-800">별도 협의사항</span>
            <p className="text-xs text-slate-500">단가 예외, 검수 기간 조정, 캠페인 운영 조건 등 상호 합의 내용을 적어 주세요.</p>
            <textarea
              value={form.negotiatedTerms}
              onChange={(e) => updateForm({ negotiatedTerms: e.target.value })}
              rows={6}
              placeholder="예: 유효 DB 1건당 단가는 별도 합의된 ○○원(VAT 별도)을 적용한다. …"
              className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500"
            />
          </label>

          <label className="block space-y-1.5">
            <span className="text-sm font-semibold text-slate-800">특별조항 (독소조항·제한 조건 등)</span>
            <p className="text-xs text-slate-500">독점, 최소 보장, 위약금, 금지 사항 등 특별 제한·의무 조항이 있으면 적어 주세요.</p>
            <textarea
              value={form.specialClauses}
              onChange={(e) => updateForm({ specialClauses: e.target.value })}
              rows={6}
              placeholder="예: 갑은 계약 기간 중 동일 업종에 대해 타 제휴 플랫폼에 동일 조건의 상품을 등록하지 않는다. …"
              className="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500"
            />
          </label>

          <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            관리자 승인 과정에서 내용이 검토되며, 필요 시 반려 후 수정 요청될 수 있습니다.
          </div>
        </section>
      ) : null}

      {step === 3 ? (
        <section className="bg-white border border-slate-200 rounded-2xl p-5 md:p-8 shadow-sm space-y-6">
          <div>
            <h2 className="text-lg font-bold text-slate-900 mb-1">3단계: 계약서 확인 및 동의</h2>
            <p className="text-sm text-slate-500">
              기본 계약과 별도 협의·특별조항이 포함된 전문을 확인하고 필수 항목에 동의해 주세요.
            </p>
          </div>

          {(form.negotiatedTerms.trim() || form.specialClauses.trim()) ? (
            <div className="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-900">
              별도 협의·특별조항이 계약서에 포함되어 있습니다. 제11조(및 제12조)를 꼭 확인해 주세요.
            </div>
          ) : null}

          <ContractDocumentViewer
            html={state?.contractHtml ?? ''}
            title="CPA 광고 제휴 계약서"
            documentPreviewUrl={state?.documentPreviewUrl}
            documentPdfUrl={state?.documentPdfUrl}
            maxHeight="70vh"
          />

          <div className="space-y-3 border-t border-slate-200 pt-4">
            {CONTRACT_REQUIRED_AGREEMENTS.map(({ key, label }) => (
              <label key={key} className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  checked={form.agreements[key]}
                  onChange={(e) =>
                    updateForm({
                      agreements: { ...form.agreements, [key]: e.target.checked },
                    })
                  }
                  className="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600"
                />
                <span className="text-sm text-slate-700">{label}</span>
              </label>
            ))}
            <FieldError message={errors.readAll || errors.hasAuthority || errors.electronic || errors.noModify} />
          </div>
        </section>
      ) : null}

      {step === 4 ? (
        <section className="bg-white border border-slate-200 rounded-2xl p-5 md:p-8 shadow-sm space-y-6">
          <div>
            <h2 className="text-lg font-bold text-slate-900 mb-1">4단계: 계약 담당자 입력 및 서명</h2>
            <p className="text-sm text-slate-500">계약 체결 권한을 가진 담당자 정보와 서명을 입력해 주세요.</p>
          </div>

          <div className="grid md:grid-cols-2 gap-4">
            <TextField label="계약 담당자 이름 *" value={form.signerName} onChange={(v) => updateForm({ signerName: v })} error={errors.signerName} />
            <TextField label="직책 *" value={form.signerPosition} onChange={(v) => updateForm({ signerPosition: v })} error={errors.signerPosition} />
            <TextField label="휴대전화번호 *" value={form.signerPhone} onChange={(v) => updateForm({ signerPhone: v })} error={errors.signerPhone} />
            <TextField label="이메일 *" type="email" value={form.signerEmail} onChange={(v) => updateForm({ signerEmail: v })} error={errors.signerEmail} />
          </div>

          <div>
            <p className="text-sm font-medium text-slate-700 mb-2">서명 *</p>
            <SignatureCanvas onChange={handleSignatureChange} disabled={submitting} />
            <FieldError message={errors.signature} />
            {hasSignature && state?.hasSignature ? (
              <p className="text-xs text-slate-500 mt-2">서명이 서버에 임시 저장되었습니다.</p>
            ) : null}
          </div>

          {submitError ? (
            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{submitError}</div>
          ) : null}

          <button
            type="button"
            disabled={submitting}
            onClick={() => {
              const stepErrors = validateStep4(form, hasSignature);
              setErrors(stepErrors);
              if (Object.keys(stepErrors).length === 0) {
                setShowConfirm(true);
              }
            }}
            className="w-full md:w-auto px-6 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 disabled:opacity-60 text-white font-bold"
          >
            {submitting ? '처리 중...' : '계약 내용을 확인하고 승인 요청합니다'}
          </button>
        </section>
      ) : null}

      <div className="flex items-center justify-between mt-6">
        <button
          type="button"
          onClick={goPrev}
          disabled={step === 1 || saving || submitting}
          className="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 disabled:opacity-50"
        >
          이전
        </button>
        <div className="flex items-center gap-3">
          {saving ? <span className="text-xs text-slate-500">저장 중...</span> : null}
          {step < 4 ? (
            <button
              type="button"
              onClick={() => void goNext()}
              disabled={saving || submitting}
              className="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-semibold disabled:opacity-60"
            >
              다음
            </button>
          ) : null}
        </div>
      </div>

      {showConfirm ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
          <div className="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-xl p-6">
            <h3 className="text-lg font-bold text-slate-900 mb-3">계약 승인 요청 전 확인</h3>
            <p className="text-sm text-slate-600 leading-relaxed mb-6">
              승인 요청 후에는 관리자가 검토를 완료할 때까지 수정할 수 없습니다.
              <br />
              입력한 광고주 정보와 계약 내용을 다시 확인해 주세요.
            </p>
            <div className="flex flex-col sm:flex-row gap-2">
              <button
                type="button"
                disabled={submitting}
                onClick={() => setShowConfirm(false)}
                className="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700"
              >
                다시 확인하기
              </button>
              <button
                type="button"
                disabled={submitting}
                onClick={() => void handleSignSubmit()}
                className="flex-1 px-4 py-2.5 rounded-xl bg-cyan-600 text-white font-semibold disabled:opacity-60"
              >
                {submitting ? '요청 중...' : '승인 요청하기'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </AdvertiserContractLayout>
  );
}
