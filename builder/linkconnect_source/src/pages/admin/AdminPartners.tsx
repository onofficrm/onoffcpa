import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { SummaryCard, StatusBadge } from '../../components/admin/AdminShared';
import { 
  Users, Activity, Database, CreditCard, Receipt, 
  Search, Calendar, ChevronDown, Download, ShieldAlert, X, Eye
} from 'lucide-react';
import { AdminPartner, bulkAdminPartners, fetchAdminPartners, updateAdminPartner, viewAsPartner } from '../../lib/api';
import { isLcSuperAdmin } from '../../lib/auth';
import { AdminEntityMetaPanel } from '../../components/AdminEntityMetaPanel';

export function AdminPartners() {
  const [partners, setPartners] = useState<AdminPartner[]>([]);
  const [summary, setSummary] = useState({ total: 0, active: 0, pending: 0 });
  const [selectedPartner, setSelectedPartner] = useState<AdminPartner | null>(null);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [updating, setUpdating] = useState(false);
  const [viewingAs, setViewingAs] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [bulkProcessing, setBulkProcessing] = useState(false);
  const [actionError, setActionError] = useState('');
  const isSuperAdmin = isLcSuperAdmin();

  const loadPartners = useCallback(() => {
    fetchAdminPartners({ q: search, status: statusFilter })
      .then((data) => {
        setPartners(data.items);
        setSummary(data.summary);
      })
      .catch(() => {
        // 샘플 UI fallback
      });
  }, [search, statusFilter]);

  useEffect(() => {
    loadPartners();
  }, [loadPartners]);

  const handleRowClick = (partner: AdminPartner) => {
    setSelectedPartner(partner);
    setActionError('');
  };

  const handleStatusChange = async (action: 'activate' | 'suspend' | 'pending') => {
    if (!selectedPartner?.id) {
      return;
    }
    setUpdating(true);
    setActionError('');
    try {
      const result = await updateAdminPartner({ action, ptId: selectedPartner.id });
      loadPartners();
      if (result.partner) {
        setSelectedPartner((prev) => (prev ? { ...prev, status: result.partner!.status, statusCode: result.partner!.statusCode } : null));
      }
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '상태 변경에 실패했습니다.');
    } finally {
      setUpdating(false);
    }
  };

  const handleViewAs = async (partner: AdminPartner, event?: React.MouseEvent) => {
    event?.stopPropagation();
    if (!partner.id) {
      return;
    }
    setViewingAs(true);
    setActionError('');
    try {
      const result = await viewAsPartner(partner.id);
      window.location.href = result.redirect || '/partner';
    } catch (error) {
      setActionError(error instanceof Error ? error.message : '계정 전환에 실패했습니다.');
      setViewingAs(false);
    }
  };

  const handleBulk = async (subAction: 'activate' | 'suspend') => {
    if (!selectedIds.length) return;
    setBulkProcessing(true);
    try {
      const result = await bulkAdminPartners(selectedIds, subAction);
      alert(result.message);
      setSelectedIds([]);
      loadPartners();
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
    <AdminLayout activeMenu="partners" title="파트너 관리" description="파트너별 유입 성과, 수익, 정산 상태를 관리하세요.">
      
      {/* 6 Summary Cards */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <SummaryCard title="전체 파트너" value={String(summary.total)} suffix="명" icon={<Users size={18} />} />
        <SummaryCard title="활성 파트너" value={String(summary.active)} suffix="명" color="emerald" highlight icon={<Activity size={18} />} />
        <SummaryCard title="승인 대기" value={String(summary.pending)} suffix="명" color="yellow" highlight icon={<Calendar size={18} />} />
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
                    placeholder="파트너 코드, 이름, 아이디 검색" 
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
                  <option value="active">활성</option>
                  <option value="pending">승인대기</option>
                  <option value="suspended">차단</option>
                </select>
                <div className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 flex items-center gap-2 cursor-pointer hover:bg-slate-100">
                  <Calendar size={16} className="text-slate-500" />
                  <span>전체 기간</span>
                  <ChevronDown size={14} className="text-slate-500" />
                </div>
                <select className="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 outline-none focus:border-cyan-500">
                  <option>가입일 최신순</option>
                  <option>승인율 높은순</option>
                  <option>수익 높은순</option>
                  <option>취소율 높은순</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table Area */}
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1">
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50 flex-wrap gap-2">
              <div className="text-sm font-medium text-slate-600">총 <strong className="text-cyan-600">{summary.total}</strong>명의 파트너</div>
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
                    <th className="px-4 py-3 font-medium">파트너 정보</th>
                    <th className="px-4 py-3 font-medium text-center">링크</th>
                    <th className="px-4 py-3 font-medium text-right">접수/승인/취소 DB</th>
                    <th className="px-4 py-3 font-medium text-right">승인율</th>
                    <th className="px-4 py-3 font-medium text-right">확정 수익</th>
                    <th className="px-4 py-3 font-medium text-right">잔액</th>
                    <th className="px-4 py-3 font-medium text-center">상태</th>
                    <th className="px-4 py-3 font-medium text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {partners.map((partner) => (
                    <tr 
                      key={partner.id || partner.code} 
                      className={`hover:bg-slate-50 transition-colors cursor-pointer ${selectedPartner?.code === partner.code ? 'bg-cyan-50/50' : ''}`}
                      onClick={() => handleRowClick(partner)}
                    >
                      <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                        {partner.id ? (
                          <input type="checkbox" checked={selectedIds.includes(partner.id)} onChange={() => toggleSelect(partner.id)} className="rounded border-slate-300" />
                        ) : null}
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <div className="flex items-center gap-2 mb-0.5">
                          <span className="font-bold text-slate-900">{partner.name}</span>
                          {partner.memberId && <span className="text-xs text-slate-500">({partner.memberId})</span>}
                        </div>
                        <div className="text-xs text-slate-400 flex items-center gap-1.5">
                          <span>{partner.code}</span>
                          {partner.date && (
                            <>
                              <span className="w-0.5 h-0.5 bg-slate-300 rounded-full"></span>
                              <span>{partner.date} 가입</span>
                            </>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center font-medium text-slate-600 whitespace-nowrap">-</td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-medium text-slate-900">{partner.totalDb.toLocaleString()}건</div>
                        <div className="text-xs mt-0.5">
                          <span className="text-emerald-600 font-bold">{partner.approvedDb.toLocaleString()}</span>
                          <span className="text-slate-300 mx-1">/</span>
                          <span className="text-red-500">{partner.canceledDb.toLocaleString()}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-700 whitespace-nowrap">
                        <span className={parseFloat(partner.rate) < 50 && partner.rate !== '-' ? 'text-red-600' : 'text-slate-900'}>{partner.rate}</span>
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap">
                        <div className="font-bold text-cyan-700">{partner.confirmedProfit.toLocaleString()}원</div>
                      </td>
                      <td className="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                        {partner.balance > 0 ? (
                          <span className="text-yellow-600">{partner.balance.toLocaleString()}원</span>
                        ) : (
                          <span className="text-slate-400">-</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <StatusBadge status={partner.status === '활성' ? '정상' : partner.status === '차단' ? '정지' : partner.status} />
                        {partner.tier ? <span className="ml-1 text-[10px] font-bold uppercase text-amber-600">{partner.tier}</span> : null}
                      </td>
                      <td className="px-4 py-3 text-center whitespace-nowrap">
                        <div className="flex items-center justify-center gap-1">
                          {isSuperAdmin && partner.id ? (
                            <button
                              type="button"
                              onClick={(e) => handleViewAs(partner, e)}
                              disabled={viewingAs}
                              className="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-xs font-bold transition-colors inline-flex items-center gap-1"
                            >
                              <Eye size={12} /> 이 계정으로 보기
                            </button>
                          ) : null}
                          <button type="button" onClick={(e) => { e.stopPropagation(); handleRowClick(partner); }} className="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded text-xs font-bold transition-colors">상세</button>
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
              주의 파트너 알림
            </h3>
            <div className="space-y-3">
              <div className="bg-white p-3 rounded-xl border border-red-100 text-sm cursor-pointer hover:border-red-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>어뷰징의심 (PT-9912)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-red-600 text-xs">최근 7일 취소율 88% 초과. 부정 트래픽 의심.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>단기급증 (PT-1052)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">특정 IP 대역에서 짧은 시간 다량 접수 발생.</p>
              </div>
              <div className="bg-white p-3 rounded-xl border border-orange-100 text-sm cursor-pointer hover:border-orange-300 transition-colors">
                <div className="font-bold text-slate-900 mb-1 flex justify-between items-center">
                  <span>이수익 (PT-5591)</span>
                  <StatusBadge status="경고" />
                </div>
                <p className="text-orange-600 text-xs">금지 채널(당근마켓) 유입 의심 내역 확인됨.</p>
              </div>
            </div>
          </div>

          {/* Detail Panel */}
          {selectedPartner ? (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1 flex flex-col relative animate-in fade-in slide-in-from-right-4 duration-300">
              <button 
                onClick={() => setSelectedPartner(null)}
                className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors z-10"
              >
                <X size={18} />
              </button>
              
              <div className="p-6 border-b border-slate-200 bg-slate-50 relative overflow-hidden">
                <div className="absolute -right-4 -top-4 w-24 h-24 bg-cyan-100 rounded-full opacity-50 blur-xl"></div>
                <div className="flex items-center gap-2 mb-2 relative z-10">
                  <span className="text-xs font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded">{selectedPartner.code}</span>
                  <StatusBadge status={selectedPartner.status === '활성' ? '정상' : selectedPartner.status === '차단' ? '정지' : selectedPartner.status} />
                </div>
                <h2 className="text-2xl font-bold text-slate-900 relative z-10">{selectedPartner.name} {selectedPartner.memberId && <span className="text-lg font-normal text-slate-500 ml-1">({selectedPartner.memberId})</span>}</h2>
                <div className="text-sm text-slate-500 mt-1 relative z-10">가입일: {selectedPartner.date || '-'}</div>
              </div>

              <div className="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3">관리자 메모/태그</h3>
                  {selectedPartner.id ? (
                    <AdminEntityMetaPanel
                      entityType="partner"
                      entityId={selectedPartner.id}
                      adminMemo={selectedPartner.adminMemo}
                      tags={selectedPartner.tags}
                      assignedTo={selectedPartner.assignedTo}
                      onSaved={loadPartners}
                    />
                  ) : null}
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    기본 정보
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">연락처</span>
                      <span className="font-medium text-slate-900">010-12**-56**</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">이메일</span>
                      <span className="font-medium text-slate-900">user****@gmail.com</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">주요 채널</span>
                      <span className="font-medium text-slate-900">네이버 블로그, 인스타그램</span>
                    </div>
                    <div className="flex justify-between py-1 border-b border-slate-100">
                      <span className="text-slate-500">정산 계좌</span>
                      <span className="font-medium text-slate-900">국민 123456-**-***</span>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                    누적 성과 요약
                  </h3>
                  <div className="bg-slate-50 rounded-xl p-4 grid grid-cols-2 gap-4">
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 접수 DB</div>
                      <div className="font-bold text-slate-900">{selectedPartner.totalDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">승인율</div>
                      <div className={`font-bold ${parseFloat(selectedPartner.rate) < 50 ? 'text-red-600' : 'text-slate-900'}`}>{selectedPartner.rate}</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 승인 DB</div>
                      <div className="font-bold text-emerald-600">{selectedPartner.approvedDb.toLocaleString()}건</div>
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 mb-1">총 취소 DB</div>
                      <div className="font-bold text-red-600">{selectedPartner.canceledDb.toLocaleString()}건</div>
                    </div>
                  </div>
                  <div className="bg-slate-900 rounded-xl p-4 mt-3 flex justify-between items-center text-white">
                    <span className="text-sm font-medium text-slate-400">누적 확정수익</span>
                    <div className="font-bold text-cyan-400">{selectedPartner.confirmedProfit.toLocaleString()}원</div>
                  </div>
                </div>
              </div>
              
              {actionError && <p className="px-6 text-sm text-red-600">{actionError}</p>}

              <div className="p-4 border-t border-slate-200 bg-slate-50 grid grid-cols-2 gap-2">
                {isSuperAdmin && selectedPartner.id ? (
                  <button
                    type="button"
                    disabled={viewingAs}
                    onClick={() => handleViewAs(selectedPartner)}
                    className="col-span-2 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 disabled:opacity-60 transition-colors shadow-sm inline-flex items-center justify-center gap-2"
                  >
                    <Eye size={16} />
                    {viewingAs ? '전환 중...' : '이 계정으로 보기'}
                  </button>
                ) : null}
                {selectedPartner.statusCode === 'pending' && (
                  <button
                    type="button"
                    disabled={updating || !selectedPartner.id}
                    onClick={() => handleStatusChange('activate')}
                    className="col-span-2 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '파트너 승인 (활성화)'}
                  </button>
                )}
                {selectedPartner.statusCode === 'active' && (
                  <button
                    type="button"
                    disabled={updating || !selectedPartner.id}
                    onClick={() => handleStatusChange('suspend')}
                    className="col-span-2 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '파트너 정지'}
                  </button>
                )}
                {selectedPartner.statusCode === 'suspended' && (
                  <button
                    type="button"
                    disabled={updating || !selectedPartner.id}
                    onClick={() => handleStatusChange('activate')}
                    className="col-span-2 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800 disabled:opacity-60 transition-colors shadow-sm"
                  >
                    {updating ? '처리 중...' : '정지 해제 (활성화)'}
                  </button>
                )}
              </div>
            </div>
          ) : (
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm flex-1 flex flex-col items-center justify-center text-slate-500 p-8 text-center min-h-[400px]">
              <Users size={48} className="text-slate-300 mb-4" />
              <p className="font-medium text-slate-900 mb-1">선택된 파트너가 없습니다.</p>
              <p className="text-sm">좌측 목록에서 파트너를 선택하면<br />상세 정보가 표시됩니다.</p>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
