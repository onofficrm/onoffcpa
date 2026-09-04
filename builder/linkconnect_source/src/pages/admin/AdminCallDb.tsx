import React, { useCallback, useEffect, useState } from 'react';
import { AdminLayout } from '../../layouts/AdminLayout';
import { Phone, PhoneCall, PhoneIncoming, Plus, Settings2, PlayCircle, Lock, Check, X, Upload, Info, Headphones, Trash2, ClipboardPaste } from 'lucide-react';
import {
  CallLog,
  CallNumber,
  CallRequest,
  CallRecordingRequestItem,
  AdminCampaign,
  AdminPartner,
  assignAdminCallDirect,
  assignAdminCallRequest,
  createAdminCallNumber,
  createAdminCallNumbersBulk,
  deleteAdminCallNumber,
  fetchAdminCallLogs,
  fetchAdminCallNumbers,
  fetchAdminCallRecordingRequests,
  fetchAdminCallRequests,
  fetchAdminCallSettings,
  fetchAdminCampaigns,
  fetchAdminPartners,
  finalizeAdminConversion,
  formatCallPhone,
  importAdminCallLogs,
  rematchAdminCallLogs,
  rejectAdminCallRecordingRequest,
  rejectAdminCallRequest,
  revokeAdminCallRequest,
  saveAdminCallSettings,
  updateAdminCallNumber,
  uploadAdminCallRecordingWav,
} from '../../lib/api';
import { isLcSuperAdmin } from '../../lib/auth';

type Tab = 'numbers' | 'requests' | 'logs' | 'recordings';

