import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { Gift, Megaphone, Clock, Calendar, Search, Plus, X, Edit3, Coins, Zap, Check, Ban, TrendingUp } from 'lucide-react';
import {
  AdminEvent,
  AdminEventReward,
  AdminEventSummary,
  autoAdminEventRankingRewards,
  bulkAdminRewardPay,
  fetchAdminEvents,
  fetchAdminEventRewards,
  fetchAdminEventRoi,
  saveAdminEvent,
  updateAdminEventReward,
  updateAdminEventStatus,
  EventRoiItem,
} from '../../lib/api';

const emptySummary: AdminEventSummary = {
  total: 0,
  active: 0,
  closing: 0,
  scheduled: 0,
  ended: 0,
};

const typeOptions = ['파트너 보너스', '단가 상승', '랭킹 이벤트', '신규 캠페인', '광고주 프로모션', '광고비 충전 보너스'];
const statusOptions = [
  { label: '전체 상태', value: '' },
  { label: '진행중', value: 'active' },
  { label: '마감임박', value: 'closing' },
  { label: '예정', value: 'scheduled' },
  { label: '종료', value: 'ended' },
];

type EventForm = {
  id: number;
  code: string;
  title: string;
  type: string;
  desc: string;
  period: string;
  product: string;
  benefit: string;
  campaigns: string;
  badges: string;
  ribbon: string;
  statusCode: string;
  featured: boolean;
  sort: number;
};

function toForm(event: AdminEvent | null, isNew = false): EventForm {
  if (!event || isNew) {
    return {
      id: 0,
      code: '자동생성',
      title: '',
      type: typeOptions[0],
      desc: '',
      period: '',
      product: '',
      benefit: '',
      campaigns: '',
      badges: '진행중',
      ribbon: '',
      statusCode: 'active',
      featured: false,
      sort: 0,
    };
  }

  return {
    id: event.id,
    code: event.code,
    title: event.title,
    type: event.type,
    desc: event.desc,
    period: event.period,
    product: event.product,
    benefit: event.benefit,
    campaigns: event.campaigns,
    badges: event.badges.join(', '),
    ribbon: event.ribbon,
    statusCode: event.statusCode,
    featured: event.featured,
    sort: event.sort,
  };
}

