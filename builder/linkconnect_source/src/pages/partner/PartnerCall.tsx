import React, { useCallback, useEffect, useState } from 'react';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import { PhoneCall, PhoneIncoming, Copy, Check } from 'lucide-react';
import {
  CallLog,
  CallRequest,
  PartnerCampaign,
  fetchPartnerCallLogs,
  fetchPartnerCallRequests,
  fetchPartnerCampaigns,
  requestPartnerCallNumber,
} from '../../lib/api';

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
  const [selectedCp, setSelectedCp] = useState('');
  const [memo, setMemo] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');
  const [copied, setCopied] = useState<number | null>(null);

  const load = useCallback(() => {
    fetchPartnerCallRequests().then((d) => setRequests(d.items)).catch(() => setRequests([]));
    fetchPartnerCallLogs().then((d) => setLogs(d.items)).catch(() => setLogs([]));
  }, []);

  useEffect(() => {
    load();
    fetchPartnerCampaigns().then((d) => setCampaigns(d.items)).catch(() => setCampaigns([]));
  }, [load]);

  const handleRequest = async () => {
    if (!selectedCp) {
      setMessage('캠페인을 선택하세요.');
      return;
    }
    setSubmitting(true);
    setMessage('');
    try {
      const res = await requestPartnerCallNumber({ cpId: Number(selectedCp), memo });
      setMessage(res.message);
      setSelectedCp('');
      setMemo('');
      load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '신청에 실패했습니다.');
    } finally {
      setSubmitting(false);
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
        {/* 안내 + 신청 */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
          <div className="flex items-center gap-2 font-bold text-slate-800 mb-1">
            <PhoneCall size={18} className="text-emerald-500" />
            가상번호 신청
          </div>
          <p className="text-sm text-slate-500 mb-4">
            캠페인별 가상번호를 신청하면 관리자가 번호를 배정합니다. 배정된 번호를 홍보 페이지에 노출하고, 그 번호로 걸려온 통화가 콜DB(수익)로 집계됩니다.
          </p>
          <div className="flex flex-col sm:flex-row gap-3">
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
              onClick={handleRequest}
              disabled={submitting}
              className="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl text-sm disabled:opacity-50 whitespace-nowrap"
            >
              번호 신청
            </button>
          </div>
          {message && <p className="mt-3 text-sm text-emerald-600">{message}</p>}
        </div>

        {/* 신청/배정 현황 */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">내 가상번호 신청 현황</div>
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
                        ) : <span className="text-slate-400">—</span>}
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

        {/* 통화 내역 */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 flex items-center gap-2 font-bold text-slate-800">
            <PhoneIncoming size={18} className="text-emerald-500" />
            콜 통화 내역
          </div>
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
                  <th className="px-4 py-3 text-center">DB</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {logs.length === 0 ? (
                  <tr><td colSpan={7} className="px-4 py-12 text-center text-slate-400">통화 내역이 없습니다.</td></tr>
                ) : logs.map((l) => {
                  const r = resultLabel[l.result] ?? { label: l.result, cls: 'text-slate-500' };
                  return (
                    <tr key={l.clogId} className="hover:bg-slate-50">
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{l.startedAt}</td>
                      <td className="px-4 py-3">{l.campaign || '—'}</td>
                      <td className="px-4 py-3 font-mono">{l.virtualNumber}</td>
                      <td className="px-4 py-3 font-mono">{l.caller}</td>
                      <td className="px-4 py-3 text-center">{formatDuration(l.duration)}</td>
                      <td className={`px-4 py-3 text-center font-bold ${r.cls}`}>{r.label}</td>
                      <td className="px-4 py-3 text-center">{l.cvId > 0 ? <span className="text-emerald-600 font-bold">집계</span> : <span className="text-slate-300">—</span>}</td>
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
