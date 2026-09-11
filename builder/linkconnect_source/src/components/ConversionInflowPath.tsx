import { useState, type MouseEvent, type ReactNode } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';

export type ConversionInflowData = {
  partner?: string;
  channel?: string;
  source?: string;
  subId?: string;
  linkCode?: string;
  pageUrl?: string;
  pageHost?: string;
  landingUrl?: string;
  referer?: string;
  utmSource?: string;
  utmMedium?: string;
  utmCampaign?: string;
  ip?: string;
  device?: string;
  userAgent?: string;
  abuseScore?: number;
  isDuplicate?: boolean;
};

function isEmbed(source?: string, channel?: string) {
  const s = (source || '').toLowerCase();
  const c = (channel || '').toLowerCase();
  return s === 'embed' || ['embed', 'wordpress', 'widget', 'external'].includes(c);
}

function refererHost(referer?: string) {
  const raw = (referer || '').trim();
  if (!raw) return '';
  try {
    return new URL(raw).host || raw;
  } catch {
    return raw;
  }
}

export function buildInflowSummary(data: ConversionInflowData) {
  const parts: string[] = [];
  if (data.channel) parts.push(data.channel);
  if (data.device) parts.push(data.device);
  const host = data.pageHost || refererHost(data.referer);
  if (host) parts.push(host);
  if (data.ip) parts.push(data.ip);
  return parts.length > 0 ? parts.join(' · ') : '-';
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="min-w-0">
      <div className="text-[11px] text-slate-400 mb-0.5">{label}</div>
      <div className="text-xs text-slate-700 break-all">{children || '-'}</div>
    </div>
  );
}

export function ConversionInflowDetails({
  data,
  showPartner = false,
  showAbuse = false,
}: {
  data: ConversionInflowData;
  showPartner?: boolean;
  showAbuse?: boolean;
}) {
  const embed = isEmbed(data.source, data.channel);

  return (
    <div className="space-y-3">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {showPartner ? (
          <Field label="유입 파트너">
            <span className="font-mono text-cyan-700">{data.partner || '-'}</span>
          </Field>
        ) : null}
        <Field label="매체(채널)">
          <span className="inline-flex flex-wrap items-center gap-1.5">
            <span>{data.channel || '-'}</span>
            {embed ? (
              <span className="inline-flex px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10px] font-bold">외부위젯</span>
            ) : null}
            {data.source === 'call' ? (
              <span className="inline-flex px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 text-[10px] font-bold">콜디비</span>
            ) : null}
          </span>
        </Field>
        <Field label="기기">{data.device || '-'}</Field>
        <Field label="IP">
          <span className="font-mono">{data.ip || '-'}</span>
        </Field>
        <Field label="링크명(subId)">
          <span className="font-mono">{data.subId || '-'}</span>
        </Field>
        <Field label="링크코드">
          <span className="font-mono">{data.linkCode || '-'}</span>
        </Field>
      </div>

      <Field label="설치/제출 페이지">
        {data.pageUrl ? (
          <a href={data.pageUrl} target="_blank" rel="noreferrer" className="text-cyan-700 hover:underline">
            {data.pageHost ? `${data.pageHost} · ` : ''}
            {data.pageUrl}
          </a>
        ) : (
          '-'
        )}
      </Field>

      <Field label="랜딩 URL">
        {data.landingUrl ? (
          <a href={data.landingUrl} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">
            {data.landingUrl}
          </a>
        ) : (
          '-'
        )}
      </Field>

      <Field label="Referer">
        <span title={data.referer || ''}>{data.referer || '-'}</span>
      </Field>

      <div className="bg-slate-50 rounded-lg p-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <Field label="UTM Source">
          <span className="font-mono">{data.utmSource || '-'}</span>
        </Field>
        <Field label="UTM Medium">
          <span className="font-mono">{data.utmMedium || '-'}</span>
        </Field>
        <Field label="UTM Campaign">
          <span className="font-mono">{data.utmCampaign || '-'}</span>
        </Field>
      </div>

      {showAbuse ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-slate-100 pt-3">
          <Field label="어뷰징 점수">
            <span className={(data.abuseScore || 0) > 0 ? 'text-orange-600 font-semibold' : ''}>
              {typeof data.abuseScore === 'number' ? data.abuseScore : '-'}
            </span>
          </Field>
          <Field label="중복 의심">{data.isDuplicate ? '예' : '아니오'}</Field>
          {data.userAgent ? (
            <div className="sm:col-span-2">
              <Field label="User-Agent">
                <span className="font-mono text-[11px] text-slate-500">{data.userAgent}</span>
              </Field>
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

export function ConversionInflowCell({
  data,
  showPartner = false,
  showAbuse = false,
}: {
  data: ConversionInflowData;
  showPartner?: boolean;
  showAbuse?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const embed = isEmbed(data.source, data.channel);
  const summary = buildInflowSummary(data);

  const toggle = (e: MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setOpen((v) => !v);
  };

  return (
    <div className="min-w-[160px] max-w-[260px]" onClick={(e) => e.stopPropagation()}>
      <button
        type="button"
        onClick={toggle}
        className="w-full text-left group"
        title="클릭하여 유입 상세 보기"
      >
        <div className="flex items-start gap-1">
          <div className="min-w-0 flex-1 space-y-0.5">
            <div className="text-slate-700 text-xs font-medium truncate">{summary}</div>
            <div className="flex flex-wrap gap-1">
              {embed ? (
                <span className="inline-flex px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 text-[10px] font-bold">외부위젯</span>
              ) : null}
              {data.source === 'call' ? (
                <span className="inline-flex px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 text-[10px] font-bold">콜디비</span>
              ) : null}
              {data.utmSource ? (
                <span className="inline-flex px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[10px] font-mono truncate max-w-[120px]">
                  {data.utmSource}
                </span>
              ) : null}
              {showAbuse && data.isDuplicate ? (
                <span className="inline-flex px-1.5 py-0.5 rounded bg-orange-50 text-orange-700 text-[10px] font-bold">중복</span>
              ) : null}
            </div>
          </div>
          <span className="shrink-0 text-slate-400 mt-0.5">
            {open ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
          </span>
        </div>
      </button>
      {open ? (
        <div className="mt-2 p-2.5 rounded-xl border border-slate-200 bg-white shadow-sm">
          <ConversionInflowDetails data={data} showPartner={showPartner} showAbuse={showAbuse} />
        </div>
      ) : null}
    </div>
  );
}