export function AdminEvents() {
  const [tab, setTab] = useState<'events' | 'rewards' | 'roi'>('events');
  const [items, setItems] = useState<AdminEvent[]>([]);
  const [rewards, setRewards] = useState<AdminEventReward[]>([]);
  const [roiItems, setRoiItems] = useState<EventRoiItem[]>([]);
  const [roiSummary, setRoiSummary] = useState({ totalReward: 0, totalRevenue: 0, netRoi: 0 });
  const [rewardStatus, setRewardStatus] = useState('');
  const [selectedRewardIds, setSelectedRewardIds] = useState<number[]>([]);
  const [bulkRewardLoading, setBulkRewardLoading] = useState(false);
  const [autoRankingLoading, setAutoRankingLoading] = useState(false);
  const [rewardProcessingId, setRewardProcessingId] = useState<number | null>(null);
  const [summary, setSummary] = useState<AdminEventSummary>(emptySummary);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState<EventForm>(toForm(null, true));
  const [saving, setSaving] = useState(false);
  const [processingId, setProcessingId] = useState<number | null>(null);

  const loadEvents = useCallback(() => {
    fetchAdminEvents({ q: search, status: statusFilter })
      .then((data) => {
        setItems(data.items);
        setSummary(data.summary);
      })
      .catch(() => {});
  }, [search, statusFilter]);

  useEffect(() => {
    loadEvents();
  }, [loadEvents]);

  const openCreate = () => {
    setForm(toForm(null, true));
    setModalOpen(true);
  };

  const openEdit = (event: AdminEvent) => {
    setForm(toForm(event));
    setModalOpen(true);
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await saveAdminEvent({
        action: form.id ? 'update' : 'create',
        evId: form.id,
        title: form.title,
        type: form.type,
        desc: form.desc,
        period: form.period,
        product: form.product,
        benefit: form.benefit,
        campaigns: form.campaigns,
        badges: form.badges.split(',').map((s) => s.trim()).filter(Boolean),
        ribbon: form.ribbon,
        statusCode: form.statusCode,
        featured: form.featured,
        sort: form.sort,
      });
      setModalOpen(false);
      loadEvents();
    } catch {
      // ignore
    } finally {
      setSaving(false);
    }
  };

  const handleStatus = async (evId: number, action: string) => {
    setProcessingId(evId);
    try {
      await updateAdminEventStatus({ action, evId });
      loadEvents();
    } catch {
      // ignore
    } finally {
      setProcessingId(null);
    }
  };

  const loadRewards = useCallback(() => {
    fetchAdminEventRewards({ status: rewardStatus })
      .then((data) => setRewards(data.items))
      .catch(() => setRewards([]));
  }, [rewardStatus]);

  const loadRoi = useCallback(() => {
    fetchAdminEventRoi()
      .then((data) => {
        setRoiItems(data.items);
        setRoiSummary(data.summary);
      })
      .catch(() => {
        setRoiItems([]);
      });
  }, []);

  useEffect(() => {
    if (tab === 'rewards') {
      loadRewards();
    } else if (tab === 'roi') {
      loadRoi();
    }
  }, [tab, loadRewards, loadRoi]);

  const handleBulkRewardPay = async () => {
    if (!selectedRewardIds.length) return;
    setBulkRewardLoading(true);
    try {
      const result = await bulkAdminRewardPay(selectedRewardIds);
      alert(result.message);
      setSelectedRewardIds([]);
      loadRewards();
    } catch {
      alert('일괄 지급에 실패했습니다.');
    } finally {
      setBulkRewardLoading(false);
    }
  };

  const toggleRewardSelect = (id: number) => {
    setSelectedRewardIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const handleAutoRanking = async () => {
    setAutoRankingLoading(true);
    try {
      const result = await autoAdminEventRankingRewards();
      alert(result.message);
      loadRewards();
    } catch {
      alert('랭킹 리워드 생성에 실패했습니다.');
    } finally {
      setAutoRankingLoading(false);
    }
  };

  const handleRewardAction = async (erId: number, action: 'pay_reward' | 'reject_reward') => {
    setRewardProcessingId(erId);
    try {
      await updateAdminEventReward({ action, erId });
      loadRewards();
    } catch {
      alert('리워드 처리에 실패했습니다.');
    } finally {
      setRewardProcessingId(null);
    }
  };

  return (
    <AdminLayout activeMenu="events" title="이벤트/프로모션 관리" description="이벤트 등록, 리워드 정산, 노출 상태를 관리하세요.">
      <div className="flex gap-2 mb-6">
        <button type="button" onClick={() => setTab('events')} className={`px-4 py-2 rounded-xl text-sm font-bold ${tab === 'events' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600'}`}>이벤트 목록</button>
        <button type="button" onClick={() => setTab('rewards')} className={`px-4 py-2 rounded-xl text-sm font-bold inline-flex items-center gap-2 ${tab === 'rewards' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600'}`}>
          <Coins size={16} /> 리워드 정산
        </button>
        <button type="button" onClick={() => setTab('roi')} className={`px-4 py-2 rounded-xl text-sm font-bold inline-flex items-center gap-2 ${tab === 'roi' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600'}`}>
          <TrendingUp size={16} /> ROI 분석
        </button>
      </div>

      {tab === 'roi' ? (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-6 border-b border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-4">
            <SummaryCard title="총 지급 리워드" value={roiSummary.totalReward.toLocaleString()} suffix="원" color="yellow" />
            <SummaryCard title="총 매출" value={roiSummary.totalRevenue.toLocaleString()} suffix="원" color="cyan" />
            <SummaryCard title="순 ROI" value={roiSummary.netRoi.toFixed(1)} suffix="%" color={roiSummary.netRoi >= 0 ? 'emerald' : 'red'} highlight />
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">이벤트</th>
                  <th className="px-4 py-3 text-right">참여 파트너</th>
                  <th className="px-4 py-3 text-right">신규 DB</th>
                  <th className="px-4 py-3 text-right">승인 DB</th>
                  <th className="px-4 py-3 text-right">매출</th>
                  <th className="px-4 py-3 text-right">지급 리워드</th>
                  <th className="px-4 py-3 text-right">ROI</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {roiItems.length === 0 ? (
                  <tr><td colSpan={7} className="px-4 py-10 text-center text-slate-500">ROI 데이터가 없습니다.</td></tr>
                ) : roiItems.map((row) => (
                  <tr key={row.evId} className="hover:bg-slate-50">
                    <td className="px-4 py-4">
                      <div className="font-bold text-slate-900">{row.title}</div>
                      <div className="text-xs text-slate-500 font-mono">{row.code}</div>
                    </td>
                    <td className="px-4 py-4 text-right">{row.participants.toLocaleString()}</td>
                    <td className="px-4 py-4 text-right">{row.totalDb.toLocaleString()}</td>
                    <td className="px-4 py-4 text-right text-emerald-600 font-bold">{row.approvedDb.toLocaleString()}</td>
                    <td className="px-4 py-4 text-right">{row.revenue.toLocaleString()}원</td>
                    <td className="px-4 py-4 text-right text-amber-600">{row.paidRewards.toLocaleString()}원</td>
                    <td className="px-4 py-4 text-right font-bold">
                      <span className={row.roi >= 0 ? 'text-emerald-600' : 'text-red-600'}>{row.roi.toFixed(1)}%</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : tab === 'rewards' ? (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-6 border-b border-slate-100 flex flex-wrap items-center gap-3 justify-between">
            <div className="flex items-center gap-3">
              <select value={rewardStatus} onChange={(e) => setRewardStatus(e.target.value)} className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                <option value="">전체 상태</option>
                <option value="pending">대기</option>
                <option value="paid">지급완료</option>
                <option value="rejected">거절</option>
              </select>
              <button type="button" onClick={loadRewards} className="px-4 py-2 bg-slate-100 rounded-xl text-sm font-bold">조회</button>
            </div>
            <button type="button" disabled={autoRankingLoading} onClick={handleAutoRanking} className="inline-flex items-center gap-2 px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-xl text-sm font-bold disabled:opacity-50">
              <Zap size={16} /> {autoRankingLoading ? '생성 중...' : '월말 랭킹 자동 정산'}
            </button>
            {selectedRewardIds.length > 0 && (
              <button type="button" disabled={bulkRewardLoading} onClick={handleBulkRewardPay} className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold disabled:opacity-50">
                <Check size={16} /> {selectedRewardIds.length}건 일괄 지급
              </button>
            )}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3 w-10"></th>
                  <th className="px-4 py-3">파트너</th>
                  <th className="px-4 py-3">이벤트</th>
                  <th className="px-4 py-3">조건</th>
                  <th className="px-4 py-3 text-right">금액</th>
                  <th className="px-4 py-3 text-center">상태</th>
                  <th className="px-4 py-3 text-center">관리</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {rewards.length === 0 ? (
                  <tr><td colSpan={7} className="px-4 py-10 text-center text-slate-500">리워드 내역이 없습니다.</td></tr>
                ) : rewards.map((row) => (
                  <tr key={row.id} className="hover:bg-slate-50">
                    <td className="px-4 py-4">
                      {row.status === 'pending' ? (
                        <input type="checkbox" checked={selectedRewardIds.includes(row.id)} onChange={() => toggleRewardSelect(row.id)} className="rounded border-slate-300" />
                      ) : null}
                    </td>
                    <td className="px-4 py-4">
                      <div className="font-bold text-slate-900">{row.name}</div>
                      <div className="text-xs text-slate-500 font-mono">{row.partner}</div>
                    </td>
                    <td className="px-4 py-4 text-slate-700">{row.eventTitle || '—'}</td>
                    <td className="px-4 py-4 text-slate-600">{row.condition}</td>
                    <td className="px-4 py-4 text-right font-bold">{row.amount.toLocaleString()}원</td>
                    <td className="px-4 py-4 text-center"><StatusBadge status={row.status === 'paid' ? '지급완료' : row.status === 'pending' ? '대기' : '거절'} /></td>
                    <td className="px-4 py-4">
                      {row.status === 'pending' ? (
                        <div className="flex items-center justify-center gap-1">
                          <button type="button" disabled={rewardProcessingId === row.id} onClick={() => handleRewardAction(row.id, 'pay_reward')} className="px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-50 rounded-lg inline-flex items-center gap-1"><Check size={12} /> 지급</button>
                          <button type="button" disabled={rewardProcessingId === row.id} onClick={() => handleRewardAction(row.id, 'reject_reward')} className="px-2 py-1 text-xs font-bold text-red-700 hover:bg-red-50 rounded-lg inline-flex items-center gap-1"><Ban size={12} /> 거절</button>
                        </div>
                      ) : (
                        <span className="text-xs text-slate-400">{row.paidAt || row.createdAt}</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : (
        <>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <SummaryCard title="전체 이벤트" value={String(summary.total)} suffix="개" icon={<Gift size={18} />} />
        <SummaryCard title="진행중" value={String(summary.active)} suffix="개" color="cyan" highlight icon={<Megaphone size={18} />} />
        <SummaryCard title="마감임박" value={String(summary.closing)} suffix="개" color="yellow" highlight icon={<Clock size={18} />} />
        <SummaryCard title="예정" value={String(summary.scheduled)} suffix="개" color="emerald" highlight icon={<Calendar size={18} />} />
      </div>

      <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="p-6 border-b border-slate-100 flex flex-wrap items-center gap-4 justify-between">
          <div className="flex flex-wrap items-center gap-3 flex-1">
            <div className="relative flex-1 min-w-[200px] max-w-md">
              <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="이벤트명, 코드, 상품 검색"
                className="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500"
              />
            </div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700"
            >
              {statusOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
            <button type="button" onClick={loadEvents} className="px-4 py-2 bg-slate-900 text-white rounded-xl text-sm font-bold">조회</button>
          </div>
          <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-xl text-sm font-bold">
            <Plus size={16} /> 이벤트 등록
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs text-slate-500 bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="px-4 py-3 font-medium">코드</th>
                <th className="px-4 py-3 font-medium">이벤트명</th>
                <th className="px-4 py-3 font-medium">유형</th>
                <th className="px-4 py-3 font-medium">기간</th>
                <th className="px-4 py-3 font-medium">적용 상품</th>
                <th className="px-4 py-3 font-medium text-center">상태</th>
                <th className="px-4 py-3 font-medium text-center">관리</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {items.length === 0 ? (
                <tr><td colSpan={7} className="px-4 py-10 text-center text-slate-500">등록된 이벤트가 없습니다.</td></tr>
              ) : items.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50">
                  <td className="px-4 py-4 font-mono text-xs text-slate-600">{item.code}</td>
                  <td className="px-4 py-4">
                    <div className="font-bold text-slate-900">{item.title}</div>
                    {item.featured && <span className="text-[10px] font-bold text-cyan-600 bg-cyan-50 px-1.5 py-0.5 rounded mt-1 inline-block">추천</span>}
                  </td>
                  <td className="px-4 py-4 text-slate-600">{item.type}</td>
                  <td className="px-4 py-4 text-slate-600 whitespace-nowrap">{item.period}</td>
                  <td className="px-4 py-4 text-slate-600 max-w-[220px] truncate">{item.product || item.campaigns}</td>
                  <td className="px-4 py-4 text-center"><StatusBadge status={item.status} /></td>
                  <td className="px-4 py-4">
                    <div className="flex items-center justify-center gap-1 flex-wrap">
                      <button type="button" onClick={() => openEdit(item)} className="px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg inline-flex items-center gap-1">
                        <Edit3 size={12} /> 수정
                      </button>
                      {item.statusCode !== 'active' && (
                        <button type="button" disabled={processingId === item.id} onClick={() => handleStatus(item.id, 'activate')} className="px-2 py-1 text-xs font-bold text-cyan-700 hover:bg-cyan-50 rounded-lg">진행</button>
                      )}
                      {item.statusCode === 'active' && (
                        <button type="button" disabled={processingId === item.id} onClick={() => handleStatus(item.id, 'closing')} className="px-2 py-1 text-xs font-bold text-orange-700 hover:bg-orange-50 rounded-lg">마감임박</button>
                      )}
                      {item.statusCode !== 'ended' && (
                        <button type="button" disabled={processingId === item.id} onClick={() => handleStatus(item.id, 'end')} className="px-2 py-1 text-xs font-bold text-red-700 hover:bg-red-50 rounded-lg">종료</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50 sticky top-0">
              <h3 className="font-bold text-slate-900">{form.id ? '이벤트 수정' : '이벤트 등록'}</h3>
              <button type="button" onClick={() => setModalOpen(false)} className="p-1.5 text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">이벤트 코드</label>
                  <input value={form.code} disabled className="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">유형</label>
                  <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm">
                    {typeOptions.map((t) => <option key={t} value={t}>{t}</option>)}
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">이벤트명 *</label>
                <input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">설명</label>
                <textarea value={form.desc} onChange={(e) => setForm({ ...form, desc: e.target.value })} rows={3} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm resize-none" />
              </div>
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">기간</label>
                  <input value={form.period} onChange={(e) => setForm({ ...form, period: e.target.value })} placeholder="2026.10.01 ~ 10.31" className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">상태</label>
                  <select value={form.statusCode} onChange={(e) => setForm({ ...form, statusCode: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm">
                    <option value="active">진행중</option>
                    <option value="closing">마감임박</option>
                    <option value="scheduled">예정</option>
                    <option value="ended">종료</option>
                  </select>
                </div>
              </div>
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">적용 상품</label>
                  <input value={form.product} onChange={(e) => setForm({ ...form, product: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">혜택</label>
                  <input value={form.benefit} onChange={(e) => setForm({ ...form, benefit: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
                </div>
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-500 mb-1.5">연결 CPA (표시용)</label>
                <input value={form.campaigns} onChange={(e) => setForm({ ...form, campaigns: e.target.value })} className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
              </div>
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">뱃지 (쉼표 구분)</label>
                  <input value={form.badges} onChange={(e) => setForm({ ...form, badges: e.target.value })} placeholder="진행중, 단가 상승" className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-500 mb-1.5">리본 문구</label>
                  <input value={form.ribbon} onChange={(e) => setForm({ ...form, ribbon: e.target.value })} placeholder="마감 3일전" className="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm" />
                </div>
              </div>
              <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" checked={form.featured} onChange={(e) => setForm({ ...form, featured: e.target.checked })} />
                추천 이벤트로 노출
              </label>
            </div>
            <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
              <button type="button" onClick={() => setModalOpen(false)} className="py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold">취소</button>
              <button type="button" onClick={handleSave} disabled={saving || !form.title.trim()} className="py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold disabled:opacity-50">{saving ? '저장 중…' : '저장'}</button>
            </div>
          </div>
        </div>
      )}

        </>
      )}
    </AdminLayout>
  );
}
