import { useCallback, useEffect, useState } from 'react';
import {
  AdminPromoGuideDetail,
  AdminPromoGuideLog,
  adminPromoGuideAction,
  fetchAdminPromoGuide,
  fetchAdminPromoGuideLogs,
} from '../../lib/api';
import {
  promoGuideApprovalLabel,
  promoGuideStatusLabel,
  promoGuideStatusStyle,
} from '../../lib/campaignPromoGuide';
import { Eye, History, Loader2, X } from 'lucide-react';

type Props = {
  campaignId: number;
  campaignName: string;
  advertiserName: string;
  onClose: () => void;
  onUpdated?: () => void;
};

function formatDate(value?: string) {
  if (!value) return '-';
  const d = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('ko-KR', { dateStyle: 'short', timeStyle: 'short' });
}

export function AdminCampaignPromoGuidePanel({ campaignId, campaignName, advertiserName, onClose, onUpdated }: Props) {
  const [loading, setLoading] = useState(true);
  const [acting, setActing] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [guide, setGuide] = useState<AdminPromoGuideDetail | null>(null);
  const [logs, setLogs] = useState<AdminPromoGuideLog[]>([]);
  const [showLogs, setShowLogs] = useState(false);
  const [revisionReason, setRevisionReason] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await fetchAdminPromoGuide(campaignId);
      const normalized = {
        ...data,
        exists: Boolean(data.exists) || Boolean(data.guideId),
      };
      setGuide(normalized);
      if (normalized.guideId) {
        const logData = await fetchAdminPromoGuideLogs(normalized.guideId);
        setLogs(logData.items);
      } else {
        setLogs([]);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : '홍보 가이드를 불러오지 못했습니다.');
      setGuide(null);
      setLogs([]);
    } finally {
      setLoading(false);
    }
  }, [campaignId]);

  useEffect(() => {
    load();
  }, [load]);

  const runAction = async (action: 'publish' | 'hide' | 'review' | 'draft' | 'request_revision') => {
    if (!guide?.guideId) return;
    if (action === 'request_revision' && revisionReason.trim() === '') {
      setError('수정 요청 사유를 입력해 주세요.');
      return;
    }

    setActing(true);
    setError('');
    setMessage('');
    try {
      const result = await adminPromoGuideAction({
        action,
        guideId: guide.guideId,
        cpId: campaignId,
        reason: action === 'request_revision' ? revisionReason.trim() : undefined,
      });
      setMessage(result.message);
      if (result.guide) {
        setGuide({ ...result.guide, exists: true, campaignName, campaignId });
      }
      if (guide.guideId) {
        const logData = await fetchAdminPromoGuideLogs(guide.guideId);
        setLogs(logData.items);
      }
      onUpdated?.();
    } catch (err) {
      setError(err instanceof Error ? err.message : '처리에 실패했습니다.');
    } finally {
      setActing(false);
    }
  };

  const createGuide = async () => {
    setActing(true);
    setError('');
    setMessage('');
    try {
      const result = await adminPromoGuideAction({
        action: 'create',
        cpId: campaignId,
      });
      setMessage(result.message);
      if (result.guide) {
        setGuide({ ...result.guide, exists: true, campaignName, campaignId });
        if (result.guide.guideId) {
          const logData = await fetchAdminPromoGuideLogs(result.guide.guideId);
          setLogs(logData.items);
        }
      } else {
        await load();
      }
      onUpdated?.();
    } catch (err) {
      setError(err instanceof Error ? err.message : '가이드 생성에 실패했습니다.');
    } finally {
      setActing(false);
    }
  };

  const status = guide?.guideStatus ?? 'draft';

  return (
    <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/50">
      <div className="bg-white w-full sm:max-w-3xl max-h-[92vh] rounded-t-2xl sm:rounded-2xl border border-slate-200 shadow-xl flex flex-col overflow-hidden">
        <div className="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-start justify-between gap-3">
          <div>
            <p className="text-xs font-semibold text-cyan-700 mb-1">홍보 가이드 검수</p>
            <h3 className="font-bold text-slate-900">{campaignName}</h3>
            <p className="text-sm text-slate-500">{advertiserName}</p>
          </div>
          <button type="button" onClick={onClose} className="p-2 rounded-lg text-slate-500 hover:bg-slate-200">
            <X size={18} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto p-5 space-y-5">
          {loading ? (
            <div className="py-16 flex items-center justify-center text-slate-500 gap-2">
              <Loader2 className="animate-spin" size={20} /> 불러오는 중...
            </div>
          ) : error && !guide?.exists ? (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
          ) : !guide?.exists && !(guide?.guideId) ? (
            <div className="py-8 text-center space-y-4">
              <p className="text-sm text-slate-500">등록된 홍보 가이드가 없습니다.</p>
              <p className="text-xs text-slate-400">
                광고주가 아직 저장하지 않았거나, 저장이 실패했을 수 있습니다. 가이드 껍데기를 만든 뒤 광고주 화면에서 내용을 다시 저장해 주세요.
              </p>
              <button
                type="button"
                disabled={acting}
                onClick={() => void createGuide()}
                className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-cyan-600 text-white text-sm font-bold hover:bg-cyan-700 disabled:opacity-50"
              >
                {acting ? <Loader2 size={16} className="animate-spin" /> : null}
                가이드 생성 (관리자)
              </button>
            </div>
          ) : (
            <>
              {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
              {message ? <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{message}</div> : null}

              <div className="grid sm:grid-cols-2 gap-3 text-sm">
                <div className="rounded-xl border border-slate-200 p-3">
                  <p className="text-xs text-slate-500 mb-1">가이드 상태</p>
                  <span className={`inline-flex px-2 py-1 rounded-lg text-xs font-bold border ${promoGuideStatusStyle(status)}`}>
                    {guide!.guideStatusLabel || promoGuideStatusLabel(status)}
                  </span>
                </div>
                <div className="rounded-xl border border-slate-200 p-3">
                  <p className="text-xs text-slate-500 mb-1">최종 수정 / 공개일</p>
                  <p className="font-medium text-slate-800">{formatDate(guide!.updatedAt)}</p>
                  <p className="text-xs text-slate-500 mt-0.5">공개: {formatDate(guide!.publishedAt)}</p>
                </div>
              </div>

              {guide!.revisionReason ? (
                <div className="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
                  <p className="font-bold mb-1">수정 요청 사유</p>
                  <p className="whitespace-pre-wrap break-words">{guide!.revisionReason}</p>
                </div>
              ) : null}

              {(guide!.promotionPoints?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">핵심 홍보 포인트</h4>
                  <ul className="list-disc pl-5 space-y-1 text-sm text-slate-700">
                    {guide!.promotionPoints!.map((item) => (
                      <li key={item} className="break-words">{item}</li>
                    ))}
                  </ul>
                </section>
              ) : (
                <section className="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">
                  핵심 홍보 포인트가 아직 입력되지 않았습니다.
                </section>
              )}

              {(guide!.recommendedKeywords?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">추천키워드 ({guide!.recommendedKeywords!.length})</h4>
                  <div className="flex flex-wrap gap-1.5">
                    {guide!.recommendedKeywords!.map((kw) => (
                      <span key={kw} className="px-2 py-1 rounded-lg bg-slate-100 text-xs font-medium text-slate-700">{kw}</span>
                    ))}
                  </div>
                </section>
              ) : null}

              {(guide!.forbiddenWords?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">금지어 ({guide!.forbiddenWords!.length})</h4>
                  <div className="flex flex-wrap gap-1.5">
                    {guide!.forbiddenWords!.map((kw) => (
                      <span key={kw} className="px-2 py-1 rounded-lg bg-rose-50 text-xs font-medium text-rose-700">{kw}</span>
                    ))}
                  </div>
                </section>
              ) : null}

              {(guide!.images?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">이미지 ({guide!.images!.length})</h4>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {guide!.images!.map((img) => (
                      <a
                        key={img.id}
                        href={img.downloadUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="block rounded-xl border border-slate-200 overflow-hidden bg-slate-50"
                      >
                        <img src={img.downloadUrl} alt={img.imageTitle || img.originalFilename} className="w-full aspect-video object-contain bg-white" />
                        <p className="text-xs p-2 truncate text-slate-600">{img.imageTitle || img.originalFilename}</p>
                      </a>
                    ))}
                  </div>
                </section>
              ) : (
                <section className="rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">
                  등록된 소재(이미지)가 없습니다.
                </section>
              )}

              {(guide!.precautions?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">유의사항</h4>
                  <ul className="list-disc pl-5 space-y-1 text-sm text-slate-700">
                    {guide!.precautions!.map((item) => (
                      <li key={item} className="break-words">{item}</li>
                    ))}
                  </ul>
                </section>
              ) : null}

              {(guide!.validDbRules?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">유효 DB 기준</h4>
                  <ul className="list-disc pl-5 space-y-1 text-sm text-slate-700">
                    {guide!.validDbRules!.map((item) => (
                      <li key={item} className="break-words">{item}</li>
                    ))}
                  </ul>
                </section>
              ) : null}

              {(guide!.invalidDbRules?.length ?? 0) > 0 ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2">무효 DB 기준</h4>
                  <ul className="list-disc pl-5 space-y-1 text-sm text-slate-700">
                    {guide!.invalidDbRules!.map((item) => (
                      <li key={item} className="break-words">{item}</li>
                    ))}
                  </ul>
                </section>
              ) : null}

              {guide!.approvalType ? (
                <section className="rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm text-cyan-900">
                  {promoGuideApprovalLabel(guide!.approvalType)}
                </section>
              ) : null}

              {guide?.guideId ? (
              <div className="rounded-xl border border-slate-200 p-4 space-y-3">
                <p className="text-sm font-bold text-slate-800">관리 작업</p>
                <div className="flex flex-wrap gap-2">
                  <button type="button" disabled={acting} onClick={() => runAction('publish')} className="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold disabled:opacity-50">파트너 공개</button>
                  <button type="button" disabled={acting} onClick={() => runAction('review')} className="px-3 py-2 rounded-lg bg-amber-500 text-white text-xs font-bold disabled:opacity-50">검토 대기</button>
                  <button type="button" disabled={acting} onClick={() => runAction('hide')} className="px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold disabled:opacity-50">비공개</button>
                  <button type="button" disabled={acting} onClick={() => setShowLogs((v) => !v)} className="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-xs font-bold inline-flex items-center gap-1">
                    <History size={14} /> 변경 이력
                  </button>
                </div>
                <div>
                  <label className="text-xs font-semibold text-slate-600 block mb-1">수정 요청 사유</label>
                  <textarea
                    value={revisionReason}
                    onChange={(e) => setRevisionReason(e.target.value)}
                    rows={2}
                    maxLength={500}
                    placeholder="광고주에게 전달할 수정 요청 사유"
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-cyan-500"
                  />
                  <button type="button" disabled={acting} onClick={() => runAction('request_revision')} className="mt-2 px-3 py-2 rounded-lg bg-orange-500 text-white text-xs font-bold disabled:opacity-50">
                    수정 요청
                  </button>
                </div>
              </div>
              ) : null}

              {showLogs ? (
                <section>
                  <h4 className="text-sm font-bold text-slate-900 mb-2 flex items-center gap-1.5">
                    <Eye size={16} /> 변경 이력 ({logs.length})
                  </h4>
                  {logs.length === 0 ? (
                    <p className="text-sm text-slate-500">기록된 이력이 없습니다.</p>
                  ) : (
                    <div className="space-y-2">
                      {logs.map((log) => (
                        <div key={log.id} className="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                          <div className="flex flex-wrap justify-between gap-2 mb-1">
                            <span className="font-bold">{log.summary}</span>
                            <span className="text-slate-500">{formatDate(log.createdAt)}</span>
                          </div>
                          <p>
                            {log.fromStatusLabel || log.fromStatus} → {log.toStatusLabel || log.toStatus}
                            <span className="text-slate-500 ml-2">({log.actorType} {log.actorId})</span>
                          </p>
                          {log.revisionReason ? <p className="text-orange-700 mt-1 break-words">사유: {log.revisionReason}</p> : null}
                        </div>
                      ))}
                    </div>
                  )}
                </section>
              ) : null}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
