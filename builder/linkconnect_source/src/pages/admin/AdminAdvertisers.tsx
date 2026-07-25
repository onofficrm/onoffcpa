import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Building2, Activity, AlertCircle, Clock,
  Search, Download, X, ShieldAlert,
  Wallet, Eye
} from 'lucide-react';
import { AdminMerchant, bulkAdminMerchants, fetchAdminMerchants, updateAdminMerchant, viewAsMerchant } from '../../lib/api';
import { isLcSuperAdmin } from '../../lib/auth';
import { AdminEntityMetaPanel } from '../../components/AdminEntityMetaPanel';

export function AdminAdvertisers() {
  const [merchants, setMerchants] = useState<AdminMerchant[]>([]);
  const [summary, setSummary] = useState({ total: 0, active: 0, pending: 0, lowBalance: 0 });
  const [selectedAdvertiser, setSelectedAdvertiser] = useState<AdminMerchant | null>(null);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [updating, setUpdating] = useState(false);
  const [viewingAs, setViewingAs] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [bulkProcessing, setBulkProcessing] = useState(false);
  const [actionError, setActionError] = useState('');
  const isSuperAdmin = isLcSuperAdmin();

  const loadMerchants = useCallback(() => {
    fetchAdminMerchants({ q: search, status: statusFilter })
      .then((data) => {
        setMerchants(data.items);
        setSummary(data.summary);
      })
      .catch(() => {
        // 샘플 UI fallback
      });
  }, [search, statusFilter]);

  useEffect(() => {
    loadMerchants();
  }, [loadMerchants]);

  const handleRowClick = (advertiser: AdminMerchant) => {
    setSelectedAdvertiser(advertiser);
    setActionError('');
  };

  const handleStatusChange = async (action: 'activate' | 'suspend' | 'pending') => {
    if (!selectedAdvertiser?.id) {
      return;
    }
    setUpdating(true);
    setActionError('');
    try {
      const result = await updateAdminMerchant({ action, mtId: selectedAdvertiser.id });
      loadMerchants();
      if (result.merchant) {
        setSelectedAdvertiser((prev) => (prev ? { ...prev, status: result.merchant!.status, statusCode: result.merchant!.statusCode } : null));
      }
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '상태 변경에 실패했습니다.');
    } finally {
      setUpdating(false);
    }
  };

  const handleViewAs = async (advertiser: AdminMerchant, event?: React.MouseEvent) => {
    event?.stopPropagation();
    if (!advertiser.id) {
      return;
    }
    setViewingAs(true);
    setActionError('');
    try {
      const result = await viewAsMerchant(advertiser.id);
      window.location.href = result.redirect || '/advertiser';
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '계정 전환에 실패했습니다.');
      setViewingAs(false);
    }
  };

  const handleBulk = async (subAction: 'activate' | 'suspend') => {
    if (!selectedIds.length) return;
    setBulkProcessing(true);
    try {
      const result = await bulkAdminMerchants(selectedIds, subAction);
      alert(result.message);
      setSelectedIds([]);
      loadMerchants();
    } catch (error) {
      alert(error instanceof Error ? error.message : '일괄 처리 실패');
    } finally {
      setBulkProcessing(false);
    }
  };

  const toggleSelect = (id: number) => {
    setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  return (
    <AdminLayout activeMenu="advertisers" title="광고주 관리" description="광고주별 광고상품, 광고비, 디비 처리 현황을 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 광고주" value={String(summary.total)} suffix="곳" icon={<Building2 size={18} />} />
        <SummaryCard title="운영중 광고주" value={String(summary.active)} suffix="곳" color="emerald" highlight icon={<Activity size={18} />} />
        <SummaryCard title="광고비 부족" value={String(summary.lowBalance)} suffix="곳" color="red" highlight icon={<AlertCircle size={18} />} />
        <SummaryCard title="승인 대기" value={String(summary.pending)} suffix="곳" color="yellow" highlight icon={<Clock size={18} />} />
      </div>

      <div className="grid lg:grid-cols-4 gap-6 mb-8">
        <div className="lg:col-span-3 flex flex-col">
          {/* Filter Area */}
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex-1 min-w-[200px]">
                <div className="relative">
                  <Search className="w-5 h-5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input 
                    type="text" 
                    placeholder="회사명, 담당자명 검색" 
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-shadow"
                  />
                </div>
              </div>
              <div className="flex items-center gap-2">
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500"
                >
                  <option value="">전체 상태</option>
                  <option value="active">운영중</option>
                  <option value="pending">승인대기</option>
                  <option value="suspended">일시중지</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>광고비 상태 (전체)</option>
                  <option>충분</option>
                  <option>부족</option>
                  <option>충전대기</option>
                </select>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>사용 광고비 높은순</option>
                  <option>가입일 최신순</option>
                  <option>취소율 높은순</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50 flex-wrap gap-2">
              <div className="text-sm font-medium text-slate-600">총 <strong className="text-cyan-600">{summary.total}</strong>곳의 광고주</div>
              <div className="flex items-center gap-2">
                {selectedIds.length > 0 && (
                  <>
                    <span className="text-xs text-slate-500">{selectedIds.length}건 선택</span>
                    <button type="button" disabled={bulkProcessing} onClick={() => handleBulk('activate')} className="px-3 py-1.5 text-xs font-bold bg-emerald-600 text-white rounded-lg">일괄 승인</button>
                    <button type="button" disabled={bulkProcessing} onClick={() => handleBulk('suspend')} className="px-3 py-1.5 text-xs font-bold bg-red-600 text-white rounded-lg">일괄 정지</button>
                  </>
                )}
                <button className="text-sm font-medium text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                  <Download size={14} /> 엑셀 다운로드
                </button>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs text-slate-500 bg-white border-b border-slate-200">
                  <tr>
                    <th className="px-4 py-3 font-medium w-10"></th>
                    <th className="px-4 py-3 font-medium">광고주명/담당자</th>
                    <th className="px-4 py-3 font-medium text-center">운영상품</th>
                    <th className="px-4 py-3 font-medium text-right">접수/승인/취소 DB</th>
                    <th className="px-4 py-3 font-medium text-right">승인율</th>
                    <th className="px-4 py-3 font-medium text-right">광고비 잔액</th>
                    <th className="px-4 py-3 font-medium text-right">이번 달 사용액</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {merchants.map((advertiser) => (
                    <tr 
                      key={advertiser.id || advertiser.code || advertiser.name} 
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedAdvertiser?.name === advertiser.name ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => handleRowClick(advertiser)}
                    >
                      <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                        {advertiser.id ? (
                          <input type="checkbox" checked={selectedIds.includes(advertiser.id)} onChange={() => toggleSelect(advertiser.id)} className="rounded border-slate-300" />
                        ) : null}
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="font-bold text-slate-900 mb-0.5">{advertiser.name}</div>
                        <div className="text-xs text-slate-500 flex items-center gap-1.5">
                          <span>{advertiser.code}</span>
                          {advertiser.memberId && (
                            <>
                              <span className="w-0.5 h-0.5 bg-slate-300 rounded-full"></span>
                              <span>{advertiser.memberId}</span>
                            </>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center font-medium text-slate-600 whitespace-nowrap">{advertiser.campaigns}개</td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-medium text-slate-900">{advertiser.totalDb.toLocaleString()}건</div>
                        <div className="text-xs mt-0.5">
                          <span className="text-emerald-600 font-bold">{advertiser.approvedDb.toLocaleString()}</span>
                          <span className="text-slate-300 mx-1">/</span>
                          <span className="text-red-500">{advertiser.canceledDb.toLocaleString()}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-700 whitespace-nowrap">
                        <span className={parseFloat(advertiser.rate) < 50 && advertiser.rate !== '-' ? 'text-red-600' : 'text-slate-900'}>{advertiser.rate}</span>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className={`font-bold ${advertiser.balance < 500000 ? 'text-red-600' : 'text-cyan-700'}`}>{advertiser.balance.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right font-medium text-slate-600 whitespace-nowrap">
                        {advertiser.spend.toLocaleString()}원
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={advertiser.status} />
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <div className="flex items-center justify-center gap-1">
                          {isSuperAdmin && advertiser.id ? (
                            <button
                              type="button"
                              onClick={(e) => handleViewAs(advertiser, e)}
                              disabled={viewingAs}
                              className="px-3 py-1.5 bg-cyan-50 text-cyan-700 hover:bg-cyan-100 rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                            >
                              <Eye size={12} /> 이 계정으로 보기
                            </button>
                          ) : null}
                          <button type="button" onClick={(e) => { e.stopPropagation(); handleRowClick(advertiser); }} className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">상세</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar / Detail Panel */}
        <div className="lg:col-span-1 flex flex-col gap-6">
          {/* Alert Card */}
          <div className="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm">
            <h3 className="text-red-900 font-bold mb-4 flex items-center gap-2">
              <ShieldAlert size={18} />
              관리자 확인 필요
            </h3>
            <div className="space-y-3">
              <div className="bg-white p-3 rounded-xl border border-red-100 text-sm cursor-pointer hover:border-red-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-red-700 transition-colors">스피드렌터카</span>
                  <StatusBadge status="광고비부족" />
                </div>
                <p className="text-red-600 text-xs">광고비 부족으로 캠페인 중지 임박 (잔액 12만원)</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-orange-700 transition-colors">투어여행사</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">최근 7일 취소율 71.5% 이상. 점검 요망.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-yellow-100 text-sm cursor-pointer hover:border-yellow-300 transition-colors group">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span className="group-hover:text-yellow-700 transition-colors">에듀스터디</span>
                  <StatusBadge status="지연" />
                </div>
                <p className="text-yellow-700 text-xs">48시간 이상 미검수 DB 28건 발생.</p>
              </div>
            </div>
          </div>

          {/* Detail Panel */}
          {selectedAdvertiser ? (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <button 
                onClick={() => setSelectedAdvertiser(null)}
                className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors z-10"
              >
                <X size={18} />
              </button>
              
              <div className="p-6 border-b border-slate-200 bg-slate-50 relative overflow-hidden">
                <div className="absolute -right-4 -top-4 w-24 h-24 bg-cyan-100 rounded-full opacity-50 blur-xl"></div>
                <div className="flex items-center gap-2 mb-2 relative z-10">
                  <span className="text-xs font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded">{selectedAdvertiser.code || 'ADV'}</span>
                  <StatusBadge status={selectedAdvertiser.status} />
                </div>
                <h2 className="text-2xl font-bold text-slate-900 relative z-10">{selectedAdvertiser.name}</h2>
                <div className="text-sm text-slate-500 mt-1 relative z-10">가입일: {selectedAdvertiser.date || '-'}</div>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3">관리자 메모/태그</h3>
                  {selectedAdvertiser.id ? (
                    <AdminEntityMetaPanel
                      entityType="merchant"
                      entityId={selectedAdvertiser.id}
                      adminMemo={selectedAdvertiser.adminMemo}
                      tags={selectedAdvertiser.tags}
                      assignedTo={selectedAdvertiser.assignedTo}
                      onSaved={loadMerchants}
                    />
                  ) : null}
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Building2 size={16} className="text-slate-500" /> 기본 정보
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">회원 ID</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.memberId || '-'}</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Wallet size={16} className="text-slate-500" /> 광고비 요약
                  </h3>
                  <div className="bg-slate-900 rounded-xl p-4 mb-3 flex justify-between items-center text-white">
                    <span className="text-sm font-medium text-slate-400">현재 잔액</span>
                    <span className={`text-xl font-bold ${selectedAdvertiser.balance < 500000 ? 'text-red-400' : 'text-cyan-400'}`}>{selectedAdvertiser.balance.toLocaleString()}원</span>
                  </div>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">가차감 광고비 (검수대기)</span>
                      <span className="font-medium text-slate-900">-</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500 font-bold">사용 가능 잔액</span>
                      <span className="font-bold text-cyan-600">{selectedAdvertiser.balance.toLocaleString()}원</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100 mt-2">
                      <span className="text-slate-500">총 사용액</span>
                      <span className="font-medium text-slate-900">{selectedAdvertiser.spend.toLocaleString()}원</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    <Activity size={16} className="text-slate-500" /> 운영 성과
                  </h3>
                  <div className="bg-slate-50 rounded-xl p-4 grid grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 접수 DB</div>
                      <div className="font-bold text-slate-900">{selectedAdvertiser.totalDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">평균 처리시간</div>
                      <div className="font-bold text-slate-900">-</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 승인 DB</div>
                      <div className="font-bold text-emerald-600">{selectedAdvertiser.approvedDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 취소 DB</div>
                      <div className="font-bold text-red-600">{selectedAdvertiser.canceledDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">승인율</div>
                      <div className={`font-bold ${parseFloat(selectedAdvertiser.rate) < 50 && selectedAdvertiser.rate !== '-' ? 'text-red-600' : 'text-slate-900'}`}>{selectedAdvertiser.rate}</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">취소율</div>
                      <div className="font-bold text-slate-900">
                        {selectedAdvertiser.totalDb > 0 ? ((selectedAdvertiser.canceledDb / selectedAdvertiser.totalDb) * 100).toFixed(1) + '%' : '-'}
                      </div>
                    </div>
                  </div>
                </div>

              </div>

              {actionError && <p className="px-6 text-sm text-red-600">{actionError}</p>}

              <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
                {isSuperAdmin && selectedAdvertiser.id ? (
                  <button
                    type="button"
                    disabled={viewingAs}
                    onClick={() => handleViewAs(selectedAdvertiser)}
                    className="col-span-2 py-2.5 bg-cyan-600 text-white rounded-xl text-sm font-bold hover:bg-cyan-700 disabled:opacity-60 transition-colors shadow-sm inline-flex items-center justify-center gap-2"
                  >
                    <Eye size={16} />
                    {viewingAs ? '전환 중...' : '이 계정으로 보기'}
                  </button>
                ) : null}
                {selectedAdvertiser.statusCode === 'pending' && (
                  <button
                    type="button"
                    disabled={updating || !selectedAdvertiser.id}
                    onClick={() => handleStatusChange('activate')}
                    className="col-span-2 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '광고주 승인 (운영중)'}
                  </button>
                )}
                {selectedAdvertiser.statusCode === 'active' && (
                  <button
                    type="button"
                    disabled={updating || !selectedAdvertiser.id}
                    onClick={() => handleStatusChange('suspend')}
                    className="col-span-2 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '광고주 일시중지'}
                  </button>
                )}
                {selectedAdvertiser.statusCode === 'suspended' && (
                  <button
                    type="button"
                    disabled={updating || !selectedAdvertiser.id}
                    onClick={() => handleStatusChange('activate')}
                    className="col-span-2 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '중지 해제 (운영중)'}
                  </button>
                )}
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm flex-1 flex flex-col items-center justify-center text-slate-500 p-8 text-center min-h-[400px]">
              <Building2 size={48} className="text-slate-300 mb-4" />
              <p className="font-medium text-slate-900 mb-1">선택된 광고주가 없습니다.</p>
              <p className="text-sm">좌측 목록에서 광고주를 선택하면<br />상세 정보가 표시됩니다.</p>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
