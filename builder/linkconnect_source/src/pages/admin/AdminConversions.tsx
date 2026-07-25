import { useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { Database, Download } from 'lucide-react';
import { AdminConversion, fetchAdminConversions } from '../../lib/api';

const fallbackRows: AdminConversion[] = [
  { id: 'DB-260706-102', cvId: 0, date: '07.06 15:42', customer: '김*성', campaign: '개인회생 상담 DB', partner: 'PT-8832', advertiser: '희망법무법인', status: '승인완료', statusCode: 'approved', price: 50000 },
  { id: 'DB-260706-098', cvId: 0, date: '07.06 14:18', customer: '박*민', campaign: '신용대출 조회', partner: 'PT-1029', advertiser: '(주)성공대부', status: '확인중', statusCode: 'pending', price: 0 },
];

export function AdminConversions() {
  const [rows, setRows] = useState<AdminConversion[]>(fallbackRows);
  const [summary, setSummary] = useState({ todayReceived: 248, approved: 173, rejected: 42, pending: 18 });

  useEffect(() => {
    fetchAdminConversions()
      .then((data) => {
        if (data.items.length) {
          setRows(data.items);
        }
        setSummary(data.summary);
      })
      .catch(() => {
        // 샘플 UI fallback
      });
  }, []);

  return (
    <AdminLayout activeMenu="db" title="전체 디비 관리" description="전체 접수·승인·취소 디비와 수익 분배를 조회합니다.">
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <SummaryCard title="오늘 접수" value={String(summary.todayReceived)} suffix="건" />
        <SummaryCard title="승인 완료" value={String(summary.approved)} suffix="건" color="emerald" highlight />
        <SummaryCard title="취소/무효" value={String(summary.rejected)} suffix="건" color="red" />
        <SummaryCard title="검수 대기" value={String(summary.pending)} suffix="건" color="orange" />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-200">
          <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
            <Database size={20} className="text-cyan-500" />
            전체 디비 목록
          </h2>
          <button type="button" className="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">
            <Download size={16} />
            엑셀 다운로드
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[960px]">
            <thead className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th className="px-4 py-3 text-left">DB ID</th>
                <th className="px-4 py-3 text-left">접수일</th>
                <th className="px-4 py-3 text-left">고객</th>
                <th className="px-4 py-3 text-left">파트너</th>
                <th className="px-4 py-3 text-left">광고주</th>
                <th className="px-4 py-3 text-left">상품</th>
                <th className="px-4 py-3 text-left">상태</th>
                <th className="px-4 py-3 text-right">단가</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id} className="border-t border-slate-100 hover:bg-slate-50/80">
                  <td className="px-4 py-3 font-mono text-xs text-slate-700">{row.id}</td>
                  <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{row.date}</td>
                  <td className="px-4 py-3 font-medium text-slate-900">{row.customer}</td>
                  <td className="px-4 py-3 font-mono text-xs">{row.partner}</td>
                  <td className="px-4 py-3 text-slate-700">{row.advertiser}</td>
                  <td className="px-4 py-3 text-slate-700">{row.campaign}</td>
                  <td className="px-4 py-3"><StatusBadge status={row.status} /></td>
                  <td className="px-4 py-3 text-right tabular-nums text-cyan-600 font-semibold">
                    {row.price > 0 ? `${row.price.toLocaleString()}원` : '-'}
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
