import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { ChannelReportItem, fetchAdminChannelReports, updateAdminChannelReport } from '../../lib/api';
import { StatusBadge } from '../../components/admin/AdminShared';
import { ShieldAlert } from 'lucide-react';

export function AdminChannelReports() {
  const [items, setItems] = useState<ChannelReportItem[]>([]);
  const [status, setStatus] = useState('pending');
  const [processing, setProcessing] = useState<number | null>(null);

  const load = useCallback(() => {
    fetchAdminChannelReports(status)
      .then((data) => setItems(data.items))
      .catch(() => setItems([]));
  }, [status]);

  useEffect(() => {
    load();
  }, [load]);

  const handleAction = async (id: number, action: 'sanction' | 'dismiss' | 'review') => {
    setProcessing(id);
    try {
      await updateAdminChannelReport({ action, crId: id });
      load();
    } finally {
      setProcessing(null);
    }
  };

  return (
    <AdminLayout activeMenu="channel_reports" title="금지 채널 신고" description="광고주 신고 접수 및 파트너 제재 워크플로우">
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div className="flex items-center gap-2 font-bold text-slate-800">
            <ShieldAlert size={18} className="text-red-500" />
            신고 목록
          </div>
          <select value={status} onChange={(e) => setStatus(e.target.value)} className="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
            <option value="">전체</option>
            <option value="pending">대기</option>
            <option value="reviewing">검토중</option>
            <option value="sanctioned">제재</option>
            <option value="dismissed">기각</option>
          </select>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-slate-500 text-left">
              <tr>
                <th className="px-4 py-3">일시</th>
                <th className="px-4 py-3">파트너</th>
                <th className="px-4 py-3">채널</th>
                <th className="px-4 py-3">사유</th>
                <th className="px-4 py-3 text-center">상태</th>
                <th className="px-4 py-3 text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {items.length === 0 ? (
                <tr><td colSpan={6} className="px-4 py-12 text-center text-slate-400">신고 내역이 없습니다.</td></tr>
              ) : items.map((row) => (
                <tr key={row.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{row.createdAt.slice(0, 16)}</td>
                  <td className="px-4 py-3">
                    <div className="font-bold">{row.partnerName}</div>
                    <div className="text-xs text-slate-400">{row.cvCode}</div>
                  </td>
                  <td className="px-4 py-3">{row.channel}</td>
                  <td className="px-4 py-3 max-w-xs truncate">{row.reason}</td>
                  <td className="px-4 py-3 text-center"><StatusBadge status={row.status} /></td>
                  <td className="px-4 py-3 text-center">
                    {row.status === 'pending' || row.status === 'reviewing' ? (
                      <div className="flex items-center justify-center gap-1">
                        <button type="button" disabled={processing === row.id} onClick={() => handleAction(row.id, 'review')} className="px-2 py-1 text-xs font-bold bg-slate-100 rounded-lg">검토</button>
                        <button type="button" disabled={processing === row.id} onClick={() => handleAction(row.id, 'sanction')} className="px-2 py-1 text-xs font-bold bg-red-50 text-red-700 rounded-lg">제재</button>
                        <button type="button" disabled={processing === row.id} onClick={() => handleAction(row.id, 'dismiss')} className="px-2 py-1 text-xs font-bold bg-slate-50 rounded-lg">기각</button>
                      </div>
                    ) : (
                      <span className="text-xs text-slate-400">완료</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AdminLayout>
  );
}
