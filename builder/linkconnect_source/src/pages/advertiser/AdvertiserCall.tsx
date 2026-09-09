import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import {
  AlertTriangle,
  CheckCircle2,
  Headphones,
  PhoneCall,
  PhoneForwarded,
  PhoneIncoming,
  Save,
  Search,
  XCircle,
} from 'lucide-react';
import {
  CallLog,
  MerchantCallCampaign,
  cancelMerchantCallConversion,
  fetchMerchantCallCampaigns,
  fetchMerchantCallLogs,
  requestMerchantCallRecording,
  saveMerchantCallSettings,
} from '../../lib/api';
import { CallRecordingCell } from '../../components/call/CallRecordingCell';
import { DataTableEmpty, EmptyState, InsightBanner, SummaryCard, tableRowClass } from '../../components/center-ui';

type Draft = { enabled: boolean; alias: string; forward1: string; forward2: string };

const resultLabel: Record<string, { label: string; cls: string }> = {
  success: { label: '통화성공', cls: 'text-emerald-600' },
  missed: { label: '부재중', cls: 'text-amber-600' },
  busy: { label: '통화중', cls: 'text-slate-500' },
  fail: { label: '실패', cls: 'text-rose-600' },
};

function formatDuration(sec: number) {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}분 ${s}초`;
}

function formatPhone(phone?: string) {
  const digits = (phone ?? '').replace(/\D/g, '');
  if (digits.length === 11) return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
  if (digits.length === 10) return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`;
  return phone || '미설정';
}