const numberStatusLabel: Record<string, { label: string; cls: string }> = {
  available: { label: '사용가능', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  assigned: { label: '배정됨', cls: 'bg-cyan-50 text-cyan-700 border-cyan-200' },
  paused: { label: '일시중지', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
  released: { label: '해지', cls: 'bg-slate-100 text-slate-500 border-slate-200' },
};

const reqStatusLabel: Record<string, { label: string; cls: string }> = {
  pending: { label: '대기', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
  assigned: { label: '배정완료', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  rejected: { label: '반려', cls: 'bg-slate-100 text-slate-500 border-slate-200' },
  revoked: { label: '회수', cls: 'bg-rose-50 text-rose-700 border-rose-200' },
};

const recReqStatusLabel: Record<string, { label: string; cls: string }> = {
  pending: { label: '요청 대기', cls: 'bg-amber-50 text-amber-700 border-amber-200' },
  fulfilled: { label: '등록 완료', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  rejected: { label: '반려', cls: 'bg-slate-100 text-slate-500 border-slate-200' },
};

const resultLabel: Record<string, { label: string; cls: string }> = {
  success: { label: '통화성공', cls: 'text-emerald-600' },
  missed: { label: '부재중', cls: 'text-amber-600' },
  busy: { label: '통화중', cls: 'text-slate-500' },
  fail: { label: '실패', cls: 'text-rose-600' },
};

function fmtDuration(sec: number) {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
}

export function AdminCallDb() {
  const [tab, setTab] = useState<Tab>('requests');
  const [numbers, setNumbers] = useState<CallNumber[]>([]);
  const [requests, setRequests] = useState<CallRequest[]>([]);
  const [logs, setLogs] = useState<CallLog[]>([]);
  const [recordingRequests, setRecordingRequests] = useState<CallRecordingRequestItem[]>([]);
  const [recStatusFilter, setRecStatusFilter] = useState('');
  const [isSuperAdmin, setIsSuperAdmin] = useState(isLcSuperAdmin());
  const [uploadTarget, setUploadTarget] = useState<CallRecordingRequestItem | null>(null);
  const [uploadFile, setUploadFile] = useState<File | null>(null);
  const [uploadMemo, setUploadMemo] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  // 번호 추가
  const [newNumber, setNewNumber] = useState('');
  const [newMemo, setNewMemo] = useState('');
  const [bulkNumbers, setBulkNumbers] = useState('');
  const [bulkMemo, setBulkMemo] = useState('');

  // 배정 모달
  const [assignTarget, setAssignTarget] = useState<CallRequest | null>(null);
  const [assignCn, setAssignCn] = useState('');
  const [assignPartnerPrice, setAssignPartnerPrice] = useState('');
  const [assignAdvertiserPrice, setAssignAdvertiserPrice] = useState('');

  // 직접 배정
  const [partners, setPartners] = useState<AdminPartner[]>([]);
  const [campaigns, setCampaigns] = useState<AdminCampaign[]>([]);
  const [directPt, setDirectPt] = useState('');
  const [directCp, setDirectCp] = useState('');
  const [directCn, setDirectCn] = useState('');
  const [directMemo, setDirectMemo] = useState('');
  const [directPartnerPrice, setDirectPartnerPrice] = useState('');
  const [directAdvertiserPrice, setDirectAdvertiserPrice] = useState('');
  const [memoDrafts, setMemoDrafts] = useState<Record<number, string>>({});
  const [priceDrafts, setPriceDrafts] = useState<Record<number, { partner: string; advertiser: string }>>({});

  // 통화내역 가져오기 (파일 / 붙여넣기)
  const [importMode, setImportMode] = useState<'paste' | 'file'>('paste');
  const [importFile, setImportFile] = useState<File | null>(null);
  const [importPaste, setImportPaste] = useState('');
  const [skipConversionOnImport, setSkipConversionOnImport] = useState(true);
  const [importResult, setImportResult] = useState('');
  const [importPreview, setImportPreview] = useState<Array<Record<string, unknown>>>([]);
  const [importHeaders, setImportHeaders] = useState<string[]>([]);
  const [importTotal, setImportTotal] = useState(0);
  const [importPreviewMsg, setImportPreviewMsg] = useState('');

  const CALL_LOG_PASTE_PLACEHOLDER = `발신번호,가상번호,착신번호,통화일자,통화시작시간,통화시간(초),녹음파일,통화결과
1091484816,50369821003,1067709798,2026-08-31,20:48:50,19초,,통화성공
1020677673,50369821002,1092262311,2026-08-31,15:41:16,25초,,통화성공`;

  const hasImportSource = importMode === 'paste' ? importPaste.trim().length > 0 : !!importFile;
  // 콜 설정 모달
  const [settingsCp, setSettingsCp] = useState<{ cpId: number; name: string } | null>(null);
  const [settingsDraft, setSettingsDraft] = useState<Record<string, unknown>>({});

  const loadRecordingRequests = useCallback(() => {
    fetchAdminCallRecordingRequests(recStatusFilter || undefined)
      .then((d) => {
        setRecordingRequests(d.items);
        setIsSuperAdmin(d.isSuperAdmin);
      })
      .catch(() => setRecordingRequests([]));
  }, [recStatusFilter]);

  const loadAll = useCallback(() => {
    fetchAdminCallNumbers()
      .then((d) => setNumbers(d.items.map((n) => ({ ...n, number: formatCallPhone(n.number) }))))
      .catch(() => setNumbers([]));
    fetchAdminCallRequests()
      .then((d) => setRequests(d.items.map((r) => ({ ...r, virtualNumber: formatCallPhone(r.virtualNumber) }))))
      .catch(() => setRequests([]));
    fetchAdminCallLogs()
      .then((d) => setLogs(d.items.map((l) => ({ ...l, virtualNumber: formatCallPhone(l.virtualNumber) }))))
      .catch(() => setLogs([]));
    loadRecordingRequests();
  }, [loadRecordingRequests]);

  useEffect(() => {
    loadAll();
    fetchAdminPartners().then((d) => setPartners(d.items)).catch(() => setPartners([]));
    fetchAdminCampaigns({ status: 'active' }).then((d) => setCampaigns(d.items)).catch(() => setCampaigns([]));
  }, [loadAll]);

  useEffect(() => {
    setMemoDrafts((prev) => {
      const next: Record<number, string> = { ...prev };
      numbers.forEach((n) => {
        if (next[n.cnId] === undefined) next[n.cnId] = n.memo || '';
      });
      return next;
    });
    // 서버 단가를 항상 반영. 빈 draft('')가 남아 있으면 placeholder 0처럼 보이고 실단가를 가린다.
    setPriceDrafts(() => {
      const next: Record<number, { partner: string; advertiser: string }> = {};
      numbers.forEach((n) => {
        const partner = n.partnerPrice > 0 ? String(n.partnerPrice) : '';
        const advertiser = n.advertiserPrice > 0 ? String(n.advertiserPrice) : partner;
        next[n.cnId] = { partner, advertiser };
      });
      return next;
    });
  }, [numbers]);

  useEffect(() => {
    if (tab === 'recordings') {
      loadRecordingRequests();
    }
  }, [tab, loadRecordingRequests]);

  const notify = (msg: string) => {
    setMessage(msg);
    setTimeout(() => setMessage(''), 3000);
  };

  const parseCallPrice = (value: string | number | undefined | null) => {
    const n = Number(String(value ?? '').replace(/,/g, '').trim());
    return Number.isFinite(n) ? n : NaN;
  };

  const handleAddNumber = async () => {
    if (!newNumber.trim()) return;
    setBusy(true);
    try {
      const res = await createAdminCallNumber({ number: newNumber, memo: newMemo });
      notify(res.message);
      setNewNumber('');
      setNewMemo('');
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '등록 실패');
    } finally {
      setBusy(false);
    }
  };

  const handleBulkAddNumbers = async () => {
    if (!bulkNumbers.trim()) return;
    setBusy(true);
    try {
      const res = await createAdminCallNumbersBulk({ numbers: bulkNumbers, memo: bulkMemo });
      notify(res.message);
      setBulkNumbers('');
      setBulkMemo('');
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '일괄 등록 실패');
    } finally {
      setBusy(false);
    }
  };

  const handleNumberStatus = async (n: CallNumber, status: string) => {
    if (status === n.status) return;
    if (n.status === 'assigned' && status !== 'assigned') {
      if (!window.confirm(`${formatCallPhone(n.number)} 배정을 회수하고 상태를 변경할까요?`)) {
        return;
      }
    }
    setBusy(true);
    try {
      const res = await updateAdminCallNumber({ cnId: n.cnId, status });
      notify(res.message || '상태를 변경했습니다.');
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '상태 변경 실패');
      loadAll();
    } finally {
      setBusy(false);
    }
  };

  const handleDeleteNumber = async (n: CallNumber) => {
    const phone = formatCallPhone(n.number);
    const confirmMsg = n.status === 'assigned'
      ? `${phone}은(는) 배정 중입니다. 배정을 회수하고 삭제할까요?`
      : `${phone} 번호를 삭제할까요?`;
    if (!window.confirm(confirmMsg)) return;
    setBusy(true);
    try {
      const res = await deleteAdminCallNumber({ cnId: n.cnId });
      notify(res.message);
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '삭제 실패');
    } finally {
      setBusy(false);
    }
  };

  const openAssign = async (req: CallRequest) => {
    setAssignTarget(req);
    setAssignCn('');
    setAssignPartnerPrice('');
    setAssignAdvertiserPrice('');
    const prices = await resolveCampaignDefaultPrices(req.cpId);
    setAssignPartnerPrice(prices.partner);
    setAssignAdvertiserPrice(prices.advertiser);
  };

  const handleAssign = async () => {
    if (!assignTarget || !assignCn) return;
    const partnerPrice = parseCallPrice(assignPartnerPrice);
    let advertiserPrice = parseCallPrice(assignAdvertiserPrice);
    if (!Number.isFinite(partnerPrice) || partnerPrice <= 0) {
      notify('파트너 단가를 입력하세요.');
      return;
    }
    if (!Number.isFinite(advertiserPrice) || advertiserPrice <= 0) {
      advertiserPrice = partnerPrice;
    }
    if (advertiserPrice < partnerPrice) {
      notify('광고주 단가는 파트너 단가 이상이어야 합니다.');
      return;
    }
    setBusy(true);
    try {
      const res = await assignAdminCallRequest({
        carId: assignTarget.carId,
        cnId: Number(assignCn),
        partnerPrice,
        advertiserPrice,
      });
      notify(res.message);
      setAssignTarget(null);
      setPriceDrafts({});
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '배정 실패');
    } finally {
      setBusy(false);
    }
  };

  const handleReject = async (req: CallRequest) => {
    await rejectAdminCallRequest({ carId: req.carId });
    loadAll();
  };

  const handleRevoke = async (req: CallRequest) => {
    await revokeAdminCallRequest({ carId: req.carId });
    loadAll();
  };

  const handleDirectAssign = async () => {
    if (!directPt || !directCp || !directCn) return;
    const partnerPrice = parseCallPrice(directPartnerPrice);
    let advertiserPrice = parseCallPrice(directAdvertiserPrice);
    if (!Number.isFinite(partnerPrice) || partnerPrice <= 0) {
      notify('파트너 단가를 입력하세요.');
      return;
    }
    if (!Number.isFinite(advertiserPrice) || advertiserPrice <= 0) {
      advertiserPrice = partnerPrice;
    }
    if (advertiserPrice < partnerPrice) {
      notify('광고주 단가는 파트너 단가 이상이어야 합니다.');
      return;
    }
    setBusy(true);
    try {
      const res = await assignAdminCallDirect({
        ptId: Number(directPt),
        cpId: Number(directCp),
        cnId: Number(directCn),
        adminMemo: directMemo,
        partnerPrice,
        advertiserPrice,
      });
      notify(res.message);
      setDirectPt('');
      setDirectCp('');
      setDirectCn('');
      setDirectMemo('');
      setDirectPartnerPrice('');
      setDirectAdvertiserPrice('');
      setPriceDrafts({});
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '직접 배정 실패');
    } finally {
      setBusy(false);
    }
  };

  const handleMemoSave = async (n: CallNumber) => {
    const memo = (memoDrafts[n.cnId] ?? n.memo ?? '').trim();
    if (memo === (n.memo || '')) return;
    setBusy(true);
    try {
      const res = await updateAdminCallNumber({ cnId: n.cnId, memo });
      notify(res.message || '메모를 저장했습니다.');
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '메모 저장 실패');
      setMemoDrafts((prev) => ({ ...prev, [n.cnId]: n.memo || '' }));
    } finally {
      setBusy(false);
    }
  };

  const handlePriceSave = async (
    n: CallNumber,
    override?: { partner?: string; advertiser?: string },
  ) => {
    if (!n.cpId) {
      notify('배정된 번호만 단가를 수정할 수 있습니다.');
      return;
    }
    const draft = {
      partner: override?.partner ?? priceDrafts[n.cnId]?.partner ?? (n.partnerPrice > 0 ? String(n.partnerPrice) : ''),
      advertiser:
        override?.advertiser
        ?? priceDrafts[n.cnId]?.advertiser
        ?? (n.advertiserPrice > 0 ? String(n.advertiserPrice) : (n.partnerPrice > 0 ? String(n.partnerPrice) : '')),
    };
    const partnerPrice = parseCallPrice(draft.partner);
    let advertiserPrice = parseCallPrice(draft.advertiser);
    const prevPartner = Number(n.partnerPrice || 0);
    const prevAdvertiser = Number(n.advertiserPrice || 0);

    // 포커스만 이동한 blur — 빈 값이면 오류 대신 무시
    if (!Number.isFinite(partnerPrice) || partnerPrice <= 0) {
      if (prevPartner <= 0) return;
      notify('파트너 단가를 입력하세요.');
      return;
    }
    if (!Number.isFinite(advertiserPrice) || advertiserPrice <= 0) {
      advertiserPrice = partnerPrice;
    }
    if (advertiserPrice < partnerPrice) {
      notify('광고주 단가는 파트너 단가 이상이어야 합니다.');
      return;
    }
    if (partnerPrice === prevPartner && advertiserPrice === prevAdvertiser) {
      return;
    }
    setBusy(true);
    try {
      const res = await updateAdminCallNumber({ cnId: n.cnId, partnerPrice, advertiserPrice });
      notify(res.message || '단가를 저장했습니다.');
      setPriceDrafts((prev) => ({
        ...prev,
        [n.cnId]: { partner: String(partnerPrice), advertiser: String(advertiserPrice) },
      }));
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '단가 저장 실패');
      setPriceDrafts((prev) => ({
        ...prev,
        [n.cnId]: {
          partner: prevPartner > 0 ? String(prevPartner) : '',
          advertiser: prevAdvertiser > 0 ? String(prevAdvertiser) : (prevPartner > 0 ? String(prevPartner) : ''),
        },
      }));
    } finally {
      setBusy(false);
    }
  };

  const resolveCampaignDefaultPrices = async (cpId: number | string) => {
    const id = Number(cpId);
    if (!id) return { partner: '', advertiser: '' };

    const campaign = campaigns.find((c) => Number(c.id) === id);
    let partner = Number(campaign?.partnerPrice ?? 0);
    let advertiser = Number(campaign?.advertiserPrice ?? 0);

    // 광고상품 단가가 없으면 콜설정 단가를 보조로 사용
    if (partner <= 0 || advertiser <= 0) {
      try {
        const res = await fetchAdminCallSettings(id);
        if (partner <= 0) partner = Number(res.settings?.cs_price ?? 0);
        if (advertiser <= 0) advertiser = Number(res.settings?.cs_merchant_price ?? 0);
      } catch {
        // ignore
      }
    }
    if (advertiser <= 0 && partner > 0) advertiser = partner;

    return {
      partner: partner > 0 ? String(partner) : '',
      advertiser: advertiser > 0 ? String(advertiser) : '',
    };
  };

  const fillPricesFromCampaign = async (cpId: string) => {
    setDirectCp(cpId);
    if (!cpId) {
      setDirectPartnerPrice('');
      setDirectAdvertiserPrice('');
      return;
    }
    const prices = await resolveCampaignDefaultPrices(cpId);
    setDirectPartnerPrice(prices.partner);
    setDirectAdvertiserPrice(prices.advertiser);
  };

  const clearImportPreview = () => {
    setImportPreview([]);
    setImportHeaders([]);
    setImportTotal(0);
    setImportPreviewMsg('');
  };

  const handlePreviewLogs = async () => {
    if (!hasImportSource) return;
    setBusy(true);
    setImportResult('');
    clearImportPreview();
    try {
      const res = await importAdminCallLogs(
        importMode === 'paste'
          ? { pasteText: importPaste, dryRun: true }
          : { file: importFile!, dryRun: true },
      );
      setImportPreview(res.preview ?? []);
      setImportHeaders(res.headers ?? []);
      setImportTotal(res.total ?? 0);
      setImportPreviewMsg(res.message);
    } catch (e) {
      const msg = e instanceof Error ? e.message : '미리보기 실패';
      setImportPreviewMsg(msg);
      notify(msg);
    } finally {
      setBusy(false);
    }
  };

  const handleImportLogs = async () => {
    if (!hasImportSource) return;
    setBusy(true);
    setImportResult('');
    try {
      const res = await importAdminCallLogs(
        importMode === 'paste'
          ? { pasteText: importPaste, skipConversion: skipConversionOnImport }
          : { file: importFile!, skipConversion: skipConversionOnImport },
      );
      setImportResult(res.message);
      notify(res.message);
      setImportFile(null);
      setImportPaste('');
      clearImportPreview();
      loadAll();
    } catch (e) {
      const msg = e instanceof Error ? e.message : '가져오기 실패';
      setImportResult(msg);
      notify(msg);
    } finally {
      setBusy(false);
    }
  };

  const handleRematchLogs = async () => {
    setBusy(true);
    try {
      const res = await rematchAdminCallLogs({ ensureNumbers: true, onlyUnmatched: true });
      const poolMsg = res.pool?.message ? ` · ${res.pool.message}` : '';
      notify(`${res.message}${poolMsg}`);
      setImportResult(`${res.message}${poolMsg}`);
      loadAll();
    } catch (e) {
      notify(e instanceof Error ? e.message : '재매칭 실패');
    } finally {
      setBusy(false);
    }
  };

  const openSettings = async (cpId: number, name: string) => {
    setSettingsCp({ cpId, name });
    try {
      const res = await fetchAdminCallSettings(cpId);
      setSettingsDraft(res.settings ?? {});
    } catch {
      setSettingsDraft({});
    }
  };

  const handleSaveSettings = async () => {
    if (!settingsCp) return;
    const partnerPrice = Number(settingsDraft.cs_price ?? 0);
    const advertiserPrice = Number(settingsDraft.cs_merchant_price ?? settingsDraft.cs_price ?? 0);
    if (!Number.isFinite(partnerPrice) || partnerPrice <= 0) {
      notify('파트너 단가를 입력하세요.');
      return;
    }
    if (!Number.isFinite(advertiserPrice) || advertiserPrice < partnerPrice) {
      notify('광고주 단가는 파트너 단가 이상이어야 합니다.');
      return;
    }
    setBusy(true);
    try {
      const res = await saveAdminCallSettings({
        cpId: settingsCp.cpId,
        adminEnabled: true,
        forward1: String(settingsDraft.cs_forward1 ?? ''),
        forward2: String(settingsDraft.cs_forward2 ?? ''),
        recordingMode: String(settingsDraft.cs_recording_mode ?? 'normal'),
        coloring: String(settingsDraft.cs_coloring ?? ''),
        callMent: String(settingsDraft.cs_call_ment ?? ''),
        businessStart: String(settingsDraft.cs_business_start ?? '00:00'),
        businessEnd: String(settingsDraft.cs_business_end ?? '23:59'),
        holidayWeeks: String(settingsDraft.cs_holiday_weeks ?? ''),
        holidayDays: String(settingsDraft.cs_holiday_days ?? ''),
        price: partnerPrice,
        partnerPrice,
        advertiserPrice,
        minDuration: Number(settingsDraft.cs_min_duration ?? 0),
        memo: String(settingsDraft.cs_memo ?? ''),
      });
      notify(res.message);
      setSettingsCp(null);
    } catch (e) {
      notify(e instanceof Error ? e.message : '저장 실패');
    } finally {
      setBusy(false);
    }
  };

  const playRecording = (url: string) => {
    if (!url) {
      notify('녹취 URL이 없습니다.');
      return;
    }
    window.open(url, '_blank', 'noopener');
  };

  const handleFinal = async (cvId: number, finalAction: 'approve' | 'reject' | 'lock') => {
    if (cvId <= 0) return;
    await finalizeAdminConversion({ cvId, finalAction });
    notify('최종확정 처리되었습니다.');
    loadAll();
  };

  const openUploadModal = (item: CallRecordingRequestItem) => {
    setUploadTarget(item);
    setUploadFile(null);
    setUploadMemo('');
  };

  const handleUploadWav = async () => {
    if (!uploadTarget || !uploadFile) return;
    setBusy(true);
    try {
      const res = await uploadAdminCallRecordingWav({
        crrId: uploadTarget.crrId,
        file: uploadFile,
        adminMemo: uploadMemo || undefined,
      });
      notify(res.message);
      setUploadTarget(null);
      loadRecordingRequests();
    } catch (e) {
      notify(e instanceof Error ? e.message : '업로드 실패');
    } finally {
      setBusy(false);
    }
  };

  const handleRejectRecording = async (item: CallRecordingRequestItem) => {
    if (!window.confirm('이 녹음 요청을 반려하시겠습니까?')) return;
    setBusy(true);
    try {
      const res = await rejectAdminCallRecordingRequest({ crrId: item.crrId });
      notify(res.message);
      loadRecordingRequests();
    } catch (e) {
      notify(e instanceof Error ? e.message : '반려 실패');
    } finally {
      setBusy(false);
    }
  };

  const availableNumbers = numbers.filter((n) => n.status === 'available');
  const setDraft = (k: string, v: unknown) => setSettingsDraft((p) => ({ ...p, [k]: v }));

  return (
    <AdminLayout activeMenu="call" title="콜디비 관리" description="수동 운영 · 가상번호 등록/배정 · 통화내역 붙여넣기/업로드">
      <div className="mb-6 bg-violet-50 border border-violet-100 rounded-2xl p-4 text-sm text-violet-900">
        <div className="flex items-start gap-2">
          <Info className="w-5 h-5 shrink-0 mt-0.5" />
          <div>
            <div className="font-bold mb-1">콜디비 수동 운영 절차</div>
            <ol className="list-decimal pl-5 space-y-1 text-violet-900/90">
              <li>가상번호 풀에 번호를 <b>등록(일괄 가능)</b> → 파트너가 사용 가능 번호 중 <b>직접 선택</b></li>
              <li>파트너는 <b>캠페인당 번호 1개</b> (다른 캠페인은 각각 추가 배정 가능)</li>
              <li>필요 시 관리자가 풀에서 번호를 <b>수동/직접 배정</b>하거나 회수</li>
              <li>콜업체 통화내역을 <b>복사 붙여넣기</b> 또는 엑셀/CSV <b>업로드</b> (가상번호 열 필수)</li>
              <li>가상번호 기준으로 파트너·광고주 화면에 <b>담당 통화내역만</b> 자동 표시</li>
              <li>파트너·광고주가 <b>녹음 요청</b> → 최고관리자가 .wav 업로드 후 요청자가 재생</li>
            </ol>
          </div>
        </div>
      </div>

      {message && (
        <div className="mb-4 px-4 py-3 bg-cyan-50 border border-cyan-200 text-cyan-800 rounded-xl text-sm font-medium">{message}</div>
      )}

      <div className="flex gap-1 mb-6 bg-slate-100 p-1 rounded-xl w-fit">
        {([
          ['requests', '번호 신청', <PhoneCall size={16} key="r" />],
          ['numbers', '가상번호 풀', <Phone size={16} key="n" />],
          ['logs', '통화 로그', <PhoneIncoming size={16} key="l" />],
          ['recordings', '녹음 요청', <Headphones size={16} key="rec" />],
        ] as Array<[Tab, string, React.ReactNode]>).map(([id, label, icon]) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition ${tab === id ? 'bg-white text-cyan-600 shadow-sm' : 'text-slate-500'}`}
          >
            {icon}
            {label}
          </button>
        ))}
      </div>

      {/* 번호 신청 */}
      {tab === 'requests' && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">파트너 가상번호 신청</div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-slate-500 text-left">
                <tr>
                  <th className="px-4 py-3">신청일</th>
                  <th className="px-4 py-3">파트너</th>
                  <th className="px-4 py-3">캠페인</th>
                  <th className="px-4 py-3">배정번호</th>
                  <th className="px-4 py-3 text-center">상태</th>
                  <th className="px-4 py-3 text-center">관리</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {requests.length === 0 ? (
                  <tr><td colSpan={6} className="px-4 py-12 text-center text-slate-400">신청 내역이 없습니다.</td></tr>
                ) : requests.map((r) => {
                  const s = reqStatusLabel[r.status] ?? { label: r.status, cls: 'bg-slate-100 text-slate-500 border-slate-200' };
                  return (
                    <tr key={r.carId} className="hover:bg-slate-50">
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{r.createdAt}</td>
                      <td className="px-4 py-3 font-medium">{r.partner || `#${r.ptId}`}</td>
                      <td className="px-4 py-3">
                        <button type="button" onClick={() => openSettings(r.cpId, r.campaign)} className="inline-flex items-center gap-1 text-slate-700 hover:text-cyan-600">
                          {r.campaign}
                          <Settings2 size={13} className="text-slate-400" />
                        </button>
                      </td>
                      <td className="px-4 py-3 font-mono text-cyan-600">{r.virtualNumber || '—'}</td>
                      <td className="px-4 py-3 text-center">
                        <span className={`inline-block px-2.5 py-1 rounded-full text-xs font-bold border ${s.cls}`}>{s.label}</span>
                      </td>
                      <td className="px-4 py-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                          {r.status === 'pending' && (
                            <>
                              <button type="button" onClick={() => openAssign(r)} className="cursor-pointer px-2.5 py-1 text-xs font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 rounded-lg hover:bg-cyan-100 active:bg-cyan-200 transition-colors">번호배정</button>
                              <button type="button" onClick={() => handleReject(r)} className="cursor-pointer px-2.5 py-1 text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-100 active:bg-slate-200 transition-colors">반려</button>
                            </>
                          )}
                          {r.status === 'assigned' && (
                            <button type="button" onClick={() => handleRevoke(r)} className="cursor-pointer px-2.5 py-1 text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 rounded-lg hover:bg-rose-100 active:bg-rose-200 transition-colors">번호회수</button>
                          )}
                          {(r.status === 'rejected' || r.status === 'revoked') && <span className="text-xs text-slate-400">완료</span>}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 가상번호 풀 */}
      {tab === 'numbers' && (
        <div className="space-y-5">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div className="font-bold text-slate-800 mb-1">가상번호 수동 등록</div>
            <p className="text-xs text-slate-500 mb-3">콜업체에서 발급받은 050 가상번호를 관리자가 직접 풀에 등록합니다. 등록된 사용가능 번호를 파트너가 선택합니다.</p>
            <div className="flex flex-col sm:flex-row gap-3">
              <input type="text" value={newNumber} onChange={(e) => setNewNumber(e.target.value)} placeholder="가상번호 (예: 0503-6982-1000)"
                className="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono" />
              <input type="text" value={newMemo} onChange={(e) => setNewMemo(e.target.value)} placeholder="메모 (선택)"
                className="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm" />
              <button type="button" onClick={handleAddNumber} disabled={busy} className="inline-flex items-center gap-1.5 px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-sm disabled:opacity-50">
                <Plus size={16} /> 등록
              </button>
            </div>
            <div className="mt-4 pt-4 border-t border-slate-100">
              <div className="text-sm font-bold text-slate-700 mb-1">일괄 등록</div>
              <p className="text-xs text-slate-500 mb-2">한 줄에 하나씩, 또는 콤마/공백으로 구분해 여러 번호를 붙여넣으세요. 앞자리 0과 하이픈은 자동 처리됩니다.</p>
              <textarea
                value={bulkNumbers}
                onChange={(e) => setBulkNumbers(e.target.value)}
                rows={4}
                placeholder={"0503-6982-1001\n0503-6982-1002\n0503-6982-1003"}
                className="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono mb-2"
              />
              <div className="flex flex-col sm:flex-row gap-3">
                <input type="text" value={bulkMemo} onChange={(e) => setBulkMemo(e.target.value)} placeholder="공통 메모 (선택)"
                  className="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm" />
                <button type="button" onClick={handleBulkAddNumbers} disabled={busy || !bulkNumbers.trim()}
                  className="inline-flex items-center gap-1.5 px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl text-sm disabled:opacity-50">
                  <Plus size={16} /> 일괄 등록
                </button>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-2xl border border-cyan-200 shadow-sm p-5">
            <div className="font-bold text-slate-800 mb-1">파트너·캠페인 직접 배정</div>
            <p className="text-xs text-slate-500 mb-3">
              파트너는 캠페인당 번호 1개입니다. 다른 캠페인을 고르면 추가로 배정되고, 같은 캠페인이면 기존 번호를 교체합니다.
            </p>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <select value={directPt} onChange={(e) => setDirectPt(e.target.value)} className="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                <option value="">파트너 선택</option>
                {partners.map((p) => <option key={p.id} value={p.id}>{p.name || p.code}</option>)}
              </select>
              <select value={directCp} onChange={(e) => fillPricesFromCampaign(e.target.value)} className="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                <option value="">캠페인 선택</option>
                {campaigns.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                    {c.partnerPrice > 0
                      ? ` · P ${c.partnerPrice.toLocaleString()} / A ${(c.advertiserPrice || c.partnerPrice).toLocaleString()}`
                      : ''}
                  </option>
                ))}
              </select>
              <select value={directCn} onChange={(e) => setDirectCn(e.target.value)} className="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono">
                <option value="">가상번호 선택</option>
                {availableNumbers.map((n) => <option key={n.cnId} value={n.cnId}>{formatCallPhone(n.number)}</option>)}
              </select>
              <div>
                <label className="block text-[11px] font-bold text-slate-500 mb-1">파트너 단가 (원)</label>
                <input
                  type="number"
                  min={1}
                  value={directPartnerPrice}
                  onChange={(e) => setDirectPartnerPrice(e.target.value)}
                  placeholder="캠페인 선택 시 자동입력"
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm"
                />
              </div>
              <div>
                <label className="block text-[11px] font-bold text-slate-500 mb-1">광고주 단가 (원)</label>
                <input
                  type="number"
                  min={1}
                  value={directAdvertiserPrice}
                  onChange={(e) => setDirectAdvertiserPrice(e.target.value)}
                  placeholder="캠페인 선택 시 자동입력"
                  className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm"
                />
              </div>
              <button type="button" onClick={handleDirectAssign} disabled={busy || !directPt || !directCp || !directCn || !directPartnerPrice}
                className="px-4 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-50 self-end">
                직접 배정
              </button>
            </div>
            <p className="mt-2 text-[11px] text-slate-400">캠페인 선택 시 광고상품에 설정된 단가가 자동 입력됩니다. 필요하면 수정할 수 있습니다.</p>
            <input type="text" value={directMemo} onChange={(e) => setDirectMemo(e.target.value)} placeholder="배정 메모 (선택)"
              className="mt-3 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm" />
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-100 font-bold text-slate-800">가상번호 목록 ({numbers.length})</div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-slate-500 text-left">
                  <tr>
                    <th className="px-4 py-3">번호</th>
                    <th className="px-4 py-3 text-center">상태</th>
                    <th className="px-4 py-3">배정된 사람</th>
                    <th className="px-3 py-3 w-[120px]">설정 단가</th>
                    <th className="px-4 py-3">메모</th>
                    <th className="px-4 py-3 text-center">관리</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {numbers.length === 0 ? (
                    <tr><td colSpan={6} className="px-4 py-12 text-center text-slate-400">등록된 가상번호가 없습니다.</td></tr>
                  ) : numbers.map((n) => {
                    const s = numberStatusLabel[n.status] ?? { label: n.status, cls: 'bg-slate-100 text-slate-500 border-slate-200' };
                    const assignee = n.assignedPartner || n.assignee || '';
                    return (
                      <tr key={n.cnId} className="hover:bg-slate-50">
                        <td className="px-4 py-3 font-mono font-bold text-slate-800">{formatCallPhone(n.number)}</td>
                        <td className="px-4 py-3 text-center"><span className={`inline-block px-2.5 py-1 rounded-full text-xs font-bold border ${s.cls}`}>{s.label}</span></td>
                        <td className="px-4 py-3 text-slate-700">
                          {assignee ? (
                            <div>
                              <div className="font-medium">{assignee}</div>
                              {n.assignedCampaign ? <div className="text-xs text-slate-400 mt-0.5">{n.assignedCampaign}</div> : null}
                            </div>
                          ) : '—'}
                        </td>
                        <td className="px-3 py-3 text-slate-700 align-top">
                          {n.cpId ? (
                            <div className="w-[108px] space-y-1">
                              <label className="block text-[10px] font-bold text-slate-400 leading-none">파트너</label>
                              <input
                                type="number"
                                min={1}
                                value={priceDrafts[n.cnId]?.partner ?? (n.partnerPrice > 0 ? String(n.partnerPrice) : '')}
                                onChange={(e) => setPriceDrafts((prev) => ({
                                  ...prev,
                                  [n.cnId]: {
                                    partner: e.target.value,
                                    advertiser: prev[n.cnId]?.advertiser
                                      ?? (n.advertiserPrice > 0 ? String(n.advertiserPrice) : (n.partnerPrice > 0 ? String(n.partnerPrice) : '')),
                                  },
                                }))}
                                onBlur={(e) => {
                                  const partner = e.currentTarget.value;
                                  const advertiserRaw = priceDrafts[n.cnId]?.advertiser;
                                  const advertiser = (advertiserRaw && Number(advertiserRaw) > 0)
                                    ? advertiserRaw
                                    : (n.advertiserPrice > 0 ? String(n.advertiserPrice) : partner);
                                  void handlePriceSave(n, { partner, advertiser });
                                }}
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter') e.currentTarget.blur();
                                }}
                                placeholder="상품단가"
                                className="w-full max-w-[108px] box-border px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs tabular-nums"
                              />
                              <label className="block text-[10px] font-bold text-slate-400 leading-none pt-0.5">광고주</label>
                              <input
                                type="number"
                                min={1}
                                value={priceDrafts[n.cnId]?.advertiser ?? (n.advertiserPrice > 0 ? String(n.advertiserPrice) : '')}
                                onChange={(e) => setPriceDrafts((prev) => ({
                                  ...prev,
                                  [n.cnId]: {
                                    partner: prev[n.cnId]?.partner ?? (n.partnerPrice > 0 ? String(n.partnerPrice) : ''),
                                    advertiser: e.target.value,
                                  },
                                }))}
                                onBlur={(e) => {
                                  const advertiser = e.currentTarget.value;
                                  const partnerRaw = priceDrafts[n.cnId]?.partner;
                                  const partner = (partnerRaw && Number(partnerRaw) > 0)
                                    ? partnerRaw
                                    : (n.partnerPrice > 0 ? String(n.partnerPrice) : '');
                                  void handlePriceSave(n, { partner, advertiser });
                                }}
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter') e.currentTarget.blur();
                                }}
                                placeholder="상품단가"
                                className="w-full max-w-[108px] box-border px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-500 tabular-nums"
                              />
                            </div>
                          ) : '—'}
                        </td>
                        <td className="px-4 py-3 align-top">
                          <input
                            type="text"
                            value={memoDrafts[n.cnId] ?? n.memo ?? ''}
                            onChange={(e) => setMemoDrafts((prev) => ({ ...prev, [n.cnId]: e.target.value }))}
                            onBlur={() => handleMemoSave(n)}
                            onKeyDown={(e) => {
                              if (e.key === 'Enter') {
                                e.currentTarget.blur();
                              }
                            }}
                            placeholder="메모 입력"
                            className="w-full max-w-[220px] min-w-[120px] px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm"
                          />
                        </td>
                        <td className="px-4 py-3 text-center">
                          <div className="inline-flex items-center gap-2">
                            <select
                              value={n.status}
                              onChange={(e) => handleNumberStatus(n, e.target.value)}
                              disabled={busy}
                              className="px-2 py-1 text-xs bg-slate-50 border border-slate-200 rounded-lg disabled:opacity-60"
                            >
                              <option value="available">사용가능</option>
                              <option value="paused">일시중지</option>
                              <option value="released">해지</option>
                              {n.status === 'assigned' && <option value="assigned">배정됨</option>}
                            </select>
                            <button
                              type="button"
                              onClick={() => handleDeleteNumber(n)}
                              disabled={busy}
                              title="삭제"
                              className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-white bg-rose-500 border border-rose-600 rounded-lg hover:bg-rose-600 disabled:opacity-40"
                            >
                              <Trash2 size={13} /> 삭제
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* 통화 로그 */}
      {tab === 'logs' && (
        <div className="space-y-5">
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div className="font-bold text-slate-800 mb-1 flex items-center gap-2">
              <ClipboardPaste size={18} className="text-cyan-600" />
              통화내역 가져오기
            </div>
            <p className="text-xs text-slate-500 mb-3">
              콜업체 시트에서 헤더 포함해 복사한 뒤 붙여넣거나, 파일(xlsx/xls/csv)을 업로드합니다.
              <b> 가상번호</b>가 배정된 파트너·캠페인에 자동 연결됩니다.
            </p>

            <div className="flex gap-1 mb-4 bg-slate-100 p-1 rounded-xl w-fit">
              <button
                type="button"
                onClick={() => { setImportMode('paste'); clearImportPreview(); setImportResult(''); }}
                className={`inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-bold transition ${importMode === 'paste' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500'}`}
              >
                <ClipboardPaste size={14} /> 복사 붙여넣기
              </button>
              <button
                type="button"
                onClick={() => { setImportMode('file'); clearImportPreview(); setImportResult(''); }}
                className={`inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-bold transition ${importMode === 'file' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500'}`}
              >
                <Upload size={14} /> 파일 업로드
              </button>
            </div>

            <div className="text-xs text-slate-600 bg-slate-50 border border-slate-100 rounded-xl p-3 mb-4 space-y-1.5">
              <div>
                지원 열: <b>발신번호, 가상번호, 착신번호, 통화일자, 통화시작시간, 통화시간(초), 녹음파일, 통화결과</b>
              </div>
              <div className="text-slate-500">
                필수: <b>가상번호</b> · 통화일자+통화시작시간은 자동 합침 · 통화시간 <code className="px-1 bg-white border border-slate-200 rounded">19초</code> 형식 지원 · 결과 <code className="px-1 bg-white border border-slate-200 rounded">통화성공</code>/<code className="px-1 bg-white border border-slate-200 rounded">부재중</code>
              </div>
            </div>

            {importMode === 'paste' ? (
              <div className="mb-4">
                <textarea
                  value={importPaste}
                  onChange={(e) => {
                    setImportPaste(e.target.value);
                    clearImportPreview();
                    setImportResult('');
                  }}
                  placeholder={CALL_LOG_PASTE_PLACEHOLDER}
                  rows={8}
                  className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono leading-relaxed focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-cyan-300 resize-y min-h-[10rem]"
                />
                <div className="mt-2 flex flex-wrap gap-2">
                  <button
                    type="button"
                    onClick={async () => {
                      try {
                        const text = await navigator.clipboard.readText();
                        if (text.trim()) {
                          setImportPaste(text);
                          clearImportPreview();
                          setImportResult('');
                        } else {
                          notify('클립보드가 비어 있습니다.');
                        }
                      } catch {
                        notify('클립보드 읽기 권한이 없습니다. 직접 붙여넣기 해주세요.');
                      }
                    }}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs"
                  >
                    <ClipboardPaste size={13} /> 클립보드에서 붙여넣기
                  </button>
                  {importPaste.trim() ? (
                    <button
                      type="button"
                      onClick={() => { setImportPaste(''); clearImportPreview(); setImportResult(''); }}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 text-slate-500 hover:text-slate-700 font-bold rounded-lg text-xs"
                    >
                      지우기
                    </button>
                  ) : null}
                </div>
              </div>
            ) : (
              <div className="mb-4">
                <input
                  type="file"
                  accept=".xlsx,.xls,.csv"
                  onChange={(e) => {
                    setImportFile(e.target.files?.[0] ?? null);
                    clearImportPreview();
                    setImportResult('');
                  }}
                  className="text-sm"
                />
              </div>
            )}

            <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center flex-wrap">
              <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" checked={skipConversionOnImport} onChange={(e) => setSkipConversionOnImport(e.target.checked)} className="accent-cyan-500" />
                콜DB 전환 자동 생성 안 함 (통화내역만 표시)
              </label>
              <button type="button" onClick={handlePreviewLogs} disabled={busy || !hasImportSource}
                className="inline-flex items-center gap-1.5 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-sm disabled:opacity-50">
                미리보기
              </button>
              <button type="button" onClick={handleImportLogs} disabled={busy || !hasImportSource}
                className="inline-flex items-center gap-1.5 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-50">
                {importMode === 'paste' ? <ClipboardPaste size={16} /> : <Upload size={16} />}
                {importMode === 'paste' ? '붙여넣기 등록' : '업로드'}
              </button>
            </div>
            {importPreviewMsg ? <p className="mt-3 text-sm text-slate-600">{importPreviewMsg}</p> : null}
            {importPreview.length > 0 && (
              <div className="mt-4 border border-slate-200 rounded-xl overflow-hidden">
                <div className="px-4 py-2 bg-slate-50 text-xs font-bold text-slate-600 border-b border-slate-200">
                  미리보기 (총 {importTotal.toLocaleString()}행 중 상위 {importPreview.length}행)
                  {importHeaders.length > 0 && <span className="font-normal text-slate-400 ml-2">원본 열: {importHeaders.join(', ')}</span>}
                </div>
                <div className="overflow-x-auto">
                  <table className="w-full text-xs">
                    <thead className="bg-slate-50 text-slate-500 text-left">
                      <tr>
                        <th className="px-3 py-2">행</th>
                        <th className="px-3 py-2">가상번호</th>
                        <th className="px-3 py-2">발신번호</th>
                        <th className="px-3 py-2">착신번호</th>
                        <th className="px-3 py-2">통화시작</th>
                        <th className="px-3 py-2">통화시간</th>
                        <th className="px-3 py-2">결과</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {importPreview.map((row, i) => (
                        <tr key={i} className="hover:bg-slate-50">
                          <td className="px-3 py-2 text-slate-400">{String(row.importRow ?? i + 1)}</td>
                          <td className="px-3 py-2 font-mono text-violet-700">{String(row.virtualNumber ?? '—')}</td>
                          <td className="px-3 py-2 font-mono">{String(row.caller ?? '—')}</td>
                          <td className="px-3 py-2 font-mono text-slate-500">{String(row.callee ?? '—')}</td>
                          <td className="px-3 py-2">{String(row.startedAt ?? '—')}</td>
                          <td className="px-3 py-2">{String(row.duration ?? '—')}</td>
                          <td className="px-3 py-2">{String(row.result ?? '—')}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
            {importResult ? <p className="mt-3 text-sm text-cyan-700">{importResult}</p> : null}
          </div>

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div className="font-bold text-slate-800">통화 로그 (녹취 열람 · 최종확정)</div>
            <button
              type="button"
              onClick={handleRematchLogs}
              disabled={busy}
              className="inline-flex items-center gap-1.5 px-4 py-2 bg-violet-50 hover:bg-violet-100 text-violet-700 border border-violet-200 font-bold rounded-xl text-sm disabled:opacity-50"
              title="미매칭 로그를 가상번호 배정 기준으로 파트너·캠페인에 다시 연결합니다"
            >
              가상번호 기준 재매칭
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-slate-500 text-left">
                <tr>
                  <th className="px-4 py-3">일시</th>
                  <th className="px-4 py-3">가상번호</th>
                  <th className="px-4 py-3">발신</th>
                  <th className="px-4 py-3">파트너/캠페인</th>
                  <th className="px-4 py-3 text-center">통화</th>
                  <th className="px-4 py-3 text-center">결과</th>
                  <th className="px-4 py-3 text-center">녹취</th>
                  <th className="px-4 py-3 text-center">최종확정</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {logs.length === 0 ? (
                  <tr><td colSpan={8} className="px-4 py-12 text-center text-slate-400">통화 로그가 없습니다.</td></tr>
                ) : logs.map((l) => {
                  const r = resultLabel[l.result] ?? { label: l.result, cls: 'text-slate-500' };
                  return (
                    <tr key={l.clogId} className="hover:bg-slate-50">
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{l.startedAt}</td>
                      <td className="px-4 py-3 font-mono">{l.virtualNumber}</td>
                      <td className="px-4 py-3 font-mono">{l.caller}</td>
                      <td className="px-4 py-3">
                        <div className="font-medium">{l.partner}</div>
                        <div className="text-xs text-slate-400">{l.campaign || '미매칭'}</div>
                      </td>
                      <td className="px-4 py-3 text-center font-mono">{fmtDuration(l.duration)}</td>
                      <td className={`px-4 py-3 text-center font-bold ${r.cls}`}>{r.label}</td>
                      <td className="px-4 py-3 text-center">
                        {l.hasRecording && l.recordingUrl ? (
                          <button type="button" onClick={() => playRecording(l.recordingUrl!)} className="inline-flex items-center gap-1 text-cyan-600 font-bold text-xs">
                            <PlayCircle size={16} /> 재생
                          </button>
                        ) : <span className="text-slate-300 text-xs">없음</span>}
                      </td>
                      <td className="px-4 py-3 text-center">
                        {l.cvId > 0 ? (
                          <div className="flex items-center justify-center gap-1">
                            <button type="button" title="최종 승인" onClick={() => handleFinal(l.cvId, 'approve')} className="p-1.5 bg-emerald-50 text-emerald-700 rounded-lg"><Check size={14} /></button>
                            <button type="button" title="최종 취소불가" onClick={() => handleFinal(l.cvId, 'reject')} className="p-1.5 bg-rose-50 text-rose-700 rounded-lg"><X size={14} /></button>
                            <button type="button" title="현재상태 잠금" onClick={() => handleFinal(l.cvId, 'lock')} className="p-1.5 bg-slate-100 text-slate-600 rounded-lg"><Lock size={14} /></button>
                          </div>
                        ) : <span className="text-slate-300 text-xs">DB 없음</span>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          </div>
        </div>
      )}

      {/* 녹음 요청 */}
      {tab === 'recordings' && (
        <div className="space-y-5">
          {!isSuperAdmin && (
            <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-900">
              녹음 파일(.wav) 업로드는 <b>최고관리자</b>만 가능합니다. 요청 목록은 확인할 수 있습니다.
            </div>
          )}

          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center gap-3 justify-between">
              <div className="font-bold text-slate-800">녹음 파일 요청 목록</div>
              <select
                value={recStatusFilter}
                onChange={(e) => setRecStatusFilter(e.target.value)}
                className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm"
              >
                <option value="">전체 상태</option>
                <option value="pending">요청 대기</option>
                <option value="fulfilled">등록 완료</option>
                <option value="rejected">반려</option>
              </select>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-slate-500 text-left">
                  <tr>
                    <th className="px-4 py-3">요청일</th>
                    <th className="px-4 py-3">요청자</th>
                    <th className="px-4 py-3">통화 정보</th>
                    <th className="px-4 py-3">요청 메모</th>
                    <th className="px-4 py-3 text-center">상태</th>
                    <th className="px-4 py-3 text-center">녹음</th>
                    {isSuperAdmin && <th className="px-4 py-3 text-center">관리</th>}
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {recordingRequests.length === 0 ? (
                    <tr>
                      <td colSpan={isSuperAdmin ? 7 : 6} className="px-4 py-12 text-center text-slate-400">
                        녹음 요청이 없습니다.
                      </td>
                    </tr>
                  ) : recordingRequests.map((item) => {
                    const s = recReqStatusLabel[item.status] ?? { label: item.statusLabel || item.status, cls: 'bg-slate-100 text-slate-500 border-slate-200' };
                    const requester = item.requesterType === 'partner'
                      ? `파트너 · ${item.partner || `#${item.ptId}`}`
                      : `광고주 · ${item.merchant || `#${item.mtId}`}`;
                    return (
                      <tr key={item.crrId} className="hover:bg-slate-50">
                        <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{item.requestedAt}</td>
                        <td className="px-4 py-3">
                          <div className="font-medium text-slate-800">{requester}</div>
                          <div className="text-xs text-slate-400">요청 #{item.crrId}</div>
                        </td>
                        <td className="px-4 py-3">
                          <div className="font-mono text-slate-800">{item.virtualNumber}</div>
                          <div className="text-xs text-slate-500">{item.startedAt} · {item.caller}</div>
                          <div className="text-xs text-slate-400">{item.campaign || '—'}</div>
                        </td>
                        <td className="px-4 py-3 text-slate-500 max-w-xs truncate">{item.requestMemo || '—'}</td>
                        <td className="px-4 py-3 text-center">
                          <span className={`inline-block px-2.5 py-1 rounded-full text-xs font-bold border ${s.cls}`}>{s.label}</span>
                        </td>
                        <td className="px-4 py-3 text-center">
                          {item.canPlay && item.playUrl ? (
                            <div className="flex flex-col items-center gap-1">
                              <a href={item.playUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-cyan-600 font-bold text-xs">
                                <PlayCircle size={16} /> 재생
                              </a>
                              {item.originalName && <span className="text-xs text-slate-400 truncate max-w-[8rem]">{item.originalName}</span>}
                            </div>
                          ) : (
                            <span className="text-slate-300 text-xs">—</span>
                          )}
                        </td>
                        {isSuperAdmin && (
                          <td className="px-4 py-3 text-center">
                            {item.status === 'pending' ? (
                              <div className="flex items-center justify-center gap-1">
                                <button
                                  type="button"
                                  onClick={() => openUploadModal(item)}
                                  className="cursor-pointer px-2.5 py-1 text-xs font-bold bg-cyan-50 text-cyan-700 border border-cyan-200 rounded-lg hover:bg-cyan-100 active:bg-cyan-200 transition-colors"
                                >
                                  .wav 업로드
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleRejectRecording(item)}
                                  className="cursor-pointer px-2.5 py-1 text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-100 active:bg-slate-200 transition-colors"
                                >
                                  반려
                                </button>
                              </div>
                            ) : (
                              <span className="text-xs text-slate-400">{item.adminMemo || '완료'}</span>
                            )}
                          </td>
                        )}
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* 녹음 업로드 모달 */}
      {uploadTarget && (
        <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" onClick={() => setUploadTarget(null)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
            <div className="font-bold text-lg text-slate-900 mb-1">녹음 파일 업로드</div>
            <p className="text-sm text-slate-500 mb-1">
              {uploadTarget.requesterType === 'partner' ? uploadTarget.partner : uploadTarget.merchant} · {uploadTarget.virtualNumber}
            </p>
            <p className="text-xs text-slate-400 mb-4">{uploadTarget.startedAt} · {uploadTarget.caller}</p>
            <label className="block text-xs font-bold text-slate-500 mb-1">.wav 파일</label>
            <input
              type="file"
              accept=".wav,audio/wav,audio/x-wav"
              onChange={(e) => setUploadFile(e.target.files?.[0] ?? null)}
              className="w-full text-sm mb-3"
            />
            <label className="block text-xs font-bold text-slate-500 mb-1">관리자 메모 (선택)</label>
            <input
              type="text"
              value={uploadMemo}
              onChange={(e) => setUploadMemo(e.target.value)}
              placeholder="처리 메모"
              className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm mb-4"
            />
            <div className="flex gap-2 justify-end">
              <button type="button" onClick={() => setUploadTarget(null)} className="px-4 py-2 text-sm font-bold text-slate-500">취소</button>
              <button
                type="button"
                onClick={handleUploadWav}
                disabled={busy || !uploadFile}
                className="px-5 py-2 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-50"
              >
                업로드
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 배정 모달 */}
      {assignTarget && (
        <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" onClick={() => setAssignTarget(null)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
            <div className="font-bold text-lg text-slate-900 mb-1">가상번호 배정</div>
            <p className="text-sm text-slate-500 mb-4">{assignTarget.partner} · {assignTarget.campaign}</p>
            <label className="block text-xs font-bold text-slate-500 mb-1">사용 가능한 번호</label>
            <select value={assignCn} onChange={(e) => setAssignCn(e.target.value)} className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono mb-4">
              <option value="">번호 선택</option>
              {availableNumbers.map((n) => <option key={n.cnId} value={n.cnId}>{formatCallPhone(n.number)}</option>)}
            </select>
            <label className="block text-xs font-bold text-slate-500 mb-1">파트너 단가 (원) *</label>
            <input
              type="number"
              min={1}
              value={assignPartnerPrice}
              onChange={(e) => setAssignPartnerPrice(e.target.value)}
              placeholder="캠페인 단가 자동입력"
              className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm mb-3"
            />
            <label className="block text-xs font-bold text-slate-500 mb-1">광고주 단가 (원) *</label>
            <input
              type="number"
              min={1}
              value={assignAdvertiserPrice}
              onChange={(e) => setAssignAdvertiserPrice(e.target.value)}
              placeholder="캠페인 단가 자동입력"
              className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm mb-1"
            />
            <p className="text-[11px] text-slate-400 mb-4">광고상품 단가가 기본값으로 채워집니다. 수정 가능합니다.</p>
            {availableNumbers.length === 0 && <p className="text-xs text-rose-500 mb-3">사용 가능한 번호가 없습니다. 가상번호 풀에서 먼저 등록하세요.</p>}
            <div className="flex gap-2 justify-end">
              <button type="button" onClick={() => setAssignTarget(null)} className="px-4 py-2 text-sm font-bold text-slate-500">취소</button>
              <button type="button" onClick={handleAssign} disabled={busy || !assignCn || !assignPartnerPrice} className="px-5 py-2 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-50">배정하기</button>
            </div>
          </div>
        </div>
      )}

      {/* 콜 설정 모달 */}
      {settingsCp && (
        <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" onClick={() => setSettingsCp(null)}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="font-bold text-lg text-slate-900 mb-1">콜 설정 — {settingsCp.name}</div>
            <p className="text-sm text-slate-500 mb-4">수신번호·단가·녹음·업무시간을 설정합니다. 수신번호는 광고주도 수정할 수 있으며, 변경 시 중요알림이 갑니다.</p>
            <div className="space-y-3">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">수신번호 1</label>
                  <input
                    type="text"
                    inputMode="tel"
                    value={String(settingsDraft.cs_forward1 ?? '')}
                    onChange={(e) => setDraft('cs_forward1', e.target.value)}
                    placeholder="01012345678"
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">수신번호 2</label>
                  <input
                    type="text"
                    inputMode="tel"
                    value={String(settingsDraft.cs_forward2 ?? '')}
                    onChange={(e) => setDraft('cs_forward2', e.target.value)}
                    placeholder="01087654321"
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono"
                  />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">파트너 단가 (원) *</label>
                  <input type="number" min={1} value={Number(settingsDraft.cs_price ?? 0)} onChange={(e) => setDraft('cs_price', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">광고주 단가 (원) *</label>
                  <input type="number" min={1} value={Number(settingsDraft.cs_merchant_price ?? settingsDraft.cs_price ?? 0)} onChange={(e) => setDraft('cs_merchant_price', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">녹음 방식</label>
                  <select value={String(settingsDraft.cs_recording_mode ?? 'normal')} onChange={(e) => setDraft('cs_recording_mode', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                    <option value="normal">녹음</option>
                    <option value="none">녹음 안함</option>
                    <option value="both">양방향 녹음</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">최소 통화시간(초)</label>
                  <input type="number" value={Number(settingsDraft.cs_min_duration ?? 0)} onChange={(e) => setDraft('cs_min_duration', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">컬러링</label>
                  <input type="text" value={String(settingsDraft.cs_coloring ?? '')} onChange={(e) => setDraft('cs_coloring', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">업무 시작</label>
                  <input type="time" value={String(settingsDraft.cs_business_start ?? '00:00')} onChange={(e) => setDraft('cs_business_start', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1">업무 종료</label>
                  <input type="time" value={String(settingsDraft.cs_business_end ?? '23:59')} onChange={(e) => setDraft('cs_business_end', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
                </div>
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 mb-1">안내 멘트</label>
                <input type="text" value={String(settingsDraft.cs_call_ment ?? '')} onChange={(e) => setDraft('cs_call_ment', e.target.value)} placeholder="연결 전 안내 멘트" className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 mb-1">관리자 메모</label>
                <input type="text" value={String(settingsDraft.cs_memo ?? '')} onChange={(e) => setDraft('cs_memo', e.target.value)} className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm" />
              </div>
            </div>
            <div className="flex gap-2 justify-end mt-5">
              <button type="button" onClick={() => setSettingsCp(null)} className="px-4 py-2 text-sm font-bold text-slate-500">취소</button>
              <button type="button" onClick={handleSaveSettings} disabled={busy} className="px-5 py-2 bg-cyan-500 hover:bg-cyan-600 text-white font-bold rounded-xl text-sm disabled:opacity-50">저장</button>
            </div>
          </div>
        </div>
      )}
    </AdminLayout>
  );
}
