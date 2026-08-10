import { Link } from 'react-router-dom';
import {
  Activity,
  ArrowRight,
  CheckCircle2,
  Lightbulb,
  MousePointerClick,
  PhoneCall,
  Sparkles,
  Target,
} from 'lucide-react';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import type { PartnerEmbedCroAnalytics } from '../../lib/api';

type Props = {
  cro?: PartnerEmbedCroAnalytics | null;
  period: number;
};

const STEP_ICONS: Record<string, typeof Sparkles> = {
  badge_click: Sparkles,
  extra_fields_open: MousePointerClick,
  sticky_submit: Activity,
  db_received: Target,
  success_call_tap: PhoneCall,
};

function toneClass(tone?: string) {
  if (tone === 'good') return 'border-emerald-200 bg-emerald-50 text-emerald-950';
  if (tone === 'warn') return 'border-amber-200 bg-amber-50 text-amber-950';
  if (tone === 'tip') return 'border-cyan-200 bg-cyan-50 text-cyan-950';
  return 'border-slate-200 bg-slate-50 text-slate-800';
}

function rateLabel(value?: number) {
  if (value == null || Number.isNaN(value)) return '—';
  return `${value}%`;
}

export function EmbedCroAnalyticsPanel({ cro, period }: Props) {
  const steps = cro?.steps ?? [];
  const maxCount = Math.max(1, ...steps.map((s) => s.count || 0));
  const insight = cro?.insight;
  const rates = cro?.rates;
  const daily = cro?.daily ?? [];
  const hosts = cro?.byHost ?? [];
  const byAb = cro?.byAb;
  const abA = byAb?.A;
  const abB = byAb?.B;
  const hasAbData = Boolean((abA?.total || 0) + (abB?.total || 0) > 0);
  const hasBehavior = steps.some((s) => s.id !== 'db_received' && (s.count || 0) > 0);

  return (
    <section className="mb-8 rounded-2xl border border-cyan-200 bg-gradient-to-br from-cyan-50 via-white to-emerald-50/40 p-5 sm:p-6 shadow-sm">
      <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-5">
        <div className="min-w-0">
          <div className="inline-flex items-center gap-1.5 text-xs font-bold text-cyan-800 bg-white/80 border border-cyan-200 px-2 py-1 rounded-lg mb-2">
            <Sparkles size={13} />
            상담 위젯 전환 퍼널
          </div>
          <h2 className="text-lg font-bold text-slate-900">방문자가 어디서 멈추는지 한눈에</h2>
          <p className="text-sm text-slate-600 mt-1 leading-relaxed">
            최근 {period}일 · 배지 관심 → 추가정보 → 모바일 제출 → DB 접수 → 완료 전화. GTM 이벤트와 같은 숫자가 파트너 센터에 연결됩니다.
          </p>
        </div>
        <Link
          to="/partner/links"
          className="inline-flex items-center gap-1.5 shrink-0 px-3 py-2 rounded-xl bg-cyan-700 hover:bg-cyan-600 text-white text-xs font-bold"
        >
          HTML 위젯 설정
          <ArrowRight size={14} />
        </Link>
      </div>

      {insight ? (
        <div className={`rounded-xl border px-4 py-3 mb-5 ${toneClass(insight.tone)}`}>
          <div className="flex items-start gap-2">
            <Lightbulb size={18} className="mt-0.5 shrink-0 opacity-80" />
            <div>
              <div className="text-sm font-bold">{insight.title}</div>
              <p className="text-xs mt-1 leading-relaxed opacity-90">{insight.body}</p>
            </div>
          </div>
        </div>
      ) : null}

      <div className="grid grid-cols-2 md:grid-cols-5 gap-2.5 mb-5">
        {steps.map((step, index) => {
          const Icon = STEP_ICONS[step.id] || Activity;
          const width = Math.max(8, Math.round(((step.count || 0) / maxCount) * 100));
          const prev = index > 0 ? steps[index - 1]?.count || 0 : 0;
          const stepRate =
            index > 0 && prev > 0 ? `${(((step.count || 0) / prev) * 100).toFixed(0)}%` : null;
          return (
            <div
              key={step.id}
              className="relative rounded-xl border border-white/80 bg-white/90 p-3.5 shadow-sm"
            >
              <div className="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 mb-2">
                <Icon size={13} className="text-cyan-600" />
                {step.label}
              </div>
              <div className="text-2xl font-extrabold text-slate-900 tabular-nums">
                {(step.count || 0).toLocaleString()}
              </div>
              <p className="text-[10px] text-slate-500 mt-1 leading-snug min-h-[2.2em]">{step.desc}</p>
              <div className="mt-3 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div className="h-full rounded-full bg-cyan-500" style={{ width: `${width}%` }} />
              </div>
              {stepRate ? (
                <div className="mt-2 text-[10px] font-bold text-emerald-700">이전 단계 대비 {stepRate}</div>
              ) : (
                <div className="mt-2 text-[10px] text-slate-400">시작 관심</div>
              )}
              {index < steps.length - 1 ? (
                <div className="hidden md:block absolute -right-2 top-1/2 -translate-y-1/2 z-10 text-slate-300">
                  →
                </div>
              ) : null}
            </div>
          );
        })}
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4">
          <div className="flex items-center justify-between gap-2 mb-3">
            <h3 className="text-sm font-bold text-slate-900">행동 추이</h3>
            <span className="text-[10px] text-slate-500">배지 · 추가정보 · 모바일제출 · 전화</span>
          </div>
          <div className="h-52">
            {daily.length === 0 || !hasBehavior ? (
              <div className="h-full flex flex-col items-center justify-center text-center px-4">
                <CheckCircle2 className="text-slate-300 mb-2" size={28} />
                <p className="text-sm text-slate-500 leading-relaxed">
                  아직 행동 이벤트가 없습니다. 최신 위젯을 설치한 뒤 방문자가 배지·제출을 하면 그래프가 채워집니다.
                </p>
              </div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={daily} margin={{ top: 8, right: 8, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="croBadge" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#06b6d4" stopOpacity={0.25} />
                      <stop offset="95%" stopColor="#06b6d4" stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="croSticky" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#10b981" stopOpacity={0.2} />
                      <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#64748b' }} allowDecimals={false} />
                  <Tooltip />
                  <Area type="monotone" dataKey="badge" name="배지" stroke="#06b6d4" fill="url(#croBadge)" strokeWidth={2} />
                  <Area type="monotone" dataKey="extra" name="추가정보" stroke="#6366f1" fillOpacity={0} strokeWidth={2} />
                  <Area type="monotone" dataKey="sticky" name="모바일제출" stroke="#10b981" fill="url(#croSticky)" strokeWidth={2} />
                  <Area type="monotone" dataKey="call" name="완료전화" stroke="#f59e0b" fillOpacity={0} strokeWidth={2} />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        <div className="space-y-4">
          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <h3 className="text-sm font-bold text-slate-900 mb-3">핵심 전환율</h3>
            <div className="space-y-2.5 text-xs">
              <div className="flex justify-between gap-2">
                <span className="text-slate-500">배지 → DB</span>
                <span className="font-bold text-slate-900">{rateLabel(rates?.dbFromBadge)}</span>
              </div>
              <div className="flex justify-between gap-2">
                <span className="text-slate-500">배지 → 추가정보</span>
                <span className="font-bold text-slate-900">{rateLabel(rates?.extraFromBadge)}</span>
              </div>
              <div className="flex justify-between gap-2">
                <span className="text-slate-500">모바일제출 → DB</span>
                <span className="font-bold text-slate-900">{rateLabel(rates?.dbFromSticky)}</span>
              </div>
              <div className="flex justify-between gap-2">
                <span className="text-slate-500">DB → 완료전화</span>
                <span className="font-bold text-slate-900">{rateLabel(rates?.callFromDb)}</span>
              </div>
            </div>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <h3 className="text-sm font-bold text-slate-900 mb-3">행동 많은 도메인</h3>
            {hosts.length === 0 ? (
              <p className="text-xs text-slate-500 leading-relaxed">도메인별 행동 데이터가 쌓이면 여기에 표시됩니다.</p>
            ) : (
              <div className="space-y-2.5">
                {hosts.slice(0, 5).map((host) => (
                  <div key={host.host}>
                    <div className="flex justify-between gap-2 text-xs mb-1">
                      <span className="font-medium text-slate-700 truncate">{host.host}</span>
                      <span className="text-slate-500 shrink-0">{host.total}</span>
                    </div>
                    <div className="text-[10px] text-slate-400">
                      배지 {host.badgeClick} · 모바일 {host.stickySubmit} · 전화 {host.successCallTap}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <h3 className="text-sm font-bold text-slate-900 mb-3">A/B 비교</h3>
            {!hasAbData ? (
              <p className="text-xs text-slate-500 leading-relaxed">
                위젯 설정에서 A/B를 켜면 방문자가 A·B에 나뉘고, 행동 숫자가 여기에 쌓입니다.
              </p>
            ) : (
              <div className="grid grid-cols-2 gap-2.5">
                {(
                  [
                    { id: 'A', row: abA },
                    { id: 'B', row: abB },
                  ] as const
                ).map((item) => (
                  <div key={item.id} className="rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2">
                    <div className="text-[11px] font-bold text-slate-500 mb-1">{item.id}안</div>
                    <div className="text-lg font-extrabold text-slate-900 tabular-nums">
                      {(item.row?.total || 0).toLocaleString()}
                    </div>
                    <div className="text-[10px] text-slate-400 mt-1 leading-snug">
                      배지 {item.row?.badgeClick || 0} · 제출 {item.row?.stickySubmit || 0} · 전화{' '}
                      {item.row?.successCallTap || 0}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
