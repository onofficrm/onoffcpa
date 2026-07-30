import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard } from '../../components/admin/AdminShared';
import { ContractDocumentViewer } from '../../components/contract/ContractDocumentViewer';
import { ContractProcessGuide } from '../../components/contract/ContractProcessGuide';
import {
  addAdminContractAddendum,
  applyAdminCustomContractDocument,
  fetchAdminContractDetail,
  fetchAdminContracts,
  seedAdminDemoContract,
  updateAdminContractStatus,
  voidAdminContractAddendum,
  type AdminContractDetail,
  type AdminContractListItem,
  type AdminContractSummary,
} from '../../lib/api';
import { FileText, Search, X, Clock, CheckCircle2, AlertTriangle, Sparkles, FilePlus2 } from 'lucide-react';

type DetailTab = 'document' | 'admin';

const emptySummary: AdminContractSummary = {
  total: 0,
  signed: 0,
  reviewPending: 0,
  rejected: 0,
  pending: 0,
  inProgress: 0,
  cancelled: 0,
  expired: 0,
  renewal: 0,
};

const STATUS_OPTIONS = [
  ['', '전체'],
  ['unsigned', '미체결'],
  ['pending', '대기'],
  ['in_progress', '작성 중'],
  ['review_pending', '승인 대기'],
  ['rejected', '승인 반려'],
  ['signed', '승인 완료'],
  ['cancelled', '취소'],
  ['expired', '만료'],
  ['renewal', '재계약 필요'],
] as const;

