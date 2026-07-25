import React, { useCallback, useEffect, useState } from 'react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { PhoneCall, Save } from 'lucide-react';
import { MerchantCallCampaign, fetchMerchantCallCampaigns, saveMerchantCallSettings } from '../../lib/api';

type Draft = { enabled: boolean; alias: string; forward1: string; forward2: string };

export function AdvertiserCall() {
  const [items, setItems] = useState<MerchantCallCampaign[]>([]);
  const [drafts, setDrafts] = useState<Record<number, Draft>>({});
  const [saving, setSaving] = useState<number | null>(null);
  const [message, setMessage] = useState('');

  const load = useCallback(() => {
    fetchMerchantCallCampaigns()
      .then((d) => {
        setItems(d.items);
        const next: Record<number, Draft> = {};
        d.items.forEach((c) => {
          next[c.cpId] = { enabled: c.enabled, alias: c.alias, forward1: c.forward1, forward2: c.forward2 };
        });
        setDrafts(next);
      })
      .catch(() => setItems([]));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const update = (cpId: number, patch: Partial<Draft>) => {
    setDrafts((prev) => ({ ...prev, [cpId]: { ...prev[cpId], ...patch } }));
  };

  const handleSave = async (cpId: number) => {
    const d = drafts[cpId];
    if (!d) return;
    setSaving(cpId);
    setMessage('');
    try {
      const res = await saveMerchantCallSettings({ cpId, enabled: d.enabled, alias: d.alias, forward1: d.forward1, forward2: d.forward2 });
      setMessage(res.message);
      load();
    } catch (e) {
      setMessage(e instanceof Error ? e.message : '저장에 실패했습니다.');
    } finally {
      setSaving(null);
    }
  };

  return (
    <AdvertiserLayout activeMenu="call" title="콜디비 설정">
      <div className="space-y-6">
        <div className="bg-cyan-50 border border-cyan-100 rounded-2xl p-5 text-sm text-cyan-900">
          <div className="flex items-center gap-2 font-bold mb-1">
            <PhoneCall size={18} /> 콜디비 안내
          </div>
          <p className="text-cyan-800/80">
            상품별로 <b>콜디비 수신 여부(ON/OFF)</b>와 <b>착신번호 1·2</b>, <b>상품 별칭</b>을 설정할 수 있습니다.
            걸려온 통화는 CPA 디비처럼 <b>디비 확인</b> 메뉴에서 승인/취소할 수 있습니다.
            (녹음 방식·업무시간·단가 등은 운영 정책상 관리자가 설정합니다.)
          </p>
        </div>

        {message && <p className="text-sm text-cyan-600 font-medium">{message}</p>}

        {items.length === 0 ? (
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center text-slate-400">등록된 광고상품이 없습니다.</div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {items.map((c) => {
              const d = drafts[c.cpId] ?? { enabled: c.enabled, alias: c.alias, forward1: c.forward1, forward2: c.forward2 };
              const blocked = !c.adminEnabled;
              return (
                <div key={c.cpId} className={`bg-white rounded-2xl border shadow-sm p-6 ${blocked ? 'border-slate-200 opacity-70' : 'border-slate-200'}`}>
                  <div className="flex items-start justify-between mb-4">
                    <div>
                      <div className="font-bold text-slate-900">{c.campaign}</div>
                      <div className="text-xs text-slate-400 font-mono">{c.code}</div>
                    </div>
                    <label className="inline-flex items-center gap-2 cursor-pointer">
                      <span className="text-xs font-bold text-slate-500">콜디비 수신</span>
                      <input
                        type="checkbox"
                        checked={d.enabled}
                        disabled={blocked}
                        onChange={(e) => update(c.cpId, { enabled: e.target.checked })}
                        className="w-5 h-5 accent-cyan-500"
                      />
                    </label>
                  </div>

                  {blocked && (
                    <div className="mb-3 text-xs font-bold text-amber-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                      관리자가 이 상품의 콜디비를 아직 활성화하지 않았습니다.
                    </div>
                  )}

                  <div className="space-y-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-500 mb-1">상품 별칭</label>
                      <input type="text" value={d.alias} disabled={blocked} onChange={(e) => update(c.cpId, { alias: e.target.value })} placeholder="예: 무료 상담 이벤트"
                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm disabled:bg-slate-100" />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs font-bold text-slate-500 mb-1">착신번호 1</label>
                        <input type="text" value={d.forward1} disabled={blocked} onChange={(e) => update(c.cpId, { forward1: e.target.value })} placeholder="01012345678"
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono disabled:bg-slate-100" />
                      </div>
                      <div>
                        <label className="block text-xs font-bold text-slate-500 mb-1">착신번호 2</label>
                        <input type="text" value={d.forward2} disabled={blocked} onChange={(e) => update(c.cpId, { forward2: e.target.value })} placeholder="(선택)"
                          className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono disabled:bg-slate-100" />
                      </div>
                    </div>
                    <div className="flex items-center justify-between pt-1">
                      <span className="text-xs text-slate-400">녹음 방식: {c.recordingMode === 'none' ? '녹음 안함' : '녹음(관리자 설정)'}</span>
                      <button type="button" onClick={() => handleSave(c.cpId)} disabled={saving === c.cpId || blocked}
                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-lg text-sm disabled:opacity-50">
                        <Save size={15} /> 저장
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </AdvertiserLayout>
  );
}
