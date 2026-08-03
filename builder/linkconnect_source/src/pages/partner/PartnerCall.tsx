import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { PhoneCall, PhoneIncoming, Copy, Check, Info, RefreshCw } from 'lucide-react';
import {
  CallLog,
  CallRequest,
  PartnerAvailableCallNumber,
  PartnerCampaign,
  claimPartnerCallNumber,
  fetchPartnerAvailableCallNumbers,
  fetchPartnerCallLogs,
  fetchPartnerCallRequests,
  fetchPartnerCampaigns,
  formatCallPhone,
  requestPartnerCallRecording,
} from '../../lib/api';
import { CallRecordingCell } from '../../components/call/CallRecordingCell';

const reqStatusLabel: Record<string, { label: string; cls: string }> = {
  pending: { label: '배정 대기', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
  assigned: { label: '배정 완료', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  rejected: { label: '반려', cls: 'bg-slate-100 text-slate-500 border-slate-200' },
  revoked: { label: '회수', cls: 'bg-rose-50 text-rose-700 border-rose-200' },
};

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

export function PartnerCall() {
  const [requests, setRequests] = useState<CallRequest[]>([]);
  const [logs, setLogs] = useState<CallLog[]>([]);
  const [campaigns, setCampaigns] = useState<PartnerCampaign[]>([]);
  const [availableNumbers, setAvailableNumbers] = useState<PartnerAvailableCallNumber[]>([]);
  const [selectedCp, setSelectedCp] = useState('');
  const [selectedCn, setSelectedCn] = useState('');
  const [memo, setMemo] = useState('');
  const [logCpFilter, setLogCpFilter] = useState('');
  const [logNumberFilter, setLogNumberFilter] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [copied, setCopied] = useState<number | null>(null);

  const assignedNumbers = useMemo(
    () => requests.filter((r) => r.status === 'assigned' && r.virtualNumber),
    [requests],
  );

  const loadRequests = useCallback(() => {
    fetchPartnerCallRequests().then((d) => setRequests(d.items)).catch(() => setRequests([]));
  }, []);

  const loadAvailable = useCallback(() => {
    fetchPartnerAvailableCallNumbers()
      .then((d) => setAvailableNumbers(d.items))
      .catch(() => setAvailableNumbers([]));
  }, []);

  const loadLogs = useCallback(() => {
    fetchPartnerCallLogs({
      cpId: logCpFilter ? Number(logCpFilter) : undefined,
      virtualNumber: logNumberFilter || undefined,
    })
      .then((d) => setLogs(d.items))
      .catch(() => setLogs([]));
  }, [logCpFilter, logNumberFilter]);

  useEffect(() => {
    loadRequests();
    loadAvailable();
    fetchPartnerCampaigns().then((d) => setCampaigns(d.items)).catch(() => setCampaigns([]));
  }, [loadRequests, loadAvailable]);

  useEffect(() => {
    loadLogs();
  }, [loadLogs]);

  const handleClaim = async () => {
    if (!selectedCp) {
      setMessage('캠페인을 선택하세요.');
      return;
    }
    if (!selectedCn) {
      setMessage('사용할 가상번호를 선택하세요.');
      return;
    }
    setSubmitting(true);
    setMessage('');
    try {
      const res = await claimPartnerCallNumber({
        cpId: Number(selectedCp),
        cnId: Number(selectedCn),
        memo,
      });
      setMessage(res.message);
      setSelectedCp('');
      setSelectedCn('');
      setMemo('');
      loadRequests();
      loadAvailable();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '번호 선택에 실패했습니다.');
      loadAvailable();
    } finally {
      setSubmitting(false);
    }
  };

  const handleRecordingRequest = async (clogId: number, memo?: string) => {
    try {
      const res = await requestPartnerCallRecording({ clogId, memo });
      setMessage(res.message);
      loadLogs();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '녹음 요청에 실패했습니다.');
    }
  };

  const copyNumber = (id: number, num: string) => {
    navigator.clipboard?.writeText(num);
    setCopied(id);
    setTimeout(() => setCopied(null), 1500);
  };

  return (
    <PartnerLayout activeMenu="call" title="콜디비">
      <div className="space-y-6">
        <div className="bg-violet-50 border border-violet-100 rounded-2xl p-5 text-sm text-violet-900">
          <div className="flex items-start gap-2">
            <Info className="w-5 h-5 shrink-0 mt-0.5" />
            <div>
              <p className="font-bold mb-1">콜디비 이용 방법</p>
              <ol className="list-decimal pl-5 space-y-1 text-violet-900/90">
                <li>캠페인을 고른 뒤, 사용 가능한 가상번호 중 <b>원하는 번호를 선택</b></li>
                <li>선택 즉시 배정되며, 홍보 채널에 해당 번호를 노출</li>
                <li>관리자가 통화내역 엑셀 업로드 시, <b>내 담당 번호</b> 통화만 아래에 표시</li>
                <li>녹음이 필요한 통화는 <b>녹음 요청</b> → 관리자 등록 후 재생 가능</li>
              </ol>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <div className="flex items-center justify-between gap-3 mb-1">
            <div className="flex items-center gap-2 font-bold text-slate-800">
              <PhoneCall size={18} className="text-emerald-500" />
              가상번호 선택
            </div>
            <button
              type="button"
              onClick={loadAvailable}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100"
            >
              <RefreshCw size={13} /> 번호 새로고침
            </button>
          </div>
          <p className="text-sm text-slate-500 mb-4">
            관리자가 등록해 둔 사용 가능 번호 중에서 골라 바로 배정받으세요. 선택과 동시에 선점되어 다른 파트너와 중복되지 않습니다.
          </p>

          <div className="flex flex-col sm:flex-row gap-3 mb-4">
            <select
              value={selectedCp}
              onChange={(e) => setSelectedCp(e.target.value)}
              className="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm"
            >
              <option value="">캠페인 선택</option>
              {campaigns.map((c) => (
                <option key={c.id} value={c.id}>{c.title}</option>
              ))}
            </select>
            <input
              type="text"
              value={memo}
              onChange={(e) => setMemo(e.target.value)}
              placeholder="요청 메모 (선택)"
              className="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm"
            />
            <button
              type="button"
              onClick={handleClaim}
              disabled={submitting || !selectedCp || !selectedCn}
              className="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl text-sm disabled:opacity-50 whitespace-nowrap"
            >
              이 번호로 배정
            </button>
          </div>

          {availableNumbers.length === 0 ? (
            <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">
              선택 가능한 번호가 없습니다. 관리자가 가상번호 풀에 번호를 등록하면 여기에 표시됩니다.
            </div>
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
              {availableNumbers.map((n) => {
                const active = selectedCn === String(n.cnId);
                return (
                  <button
                    key={n.cnId}
                    type="button"
                    onClick={() => setSelectedCn(String(n.cnId))}
                    className={`text-left rounded-xl border px-3 py-3 transition ${
                      active
                        ? 'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-200'
                        : 'border-slate-200 bg-slate-50 hover:border-emerald-300 hover:bg-white'
                    }`}
                  >
                    <div className={`font-mono font-bold text-sm ${active ? 'text-emerald-700' : 'text-slate-800'}`}>
                      {formatCallPhone(n.number)}
                    </div>
                    {n.memo ? <div className="mt-1 text-[11px] text-slate-500 truncate">{n.memo}</div> : null}
                  </button>
                );
              })}
            </div>
          )}

          {message && <p className="mt-3 text-sm text-emerald-600">{message}</p>}
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">내 가상번호 신청·배정 현황</div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-slate-500 text-left">
                <tr>
                  <th className="px-4 py-3">캠페인</th>
                  <th className="px-4 py-3">배정 번호</th>
                  <th className="px-4 py-3 text-center">상태</th>
                  <th className="px-4 py-3">신청일</th>
                  <th className="px-4 py-3">메모</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {requests.length === 0 ? (
                  <tr><td colSpan={5} className="px-4 py-12 text-center text-slate-400">신청 내역이 없습니다.</td></tr>
                ) : requests.map((r) => {
                  const s = reqStatusLabel[r.status] ?? { label: r.status, cls: 'bg-slate-100 text-slate-500 border-slate-200' };
                  return (
                    <tr key={r.carId} className="hover:bg-slate-50">
                      <td className="px-4 py-3 font-medium text-slate-800">{r.campaign}</td>
                      <td className="px-4 py-3">
                        {r.virtualNumber ? (
                          <button type="button" onClick={() => copyNumber(r.carId, r.virtualNumber)} className="inline-flex items-center gap-2 font-mono font-bold text-emerald-600">
                            {r.virtualNumber}
                            {copied === r.carId ? <Check size={14} /> : <Copy size={14} className="text-slate-400" />}
                          </button>
                        ) : <span className="text-slate-400">배정 대기</span>}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-block px-2.5 py-1 rounded-full text-xs font-bold border ${s.cls}`}>{s.label}</span>
                      </td>
                      <td className="px-4 py-3 text-slate-500">{r.createdAt}</td>
                      <td className="px-4 py-3 text-slate-500 max-w-xs truncate">{r.adminMemo || r.requestMemo || '—'}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center gap-3 justify-between">
            <div className="flex items-center gap-2 font-bold text-slate-800">
              <PhoneIncoming size={18} className="text-emerald-500" />
              내 통화 내역
            </div>
            <div className="flex flex-wrap gap-2">
              <select value={logCpFilter} onChange={(e) => setLogCpFilter(e.target.value)} className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                <option value="">전체 캠페인</option>
                {campaigns.map((c) => <option key={c.id} value={c.id}>{c.title}</option>)}
              </select>
              <select value={logNumberFilter} onChange={(e) => setLogNumberFilter(e.target.value)} className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono">
                <option value="">전체 담당번호</option>
                {assignedNumbers.map((r) => (
                  <option key={r.carId} value={r.virtualNumber}>{r.virtualNumber} ({r.campaign})</option>
                ))}
              </select>
            </div>
          </div>
          <p className="px-5 py-2 text-xs text-slate-500 border-b border-slate-50">
            관리자가 업로드한 통화내역 중, 내게 배정된 가상번호와 일치하는 건만 표시됩니다.
          </p>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-slate-500 text-left">
                <tr>
                  <th className="px-4 py-3">일시</th>
                  <th className="px-4 py-3">캠페인</th>
                  <th className="px-4 py-3">가상번호</th>
                  <th className="px-4 py-3">발신번호</th>
                  <th className="px-4 py-3 text-center">통화시간</th>
                  <th className="px-4 py-3 text-center">결과</th>
                  <th className="px-4 py-3 text-center">녹음</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {logs.length === 0 ? (
                  <tr><td colSpan={7} className="px-4 py-12 text-center text-slate-400">통화 내역이 없습니다. 번호 배정 후 관리자가 엑셀을 업로드하면 표시됩니다.</td></tr>
                ) : logs.map((l) => {
                  const r = resultLabel[l.result] ?? { label: l.result, cls: 'text-slate-500' };
                  return (
                    <tr key={l.clogId} className="hover:bg-slate-50">
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{l.startedAt}</td>
                      <td className="px-4 py-3">{l.campaign || '—'}</td>
                      <td className="px-4 py-3 font-mono font-semibold text-violet-700">{l.virtualNumber}</td>
                      <td className="px-4 py-3 font-mono">{l.caller}</td>
                      <td className="px-4 py-3 text-center">{formatDuration(l.duration)}</td>
                      <td className={`px-4 py-3 text-center font-bold ${r.cls}`}>{r.label}</td>
                      <td className="px-4 py-3 text-center">
                        <CallRecordingCell
                          meta={l.recordingRequest}
                          onRequest={(memo) => handleRecordingRequest(l.clogId, memo)}
                          compact
                        />
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </PartnerLayout>
  );
}