export function AdvertiserCall() {
  const [items, setItems] = useState<MerchantCallCampaign[]>([]);
  const [logs, setLogs] = useState<CallLog[]>([]);
  const [logCpFilter, setLogCpFilter] = useState('');
  const [logNumberFilter, setLogNumberFilter] = useState('');
  const [drafts, setDrafts] = useState<Record<number, Draft>>({});
  const [saving, setSaving] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [cancelTarget, setCancelTarget] = useState<CallLog | null>(null);
  const [cancelReason, setCancelReason] = useState('');
  const [cancelComment, setCancelComment] = useState('');
  const [partnerVisible, setPartnerVisible] = useState(true);
  const [cancelBusy, setCancelBusy] = useState(false);
  const [cancelError, setCancelError] = useState('');

  const summary = useMemo(() => {
    const adminReady = items.filter((item) => item.adminEnabled).length;
    const enabled = items.filter((item) => drafts[item.cpId]?.enabled).length;
    const success = logs.filter((log) => log.result === 'success').length;
    const missed = logs.filter((log) => log.result === 'missed').length;
    const cancellable = logs.filter((log) => log.canCancel).length;
    return {
      total: items.length,
      adminReady,
      enabled,
      success,
      missed,
      totalCalls: logs.length,
      cancellable,
    };
  }, [drafts, items, logs]);

  const load = useCallback(() => {
    fetchMerchantCallCampaigns()
      .then((d) => {
        setItems(d.items);
        const next: Record<number, Draft> = {};
        d.items.forEach((c) => {
          next[c.cpId] = { enabled: c.enabled, alias: c.alias, forward1: c.forward1, forward2: c.forward2 };
        });
        setDrafts(next);
      })
      .catch(() => setItems([]));
  }, []);

  const loadLogs = useCallback(() => {
    fetchMerchantCallLogs({
      cpId: logCpFilter ? Number(logCpFilter) : undefined,
      virtualNumber: logNumberFilter || undefined,
    })
      .then((d) => setLogs(d.items))
      .catch(() => setLogs([]));
  }, [logCpFilter, logNumberFilter]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    loadLogs();
  }, [loadLogs]);

  const update = (cpId: number, patch: Partial<Draft>) => {
    setDrafts((prev) => ({ ...prev, [cpId]: { ...prev[cpId], ...patch } }));
  };

  const handleRecordingRequest = async (clogId: number, memo?: string) => {
    try {
      const res = await requestMerchantCallRecording({ clogId, memo });
      setMessage(res.message);
      loadLogs();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '녹음 요청에 실패했습니다.');
    }
  };

  const handleSave = async (cpId: number) => {
    const d = drafts[cpId];
    if (!d) return;
    setSaving(cpId);
    setMessage('');
    try {
      const res = await saveMerchantCallSettings({ cpId, enabled: d.enabled, alias: d.alias, forward1: d.forward1, forward2: d.forward2 });
      setMessage(res.message);
      load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '저장에 실패했습니다.');
    } finally {
      setSaving(null);
    }
  };

  const openCancel = (log: CallLog) => {
    setCancelTarget(log);
    setCancelReason('');
    setCancelComment('');
    setPartnerVisible(true);
    setCancelError('');
  };

  const handleCancelConfirm = async () => {
    if (!cancelTarget || !cancelReason) {
      setCancelError('취소 사유를 선택해 주세요.');
      return;
    }
    setCancelBusy(true);
    setCancelError('');
    try {
      const res = await cancelMerchantCallConversion({
        clogId: cancelTarget.clogId,
        reason: cancelReason,
        comment: cancelComment,
        partnerVisible,
      });
      setMessage(res.message);
      setCancelTarget(null);
      loadLogs();
    } catch (e) {
      setCancelError(e instanceof Error ? e.message : '취소 처리에 실패했습니다.');
    } finally {
      setCancelBusy(false);
    }
  };

  return (
    <AdvertiserLayout activeMenu="call" title="콜디비">
      <div className="space-y-6">
        <InsightBanner
          accent="cyan"
          message={<>콜디비 수신 상품 <strong>{summary.enabled}개</strong>, 최근 통화 <strong>{summary.totalCalls}건</strong>이 집계되었습니다.</>}
          subMessage="연결된 콜디비(신규접수)는 이 화면에서 CPA와 동일하게 취소할 수 있습니다. 상세 검수는 디비 확인에서도 가능합니다."
          actions={[{ label: '디비 확인', to: '/advertiser/db?source=call', variant: 'secondary' }]}
        />

        <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
          <SummaryCard title="전체 상품" value={summary.total} suffix="개" icon={<PhoneCall className="text-slate-500" />} caption="콜디비 설정 대상" />
          <SummaryCard title="관리자 활성" value={summary.adminReady} suffix="개" icon={<CheckCircle2 className="text-cyan-500" />} highlight color="cyan" caption="가상번호 배정 가능" />
          <SummaryCard title="수신 ON" value={summary.enabled} suffix="개" icon={<PhoneForwarded className="text-emerald-500" />} highlight color="emerald" caption="착신 운영 중" />
          <SummaryCard title="통화 성공" value={summary.success} suffix="건" icon={<PhoneIncoming className="text-blue-500" />} caption={`부재중 ${summary.missed}건`} />
          <SummaryCard title="취소 가능" value={summary.cancellable} suffix="건" icon={<XCircle className="text-rose-500" />} caption="신규접수 콜디비" />
        </div>

        {message && (
          <div className="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-800">
            {message}
          </div>
        )}

        {items.length === 0 ? (
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <EmptyState
              title="등록된 광고상품이 없습니다"
              description="광고상품이 생성되면 상품별 콜디비 수신 여부와 착신번호를 설정할 수 있습니다."
              actionLabel="내 광고상품 보기"
              actionTo="/advertiser/campaigns"
            />
          </div>
        ) : (
          <section className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="px-5 sm:px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <PhoneCall size={20} className="text-cyan-500" />
                  상품별 콜 운영 설정
                </h2>
                <p className="text-xs text-slate-500 mt-1">광고상품별 수신 상태와 착신번호를 관리합니다.</p>
              </div>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-4 p-4 sm:p-5 bg-slate-50/60">
            {items.map((c) => {
              const d = drafts[c.cpId] ?? { enabled: c.enabled, alias: c.alias, forward1: c.forward1, forward2: c.forward2 };
              const blocked = !c.adminEnabled;
              return (
                <div
                  key={c.cpId}
                  className={`relative bg-white rounded-2xl border shadow-sm p-5 transition-all ${
                    blocked ? 'border-slate-200 opacity-75' : d.enabled ? 'border-cyan-200 ring-1 ring-cyan-100' : 'border-slate-200'
                  }`}
                >
                  <div className="flex items-start justify-between gap-4 mb-4">
                    <div className="min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <div className="font-bold text-slate-900 truncate">{c.campaign}</div>
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border ${
                          blocked
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : d.enabled
                              ? 'bg-cyan-50 text-cyan-700 border-cyan-200'
                              : 'bg-slate-100 text-slate-600 border-slate-200'
                        }`}>
                          {blocked ? '관리자 대기' : d.enabled ? '수신 ON' : '수신 OFF'}
                        </span>
                      </div>
                      <div className="text-xs text-slate-400 font-mono mt-1">{c.code}</div>
                    </div>
                    <label className="inline-flex items-center gap-2 cursor-pointer shrink-0">
                      <span className="text-xs font-bold text-slate-500">수신</span>
                      <input
                        type="checkbox"
                        checked={d.enabled}
                        disabled={blocked}
                        onChange={(e) => update(c.cpId, { enabled: e.target.checked })}
                        className="w-5 h-5 accent-cyan-500"
                      />
                    </label>
                  </div>

                  {blocked && (
                    <div className="mb-4 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 flex items-center gap-2">
                      <AlertTriangle size={14} />
                      관리자가 이 상품의 콜디비를 아직 활성화하지 않았습니다.
                    </div>
                  )}

                  <div className="grid grid-cols-1 md:grid-cols-[1.2fr_1fr] gap-4">
                    <div>
                      <label className="block text-xs font-bold text-slate-500 mb-1">상품 별칭</label>
                      <input type="text" value={d.alias} disabled={blocked} onChange={(e) => update(c.cpId, { alias: e.target.value })} placeholder="예: 무료 상담 이벤트"
                        className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm disabled:bg-slate-100 focus:border-cyan-500 outline-none" />
                    </div>
                    <div className="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2">
                      <div className="text-[11px] font-bold text-slate-400 mb-1">녹음 방식</div>
                      <div className="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                        <Headphones size={15} className="text-cyan-500" />
                        {c.recordingMode === 'none' ? '녹음 안함' : c.recordingMode === 'both' ? '양방향 녹음' : '녹음'}
                      </div>
                    </div>
                    <div className="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs font-bold text-slate-500 mb-1">수신번호 1</label>
                        <input type="text" inputMode="tel" value={d.forward1} disabled={blocked} onChange={(e) => update(c.cpId, { forward1: e.target.value })} placeholder="01012345678"
                          className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono disabled:bg-slate-100 focus:border-cyan-500 outline-none" />
                      </div>
                      <div>
                        <label className="block text-xs font-bold text-slate-500 mb-1">수신번호 2</label>
                        <input type="text" inputMode="tel" value={d.forward2} disabled={blocked} onChange={(e) => update(c.cpId, { forward2: e.target.value })} placeholder="01087654321"
                          className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono disabled:bg-slate-100 focus:border-cyan-500 outline-none" />
                      </div>
                    </div>
                    <div className="md:col-span-2 flex items-center justify-between gap-3 pt-1">
                      <div className="text-xs text-slate-400">
                        수신: <span className="font-mono text-slate-600">{formatPhone(d.forward1)}</span>
                        {d.forward2 ? <span className="font-mono text-slate-500"> / {formatPhone(d.forward2)}</span> : null}
                      </div>
                      <button type="button" onClick={() => handleSave(c.cpId)} disabled={saving === c.cpId || blocked}
                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-sm disabled:opacity-50 shadow-sm">
                        <Save size={15} /> {saving === c.cpId ? '저장 중' : '저장'}
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
            </div>
          </section>
        )}

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 sm:px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center gap-3 justify-between">
            <div>
              <div className="flex items-center gap-2 font-bold text-slate-900">
                <PhoneIncoming size={20} className="text-cyan-500" />
                통화 내역
              </div>
              <p className="text-xs text-slate-500 mt-1">
                연결된 콜디비가 신규접수이면 「취소」로 CPA와 동일하게 처리할 수 있습니다.
              </p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <select value={logCpFilter} onChange={(e) => setLogCpFilter(e.target.value)} className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-cyan-500 outline-none">
                <option value="">전체 상품</option>
                {items.map((c) => <option key={c.cpId} value={c.cpId}>{c.campaign}</option>)}
              </select>
              <div className="relative">
                <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={logNumberFilter}
                  onChange={(e) => setLogNumberFilter(e.target.value)}
                  placeholder="가상번호 검색"
                  className="pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono w-44 focus:border-cyan-500 outline-none"
                />
              </div>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="bg-slate-50 text-slate-500 border-b border-slate-200">
                <tr>
                  <th className="px-5 py-3 font-medium">일시</th>
                  <th className="px-5 py-3 font-medium">상품</th>
                  <th className="px-5 py-3 font-medium">가상번호</th>
                  <th className="px-5 py-3 font-medium">발신번호</th>
                  <th className="px-5 py-3 font-medium">파트너</th>
                  <th className="px-5 py-3 font-medium text-center">통화시간</th>
                  <th className="px-5 py-3 font-medium text-center">결과</th>
                  <th className="px-5 py-3 font-medium text-center">디비</th>
                  <th className="px-5 py-3 font-medium text-center">녹음</th>
                  <th className="px-5 py-3 font-medium text-center">처리</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {logs.length === 0 ? (
                  <DataTableEmpty
                    colSpan={10}
                    title="통화 내역이 없습니다"
                    description="관리자가 콜업체 통화내역을 업로드하면 이곳에 표시됩니다."
                  />
                ) : logs.map((l) => {
                  const r = resultLabel[l.result] ?? { label: l.result, cls: 'text-slate-500' };
                  return (
                    <tr key={l.clogId} className={tableRowClass(undefined, l.result === 'missed')}>
                      <td className="px-5 py-4 text-slate-500 whitespace-nowrap">{l.startedAt}</td>
                      <td className="px-5 py-4 font-medium text-slate-900">{l.campaign || '—'}</td>
                      <td className="px-5 py-4 font-mono text-slate-700">{formatPhone(l.virtualNumber)}</td>
                      <td className="px-5 py-4 font-mono text-slate-600">{formatPhone(l.caller)}</td>
                      <td className="px-5 py-4">{l.partner || '—'}</td>
                      <td className="px-5 py-4 text-center">{formatDuration(l.duration)}</td>
                      <td className="px-5 py-4 text-center">
                        <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-50 border border-slate-200 ${r.cls}`}>
                          {r.label}
                        </span>
                      </td>
                      <td className="px-5 py-4 text-center text-xs">
                        {l.cvId > 0 ? (
                          <span className={`font-bold ${l.canCancel ? 'text-amber-600' : 'text-slate-500'}`}>
                            {l.cvStatusLabel || l.cvStatus || '연결됨'}
                            {l.finalLocked ? ' · 잠금' : ''}
                          </span>
                        ) : (
                          <span className="text-slate-400" title="통화만 있고 콜디비 전환이 아직 없습니다. 관리자 재매칭·콜디비 생성 후 취소할 수 있습니다.">
                            DB 없음
                          </span>
                        )}
                      </td>
                      <td className="px-5 py-4 text-center">
                        <CallRecordingCell
                          meta={l.recordingRequest}
                          onRequest={(memo) => handleRecordingRequest(l.clogId, memo)}
                          compact
                        />
                      </td>
                      <td className="px-5 py-4 text-center">
                        {l.canCancel ? (
                          <button
                            type="button"
                            onClick={() => openCancel(l)}
                            className="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg"
                          >
                            <XCircle size={13} /> 취소
                          </button>
                        ) : l.cvId > 0 ? (
                          <Link
                            to={`/advertiser/db?source=call&q=${encodeURIComponent(String(l.cvId))}`}
                            className="text-xs font-bold text-cyan-600 hover:underline"
                          >
                            디비 확인
                          </Link>
                        ) : (
                          <span className="text-xs text-slate-400">—</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {cancelTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
          <div className="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-100">
              <h3 className="font-bold text-slate-900">콜디비 취소</h3>
              <p className="text-xs text-slate-500 mt-1">
                {cancelTarget.startedAt} · {formatPhone(cancelTarget.caller)} · CPA 취소와 동일하게 처리됩니다.
              </p>
            </div>
            <div className="px-5 py-4 space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">취소 사유 *</label>
                <select
                  value={cancelReason}
                  onChange={(e) => setCancelReason(e.target.value)}
                  className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-red-500"
                >
                  <option value="" disabled>사유를 선택해주세요</option>
                  <option value="연락불가">연락불가 (3회 이상 부재 등)</option>
                  <option value="중복디비">중복디비</option>
                  <option value="장난접수">장난/허위 정보 접수</option>
                  <option value="조건불일치">조건불일치 (나이/지역 등)</option>
                  <option value="지역불가">서비스 불가 지역</option>
                  <option value="이미상담">이미 상담받은 고객</option>
                  <option value="기타">기타</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">상세 코멘트</label>
                <textarea
                  value={cancelComment}
                  onChange={(e) => setCancelComment(e.target.value)}
                  placeholder="상세한 취소 사유를 남겨주세요."
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm min-h-[100px] resize-none outline-none focus:border-red-500"
                />
              </div>
              <label className="flex items-start gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" checked={partnerVisible} onChange={(e) => setPartnerVisible(e.target.checked)} className="mt-1 accent-cyan-600" />
                <span>
                  <span className="font-medium block">파트너에게 사유 공개</span>
                  <span className="text-xs text-slate-500">체크 시 파트너도 취소 사유와 코멘트를 확인할 수 있습니다.</span>
                </span>
              </label>
              {cancelError ? <p className="text-sm text-red-600 font-medium">{cancelError}</p> : null}
            </div>
            <div className="px-5 py-4 border-t border-slate-100 flex justify-end gap-2">
              <button type="button" onClick={() => setCancelTarget(null)} disabled={cancelBusy}
                className="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 disabled:opacity-50">
                닫기
              </button>
              <button type="button" onClick={handleCancelConfirm} disabled={cancelBusy || !cancelReason}
                className="px-4 py-2 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50">
                {cancelBusy ? '처리 중…' : '취소 확정'}
              </button>
            </div>
          </div>
        </div>
      )}
    </AdvertiserLayout>
  );
}
