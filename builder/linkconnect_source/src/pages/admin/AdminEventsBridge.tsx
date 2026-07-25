import { ExternalLink, Gift } from 'lucide-react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { lcPluginUrl } from '../../lib/urls';

const adminEventsUrl = lcPluginUrl('admin/events.php');

export function AdminEventsBridge() {
  return (
    <AdminLayout
      activeMenu="events"
      title="이벤트/프로모션 관리"
      description="이벤트 등록, CPA 연결, 리워드·지급 관리는 PHP 운영 화면에서 제공됩니다."
    >
      <div className="max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
        <div className="w-12 h-12 rounded-xl bg-cyan-50 border border-cyan-100 flex items-center justify-center mb-5">
          <Gift className="text-cyan-600" size={24} />
        </div>
        <h2 className="text-xl font-bold text-slate-900 mb-3">운영 관리 페이지로 이동</h2>
        <p className="text-slate-600 leading-relaxed mb-6">
          이벤트 목록, 등록/수정, CPA 연결, 참여 파트너·성과·지급 관리 UI는
          링크커넥트 PHP 관리자센터에 구현되어 있습니다.
          아래 버튼으로 운영 화면을 열어주세요.
        </p>
        <a
          href={adminEventsUrl}
          className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold rounded-xl transition-colors"
        >
          이벤트/프로모션 관리 열기
          <ExternalLink size={18} />
        </a>
      </div>
    </AdminLayout>
  );
}
