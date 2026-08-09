import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  MousePointerClick,
  Target,
  CheckCircle2,
  XCircle,
  Percent,
  Lightbulb,
  TrendingUp,
  Users,
  DollarSign,
  Filter,
  Globe,
  Smartphone,
  BarChart3,
  ShoppingBag,
  Link2,
  ExternalLink,
} from 'lucide-react';
import { SummaryCard } from '../../components/partner/PartnerShared';
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { PartnerLayout } from '../../layouts/PartnerLayout';
import {
  fetchPartnerAnalytics,
  PartnerAnalyticsFilters,
  PartnerAnalyticsLinkRow,
  PartnerAnalyticsResponse,
  PartnerAnalyticsSource,
} from '../../lib/api';
import {
  InsightBanner,
  SkeletonCardGrid,
  SkeletonChart,
  Skeleton,
  DataTableEmpty,
  RankBadge,
  ProgressBar,
  tableRowClass,
} from '../../components/center-ui';

const emptyData: PartnerAnalyticsResponse = {
  source: 'cpa',
  summary: {
    totalClicks: 0,
    uniqueVisitors: 0,
    totalDb: 0,
    approvedDb: 0,
    rejectedDb: 0,
    confRevenue: 0,
    avgConvRate: 0,
    avgApprovalRate: 0,
    epc: 0,
  },
  range: { dateFrom: '', dateTo: '', period: 7 },
  funnel: { clicks: 0, received: 0, approved: 0, confirmed: 0 },
  chart: [],
  chart7d: [],
  channels: [],
  linkNames: [],
  links: [],
  compareLinks: [],
  referrers: [],
  devices: [],
  campaigns: [],
  filterOptions: { links: [], channels: [], linkNames: [], cpsLinks: [] },
  dbReady: false,
};

const PERIOD_OPTIONS: Array<{ value: 7 | 30 | 90; label: string }> = [
  { value: 7, label: '7일' },
  { value: 30, label: '30일' },
  { value: 90, label: '90일' },
];

const SOURCE_OPTIONS: Array<{ value: PartnerAnalyticsSource; label: string; desc: string }> = [
  { value: 'cpa', label: 'CPA 홍보링크', desc: '캠페인 DB 유입' },
  { value: 'cps', label: 'CPS 쇼핑링크', desc: '쇼핑몰 클릭·주문' },
  { value: 'embed', label: '외부 상담위젯', desc: '홈페이지·WP 폼' },
];

function funnelRate(current: number, previous: number) {
  if (previous <= 0) return '0%';
  return `${((current / previous) * 100).toFixed(1)}%`;
}

function normalizeAnalyticsResponse(result: PartnerAnalyticsResponse): PartnerAnalyticsResponse {
  return {
    ...emptyData,
    ...result,
    summary: { ...emptyData.summary, ...(result.summary ?? {}) },
    range: { ...emptyData.range, ...(result.range ?? {}) },
    funnel: { ...emptyData.funnel, ...(result.funnel ?? {}) },
    chart: result.chart ?? [],
    chart7d: result.chart7d ?? [],
    channels: result.channels ?? [],
    linkNames: result.linkNames ?? [],
    links: result.links ?? [],
    compareLinks: result.compareLinks ?? [],
    referrers: result.referrers ?? [],
    devices: result.devices ?? [],
    campaigns: result.campaigns ?? [],
    filterOptions: {
      ...emptyData.filterOptions,
      ...(result.filterOptions ?? {}),
      links: result.filterOptions?.links ?? [],
      channels: result.filterOptions?.channels ?? [],
      linkNames: result.filterOptions?.linkNames ?? [],
      cpsLinks: result.filterOptions?.cpsLinks ?? [],
    },
  };
}

function parseAnalyticsSource(raw: string | null): PartnerAnalyticsSource {
  if (raw === 'cps' || raw === 'embed' || raw === 'cpa') return raw;
  return 'cpa';
}

