import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { fetchAdminLogs, AdminLogItem } from '../../lib/api';
import { ScrollText, Search, Loader2 } from 'lucide-react';

export function AdminLogs() {
  const [items, setItems] = useState<AdminLogItem[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [actionFilter, setActionFilter] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    fetchAdminLogs({ q: search, action: actionFilter })
      .then((data) => {
        setItems(data.items);
        setTotal(data.total);
      })
      .catch(() => {
        setItems([]);
        setTotal(0);
      })
      .finally(() => setLoading(false));
  }, [search, actionFilter]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <AdminLayout activeMenu="logs" title="관리자 작업 로그" description="관리자센터 주요 작업 이력">
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
          <div className="flex items-center gap-2 text-slate-800 font-bold">
            <ScrollText size={18} className="text-slate-400" />
            작업 로그
            <span className="text-xs font-normal text-slate-400">총 {total.toLocaleString()}건</span>
          </div>
          <div className="flex flex-col sm:flex-row gap-2">
            <div className="relative">
              <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="검색 (관리자/요약/액션)"
                className="pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm w-full sm:w-56 outline-none focus:border-cyan-500"
              />
            </div>
            <select
              value={actionFilter}
              onChange={(e) => setActionFilter(e.target.value)}
              className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:border-cyan-500"
            >
              <option value="">전체 액션</option>
              <option value="event_reward_pay">리워드 지급</option>
              <option value="event_reward_reject">리워드 거절</option>
              <option value="event_auto_ranking">랭킹 자동정산</option>
              <option value="wallet_adjust">광고비 조정</option>
            </select>
          </div>
        </div>

        {loading ? (
          <div className="py-16 flex justify-center text-slate-400">
            <Loader2 size={24} className="animate-spin" />
          </div>
        ) : items.length === 0 ? (
          <div className="py-16 text-center text-slate-400 text-sm">기록된 작업 로그가 없습니다.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-slate-50 text-slate-500 text-left">
                  <th className="px-5 py-3 font-medium">일시</th>
                  <th className="px-5 py-3 font-medium">관리자</th>
                  <th className="px-5 py-3 font-medium">액션</th>
                  <th className="px-5 py-3 font-medium">요약</th>
                  <th className="px-5 py-3 font-medium">IP</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id} className="border-t border-slate-100 hover:bg-slate-50/50">
                    <td className="px-5 py-3 text-slate-500 whitespace-nowrap">{item.createdAt.slice(0, 16)}</td>
                    <td className="px-5 py-3 font-mono text-slate-700">{item.memberId}</td>
                    <td className="px-5 py-3">
                      <span className="text-xs font-bold px-2 py-1 rounded bg-slate-100 text-slate-600">{item.action}</span>
                    </td>
                    <td className="px-5 py-3 text-slate-800">{item.summary}</td>
                    <td className="px-5 py-3 text-slate-400 font-mono text-xs">{item.ip || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
