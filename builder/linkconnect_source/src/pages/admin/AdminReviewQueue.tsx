import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { fetchAdminReviewQueue, ReviewQueueItem, viewAsMerchant, viewAsPartner } from '../../lib/api';
import { StatusBadge } from '../../components/admin/AdminShared';
import { ClipboardList, Eye } from 'lucide-react';

export function AdminReviewQueue() {
  const [items, setItems] = useState<ReviewQueueItem[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    fetchAdminReviewQueue()
      .then((data) => setItems(data.items))
      .catch(() => setItems([]))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const handleView = async (item: ReviewQueueItem) => {
    if (item.entityType === 'merchant') {
      const r = await viewAsMerchant(item.entityId);
      window.location.href = r.redirect || '/advertiser';
    } else {
      const r = await viewAsPartner(item.entityId);
      window.location.href = r.redirect || '/partner';
    }
  };

  return (
    <AdminLayout activeMenu="review" title="자동 심사 큐" description="승인 대기 파트너·광고주 우선순위">
      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-100 flex items-center gap-2 font-bold text-slate-800">
          <ClipboardList size={18} className="text-slate-400" />
          심사 우선순위 ({items.length}건)
        </div>
        {loading ? (
          <div className="py-16 text-center text-slate-400">로딩 중...</div>
        ) : items.length === 0 ? (
          <div className="py-16 text-center text-slate-400">승인 대기 항목이 없습니다.</div>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-slate-500 text-left">
              <tr>
                <th className="px-4 py-3">우선순위</th>
                <th className="px-4 py-3">유형</th>
                <th className="px-4 py-3">코드/명</th>
                <th className="px-4 py-3 text-center">심사점수</th>
                <th className="px-4 py-3 text-center">어뷰징</th>
                <th className="px-4 py-3 text-center">상태</th>
                <th className="px-4 py-3 text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {items.map((item, idx) => (
                <tr key={item.entityType + '-' + item.entityId} className="hover:bg-slate-50">
                  <td className="px-4 py-3 font-bold text-cyan-600">#{idx + 1}</td>
                  <td className="px-4 py-3">{item.entityType === 'merchant' ? '광고주' : '파트너'}</td>
                  <td className="px-4 py-3">
                    <div className="font-bold">{item.name}</div>
                    <div className="text-xs text-slate-400 font-mono">{item.code}</div>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span className={`font-bold ${item.reviewScore >= 70 ? 'text-red-600' : item.reviewScore >= 40 ? 'text-orange-600' : 'text-slate-700'}`}>
                      {item.reviewScore}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center">{item.abuseScore ?? 0}</td>
                  <td className="px-4 py-3 text-center"><StatusBadge status={item.status} /></td>
                  <td className="px-4 py-3 text-center">
                    <button type="button" onClick={() => handleView(item)} className="px-2 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-lg inline-flex items-center gap-1">
                      <Eye size={12} /> 보기
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </AdminLayout>
  );
}