export function AdminContracts() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [items, setItems] = useState<AdminContractListItem[]>([]);
  const [summary, setSummary] = useState<AdminContractSummary>(emptySummary);
  const [currentVersion, setCurrentVersion] = useState('');
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [detail, setDetail] = useState<AdminContractDetail | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const statusFromUrl = searchParams.get('status') || '';
  const statusFilter = STATUS_OPTIONS.some(([value]) => value === statusFromUrl) ? statusFromUrl : '';
  const [versionFilter, setVersionFilter] = useState('');
  const [signedFrom, setSignedFrom] = useState('');
  const [signedTo, setSignedTo] = useState('');
  const [mtIdFilter, setMtIdFilter] = useState('');
  const [statusReason, setStatusReason] = useState('');
  const [loading, setLoading] = useState(true);
  const [detailLoading, setDetailLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [seeding, setSeeding] = useState(false);
  const [seedMessage, setSeedMessage] = useState('');
  const [applyingCustom, setApplyingCustom] = useState(false);
  const [error, setError] = useState('');
  const [detailTab, setDetailTab] = useState<DetailTab>('document');
  const [addendumTitle, setAddendumTitle] = useState('특약사항');
  const [addendumBody, setAddendumBody] = useState('');
  const [addendumSaving, setAddendumSaving] = useState(false);
  const hasLoadedRef = useRef(false);
  const loadSeqRef = useRef(0);

  const selectedItem = useMemo(
    () => items.find((item) => item.id === selectedId) ?? null,
    [items, selectedId],
  );

  const setStatusFilter = useCallback(
    (value: string) => {
      const current = searchParams.get('status') || '';
      const upcoming = value || '';
      if (current === upcoming) return;
      const next = new URLSearchParams(searchParams);
      if (value) {
        next.set('status', value);
      } else {
        next.delete('status');
      }
      setSearchParams(next, { replace: true });
    },
    [searchParams, setSearchParams],
  );

  const load = useCallback(async (opts?: { soft?: boolean }) => {
    const soft = opts?.soft ?? hasLoadedRef.current;
    const seq = ++loadSeqRef.current;
    if (!soft) {
      setLoading(true);
    }
    setError('');
    try {
      const data = await fetchAdminContracts({
        q: searchQuery,
        status: statusFilter,
        version: versionFilter,
        mtId: mtIdFilter ? Number(mtIdFilter) : undefined,
        signedFrom,
        signedTo,
      });
      if (seq !== loadSeqRef.current) return;
      setItems(data.items);
      setSummary(data.summary);
      setCurrentVersion(data.currentVersion);
      hasLoadedRef.current = true;
    } catch (err) {
      if (seq !== loadSeqRef.current) return;
      setError(err instanceof Error ? err.message : '계약 목록을 불러오지 못했습니다.');
    } finally {
      if (seq === loadSeqRef.current) {
        setLoading(false);
      }
    }
  }, [searchQuery, statusFilter, versionFilter, mtIdFilter, signedFrom, signedTo]);

  const loadDetail = useCallback(async (mcId: number) => {
    setDetailLoading(true);
    setError('');
    try {
      const data = await fetchAdminContractDetail(mcId);
      setDetail(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : '계약 상세를 불러오지 못했습니다.');
      setDetail(null);
    } finally {
      setDetailLoading(false);
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setSearchQuery(searchInput.trim());
    }, 300);
    return () => window.clearTimeout(timer);
  }, [searchInput]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (selectedId) {
      setDetailTab('document');
      void loadDetail(selectedId);
    } else {
      setDetail(null);
    }
  }, [selectedId, loadDetail]);

  const handleStatusAction = async (action: 'approve' | 'reject' | 'cancel' | 'expire' | 'requireRenewal') => {
    if (!selectedId || (action !== 'approve' && !statusReason.trim())) {
      setError('상태 변경 사유를 입력해 주세요.');
      return;
    }
    const confirmMessage =
      action === 'approve'
        ? '계약서를 승인하시겠습니까? 승인 즉시 광고주가 광고를 등록할 수 있습니다.'
        : action === 'reject'
          ? '계약서를 반려하시겠습니까? 광고주는 수정 후 다시 요청할 수 있습니다.'
          : '계약 상태를 변경하시겠습니까? 체결 내용은 수정되지 않습니다.';
    if (!window.confirm(confirmMessage)) {
      return;
    }
    setSaving(true);
    setError('');
    try {
      const result = await updateAdminContractStatus({
        mcId: selectedId,
        action,
        reason: statusReason.trim(),
      });
      setDetail(result.detail);
      setStatusReason('');
      await load({ soft: true });
    } catch (err) {
      setError(err instanceof Error ? err.message : '상태 변경에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  const handleSeedDemo = async () => {
    if (!window.confirm('데모광고주(lc_advertiser)에 샘플 CPA 계약을 생성합니다. 이미 체결된 경우 건너뜁니다.')) {
      return;
    }
    setSeeding(true);
    setSeedMessage('');
    setError('');
    try {
      const result = await seedAdminDemoContract();
      setSeedMessage(result.message);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : '샘플 계약 생성에 실패했습니다.');
    } finally {
      setSeeding(false);
    }
  };

  const handleApplyModuicheolge = async () => {
    if (
      !window.confirm(
        'ADV-0008(김장수/모두의철거)에 「모두의철거 x 링크커넥트 계약서」를 적용하고 체결 완료 처리할까요?\n이미 체결된 경우 강제로 덮어씁니다.',
      )
    ) {
      return;
    }
    setApplyingCustom(true);
    setSeedMessage('');
    setError('');
    try {
      const result = await applyAdminCustomContractDocument({
        mtCode: 'ADV-0008',
        documentKey: 'adv-0008-moduicheolge',
        force: true,
      });
      setSeedMessage(result.message);
      await load();
      if (result.mcId) {
        setSelectedId(result.mcId);
      } else if (result.detail?.listItem?.id) {
        setSelectedId(result.detail.listItem.id);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : '첨부 계약서 적용에 실패했습니다.');
    } finally {
      setApplyingCustom(false);
    }
  };

  const handleAddAddendum = async () => {
    if (!selectedId || !addendumBody.trim()) {
      setError('특약 내용을 입력해 주세요.');
      return;
    }
    if (!window.confirm('원 계약은 유지한 채 특약(부속)을 추가합니다. 계속할까요?')) {
      return;
    }
    setAddendumSaving(true);
    setError('');
    try {
      const result = await addAdminContractAddendum({
        mcId: selectedId,
        title: addendumTitle.trim() || '특약사항',
        body: addendumBody.trim(),
      });
      setDetail(result.detail);
      setAddendumBody('');
      setAddendumTitle('특약사항');
      setDetailTab('document');
    } catch (err) {
      setError(err instanceof Error ? err.message : '특약 추가에 실패했습니다.');
    } finally {
      setAddendumSaving(false);
    }
  };

  const handleVoidAddendum = async (addendumId: number) => {
    const reason = window.prompt('특약 무효 사유를 입력하세요.');
    if (reason === null) return;
    setAddendumSaving(true);
    setError('');
    try {
      const result = await voidAdminContractAddendum({ addendumId, reason: reason.trim() });
      if (result.detail) {
        setDetail(result.detail);
      } else if (selectedId) {
        await loadDetail(selectedId);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : '특약 무효 처리에 실패했습니다.');
    } finally {
      setAddendumSaving(false);
    }
  };

  return (
    <AdminLayout activeMenu="contracts" title="광고주 계약 관리" description="광고주 계약서 승인 요청을 검토하고 승인 또는 반려합니다.">
      <ContractProcessGuide audience="admin" className="mb-6" />

      <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p className="text-sm text-slate-600">
          광고주가 작성·서명한 계약서를 검토하여 <b>승인 또는 반려</b>해 주세요. 승인 즉시 광고 등록이 가능합니다.
        </p>
        <div className="flex flex-col sm:flex-row gap-2 shrink-0">
          <button
            type="button"
            disabled={applyingCustom}
            onClick={() => void handleApplyModuicheolge()}
            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold disabled:opacity-50"
          >
            <FilePlus2 size={16} />
            {applyingCustom ? '적용 중...' : 'ADV-0008 첨부계약 적용'}
          </button>
          <button
            type="button"
            disabled={seeding}
            onClick={() => void handleSeedDemo()}
            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold disabled:opacity-50"
          >
            <Sparkles size={16} />
            {seeding ? '생성 중...' : '데모광고주 샘플 계약 생성'}
          </button>
        </div>
      </div>

      {seedMessage ? (
        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{seedMessage}</div>
      ) : null}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-9 gap-4 mb-8">
        {(
          [
            ['', '전체', summary.total, undefined, <FileText size={18} key="all" />],
            ['review_pending', '승인 대기', summary.reviewPending, 'yellow', <Clock size={18} key="rp" />],
            ['signed', '승인 완료', summary.signed, 'emerald', <CheckCircle2 size={18} key="sg" />],
            ['rejected', '반려', summary.rejected, 'red', <AlertTriangle size={18} key="rj" />],
            ['pending', '미체결', summary.pending, 'yellow', <Clock size={18} key="pd" />],
            ['in_progress', '작성 중', summary.inProgress, undefined, <Clock size={18} key="ip" />],
            ['cancelled', '취소', summary.cancelled, 'red', <AlertTriangle size={18} key="cx" />],
            ['expired', '만료', summary.expired, undefined, undefined],
            ['renewal', '재계약', summary.renewal, 'amber', undefined],
          ] as const
        ).map(([value, title, count, color, icon]) => (
          <button
            key={value || 'all'}
            type="button"
            onClick={() => setStatusFilter(value)}
            className={`text-left rounded-2xl transition ring-offset-2 ${statusFilter === value ? 'ring-2 ring-cyan-500' : 'hover:opacity-90'}`}
          >
            <SummaryCard
              title={title}
              value={count.toLocaleString()}
              suffix="건"
              color={color}
              icon={icon}
            />
          </button>
        ))}
      </div>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      <div className="grid lg:grid-cols-5 gap-6">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-4 border-b border-slate-100 space-y-3">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <input
                value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                placeholder="회사명, ADV-코드, 아이디, 사업자번호, 계약번호"
                className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl text-sm"
              />
            </div>
            <div className="grid grid-cols-2 gap-2">
              <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="px-3 py-2 border border-slate-200 rounded-xl text-sm">
                {STATUS_OPTIONS.map(([value, label]) => (
                  <option key={value || 'all'} value={value}>{label}</option>
                ))}
              </select>
              <input
                value={versionFilter}
                onChange={(e) => setVersionFilter(e.target.value)}
                placeholder={`버전 (예: ${currentVersion || 'CPA-CONTRACT-2026-07'})`}
                className="px-3 py-2 border border-slate-200 rounded-xl text-sm"
              />
              <input value={mtIdFilter} onChange={(e) => setMtIdFilter(e.target.value)} placeholder="광고주 ID" className="px-3 py-2 border border-slate-200 rounded-xl text-sm" />
              <input type="date" value={signedFrom} onChange={(e) => setSignedFrom(e.target.value)} className="px-3 py-2 border border-slate-200 rounded-xl text-sm" />
              <input type="date" value={signedTo} onChange={(e) => setSignedTo(e.target.value)} className="px-3 py-2 border border-slate-200 rounded-xl text-sm col-span-2" />
            </div>
          </div>

          <div className="max-h-[65vh] overflow-y-auto divide-y divide-slate-100">
            {loading && items.length === 0 ? (
              <p className="p-6 text-sm text-slate-500">불러오는 중...</p>
            ) : items.length === 0 ? (
              <div className="p-6 text-sm text-slate-600 space-y-3">
                <p className="font-semibold text-slate-800">아직 등록된 계약 요청이 없습니다.</p>
                <p className="leading-relaxed">
                  광고주가 <b>광고주센터 → 계약서 작성</b>에서 작성·서명 후 승인 요청하면 이 목록에 표시됩니다.
                </p>
                <p className="text-xs text-slate-500">
                  데모 테스트: 위 「데모광고주 샘플 계약 생성」 버튼으로 lc_advertiser 계정에 샘플 계약을 바로 넣을 수 있습니다.
                </p>
              </div>
            ) : (
              items.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  onClick={() => setSelectedId(item.id)}
                  className={`w-full text-left p-4 hover:bg-slate-50 transition-colors ${selectedId === item.id ? 'bg-cyan-50' : ''}`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <p className="font-semibold text-slate-900">{item.companyName || '(회사명 없음)'}</p>
                      <p className="text-xs text-slate-500 mt-1">
                        {item.advertiserCode ? `${item.advertiserCode} · ` : ''}
                        광고주 #{item.advertiserId} · {item.contractVersion}
                      </p>
                      <p className="text-xs text-slate-500 font-mono mt-1">{item.contractCode || '-'}</p>
                    </div>
                    <span
                      className={`text-xs px-2 py-1 rounded-full whitespace-nowrap ${
                        item.status === 'review_pending'
                          ? 'bg-amber-100 text-amber-800 font-semibold'
                          : 'bg-slate-100 text-slate-700'
                      }`}
                    >
                      {item.statusLabel}
                    </span>
                  </div>
                </button>
              ))
            )}
          </div>
        </div>

        <div className="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          {!selectedItem ? (
            <div className="p-10 text-center text-slate-500 text-sm">목록에서 계약을 선택하세요.</div>
          ) : detailLoading || !detail ? (
            <div className="p-10 text-center text-slate-500 text-sm">{detailLoading ? '상세 불러오는 중...' : '상세 정보 없음'}</div>
          ) : (
            <div className="max-h-[80vh] overflow-y-auto">
              <div className="p-5 border-b border-slate-100 flex items-start justify-between gap-4">
                <div>
                  <div className="flex flex-wrap items-center gap-2 mb-1">
                    <h2 className="text-lg font-bold text-slate-900">{detail.contract.companyName}</h2>
                    <span className="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-700">{detail.contract.statusLabel}</span>
                  </div>
                  <p className="text-sm text-slate-500">
                    {detail.merchant?.code ? `${detail.merchant.code} · ` : ''}
                    광고주 #{detail.listItem.advertiserId} · {detail.contract.contractVersion}
                  </p>
                  <p className="text-sm font-mono text-slate-700 mt-1">{detail.contract.contractCode}</p>
                </div>
                <button type="button" onClick={() => setSelectedId(null)} className="p-2 text-slate-400 hover:text-slate-700">
                  <X size={18} />
                </button>
              </div>

              {detail.contract.status === 'review_pending' ? (
                <div className="sticky top-0 z-10 border-b border-emerald-200 bg-emerald-50 px-5 py-4">
                  <p className="text-sm font-bold text-emerald-900 mb-1">다음 단계: 계약 승인 또는 반려</p>
                  <p className="text-xs text-emerald-800/80 mb-3">
                    계약서·서명을 확인한 뒤 승인하면 광고주가 바로 광고를 등록할 수 있습니다. 반려 시에는 사유가 필요합니다.
                  </p>
                  <textarea
                    value={statusReason}
                    onChange={(e) => setStatusReason(e.target.value)}
                    placeholder="반려·취소 등 상태 변경 시 사유를 입력하세요. (승인만 할 때는 생략 가능)"
                    className="w-full min-h-[64px] px-3 py-2 border border-emerald-200 rounded-xl text-sm mb-3 bg-white"
                  />
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      disabled={saving}
                      onClick={() => void handleStatusAction('approve')}
                      className="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold disabled:opacity-50"
                    >
                      {saving ? '처리 중...' : '계약 승인'}
                    </button>
                    <button
                      type="button"
                      disabled={saving || !statusReason.trim()}
                      onClick={() => void handleStatusAction('reject')}
                      className="px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold disabled:opacity-50"
                    >
                      계약 반려
                    </button>
                    <button
                      type="button"
                      onClick={() => setDetailTab('admin')}
                      className="px-4 py-2.5 rounded-lg border border-emerald-300 bg-white text-emerald-800 text-sm font-semibold"
                    >
                      관리 정보 보기
                    </button>
                  </div>
                </div>
              ) : null}

              <div className="px-5 pt-4 border-b border-slate-100">
                <div className="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                  <button
                    type="button"
                    onClick={() => setDetailTab('document')}
                    className={`px-4 py-2 rounded-lg text-sm font-semibold transition-colors ${detailTab === 'document' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
                  >
                    계약서 문서
                  </button>
                  <button
                    type="button"
                    onClick={() => setDetailTab('admin')}
                    className={`px-4 py-2 rounded-lg text-sm font-semibold transition-colors ${detailTab === 'admin' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
                  >
                    관리 정보
                  </button>
                </div>
              </div>

              <div className="p-5 space-y-6">
                {detailTab === 'document' ? (
                  <ContractDocumentViewer
                    html={detail.contract.contractHtml}
                    title="CPA 광고 제휴 계약서"
                    contractCode={detail.contract.contractCode}
                    signedAt={detail.contract.signedAt}
                    signatureUrl={detail.signatureUrl}
                    documentPreviewUrl={detail.documentPreviewUrl}
                    documentPdfUrl={['review_pending', 'rejected', 'signed'].includes(detail.contract.status) ? detail.documentPdfUrl : undefined}
                    maxHeight="75vh"
                  />
                ) : (
                <>
                <section>
                  <h3 className="font-bold text-slate-900 mb-3">회사정보 비교</h3>
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm border border-slate-200 rounded-xl overflow-hidden">
                      <thead className="bg-slate-50">
                        <tr>
                          <th className="px-3 py-2 text-left">항목</th>
                          <th className="px-3 py-2 text-left">계약 당시</th>
                          <th className="px-3 py-2 text-left">현재</th>
                        </tr>
                      </thead>
                      <tbody>
                        {(
                          [
                            ['companyName', '회사명'],
                            ['representativeName', '대표자명'],
                            ['businessNumber', '사업자등록번호'],
                            ['companyAddress', '사업장 주소'],
                            ['companyPhone', '회사 연락처'],
                          ] as const
                        ).map(([key, label]) => {
                          const row = detail.companyCompare[key];
                          if (!row) return null;
                          return (
                          <tr key={key} className={row.changed ? 'bg-amber-50' : ''}>
                            <td className="px-3 py-2 border-t border-slate-100">{label}</td>
                            <td className="px-3 py-2 border-t border-slate-100">{row.contract || '-'}</td>
                            <td className="px-3 py-2 border-t border-slate-100">{row.current || '-'}</td>
                          </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                </section>

                <section className="grid sm:grid-cols-2 gap-3 text-sm">
                  <Info label="계약 담당자" value={`${detail.contract.signerName} (${detail.contract.signerPosition})`} />
                  <Info label="승인 요청일시" value={detail.contract.signedAt} />
                  <Info label="요청 IP" value={detail.contract.signedIp} />
                  <Info label="PDF 해시" value={detail.contract.pdfHashMasked} />
                  <Info label="User-Agent" value={detail.contract.userAgent} />
                  <Info label="등록일" value={detail.contract.createdAt} />
                </section>

                {(detail.contract.negotiatedTerms?.trim() || detail.contract.specialClauses?.trim()) ? (
                  <section className="space-y-3">
                    <h3 className="font-bold text-slate-900">별도 협의·특별조항</h3>
                    {detail.contract.negotiatedTerms?.trim() ? (
                      <div>
                        <p className="text-xs font-semibold text-slate-500 mb-1">별도 협의사항</p>
                        <pre className="text-sm text-slate-800 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-3">
                          {detail.contract.negotiatedTerms}
                        </pre>
                      </div>
                    ) : null}
                    {detail.contract.specialClauses?.trim() ? (
                      <div>
                        <p className="text-xs font-semibold text-amber-700 mb-1">특별조항</p>
                        <pre className="text-sm text-slate-800 whitespace-pre-wrap rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                          {detail.contract.specialClauses}
                        </pre>
                      </div>
                    ) : null}
                  </section>
                ) : (
                  <section>
                    <h3 className="font-bold text-slate-900 mb-1">별도 협의·특별조항</h3>
                    <p className="text-sm text-slate-500">추가 협의사항·특별조항 없음</p>
                  </section>
                )}

                {detail.signatureUrl ? (
                  <section>
                    <h3 className="font-bold text-slate-900 mb-2">서명 이미지</h3>
                    <img src={detail.signatureUrl} alt="서명" className="max-h-28 border rounded-lg bg-white p-2" />
                  </section>
                ) : null}

                {detail.history.length > 1 ? (
                  <section>
                    <h3 className="font-bold text-slate-900 mb-2">광고주 계약 이력</h3>
                    <ul className="text-sm space-y-1">
                      {detail.history.map((h) => (
                        <li key={h.id} className="flex justify-between gap-2 border-b border-slate-100 py-1">
                          <span>{h.contractVersion} · {h.contractCode || '-'}</span>
                          <span className="text-slate-500">{h.statusLabel} · {h.signedAt || h.createdAt}</span>
                        </li>
                      ))}
                    </ul>
                  </section>
                ) : null}

                <section className="space-y-3">
                  <div>
                    <h3 className="font-bold text-slate-900 mb-1">체결 후 특약(부속)</h3>
                    <p className="text-xs text-slate-500">
                      원 계약서·PDF는 변경하지 않고, 특약을 부속으로 추가합니다. 충돌 시 특약이 우선합니다.
                    </p>
                  </div>
                  {(detail.addenda ?? []).length > 0 ? (
                    <ul className="space-y-2">
                      {(detail.addenda ?? []).map((item) => (
                        <li
                          key={item.id}
                          className={`rounded-xl border p-3 ${item.status === 'active' ? 'border-cyan-200 bg-cyan-50/40' : 'border-slate-200 bg-slate-50 opacity-70'}`}
                        >
                          <div className="flex items-start justify-between gap-2">
                            <div>
                              <p className="text-sm font-bold text-slate-900">
                                특약 {item.seq}. {item.title}
                                {item.status !== 'active' ? (
                                  <span className="ml-2 text-xs font-semibold text-slate-500">무효</span>
                                ) : null}
                              </p>
                              <p className="text-xs text-slate-500 mt-0.5">
                                {item.createdByType === 'merchant' ? '광고주' : '관리자'}
                                {item.createdBy ? ` (${item.createdBy})` : ''} · {item.createdAt}
                              </p>
                            </div>
                            {item.status === 'active' ? (
                              <button
                                type="button"
                                disabled={addendumSaving}
                                onClick={() => handleVoidAddendum(item.id)}
                                className="text-xs font-semibold text-red-600 hover:underline disabled:opacity-50"
                              >
                                무효
                              </button>
                            ) : null}
                          </div>
                          <pre className="mt-2 text-sm text-slate-800 whitespace-pre-wrap">{item.body}</pre>
                          {item.status === 'void' && item.voidReason ? (
                            <p className="mt-1 text-xs text-slate-500">무효 사유: {item.voidReason}</p>
                          ) : null}
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-sm text-slate-500">등록된 특약이 없습니다.</p>
                  )}
                  {detail.contract.canAddAddendum ? (
                    <div className="rounded-xl border border-dashed border-cyan-300 bg-white p-3 space-y-2">
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
                        placeholder="체결 후 추가할 특약 내용을 입력하세요."
                        className="w-full min-h-[100px] px-3 py-2 border border-slate-200 rounded-lg text-sm"
                      />
                      <button
                        type="button"
                        disabled={addendumSaving || !addendumBody.trim()}
                        onClick={() => void handleAddAddendum()}
                        className="px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-bold disabled:opacity-50"
                      >
                        {addendumSaving ? '저장 중...' : '특약 추가'}
                      </button>
                    </div>
                  ) : (
                    <p className="text-xs text-slate-500">승인 완료(또는 승인 대기) 계약에서만 특약을 추가할 수 있습니다.</p>
                  )}
                </section>

                <section>
                  <h3 className="font-bold text-slate-900 mb-2">계약 승인 관리</h3>
                  {detail.contract.status === 'review_pending' ? (
                    <div className="mb-3 flex flex-wrap gap-2">
                      <button type="button" disabled={saving} onClick={() => handleStatusAction('approve')} className="px-5 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-bold disabled:opacity-50">
                        계약 승인
                      </button>
                      <button type="button" disabled={saving || !statusReason.trim()} onClick={() => handleStatusAction('reject')} className="px-5 py-2.5 rounded-lg bg-red-600 text-white text-sm font-bold disabled:opacity-50">
                        계약 반려
                      </button>
                    </div>
                  ) : null}
                  <textarea
                    value={statusReason}
                    onChange={(e) => setStatusReason(e.target.value)}
                    placeholder="상태 변경 사유를 입력하세요."
                    className="w-full min-h-[80px] px-3 py-2 border border-slate-200 rounded-xl text-sm mb-3"
                  />
                  <div className="flex flex-wrap gap-2">
                    <button type="button" disabled={saving} onClick={() => handleStatusAction('cancel')} className="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold disabled:opacity-50">
                      계약 취소
                    </button>
                    <button type="button" disabled={saving} onClick={() => handleStatusAction('expire')} className="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm font-semibold disabled:opacity-50">
                      계약 만료
                    </button>
                    <button type="button" disabled={saving} onClick={() => handleStatusAction('requireRenewal')} className="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold disabled:opacity-50">
                      재계약 필요
                    </button>
                  </div>
                </section>

                {detail.statusLogs.length > 0 ? (
                  <section>
                    <h3 className="font-bold text-slate-900 mb-2">상태 변경 감사 로그</h3>
                    <ul className="text-xs space-y-2">
                      {detail.statusLogs.map((log) => (
                        <li key={log.id} className="border border-slate-200 rounded-lg p-3 bg-slate-50">
                          <p><strong>{log.oldLabel}</strong> → <strong>{log.newLabel}</strong> · {log.createdAt}</p>
                          <p className="text-slate-600 mt-1">관리자: {log.adminId || '-'} · IP: {log.ip}</p>
                          <p className="text-slate-700 mt-1">사유: {log.reason}</p>
                        </li>
                      ))}
                    </ul>
                  </section>
                ) : null}

                </>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border border-slate-200 p-3 bg-slate-50">
      <dt className="text-xs text-slate-500">{label}</dt>
      <dd className="font-medium text-slate-900 mt-1 break-all">{value || '-'}</dd>
    </div>
  );
}
