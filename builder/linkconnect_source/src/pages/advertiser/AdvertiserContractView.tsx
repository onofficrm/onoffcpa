import React, { useEffect, useState } from 'react';
import { Link, Navigate, useSearchParams } from 'react-router-dom';
import { FileText, Loader2 } from 'lucide-react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { ContractDocumentViewer } from '../../components/contract/ContractDocumentViewer';
import { CONTRACT_REQUIRED_AGREEMENTS } from '../../components/advertiser/contract/contractForm';
import {
  addMerchantContractAddendum,
  fetchMerchantContractRead,
  type MerchantContractAddendum,
  type MerchantContractCampaignItem,
  type MerchantContractRead,
  PartnerApiError,
} from '../../lib/api';
import { getLcAuth, isLcLoggedIn } from '../../lib/auth';
import { g5LoginUrl, spaReturnUrl } from '../../lib/urls';

function ReadOnlyField({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <label className="block text-xs font-medium text-slate-500 mb-1">{label}</label>
      <input
        type="text"
        readOnly
        disabled
        value={value || '-'}
        className="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 text-sm cursor-not-allowed"
      />
    </div>
  );
}

export function AdvertiserContractView() {
  const auth = getLcAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [contract, setContract] = useState<MerchantContractRead | null>(null);
  const [history, setHistory] = useState<Array<{ contractVersion: string; contractCode: string; signedAt: string }>>([]);
  const [campaigns, setCampaigns] = useState<MerchantContractCampaignItem[]>([]);
  const [selectedCampaign, setSelectedCampaign] = useState<MerchantContractCampaignItem | null>(null);
  const [addenda, setAddenda] = useState<MerchantContractAddendum[]>([]);
  const [csrfToken, setCsrfToken] = useState('');
  const [addendumTitle, setAddendumTitle] = useState('특약사항');
  const [addendumBody, setAddendumBody] = useState('');
  const [addendumSaving, setAddendumSaving] = useState(false);
  const [notSigned, setNotSigned] = useState(false);
  const [error, setError] = useState('');
  const [addendumError, setAddendumError] = useState('');
  const selectedVersion = searchParams.get('version') ?? '';
  const selectedCpId = Number(searchParams.get('cpId') || 0) || 0;

  useEffect(() => {
    if (!isLcLoggedIn()) {
      window.location.replace(g5LoginUrl(spaReturnUrl('/advertiser/contract/view')));
    }
  }, []);

  useEffect(() => {
    if (!auth.isMerchant) {
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    setNotSigned(false);
    fetchMerchantContractRead({
      version: selectedVersion || undefined,
      cpId: selectedCpId || undefined,
    })
      .then((data) => {
        setContract(data.contract);
        setHistory(data.history);
        setCampaigns(data.campaigns ?? []);
        setSelectedCampaign(data.campaign ?? null);
        setAddenda(data.addenda ?? []);
        setCsrfToken(data.csrfToken ?? '');
      })
      .catch((err) => {
        if (err instanceof PartnerApiError && (err.code === 'NOT_FOUND' || err.status === 404)) {
          setNotSigned(true);
          return;
        }
        setError(err instanceof Error ? err.message : '계약 정보를 불러오지 못했습니다.');
      })
      .finally(() => setLoading(false));
  }, [auth.isMerchant, selectedVersion, selectedCpId]);

  const handleAddAddendum = async () => {
    if (!contract?.canAddAddendum || !csrfToken || !addendumBody.trim()) {
      setAddendumError('특약 내용을 입력해 주세요.');
      return;
    }
    if (!window.confirm('원 계약은 유지한 채 특약을 추가합니다. 계속할까요?')) {
      return;
    }
    setAddendumSaving(true);
    setAddendumError('');
    try {
      const result = await addMerchantContractAddendum({
        csrfToken,
        title: addendumTitle.trim() || '특약사항',
        body: addendumBody.trim(),
        version: contract.contractVersion,
      });
      if (result.data?.contract) {
        setContract(result.data.contract);
        setAddenda(result.data.addenda ?? []);
        if (result.data.csrfToken) setCsrfToken(result.data.csrfToken);
      }
      setAddendumBody('');
      setAddendumTitle('특약사항');
    } catch (err) {
      setAddendumError(err instanceof Error ? err.message : '특약 추가에 실패했습니다.');
    } finally {
      setAddendumSaving(false);
    }
  };

  const updateParams = (next: { version?: string; cpId?: number | null }) => {
    const params: Record<string, string> = {};
    const version = next.version !== undefined ? next.version : selectedVersion;
    const cpId = next.cpId !== undefined ? next.cpId : selectedCpId;
    if (version) params.version = version;
    if (cpId && cpId > 0) params.cpId = String(cpId);
    setSearchParams(params);
  };

  if (!auth.loggedIn) {
    return (
      <AdvertiserLayout activeMenu="contract" title="계약정보">
        <p className="text-slate-600">로그인 페이지로 이동 중...</p>
      </AdvertiserLayout>
    );
  }

  if (!auth.isMerchant) {
    return (
      <AdvertiserLayout activeMenu="contract" title="계약정보">
        <div className="bg-white border border-slate-200 rounded-2xl p-6">
          <p className="text-slate-700">광고주 계정으로만 계약 정보를 열람할 수 있습니다.</p>
        </div>
      </AdvertiserLayout>
    );
  }

  if (!loading && notSigned) {
    return <Navigate to="/advertiser/contract" replace />;
  }

  if (loading) {
    return (
      <AdvertiserLayout activeMenu="contract" title="계약정보">
        <div className="flex items-center gap-2 text-slate-600">
          <Loader2 className="animate-spin" size={18} />
          불러오는 중...
        </div>
      </AdvertiserLayout>
    );
  }

  if (error || !contract) {
    return (
      <AdvertiserLayout activeMenu="contract" title="계약정보">
        <div className="bg-white border border-slate-200 rounded-2xl p-6">
          <p className="text-slate-700">{error || '계약 정보가 없습니다.'}</p>
          <Link to="/advertiser/contract" className="inline-block mt-4 text-sm text-cyan-700 hover:underline">
            계약서 작성으로 이동
          </Link>
        </div>
      </AdvertiserLayout>
    );
  }

  return (
    <AdvertiserLayout activeMenu="contract" title="계약정보" companyName={contract.companyName}>
      <div className="space-y-6">
        <p className="text-sm text-slate-500">
          체결된 CPA 계약서를 광고상품별로 확인할 수 있습니다. 상품을 선택하면 해당 상품 단가·조건이 부속으로 표시됩니다.
        </p>

        <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
            <h2 className="text-sm font-bold text-slate-900 flex items-center gap-2">
              <FileText size={16} className="text-cyan-600" />
              광고상품별 계약서
            </h2>
            <span className="text-xs text-slate-500">총 {campaigns.length}개 상품</span>
          </div>
          {campaigns.length === 0 ? (
            <div className="px-5 py-8 text-sm text-slate-500 text-center">
              등록된 광고상품이 없습니다.{' '}
              <Link to="/advertiser/campaigns" className="text-cyan-700 font-semibold hover:underline">
                내 광고상품
              </Link>
              에서 상품을 확인해 주세요.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                  <tr>
                    <th className="px-5 py-3 font-medium">광고상품</th>
                    <th className="px-5 py-3 font-medium text-center">유형</th>
                    <th className="px-5 py-3 font-medium text-right">단가</th>
                    <th className="px-5 py-3 font-medium text-center">계약</th>
                    <th className="px-5 py-3 font-medium text-center">보기</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {campaigns.map((camp) => {
                    const active = selectedCpId === camp.id;
                    return (
                      <tr key={camp.id} className={active ? 'bg-cyan-50/60' : 'hover:bg-slate-50'}>
                        <td className="px-5 py-3">
                          <div className="font-bold text-slate-900">{camp.name}</div>
                          <div className="text-xs text-slate-500 font-mono mt-0.5">{camp.code}</div>
                        </td>
                        <td className="px-5 py-3 text-center">
                          <span className="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs font-bold">{camp.type}</span>
                        </td>
                        <td className="px-5 py-3 text-right font-medium text-slate-700">{camp.cpa.toLocaleString()}원</td>
                        <td className="px-5 py-3 text-center">
                          <span className="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded">
                            {camp.contractStatusLabel || '체결완료'}
                          </span>
                        </td>
                        <td className="px-5 py-3 text-center">
                          <button
                            type="button"
                            onClick={() => updateParams({ cpId: camp.id })}
                            className={`inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold rounded-lg border transition-colors ${
                              active
                                ? 'bg-cyan-600 text-white border-cyan-600'
                                : 'text-cyan-700 bg-cyan-50 border-cyan-200 hover:bg-cyan-100'
                            }`}
                          >
                            <FileText size={13} />
                            계약서 보기
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </section>

        {selectedCampaign ? (
          <div className="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-900">
            <span className="font-bold">선택 광고상품:</span> {selectedCampaign.name}
            <span className="text-cyan-700/80"> · {selectedCampaign.code}</span>
            <span className="text-cyan-700/80"> · 단가 {selectedCampaign.cpa.toLocaleString()}원</span>
            <button
              type="button"
              onClick={() => updateParams({ cpId: null })}
              className="ml-3 text-xs font-semibold text-cyan-700 underline"
            >
              기본 계약서만 보기
            </button>
          </div>
        ) : null}

        <div className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
          <div className="flex flex-wrap items-end gap-4 justify-between">
            <div>
              <label className="block text-xs font-medium text-slate-500 mb-2">계약 이력 (버전)</label>
              <select
                value={contract.contractVersion}
                onChange={(e) => updateParams({ version: e.target.value })}
                className="min-w-[240px] px-3 py-2 rounded-xl border border-slate-300 text-sm bg-white"
              >
                {history.map((item) => (
                  <option key={item.contractVersion} value={item.contractVersion}>
                    {item.contractVersion} · {item.contractCode || '미발급'} · {item.signedAt || '-'}
                  </option>
                ))}
              </select>
            </div>
            <p className="text-xs text-slate-500 max-w-md">
              체결된 계약서는 읽기 전용입니다. 계약 당시 기록된 정보를 표시하며, 현재 회원정보와 다를 수 있습니다.
            </p>
          </div>

          <ContractDocumentViewer
            html={contract.contractHtml}
            title={selectedCampaign ? `CPA 광고 제휴 계약서 · ${selectedCampaign.name}` : 'CPA 광고 제휴 계약서'}
            contractCode={contract.contractCode}
            signedAt={contract.signedAt}
            signatureUrl={contract.signatureUrl}
            documentPreviewUrl={contract.documentPreviewUrl}
            documentPdfUrl={contract.documentPdfUrl}
          />
        </div>

        <section className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
          <h2 className="text-base font-bold text-slate-900">계약 요약 정보</h2>
          <div className="grid sm:grid-cols-2 gap-4">
            <ReadOnlyField label="계약 상태" value={contract.statusLabel} />
            <ReadOnlyField label="계약번호" value={contract.contractCode} />
            <ReadOnlyField label="계약서 버전" value={contract.contractVersion} />
            <ReadOnlyField label="계약 체결일시" value={contract.signedAt} />
            <ReadOnlyField label="회사명" value={contract.companyName} />
            <ReadOnlyField label="대표자명" value={contract.representativeName} />
            <ReadOnlyField label="사업자등록번호" value={contract.businessNumber} />
            <ReadOnlyField label="사업장 주소" value={contract.companyAddress} />
            <ReadOnlyField label="회사 연락처" value={contract.companyPhone} />
            <ReadOnlyField label="계약 담당자" value={contract.signerName} />
            <ReadOnlyField label="담당자 직책" value={contract.signerPosition} />
            <ReadOnlyField label="담당자 연락처" value={contract.signerPhone} />
            <ReadOnlyField label="담당자 이메일" value={contract.signerEmail} />
            <ReadOnlyField label="PDF 해시 (일부)" value={contract.pdfHashMasked} />
          </div>
          {(contract.negotiatedTerms?.trim() || contract.specialClauses?.trim()) ? (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-800">별도 협의·특별조항</h3>
              {contract.negotiatedTerms?.trim() ? (
                <div>
                  <p className="text-xs font-semibold text-slate-500 mb-1">별도 협의사항</p>
                  <pre className="text-sm text-slate-800 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-3">
                    {contract.negotiatedTerms}
                  </pre>
                </div>
              ) : null}
              {contract.specialClauses?.trim() ? (
                <div>
                  <p className="text-xs font-semibold text-slate-500 mb-1">특별조항</p>
                  <pre className="text-sm text-slate-800 whitespace-pre-wrap rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                    {contract.specialClauses}
                  </pre>
                </div>
              ) : null}
            </div>
          ) : null}

          <div className="space-y-3">
            <div>
              <h3 className="text-sm font-bold text-slate-800">체결 후 특약</h3>
              <p className="text-xs text-slate-500 mt-0.5">원 계약 이후 추가된 부속 합의입니다. 충돌 시 특약이 우선합니다.</p>
            </div>
            {addenda.filter((a) => a.status === 'active').length > 0 ? (
              <ul className="space-y-2">
                {addenda.map((item) => (
                  <li
                    key={item.id}
                    className={`rounded-xl border p-3 ${item.status === 'active' ? 'border-cyan-200 bg-cyan-50/40' : 'border-slate-200 bg-slate-50 opacity-60'}`}
                  >
                    <p className="text-sm font-bold text-slate-900">
                      특약 {item.seq}. {item.title}
                      {item.status !== 'active' ? <span className="ml-2 text-xs text-slate-500">무효</span> : null}
                    </p>
                    <p className="text-xs text-slate-500 mt-0.5">
                      {item.createdByType === 'merchant' ? '광고주' : '관리자'} · {item.createdAt}
                    </p>
                    <pre className="mt-2 text-sm text-slate-800 whitespace-pre-wrap">{item.body}</pre>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-slate-500">등록된 특약이 없습니다.</p>
            )}
            {contract.canAddAddendum ? (
              <div className="rounded-xl border border-dashed border-cyan-300 bg-white p-3 space-y-2">
                {addendumError ? (
                  <p className="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    {addendumError}
                  </p>
                ) : null}
                <input
                  type="text"
                  value={addendumTitle}
                  onChange={(e) => setAddendumTitle(e.target.value)}
                  placeholder="특약 제목"
                  className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
                />
                <textarea
                  value={addendumBody}
                  onChange={(e) => setAddendumBody(e.target.value)}
                  placeholder="추가할 특약 내용을 입력하세요."
                  className="w-full min-h-[100px] px-3 py-2 border border-slate-200 rounded-lg text-sm"
                />
                <button
                  type="button"
                  disabled={addendumSaving || !addendumBody.trim() || !csrfToken}
                  onClick={() => void handleAddAddendum()}
                  className="px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-bold disabled:opacity-50"
                >
                  {addendumSaving ? '저장 중...' : '특약 추가'}
                </button>
              </div>
            ) : null}
          </div>

          <div>
            <h3 className="text-sm font-bold text-slate-800 mb-2">동의 항목</h3>
            <ul className="space-y-1.5">
              {CONTRACT_REQUIRED_AGREEMENTS.map(({ key, label }) => (
                <li key={key} className="text-sm text-slate-700 flex items-start gap-2">
                  <span className={contract.agreements?.[key] ? 'text-emerald-600' : 'text-slate-400'}>
                    {contract.agreements?.[key] ? '✓' : '○'}
                  </span>
                  <span>{label}</span>
                </li>
              ))}
            </ul>
            {contract.agreementCheckedAt ? (
              <p className="text-xs text-slate-500 mt-2">동의 시각: {contract.agreementCheckedAt}</p>
            ) : null}
          </div>
        </section>
      </div>
    </AdvertiserLayout>
  );
}
