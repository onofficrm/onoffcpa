import React, { useEffect, useState } from 'react';
import { Bell, CheckCircle2, Mail, AlertTriangle, Send } from 'lucide-react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import {
  fetchMerchantNotificationPrefs,
  MerchantNotificationPrefsResponse,
  saveMerchantNotificationPrefs,
  sendMerchantNotificationTest,
} from '../../lib/api';

type DbMode = 'realtime' | 'digest' | 'off';

const MODE_OPTIONS: Array<{ value: DbMode; label: string; help: string }> = [
  { value: 'realtime', label: '즉시 발송', help: 'DB가 들어오면 바로 메일로 알립니다.' },
  { value: 'digest', label: '하루 1회 요약', help: '쌓인 건수를 하루에 한 번 요약해 보냅니다.' },
  { value: 'off', label: '끄기', help: '이메일 알림을 보내지 않습니다. (센터 알림은 유지)' },
];

export function AdvertiserSettings() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [data, setData] = useState<MerchantNotificationPrefsResponse | null>(null);
  const [dbReceived, setDbReceived] = useState<DbMode>('realtime');
  const [lowBalance, setLowBalance] = useState(true);

  const applyPayload = (payload: MerchantNotificationPrefsResponse) => {
    setData(payload);
    const mode = (payload.prefs?.dbReceived || 'realtime') as DbMode;
    setDbReceived(['realtime', 'digest', 'off'].includes(mode) ? mode : 'realtime');
    setLowBalance(payload.prefs?.lowBalance !== false);
  };

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const payload = await fetchMerchantNotificationPrefs();
      applyPayload(payload);
    } catch (err) {
      setError(err instanceof Error ? err.message : '알림 설정을 불러오지 못했습니다.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const handleSave = async () => {
    setSaving(true);
    setError('');
    setMessage('');
    try {
      const payload = await saveMerchantNotificationPrefs({
        dbReceived,
        lowBalance,
      });
      applyPayload(payload);
      setMessage(payload.message || '알림 설정이 저장되었습니다.');
    } catch (err) {
      setError(err instanceof Error ? err.message : '저장에 실패했습니다.');
    } finally {
      setSaving(false);
    }
  };

  const handleTest = async () => {
    setTesting(true);
    setError('');
    setMessage('');
    try {
      const payload = await sendMerchantNotificationTest();
      applyPayload(payload);
      setMessage(payload.message || '테스트 메일을 발송했습니다.');
    } catch (err) {
      setError(err instanceof Error ? err.message : '테스트 메일 발송에 실패했습니다.');
    } finally {
      setTesting(false);
    }
  };

  const systemReady = Boolean(data?.system?.ready);
  const recipientEmail = data?.recipient?.email || '';

  return (
    <AdvertiserLayout activeMenu="settings" title="알림 설정">
      <div className="flex flex-col mb-8 -mt-2">
        <p className="text-slate-500">신규 DB 접수와 잔액 부족 알림을 이메일로 받을지 설정합니다.</p>
      </div>

      {(error || message) && (
        <div
          className={`mb-6 rounded-xl border px-4 py-3 text-sm ${
            error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'
          }`}
        >
          {error || message}
        </div>
      )}

      {loading ? (
        <div className="rounded-2xl border border-slate-200 bg-white p-8 text-slate-500">불러오는 중…</div>
      ) : (
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
          <section className="xl:col-span-2 space-y-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-2 mb-1">
                <Bell className="text-cyan-600" size={20} />
                <h2 className="text-lg font-bold text-slate-900">신규 DB 메일 알림</h2>
              </div>
              <p className="text-sm text-slate-500 mb-5">
                상담 DB가 생성되면 이름·연락처·지역 요약을 메일로 받습니다. 광고주센터 알림센터 표시는 항상 유지됩니다.
              </p>

              <div className="grid gap-3">
                {MODE_OPTIONS.map((opt) => {
                  const active = dbReceived === opt.value;
                  return (
                    <label
                      key={opt.value}
                      className={`flex items-start gap-3 rounded-xl border px-4 py-3 cursor-pointer transition-colors ${
                        active ? 'border-cyan-300 bg-cyan-50/70' : 'border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <input
                        type="radio"
                        name="dbReceived"
                        className="mt-1"
                        checked={active}
                        onChange={() => setDbReceived(opt.value)}
                      />
                      <span>
                        <span className="block text-sm font-bold text-slate-900">{opt.label}</span>
                        <span className="block text-xs text-slate-500 mt-0.5">{opt.help}</span>
                      </span>
                    </label>
                  );
                })}
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="text-lg font-bold text-slate-900 mb-1">잔액 부족 알림</h2>
              <p className="text-sm text-slate-500 mb-4">광고비 잔액이 기준 이하일 때 이메일로 안내합니다.</p>
              <label className="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                <input type="checkbox" checked={lowBalance} onChange={(e) => setLowBalance(e.target.checked)} />
                잔액 부족 이메일 받기
              </label>
            </div>

            <div className="flex flex-wrap gap-3">
              <button
                type="button"
                onClick={handleSave}
                disabled={saving}
                className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-sm font-bold disabled:opacity-60"
              >
                <CheckCircle2 size={16} />
                {saving ? '저장 중…' : '설정 저장'}
              </button>
              <button
                type="button"
                onClick={handleTest}
                disabled={testing || !recipientEmail}
                className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-800 text-sm font-bold hover:bg-slate-50 disabled:opacity-60"
              >
                <Send size={16} />
                {testing ? '발송 중…' : '테스트 메일 보내기'}
              </button>
            </div>
          </section>

          <aside className="space-y-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div className="flex items-center gap-2 mb-3">
                <Mail size={18} className="text-slate-700" />
                <h3 className="font-bold text-slate-900">수신 이메일</h3>
              </div>
              {recipientEmail ? (
                <p className="text-sm text-slate-700 break-all font-medium">{recipientEmail}</p>
              ) : (
                <p className="text-sm text-rose-600">회원정보에 이메일이 없습니다.</p>
              )}
              <p className="text-xs text-slate-500 mt-2">회원 계정의 이메일로 발송됩니다.</p>
            </div>

            <div
              className={`rounded-2xl border p-5 shadow-sm ${
                systemReady ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/70'
              }`}
            >
              <div className="flex items-center gap-2 mb-2">
                {systemReady ? (
                  <CheckCircle2 size={18} className="text-emerald-600" />
                ) : (
                  <AlertTriangle size={18} className="text-amber-600" />
                )}
                <h3 className="font-bold text-slate-900">{systemReady ? '메일 발송 준비됨' : '메일 발송 점검 필요'}</h3>
              </div>
              <ul className="space-y-1.5 text-sm text-slate-700">
                <li>메일 기능: {data?.system?.mailer ? '정상' : '불가'}</li>
                <li>메일발송 사용: {data?.system?.emailUse ? 'ON' : 'OFF'}</li>
                <li>발신 메일: {data?.system?.fromConfigured ? '설정됨' : '미설정'}</li>
              </ul>
              {data?.system?.issues?.length ? (
                <ul className="mt-3 space-y-1 text-xs text-amber-800 list-disc pl-4">
                  {data.system.issues.map((issue) => (
                    <li key={issue}>{issue}</li>
                  ))}
                </ul>
              ) : null}
            </div>
          </aside>
        </div>
      )}
    </AdvertiserLayout>
  );
}