export function PartnerAnalytics() {
  const [searchParams] = useSearchParams();
  const [data, setData] = useState<PartnerAnalyticsResponse>(emptyData);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [source, setSource] = useState<PartnerAnalyticsSource>(() => parseAnalyticsSource(searchParams.get('source')));
  const [period, setPeriod] = useState<7 | 30 | 90>(7);
  const [channel, setChannel] = useState('');
  const [linkName, setLinkName] = useState('');
  const [linkId, setLinkId] = useState(0);
  const [lpmId, setLpmId] = useState(0);
  const [compareIds, setCompareIds] = useState<number[]>([]);
  const [compareLpmIds, setCompareLpmIds] = useState<number[]>([]);

  const isCps = source === 'cps';
  const isEmbed = source === 'embed';
  const isCpa = source === 'cpa';

  const loadData = useCallback(async (filters: PartnerAnalyticsFilters) => {
    setLoading(true);
    setError('');
    try {
      const result = await fetchPartnerAnalytics(filters);
      setData(normalizeAnalyticsResponse(result));
    } catch (err) {
      setError(err instanceof Error ? err.message : '분석 데이터를 불러오지 못했습니다.');
      setData(emptyData);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadData({
      source,
      period,
      channel: isCpa ? channel : '',
      linkName: isCpa ? linkName : '',
      linkId: isCpa ? linkId || undefined : undefined,
      lpmId: isCps ? lpmId || undefined : undefined,
      compareIds: isCpa && compareIds.length ? compareIds : undefined,
      compareLpmIds: isCps && compareLpmIds.length ? compareLpmIds : undefined,
    });
  }, [source, period, channel, linkName, linkId, lpmId, compareIds, compareLpmIds, isCpa, isCps, loadData]);

  const switchSource = (next: PartnerAnalyticsSource) => {
    setSource(next);
    setChannel('');
    setLinkName('');
    setLinkId(0);
    setLpmId(0);
    setCompareIds([]);
    setCompareLpmIds([]);
  };

  const toggleCompare = (id: number) => {
    if (isCps) {
      setCompareLpmIds((prev) => {
        if (prev.includes(id)) return prev.filter((item) => item !== id);
        if (prev.length >= 3) return prev;
        return [...prev, id];
      });
      return;
    }
    setCompareIds((prev) => {
      if (prev.includes(id)) return prev.filter((item) => item !== id);
      if (prev.length >= 3) return prev;
      return [...prev, id];
    });
  };

  const chartData = (data.chart ?? []).length ? data.chart : (data.chart7d ?? []);
  const tableRows = isCps ? (data.cpsLinks?.length ? data.cpsLinks : (data.links ?? [])) : (data.links ?? []);
  const activeCompareIds = isCps ? compareLpmIds : compareIds;
  const compareRows = !isEmbed && (data.compareLinks ?? []).length
    ? data.compareLinks
    : !isEmbed
      ? tableRows.filter((link) => activeCompareIds.includes(link.id))
      : [];

  const rangeLabel = useMemo(() => {
    if (data.range.dateFrom && data.range.dateTo) {
      return `${data.range.dateFrom} ~ ${data.range.dateTo}`;
    }
    return '';
  }, [data.range.dateFrom, data.range.dateTo]);

  const labels = isCps
    ? {
        db: '주문',
        approved: '확정주문',
        rejected: '취소주문',
        funnelReceived: '주문',
        funnelApproved: '확정',
        revenue: '확정수익',
        tableTitle: 'CPS 링크별 성과',
        tableSub: '쇼핑몰 홍보링크별 클릭·주문·수익을 비교합니다.',
        nameCol: '쇼핑몰',
        codeCol: '머천트코드',
        clickTitle: '총 클릭',
        clickCaption: '유입 합계',
        funnelClick: '클릭',
      }
    : isEmbed
      ? {
          db: '접수 DB',
          approved: '승인 DB',
          rejected: '취소 DB',
          funnelReceived: 'DB 접수',
          funnelApproved: '승인',
          revenue: '확정수익',
          tableTitle: '홍보코드별 위젯 성과',
          tableSub: '외부 홈페이지·워드프레스 위젯으로 접수된 DB를 비교합니다.',
          nameCol: '캠페인',
          codeCol: '채널',
          clickTitle: '총 접수',
          clickCaption: '위젯 DB 합계',
          funnelClick: '접수',
        }
      : {
          db: '접수 DB',
          approved: '승인 DB',
          rejected: '취소 DB',
          funnelReceived: 'DB 접수',
          funnelApproved: '승인',
          revenue: '확정수익',
          tableTitle: '홍보 링크별 성과',
          tableSub: '캠페인 홍보링크별 클릭·DB·수익을 비교합니다.',
          nameCol: '캠페인',
          codeCol: '채널',
          clickTitle: '총 클릭',
          clickCaption: '유입 합계',
          funnelClick: '클릭',
        };

  return (
    <PartnerLayout activeMenu="analytics" title="유입 분석">
      <InsightBanner
        accent={isEmbed ? 'cyan' : 'emerald'}
        message={
          <>
            최근 {period}일간 {isEmbed ? '위젯 DB' : '클릭'}{' '}
            <strong>{data.summary.totalClicks.toLocaleString()}{isEmbed ? '건' : '회'}</strong>,{' '}
            {isCps ? '주문' : 'DB'} <strong>{data.summary.totalDb.toLocaleString()}건</strong>, 확정수익{' '}
            <strong>{data.summary.confRevenue.toLocaleString()}원</strong>
          </>
        }
        subMessage={
          isEmbed
            ? `승인율 ${data.summary.avgApprovalRate}% · DB당 수익 ${data.summary.epc.toLocaleString()}원`
            : `전환율 ${data.summary.avgConvRate}% · ${isCps ? '확정율' : '승인율'} ${data.summary.avgApprovalRate}%`
        }
        actions={[
          {
            label: isCps ? 'CPS 링크 관리' : isEmbed ? '내 홍보 링크' : '내 홍보 링크',
            to: isCps ? '/partner/cps/links' : '/partner/links',
          },
          { label: '수익 리포트', to: '/partner/report', variant: 'secondary' },
        ]}
      />

      <div className="flex flex-col gap-4 mb-8 -mt-2">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
          <p className="text-slate-500">
            {isCps
              ? 'CPS 쇼핑링크별 클릭·주문·확정수익 흐름과 유입 도메인·기기를 분석합니다.'
              : isEmbed
                ? '외부 홈페이지·워드프레스 상담 위젯으로 접수된 DB·승인·수익과 설치 도메인을 분석합니다.'
                : 'CPA 홍보링크별 클릭·접수·승인·수익 흐름과 채널·링크이름·유입 경로를 비교합니다.'}
          </p>
          {isCps && (
            <Link
              to="/partner/cps/links"
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-violet-50 text-violet-700 border border-violet-200 text-sm font-bold hover:bg-violet-100 shrink-0"
            >
              <Link2 size={16} />
              내 CPS 홍보링크
              <ExternalLink size={14} className="opacity-60" />
            </Link>
          )}
        </div>

        <div className="inline-flex p-1 bg-slate-100 rounded-xl w-fit flex-wrap">
          {SOURCE_OPTIONS.map((opt) => (
            <button
              key={opt.value}
              type="button"
              onClick={() => switchSource(opt.value)}
              className={`px-4 py-2.5 rounded-lg text-sm font-bold transition-all ${
                source === opt.value
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <span className="inline-flex items-center gap-1.5">
                {opt.value === 'cps' ? (
                  <ShoppingBag size={15} className="text-violet-500" />
                ) : opt.value === 'embed' ? (
                  <Globe size={15} className="text-cyan-500" />
                ) : (
                  <Link2 size={15} className="text-emerald-500" />
                )}
                {opt.label}
              </span>
              <span className="block text-[10px] font-normal text-slate-400 mt-0.5">{opt.desc}</span>
            </button>
          ))}
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
          <div className="flex items-center gap-2 mb-4 text-slate-700 font-medium">
            <Filter size={18} className="text-emerald-500" />
            필터
            {rangeLabel && <span className="text-xs font-normal text-slate-400 ml-2">{rangeLabel}</span>}
          </div>
          <div className="flex flex-wrap gap-3">
            <div className="flex rounded-xl border border-slate-200 overflow-hidden">
              {PERIOD_OPTIONS.map((option) => (
                <button
                  key={option.value}
                  type="button"
                  onClick={() => setPeriod(option.value)}
                  className={`px-4 py-2 text-sm font-medium transition-colors ${
                    period === option.value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  {option.label}
                </button>
              ))}
            </div>

            {isCpa && (
              <>
                <select
                  value={channel}
                  onChange={(e) => setChannel(e.target.value)}
                  className="px-4 py-2 border border-slate-200 rounded-xl text-sm bg-white"
                >
                  <option value="">전체 채널</option>
                  {data.filterOptions.channels.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
                <select
                  value={linkName}
                  onChange={(e) => setLinkName(e.target.value)}
                  className="px-4 py-2 border border-slate-200 rounded-xl text-sm bg-white"
                >
                  <option value="">전체 링크이름</option>
                  {data.filterOptions.linkNames.map((item) => (
                    <option key={item} value={item}>{item}</option>
                  ))}
                </select>
                <select
                  value={linkId || ''}
                  onChange={(e) => setLinkId(Number(e.target.value) || 0)}
                  className="px-4 py-2 border border-slate-200 rounded-xl text-sm bg-white min-w-[220px]"
                >
                  <option value="">전체 홍보 링크</option>
                  {data.filterOptions.links.map((link) => (
                    <option key={link.id} value={link.id}>
                      {link.campaign} · {link.channel}{link.linkName ? ` · ${link.linkName}` : ''}
                    </option>
                  ))}
                </select>
              </>
            )}

            {isEmbed && (
              <p className="text-xs text-slate-500 self-center">
                위젯 DB는 클릭 추적 없이 접수·승인·설치 도메인 기준으로 집계됩니다.
              </p>
            )}

            {isCps && (
              <select
                value={lpmId || ''}
                onChange={(e) => setLpmId(Number(e.target.value) || 0)}
                className="px-4 py-2 border border-violet-200 rounded-xl text-sm bg-violet-50/50 min-w-[260px]"
              >
                <option value="">전체 CPS 링크</option>
                {(data.filterOptions.cpsLinks ?? []).map((link) => (
                  <option key={link.id} value={link.id}>
                    {link.merchantName} ({link.merchantCode})
                  </option>
                ))}
              </select>
            )}
          </div>
        </div>
      </div>

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}

      {loading ? (
        <>
          <SkeletonCardGrid count={8} />
          <div className="mt-8"><SkeletonChart /></div>
        </>
      ) : (
      <>
      <div className="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-4 mb-8">
        <SummaryCard
          title={labels.clickTitle}
          value={data.summary.totalClicks.toLocaleString()}
          suffix={isEmbed ? '건' : '회'}
          icon={<MousePointerClick className="text-blue-500" />}
          caption={labels.clickCaption}
        />
        {!isEmbed && (
          <SummaryCard title="순 방문자" value={data.summary.uniqueVisitors.toLocaleString()} suffix="명" icon={<Users className="text-indigo-500" />} />
        )}
        {isEmbed && (
          <SummaryCard
            title="설치 도메인"
            value={String(data.referrers.length)}
            suffix="곳"
            icon={<Globe className="text-indigo-500" />}
            caption="기간 내 접수 도메인"
          />
        )}
        <SummaryCard title={labels.db} value={data.summary.totalDb.toLocaleString()} suffix="건" icon={<Target className="text-cyan-500" />} />
        <SummaryCard title={labels.approved} value={data.summary.approvedDb.toLocaleString()} suffix="건" icon={<CheckCircle2 className="text-emerald-500" />} highlight />
        <SummaryCard title={labels.rejected} value={data.summary.rejectedDb.toLocaleString()} suffix="건" icon={<XCircle className="text-red-500" />} />
        {!isEmbed && (
          <SummaryCard title="전환율" value={String(data.summary.avgConvRate)} suffix="%" icon={<Percent className="text-purple-500" />} />
        )}
        {isEmbed && (
          <SummaryCard title="캠페인 수" value={String(data.campaigns.length)} suffix="개" icon={<BarChart3 className="text-purple-500" />} />
        )}
        <SummaryCard title={isCps ? '확정율' : '승인율'} value={String(data.summary.avgApprovalRate)} suffix="%" icon={<Percent className="text-emerald-500" />} />
        <SummaryCard
          title={isEmbed ? 'DB당 수익' : '클릭당 수익'}
          value={data.summary.epc.toLocaleString()}
          suffix="원"
          icon={<DollarSign className="text-amber-500" />}
          highlight
        />
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-4">전환 퍼널</h2>
          <div className="grid sm:grid-cols-4 gap-3">
            {[
              { label: labels.funnelClick, value: data.funnel.clicks, rate: null },
              {
                label: labels.funnelReceived,
                value: data.funnel.received,
                rate: isEmbed ? null : funnelRate(data.funnel.received, data.funnel.clicks),
              },
              { label: labels.funnelApproved, value: data.funnel.approved, rate: funnelRate(data.funnel.approved, data.funnel.received) },
              { label: '확정', value: data.funnel.confirmed, rate: funnelRate(data.funnel.confirmed, data.funnel.approved) },
            ].map((step, index) => (
              <div key={step.label} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div className="text-xs text-slate-500 mb-1">{step.label}</div>
                <div className="text-2xl font-bold text-slate-900">{step.value.toLocaleString()}</div>
                {step.rate && <div className="text-xs text-emerald-600 mt-2">전환 {step.rate}</div>}
                {index < 3 && <div className="hidden sm:block text-[10px] text-slate-300 mt-3">↓</div>}
              </div>
            ))}
          </div>
          <div className="mt-4 text-sm text-slate-500">
            {labels.revenue} <span className="font-bold text-emerald-600">{data.summary.confRevenue.toLocaleString()}원</span>
          </div>
        </div>

        <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
            <Lightbulb size={20} className="text-yellow-500" />
            {isCps || isEmbed ? '유입 TOP' : '채널 TOP'}
          </h2>
          <div className="space-y-4">
            {(isCps || isEmbed ? data.referrers : data.channels).length === 0 ? (
              <p className="text-sm text-slate-500">
                {isCps || isEmbed ? '유입 데이터가 없습니다.' : '채널 데이터가 없습니다.'}
              </p>
            ) : (isCps || isEmbed ? data.referrers : data.channels).map((item) => {
              const label = isCps || isEmbed ? (item as { domain: string }).domain : (item as { channel: string }).channel;
              const pct = item.percentage;
              const clicks = item.clicks;
              return (
                <div key={label}>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="font-medium text-slate-700 truncate pr-2">{label}</span>
                    <span className="text-slate-500">{pct}%</span>
                  </div>
                  <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div
                      className={`h-full rounded-full ${isCps ? 'bg-violet-500' : isEmbed ? 'bg-cyan-500' : 'bg-emerald-500'}`}
                      style={{ width: `${pct}%` }}
                    />
                  </div>
                  <div className="text-xs text-slate-400 mt-1">
                    {isEmbed ? `DB ${clicks}` : `클릭 ${clicks}`}
                    {isCpa && 'dbs' in item && ` · DB ${(item as { dbs: number }).dbs} · 승인 ${(item as { approved: number }).approved}`}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      <div className="grid lg:grid-cols-3 gap-8 mb-8">
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
          <h2 className="text-lg font-bold text-slate-900 mb-6">일별 성과 추이</h2>
          <div className="h-72 w-full">
            {loading ? (
              <div className="h-full flex items-center justify-center"><Skeleton className="h-full w-full rounded-xl" /></div>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                  <defs>
                    <linearGradient id="colorClick" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#3b82f6" stopOpacity={0.1}/><stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/></linearGradient>
                    <linearGradient id="colorDb" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#06b6d4" stopOpacity={0.2}/><stop offset="95%" stopColor="#06b6d4" stopOpacity={0}/></linearGradient>
                    <linearGradient id="colorApproval" x1="0" y1="0" x2="0" y2="1"><stop offset="5%" stopColor="#10b981" stopOpacity={0.2}/><stop offset="95%" stopColor="#10b981" stopOpacity={0}/></linearGradient>
                  </defs>
                  <XAxis dataKey="date" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                  <Tooltip />
                  {!isEmbed && (
                    <Area type="monotone" dataKey="click" stroke="#3b82f6" fill="url(#colorClick)" strokeWidth={2} name="클릭" />
                  )}
                  <Area type="monotone" dataKey="db" stroke="#06b6d4" fill="url(#colorDb)" strokeWidth={2} name={isCps ? '주문' : 'DB'} />
                  <Area type="monotone" dataKey="approval" stroke="#10b981" fill="url(#colorApproval)" strokeWidth={2} name={isCps ? '확정' : '승인'} />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>

        <div className="space-y-8">
          <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
              <Globe size={18} className="text-cyan-500" />
              {isEmbed ? '설치 도메인' : '유입 도메인'}
            </h2>
            <div className="space-y-3">
              {data.referrers.length === 0 ? (
                <p className="text-sm text-slate-500">{isEmbed ? '설치 도메인 데이터가 없습니다.' : '유입 도메인 데이터가 없습니다.'}</p>
              ) : data.referrers.map((item) => (
                <div key={item.domain}>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="font-medium text-slate-700 truncate pr-2">{item.domain}</span>
                    <span className="text-slate-500 shrink-0">{item.clicks}{isEmbed ? '건' : '회'}</span>
                  </div>
                  <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div className="h-full bg-cyan-500 rounded-full" style={{ width: `${item.percentage}%` }} />
                  </div>
                </div>
              ))}
            </div>
          </div>

          {!isEmbed ? (
            <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <Smartphone size={18} className="text-violet-500" />
                기기별 클릭
              </h2>
              <div className="space-y-3">
                {data.devices.length === 0 ? (
                  <p className="text-sm text-slate-500">기기 데이터가 없습니다.</p>
                ) : data.devices.map((item) => (
                  <div key={item.deviceCode}>
                    <div className="flex justify-between text-sm mb-1">
                      <span className="font-medium text-slate-700">{item.device}</span>
                      <span className="text-slate-500">{item.percentage}%</span>
                    </div>
                    <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-violet-500 rounded-full" style={{ width: `${item.percentage}%` }} />
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <BarChart3 size={18} className="text-violet-500" />
                캠페인별 접수
              </h2>
              <div className="space-y-3">
                {data.campaigns.length === 0 ? (
                  <p className="text-sm text-slate-500">캠페인 데이터가 없습니다.</p>
                ) : data.campaigns.slice(0, 8).map((item) => (
                  <div key={item.campaign}>
                    <div className="flex justify-between text-sm mb-1 gap-2">
                      <span className="font-medium text-slate-700 truncate">{item.campaign}</span>
                      <span className="text-slate-500 shrink-0">{item.received}건</span>
                    </div>
                    <div className="text-xs text-slate-400">
                      승인 {item.approved} · {item.appRate} · {(item.confRev || 0).toLocaleString()}원
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {compareRows.length > 0 && (
        <div className={`bg-white rounded-2xl border p-6 shadow-sm mb-8 ${isCps ? 'border-violet-200' : 'border-emerald-200'}`}>
          <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <BarChart3 size={20} className={isCps ? 'text-violet-500' : 'text-emerald-500'} />
            링크 비교 ({compareRows.length}개)
          </h2>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">{isCps ? '쇼핑몰 / 코드' : '캠페인 / 채널 / 링크이름'}</th>
                  <th className="px-4 py-3 text-right">클릭</th>
                  <th className="px-4 py-3 text-right">{isCps ? '주문' : '접수'}</th>
                  <th className="px-4 py-3 text-right">{isCps ? '확정' : '승인'}</th>
                  <th className="px-4 py-3 text-right">전환율</th>
                  <th className="px-4 py-3 text-right">{isCps ? '확정율' : '승인율'}</th>
                  <th className="px-4 py-3 text-right">EPC</th>
                  <th className="px-4 py-3 text-right">확정수익</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {compareRows.map((item) => (
                  <CompareRow key={item.id} item={item} isCps={isCps} />
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div className="p-5 border-b border-slate-100 flex items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <TrendingUp size={20} className={isCps ? 'text-violet-500' : 'text-emerald-500'} />
            <div>
              <h2 className="text-lg font-bold text-slate-900">{labels.tableTitle}</h2>
              <p className="text-xs text-slate-500 mt-0.5">{labels.tableSub}</p>
            </div>
          </div>
          {!isEmbed && <p className="text-xs text-slate-500 shrink-0">최대 3개까지 선택해 비교</p>}
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                {!isEmbed && <th className="px-4 py-4">비교</th>}
                <th className="px-4 py-4">{labels.nameCol}</th>
                {!isCps && <th className="px-4 py-4">채널</th>}
                <th className="px-4 py-4">{isCps ? '머천트코드' : '링크이름'}</th>
                <th className="px-4 py-4 text-right">{isEmbed ? '접수' : '클릭'}</th>
                <th className="px-4 py-4 text-right">{isCps ? '주문' : '접수'}</th>
                <th className="px-4 py-4 text-right">{isCps ? '확정' : '승인'}</th>
                <th className="px-4 py-4 text-right">취소</th>
                {!isEmbed && <th className="px-4 py-4 text-right">전환율</th>}
                <th className="px-4 py-4 text-right">{isCps ? '확정율' : '승인율'}</th>
                <th className="px-4 py-4 text-right">{isEmbed ? 'DB당' : 'EPC'}</th>
                <th className="px-4 py-4 text-right">확정수익</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {tableRows.length === 0 ? (
                <DataTableEmpty
                  colSpan={isEmbed ? 9 : isCps ? 11 : 12}
                  title={isCps ? 'CPS 링크 성과가 없습니다' : isEmbed ? '외부위젯 성과가 없습니다' : '홍보 링크 성과가 없습니다'}
                  description={isCps
                    ? 'CPS 홍보링크를 배포한 뒤 클릭이 발생하면 성과가 표시됩니다.'
                    : isEmbed
                      ? '홈페이지·워드프레스에 상담 위젯을 설치하고 DB가 접수되면 표시됩니다.'
                      : '홍보 링크를 생성하고 트래픽을 유입하면 성과가 표시됩니다.'}
                  actionLabel={isCps ? 'CPS 링크 만들기' : '광고상품 찾기'}
                  actionTo={isCps ? '/partner/cps/links' : '/partner/search'}
                />
              ) : tableRows.map((item, index) => (
                <tr key={item.id || `${item.code}-${index}`} className={tableRowClass(index + 1)}>
                  {!isEmbed && (
                    <td className="px-4 py-4">
                      <input
                        type="checkbox"
                        checked={activeCompareIds.includes(item.id)}
                        onChange={() => toggleCompare(item.id)}
                        disabled={!activeCompareIds.includes(item.id) && activeCompareIds.length >= 3}
                        className={`rounded border-slate-300 focus:ring-2 ${isCps ? 'text-violet-600 focus:ring-violet-500' : 'text-emerald-600 focus:ring-emerald-500'}`}
                      />
                    </td>
                  )}
                  <td className="px-4 py-4 font-medium text-slate-900">
                    <RankBadge rank={index + 1} />
                    {item.campaign}
                  </td>
                  {!isCps && <td className="px-4 py-4 text-slate-600">{item.channel}</td>}
                  <td className="px-4 py-4 text-slate-600 font-mono text-xs">{item.linkName || item.code || '-'}</td>
                  <td className="px-4 py-4 text-right">{item.clicks.toLocaleString()}</td>
                  <td className="px-4 py-4 text-right">{item.received.toLocaleString()}</td>
                  <td className="px-4 py-4 text-right text-emerald-600 font-medium">{item.approved.toLocaleString()}</td>
                  <td className="px-4 py-4 text-right text-red-500">{item.canceled.toLocaleString()}</td>
                  {!isEmbed && (
                    <td className="px-4 py-4 text-right">
                      <div className="flex flex-col items-end gap-1">
                        <span>{item.convRate}%</span>
                        <ProgressBar value={item.convRate} accent="emerald" showLabel={false} size="sm" />
                      </div>
                    </td>
                  )}
                  <td className="px-4 py-4 text-right">{item.appRate}%</td>
                  <td className="px-4 py-4 text-right">{item.epc.toLocaleString()}원</td>
                  <td className="px-4 py-4 text-right font-bold text-emerald-600">{item.confRev.toLocaleString()}원</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {isCpa && (
        <div className="grid lg:grid-cols-2 gap-8 mb-8">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100">
              <h2 className="text-lg font-bold text-slate-900">링크이름별 성과</h2>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3">링크이름</th>
                    <th className="px-4 py-3">채널</th>
                    <th className="px-4 py-3 text-right">클릭</th>
                    <th className="px-4 py-3 text-right">DB</th>
                    <th className="px-4 py-3 text-right">승인</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {data.linkNames.map((item) => (
                    <tr key={`${item.linkName}-${item.channel}`}>
                      <td className="px-4 py-3 font-medium">{item.linkName}</td>
                      <td className="px-4 py-3 text-slate-600">{item.channel}</td>
                      <td className="px-4 py-3 text-right">{item.clicks}</td>
                      <td className="px-4 py-3 text-right">{item.dbs}</td>
                      <td className="px-4 py-3 text-right text-emerald-600">{item.approved}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-5 border-b border-slate-100">
              <h2 className="text-lg font-bold text-slate-900">캠페인별 성과</h2>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3">캠페인</th>
                    <th className="px-4 py-3 text-right">클릭</th>
                    <th className="px-4 py-3 text-right">접수</th>
                    <th className="px-4 py-3 text-right">승인</th>
                    <th className="px-4 py-3 text-right">확정수익</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {data.campaigns.map((item) => (
                    <tr key={item.campaign}>
                      <td className="px-4 py-3 font-medium">{item.campaign}</td>
                      <td className="px-4 py-3 text-right">{item.clicks.toLocaleString()}</td>
                      <td className="px-4 py-3 text-right">{item.received.toLocaleString()}</td>
                      <td className="px-4 py-3 text-right">{item.approved.toLocaleString()}</td>
                      <td className="px-4 py-3 text-right font-bold text-emerald-600">{item.confRev.toLocaleString()}원</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
      </>
      )}
    </PartnerLayout>
  );
}

function CompareRow({ item, isCps }: { item: PartnerAnalyticsLinkRow; isCps: boolean }) {
  return (
    <tr className="hover:bg-slate-50">
      <td className="px-4 py-3">
        <div className="font-medium text-slate-900">{item.campaign}</div>
        <div className="text-xs text-slate-500">
          {isCps ? item.linkName : `${item.channel} · ${item.linkName}`}
        </div>
      </td>
      <td className="px-4 py-3 text-right">{item.clicks.toLocaleString()}</td>
      <td className="px-4 py-3 text-right">{item.received.toLocaleString()}</td>
      <td className="px-4 py-3 text-right text-emerald-600">{item.approved.toLocaleString()}</td>
      <td className="px-4 py-3 text-right">{item.convRate}%</td>
      <td className="px-4 py-3 text-right">{item.appRate}%</td>
      <td className="px-4 py-3 text-right">{item.epc.toLocaleString()}원</td>
      <td className="px-4 py-3 text-right font-bold text-emerald-600">{item.confRev.toLocaleString()}원</td>
    </tr>
  );
}
