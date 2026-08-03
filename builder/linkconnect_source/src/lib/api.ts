import { lcPluginUrl } from './urls';

export type ApiErrorBody = {
  ok: false;
  error: string;
  code: string;
  data?: Record<string, unknown>;
};

export type ApiSuccessBody<T> = {
  ok: true;
  data: T;
  meta?: Record<string, unknown>;
};

export class PartnerApiError extends Error {
  code: string;
  status: number;

  constructor(message: string, code: string, status: number) {
    super(message);
    this.name = 'PartnerApiError';
    this.code = code;
    this.status = status;
  }
}

const PARTNER_API_BASE = lcPluginUrl('partner/api');

async function parseJson<T>(response: Response): Promise<T> {
  const text = await response.text();
  if (!text) {
    throw new PartnerApiError('빈 응답입니다.', 'EMPTY_RESPONSE', response.status);
  }

  try {
    return JSON.parse(text) as T;
  } catch {
    throw new PartnerApiError('JSON 파싱에 실패했습니다.', 'INVALID_JSON', response.status);
  }
}

export async function partnerApiGet<T>(endpoint: string, query?: Record<string, string>): Promise<T> {
  const url = new URL(`${PARTNER_API_BASE}/${endpoint}`, window.location.origin);
  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== '') {
        url.searchParams.set(key, value);
      }
    });
  }

  const response = await fetch(url.toString(), {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export async function partnerApiPost<T>(endpoint: string, payload?: Record<string, unknown>): Promise<T> {
  const response = await fetch(`${PARTNER_API_BASE}/${endpoint}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: payload ? JSON.stringify(payload) : undefined,
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export type PartnerProfile = {
  id: number;
  code: string;
  name: string;
  status: string;
  statusLabel: string;
  balance: number;
  bankName: string;
  bankAccount: string;
  bankHolder: string;
  createdAt: string;
};

export type PartnerMeResponse = {
  auth: import('./auth').LcAuth;
  partner: PartnerProfile | null;
  dbReady: boolean;
};

export type PartnerCampaign = {
  id: number;
  code: string;
  title: string;
  category: string;
  type: string;
  description: string;
  price: number;
  priceFormatted: string;
  approvalRate: string;
  avgTime: string;
  allowedChannels: string;
  forbiddenChannels: string;
  status: string;
  statusCode: string;
  badge: string;
  recommended: boolean;
  landingUrl: string;
  trackingBaseUrl?: string;
  thumbnailUrl?: string;
  hasPublishedGuide?: boolean;
  campaignType?: 'cpa' | 'cps';
  merchantCode?: string;
  promoUrl?: string;
};

export type PartnerPromoGuideImage = {
  id: number;
  assetId: number;
  originalFilename: string;
  mimeType: string;
  fileSize: number;
  imageTitle: string;
  sortOrder: number;
  downloadUrl: string;
};

export type PartnerPromoGuideConfirmation = {
  confirmed: boolean;
  confirmedAt: string;
  guideUpdatedAt: string;
  guideId: number;
};

export type PartnerPromoGuideDetail = {
  campaign: PartnerCampaign;
  guide: {
    id: number;
    guideId: number;
    campaignId: number;
    promotionPoints: string[];
    recommendedKeywords: string[];
    forbiddenWords: string[];
    precautions: string[];
    validDbRules: string[];
    invalidDbRules: string[];
    approvalType: 'free' | 'first_review' | 'all_review';
    guideStatus: string;
    updatedAt: string;
    publishedAt: string;
    images: PartnerPromoGuideImage[];
  };
  confirmation: PartnerPromoGuideConfirmation;
};

export function fetchPartnerPromoGuide(cpId: number) {
  return partnerApiGet<PartnerPromoGuideDetail>('campaign-guide.php', { cpId: String(cpId) });
}

export function confirmPartnerPromoGuide(cpId: number) {
  return partnerApiPost<{ message: string; confirmation: PartnerPromoGuideConfirmation }>('campaign-guide.php', {
    action: 'confirm',
    cpId,
  });
}

export type PartnerCampaignsResponse = {
  items: PartnerCampaign[];
  categories: string[];
  dbReady: boolean;
};

export function fetchPartnerMe() {
  return partnerApiGet<PartnerMeResponse>('me.php');
}

export function fetchPartnerCampaigns(filters?: { category?: string; q?: string }) {
  return partnerApiGet<PartnerCampaignsResponse>('campaigns.php', {
    category: filters?.category ?? '',
    q: filters?.q ?? '',
  });
}

export type PartnerLink = {
  id: number;
  code: string;
  campaign: string;
  campaignId: number;
  channel: string;
  subId: string;
  url: string;
  landingUrl?: string;
  trackingBaseUrl?: string;
  clicks: number;
  received: number;
  approved: number;
  canceled: number;
  estRevenue: number;
  confRevenue: number;
  status: string;
  statusCode: string;
  createdAt: string;
};

export type PartnerConversion = {
  id: string;
  cvId: number;
  date: string;
  campaign: string;
  name: string;
  phone: string;
  channel: string;
  subId: string;
  status: string;
  statusCode: string;
  price: number;
  estRevenue: number;
  confRevenue: number;
  comment: string;
  reason?: string;
  appeal?: string;
  hasAppeal?: boolean;
};

export type PartnerDashboardResponse = {
  balance: number;
  balanceFormatted: string;
  summary: {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
    todayReceived: number;
    todayClicks: number;
    estRevenue: number;
    confRevenue: number;
    todayEstRevenue: number;
  };
  chart7d: Array<{ date: string; click: number; db: number; approval: number }>;
  channels: Array<{ channel: string; clicks: number; dbs: number; approved: number; percentage: number }>;
  recent: PartnerConversion[];
};

export function fetchPartnerDashboard() {
  return partnerApiGet<PartnerDashboardResponse>('dashboard.php');
}

export function fetchPartnerLinks() {
  return partnerApiGet<{ items: PartnerLink[]; total: number }>('links.php');
}

export function createPartnerLink(payload: { campaignId: number; channel?: string; subId?: string }) {
  return partnerApiPost<{ message: string; link: PartnerLink | null }>('links.php', payload);
}

/** CPA /r/{code} → /s/{short} 숏링크 (linkId / code / campaignId) */
export function buildPartnerCpaShortlink(
  payload: { linkId: number } | { code: string } | { campaignId: number },
) {
  return partnerApiPost<{
    message: string;
    shortUrl: string;
    promoUrl: string;
    shortCode: string;
    link?: PartnerLink | null;
  }>('links.php', {
    action: 'shortlink',
    ...payload,
  });
}

export function fetchPartnerConversions(filters?: { status?: string; q?: string; rejected?: boolean }) {
  return partnerApiGet<{ items: PartnerConversion[]; summary: PartnerDashboardResponse['summary']; total: number }>(
    'conversions.php',
    {
      status: filters?.status ?? '',
      q: filters?.q ?? '',
      rejected: filters?.rejected ? '1' : '',
    },
  );
}

export function applyPartner() {
  return partnerApiPost<{ partner: PartnerProfile | null; message: string }>('apply.php');
}

const MERCHANT_API_BASE = lcPluginUrl('merchant/api');

export async function merchantApiGet<T>(endpoint: string, query?: Record<string, string>): Promise<T> {
  const url = new URL(`${MERCHANT_API_BASE}/${endpoint}`, window.location.origin);
  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== '') {
        url.searchParams.set(key, value);
      }
    });
  }

  const response = await fetch(url.toString(), {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export async function merchantApiPost<T>(endpoint: string, payload?: Record<string, unknown>): Promise<T> {
  const response = await fetch(`${MERCHANT_API_BASE}/${endpoint}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: payload ? JSON.stringify(payload) : undefined,
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    const extra = errBody.data && typeof errBody.data === 'object' ? (errBody.data as Record<string, unknown>) : undefined;
    const err = new PartnerApiError(errBody.error, errBody.code, response.status);
    if (extra?.errors) {
      (err as PartnerApiError & { fieldErrors?: Record<string, string> }).fieldErrors = extra.errors as Record<string, string>;
    }
    throw err;
  }

  return (body as ApiSuccessBody<T>).data;
}

export async function merchantApiPostFormData<T>(endpoint: string, formData: FormData): Promise<T> {
  const response = await fetch(`${MERCHANT_API_BASE}/${endpoint}`, {
    method: 'POST',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    body: formData,
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    const extra = errBody.data && typeof errBody.data === 'object' ? (errBody.data as Record<string, unknown>) : undefined;
    const err = new PartnerApiError(errBody.error, errBody.code, response.status);
    if (extra?.errors) {
      (err as PartnerApiError & { fieldErrors?: Record<string, string> }).fieldErrors = extra.errors as Record<string, string>;
    }
    throw err;
  }

  return (body as ApiSuccessBody<T>).data;
}

export type MerchantProfile = {
  id: number;
  code: string;
  company: string;
  status: string;
  statusLabel: string;
  balance: number;
  createdAt: string;
};

export type MerchantMeResponse = {
  auth: import('./auth').LcAuth;
  merchant: MerchantProfile | null;
  dbReady: boolean;
};

export type MerchantConversion = {
  id: string;
  cvId: number;
  date: string;
  campaign: string;
  name: string;
  phone: string;
  email: string;
  region: string;
  inquiry: string;
  partner: string;
  status: string;
  statusCode: string;
  price: number;
  comment: string;
  needsAction: boolean;
  channel: string;
  subId: string;
  qualityScore?: number;
  qualityTags?: string[];
  partnerVisible?: boolean;
};

export type MerchantDashboardResponse = {
  balance: number;
  balanceFormatted: string;
  summary: {
    pending: number;
    approved: number;
    rejected: number;
    needsAction: number;
    todayReceived: number;
    todaySpend: number;
  };
  chart7d: Array<{ date: string; db: number; approval: number; cancel: number }>;
  recent: MerchantConversion[];
  pendingAction: number;
};

export function fetchMerchantMe() {
  return merchantApiGet<MerchantMeResponse>('me.php');
}

export function fetchMerchantDashboard() {
  return merchantApiGet<MerchantDashboardResponse>('dashboard.php');
}

export function fetchMerchantConversions(filters?: { status?: string; q?: string; needsAction?: boolean }) {
  return merchantApiGet<{ items: MerchantConversion[]; summary: MerchantDashboardResponse['summary']; total: number }>(
    'conversions.php',
    {
      status: filters?.status ?? '',
      q: filters?.q ?? '',
      needs_action: filters?.needsAction ? '1' : '',
    },
  );
}

export function updateMerchantConversion(payload: {
  action: 'approve' | 'reject';
  cvId: number;
  comment?: string;
  reason?: string;
  qualityScore?: number;
  qualityTags?: string[];
  partnerVisible?: boolean;
}) {
  return merchantApiPost<{ message: string; conversion: MerchantConversion | null; merchant: MerchantProfile | null }>(
    'conversions.php',
    payload,
  );
}

export function applyMerchant() {
  return merchantApiPost<{ merchant: MerchantProfile | null; message: string }>('apply.php');
}

const ADMIN_API_BASE = lcPluginUrl('admin/api');

export async function adminApiGet<T>(endpoint: string, query?: Record<string, string>): Promise<T> {
  const url = new URL(`${ADMIN_API_BASE}/${endpoint}`, window.location.origin);
  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== '') {
        url.searchParams.set(key, value);
      }
    });
  }

  const response = await fetch(url.toString(), {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export async function adminApiPost<T>(endpoint: string, payload?: Record<string, unknown>): Promise<T> {
  const response = await fetch(`${ADMIN_API_BASE}/${endpoint}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: payload ? JSON.stringify(payload) : undefined,
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export type AdminPartner = {
  id: number;
  code: string;
  name: string;
  memberId: string;
  date: string;
  totalDb: number;
  approvedDb: number;
  canceledDb: number;
  rate: string;
  confirmedProfit: number;
  balance: number;
  status: string;
  statusCode: string;
  adminMemo?: string;
  tags?: string[];
  assignedTo?: string;
  abuseScore?: number;
  reviewScore?: number;
  tier?: string | null;
};

export type AdminMerchant = {
  id: number;
  code: string;
  name: string;
  memberId: string;
  date: string;
  campaigns: number;
  totalDb: number;
  approvedDb: number;
  canceledDb: number;
  rate: string;
  balance: number;
  spend: number;
  status: string;
  statusCode: string;
  adminMemo?: string;
  tags?: string[];
  assignedTo?: string;
  abuseScore?: number;
  reviewScore?: number;
};

export type AdminDashboardResponse = {
  summary: {
    todayReceived: number;
    todayApproved: number;
    todayRejected: number;
    todayRate: number;
    todayRevenue: number;
    pendingDb: number;
    pendingCharge: number;
    pendingPartners: number;
    pendingMerchants: number;
    pendingContracts?: number;
    pendingSettlements?: number;
    pendingInspections?: number;
  };
  chart7d: Array<{ date: string; received: number; approved: number; rejected: number; revenue: number }>;
  recent: AdminConversion[];
  partners: { total: number; active: number; pending: number };
  merchants: { total: number; active: number; pending: number; lowBalance: number };
  campaignTop?: Array<{ name: string; advertiser: string; total: number; approved: number; canceled: number; rate: string; revenue: number; status: string }>;
  partnerTop5?: Array<{ code: string; total: number; approved: number; rate: string; profit: number }>;
  advertiserTop5?: Array<{ name: string; total: number; approved: number; spend: number; balance: number }>;
  recentCancels?: AdminInspection[];
  apiErrors?: Array<{ time: string; name: string; code: string; msg: string; alId?: number }>;
};

export type AdminPendingCharge = {
  id: number;
  date: string;
  merchant: string;
  merchantCode: string;
  mtId: number;
  amount: number;
  memo: string;
  status: string;
};

export type AdminConversion = {
  id: string;
  cvId: number;
  date: string;
  campaign: string;
  partner: string;
  advertiser: string;
  customer: string;
  status: string;
  statusCode: string;
  price: number;
};

export function fetchAdminMe() {
  return adminApiGet<{ auth: import('./auth').LcAuth; dbReady: boolean }>('me.php');
}

export function fetchAdminDashboard() {
  return adminApiGet<AdminDashboardResponse>('dashboard.php');
}

export function fetchAdminPartners(filters?: { status?: string; q?: string }) {
  return adminApiGet<{
    items: AdminPartner[];
    summary: { total: number; active: number; pending: number };
    dbReady: boolean;
  }>('partners.php', {
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function updateAdminPartner(payload: { action: 'activate' | 'suspend' | 'pending'; ptId: number }) {
  return adminApiPost<{ message: string; partner: Pick<AdminPartner, 'id' | 'code' | 'name' | 'status' | 'statusCode'> | null }>(
    'partners.php',
    payload,
  );
}

export function fetchAdminMerchants(filters?: { status?: string; q?: string }) {
  return adminApiGet<{
    items: AdminMerchant[];
    summary: { total: number; active: number; pending: number; lowBalance: number };
    dbReady: boolean;
  }>('merchants.php', {
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function updateAdminMerchant(payload: { action: 'activate' | 'suspend' | 'pending'; mtId: number }) {
  return adminApiPost<{ message: string; merchant: Pick<AdminMerchant, 'id' | 'code' | 'name' | 'status' | 'statusCode'> | null }>(
    'merchants.php',
    payload,
  );
}

export type ImpersonateState = {
  active: boolean;
  type: string | null;
  id: number | null;
  label: string;
};

export function viewAsPartner(ptId: number) {
  return adminApiPost<{ message: string; impersonate: ImpersonateState; redirect: string }>('impersonate.php', {
    action: 'view_partner',
    ptId,
  });
}

export function viewAsMerchant(mtId: number) {
  return adminApiPost<{ message: string; impersonate: ImpersonateState; redirect: string }>('impersonate.php', {
    action: 'view_merchant',
    mtId,
  });
}

export function exitImpersonate() {
  return adminApiPost<{ message: string; impersonate: ImpersonateState; redirect: string }>('impersonate.php', {
    action: 'exit',
  });
}

export type ImpersonateHistoryItem = {
  id: number;
  type: string;
  targetId: number;
  label: string;
  startedAt: string;
  endedAt: string;
};

export function fetchImpersonateHistory() {
  return adminApiGet<{ history: ImpersonateHistoryItem[]; impersonate: ImpersonateState }>('impersonate.php');
}

export type ReviewQueueItem = {
  entityType: string;
  entityId: number;
  code: string;
  name: string;
  status: string;
  reviewScore: number;
  abuseScore?: number;
  tags?: string[];
};

export function fetchAdminReviewQueue() {
  return adminApiGet<{ items: ReviewQueueItem[]; dbReady: boolean }>('ops.php', { view: 'review_queue' });
}

export function saveAdminEntityMeta(payload: {
  entityType: 'partner' | 'merchant';
  entityId: number;
  adminMemo?: string;
  tags?: string[];
  assignedTo?: string;
}) {
  return adminApiPost<{ message: string }>('ops.php', { action: 'save_meta', ...payload });
}

export function bulkAdminPartners(ids: number[], subAction: 'activate' | 'suspend' | 'pending') {
  return adminApiPost<{ message: string; count: number }>('ops.php', { action: 'bulk_partner', ids, subAction });
}

export function bulkAdminMerchants(ids: number[], subAction: 'activate' | 'suspend' | 'pending') {
  return adminApiPost<{ message: string; count: number }>('ops.php', { action: 'bulk_merchant', ids, subAction });
}

export function bulkAdminRewardPay(ids: number[]) {
  return adminApiPost<{ message: string; count: number }>('ops.php', { action: 'bulk_reward_pay', ids });
}

export type EventRoiItem = {
  evId: number;
  code: string;
  title: string;
  status: string;
  participants: number;
  totalDb: number;
  approvedDb: number;
  revenue: number;
  paidRewards: number;
  pendingRewards: number;
  roi: number;
};

export function fetchAdminEventRoi() {
  return adminApiGet<{ items: EventRoiItem[]; summary: { totalReward: number; totalRevenue: number; netRoi: number } }>(
    'events.php',
    { view: 'roi' },
  );
}

export type ChannelReportItem = {
  id: number;
  cvId: number;
  cvCode: string;
  ptId: number;
  partner: string;
  partnerName: string;
  channel: string;
  reason: string;
  status: string;
  adminMemo: string;
  createdAt: string;
};

export function fetchAdminChannelReports(status?: string) {
  return adminApiGet<{ items: ChannelReportItem[]; dbReady: boolean }>('channel_reports.php', { status: status ?? '' });
}

export function updateAdminChannelReport(payload: { action: 'sanction' | 'dismiss' | 'review'; crId: number; memo?: string }) {
  return adminApiPost<{ message: string }>('channel_reports.php', payload);
}

export function reportMerchantChannel(payload: { cvId: number; channel?: string; reason: string }) {
  return merchantApiPost<{ message: string }>('conversions.php', { action: 'report_channel', ...payload });
}

export function fetchSettlementRisk(stId: number) {
  return adminApiGet<{ risk: { score: number; level: string; risks: Array<{ level: string; code: string; message: string }>; blocked: boolean } }>(
    'settlements.php',
    { view: 'risk', stId: String(stId) },
  );
}

export function fetchAdminPendingCharges() {
  return adminApiGet<{ items: AdminPendingCharge[]; pending: number; dbReady: boolean }>('wallet.php');
}

export function updateAdminCharge(payload: { action: 'approve' | 'reject'; wtId: number; memo?: string }) {
  return adminApiPost<{ message: string }>('wallet.php', payload);
}

export function fetchAdminConversions(filters?: { status?: string }) {
  return adminApiGet<{
    items: AdminConversion[];
    summary: { todayReceived: number; approved: number; rejected: number; pending: number };
    total: number;
    dbReady: boolean;
  }>('conversions.php', {
    status: filters?.status ?? '',
  });
}

export type AdminCampaignPromoGuideSummary = {
  exists: boolean;
  guideId?: number;
  status?: string;
  statusLabel?: string;
  hasPoints?: boolean;
  keywordCount?: number;
  forbiddenCount?: number;
  imageCount?: number;
  updatedAt?: string;
  publishedAt?: string;
  revisionReason?: string;
};

export type AdminCampaign = {
  id: number;
  code: string;
  name: string;
  advertiser: string;
  mtId: number;
  category: string;
  type: string;
  partnerPrice: number;
  advertiserPrice: number;
  margin: number;
  totalDb: number;
  approvedDb: number;
  canceledDb: number;
  spend: number;
  rate: string;
  cancelRate: string;
  status: string;
  statusCode: string;
  lowBalance: boolean;
  description: string;
  approvalRate: string;
  avgTime: string;
  allowedChannels: string;
  forbiddenChannels: string;
  landingUrl: string;
  trackingBaseUrl?: string;
  badge: string;
  recommended: boolean;
  promoGuide?: AdminCampaignPromoGuideSummary;
  thumbnailUrl?: string;
};

export type AdminPromoGuideDetail = {
  exists: boolean;
  guideId?: number;
  campaignId?: number;
  campaignName?: string;
  campaignStatus?: string;
  promotionPoints?: string[];
  recommendedKeywords?: string[];
  forbiddenWords?: string[];
  precautions?: string[];
  validDbRules?: string[];
  invalidDbRules?: string[];
  approvalType?: 'free' | 'first_review' | 'all_review';
  guideStatus?: string;
  guideStatusLabel?: string;
  revisionReason?: string;
  createdAt?: string;
  updatedAt?: string;
  publishedAt?: string;
  images?: Array<{
    id: number;
    assetId: number;
    originalFilename: string;
    mimeType: string;
    fileSize: number;
    imageTitle: string;
    sortOrder: number;
    downloadUrl: string;
  }>;
};

export type AdminPromoGuideLog = {
  id: number;
  guideId: number;
  campaignId: number;
  actorType: string;
  actorId: string;
  fromStatus: string;
  fromStatusLabel: string;
  toStatus: string;
  toStatusLabel: string;
  summary: string;
  revisionReason: string;
  createdAt: string;
};

export function fetchAdminPromoGuide(cpId: number) {
  return adminApiGet<AdminPromoGuideDetail>('campaign-guide.php', { cpId: String(cpId) });
}

export function fetchAdminPromoGuideLogs(guideId: number) {
  return adminApiGet<{ items: AdminPromoGuideLog[] }>('campaign-guide.php', {
    guideId: String(guideId),
    logs: '1',
  });
}

export function adminPromoGuideAction(payload: {
  action: 'publish' | 'hide' | 'review' | 'draft' | 'request_revision' | 'create';
  guideId?: number;
  cpId?: number;
  reason?: string;
}) {
  return adminApiPost<{ message: string; guide: AdminPromoGuideDetail | null; created?: boolean }>('campaign-guide.php', payload);
}

export type AdminCampaignSummary = {
  total: number;
  active: number;
  paused: number;
  lowBalance: number;
  avgPrice: number;
  avgApproval: number;
};

export function fetchAdminCampaigns(filters?: { status?: string; category?: string; q?: string }) {
  return adminApiGet<{ items: AdminCampaign[]; summary: AdminCampaignSummary; dbReady: boolean }>('campaigns.php', {
    status: filters?.status ?? '',
    category: filters?.category ?? '',
    q: filters?.q ?? '',
  });
}

export function saveAdminCampaign(payload: Record<string, unknown>) {
  return adminApiPost<{ message: string; campaign: AdminCampaign | null }>('campaigns.php', {
    action: payload.cpId ? 'update' : 'create',
    ...payload,
  });
}

export function updateAdminCampaignStatus(payload: { action: 'activate' | 'pause' | 'end' | 'draft'; cpId: number }) {
  return adminApiPost<{ message: string; campaign: AdminCampaign | null }>('campaigns.php', payload);
}

export type MerchantCampaign = {
  id: number;
  code: string;
  name: string;
  type: string;
  status: string;
  statusCode: string;
  cpa: number;
  budget: number;
  spend: number;
  dbCount: number;
  category: string;
};

export type MerchantCampaignSummary = {
  total: number;
  active: number;
  pending: number;
  ended: number;
};

export function fetchMerchantCampaigns(filters?: { status?: string }) {
  return merchantApiGet<{ items: MerchantCampaign[]; summary: MerchantCampaignSummary; dbReady: boolean }>(
    'campaigns.php',
    { status: filters?.status ?? '' },
  );
}

export type MerchantPromoGuideImage = {
  id: number;
  assetId: number;
  originalFilename: string;
  mimeType: string;
  fileSize: number;
  imageTitle: string;
  sortOrder: number;
  downloadUrl: string;
};

export type MerchantPromoGuideLimits = {
  promotion_points: number;
  recommended_keywords: number;
  forbidden_words: number;
  precautions: number;
  valid_db_rules: number;
  invalid_db_rules: number;
  images: number;
};

export type MerchantPromoGuideData = {
  exists: boolean;
  id?: number;
  campaignId?: number;
  campaignName?: string;
  guideId?: number;
  promotionPoints?: string[];
  recommendedKeywords?: string[];
  forbiddenWords?: string[];
  precautions?: string[];
  validDbRules?: string[];
  invalidDbRules?: string[];
  approvalType?: 'free' | 'first_review' | 'all_review';
  guideStatus?: string;
  guideStatusLabel?: string;
  revisionReason?: string;
  createdAt?: string;
  updatedAt?: string;
  publishedAt?: string;
  images?: MerchantPromoGuideImage[];
  limits?: MerchantPromoGuideLimits;
  maxImageBytes?: number;
  skipReview?: boolean;
  csrfToken?: string;
};

export type MerchantPromoGuideSaveResponse = {
  message: string;
  guide: MerchantPromoGuideData | null;
  csrfToken?: string;
};

export function fetchMerchantPromoGuide(cpId: number) {
  return merchantApiGet<MerchantPromoGuideData>('campaign-guide.php', { cpId: String(cpId) });
}

export function saveMerchantPromoGuideDraft(cpId: number, csrfToken: string, payload: Record<string, unknown>) {
  return merchantApiPost<MerchantPromoGuideSaveResponse>('campaign-guide.php', {
    action: 'save_draft',
    cpId,
    csrfToken,
    ...payload,
  });
}

export function updateMerchantPromoGuide(cpId: number, csrfToken: string, payload: Record<string, unknown>) {
  return merchantApiPost<MerchantPromoGuideSaveResponse>('campaign-guide.php', {
    action: 'update',
    cpId,
    csrfToken,
    ...payload,
  });
}

export function submitMerchantPromoGuideReview(cpId: number, csrfToken: string, payload?: Record<string, unknown>) {
  return merchantApiPost<MerchantPromoGuideSaveResponse>('campaign-guide.php', {
    action: 'submit_review',
    cpId,
    csrfToken,
    payload,
  });
}

export function publishMerchantPromoGuide(cpId: number, csrfToken: string, payload?: Record<string, unknown>) {
  return merchantApiPost<MerchantPromoGuideSaveResponse>('campaign-guide.php', {
    action: 'publish',
    cpId,
    csrfToken,
    payload,
  });
}

export function createMerchantPromoGuide(cpId: number, csrfToken: string) {
  return merchantApiPost<MerchantPromoGuideSaveResponse>('campaign-guide.php', {
    action: 'create',
    cpId,
    csrfToken,
  });
}

export function uploadMerchantPromoGuideImage(cpId: number, csrfToken: string, file: File, imageTitle = '') {
  const form = new FormData();
  form.append('action', 'upload');
  form.append('cpId', String(cpId));
  form.append('csrfToken', csrfToken);
  form.append('file', file);
  if (imageTitle) form.append('imageTitle', imageTitle);
  return merchantApiPostFormData<{ message: string; asset: MerchantPromoGuideImage; csrfToken?: string }>(
    'campaign-guide-asset.php',
    form,
  );
}

export function deleteMerchantPromoGuideImage(assetId: number, csrfToken: string) {
  return merchantApiPost<{ message: string; csrfToken?: string }>('campaign-guide-asset.php', {
    action: 'delete',
    assetId,
    csrfToken,
  });
}

export function sortMerchantPromoGuideImages(cpId: number, csrfToken: string, assetIds: number[]) {
  return merchantApiPost<{ message: string; guide: MerchantPromoGuideData | null; csrfToken?: string }>(
    'campaign-guide-asset.php',
    {
      action: 'sort',
      cpId,
      csrfToken,
      assetIds,
    },
  );
}

export function updateMerchantPromoGuideImageTitle(assetId: number, csrfToken: string, imageTitle: string) {
  return merchantApiPost<{ message: string; asset: MerchantPromoGuideImage; csrfToken?: string }>(
    'campaign-guide-asset.php',
    {
      action: 'update_title',
      assetId,
      csrfToken,
      imageTitle,
    },
  );
}

export type MerchantWalletTransaction = {
  id: number;
  date: string;
  type: string;
  typeCode: string;
  amount: number;
  balance: number;
  status: string;
  memo: string;
};

export type MerchantWalletResponse = {
  balance: number;
  balanceFormatted: string;
  summary: {
    balance: number;
    monthlyCharge: number;
    monthlySpend: number;
    availableBalance: number;
  };
  items: MerchantWalletTransaction[];
  dbReady: boolean;
};

export function fetchMerchantWallet() {
  return merchantApiGet<MerchantWalletResponse>('wallet.php');
}

export function requestMerchantCharge(payload: { amount: number; memo?: string }) {
  return merchantApiPost<{ message: string }>('wallet.php', payload);
}

const PUBLIC_API_BASE = lcPluginUrl('api');

async function publicApiGet<T>(endpoint: string, query?: Record<string, string>): Promise<T> {
  const url = new URL(`${PUBLIC_API_BASE}/${endpoint}`, window.location.origin);
  if (query) {
    Object.entries(query).forEach(([key, value]) => {
      if (value !== '') {
        url.searchParams.set(key, value);
      }
    });
  }

  const response = await fetch(url.toString(), {
    method: 'GET',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

async function publicApiPost<T>(endpoint: string, payload?: Record<string, unknown>): Promise<T> {
  const response = await fetch(`${PUBLIC_API_BASE}/${endpoint}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: payload ? JSON.stringify(payload) : undefined,
  });

  const body = await parseJson<ApiSuccessBody<T> | ApiErrorBody>(response);
  if (!body.ok) {
    const errBody = body as ApiErrorBody;
    throw new PartnerApiError(errBody.error, errBody.code, response.status);
  }

  return (body as ApiSuccessBody<T>).data;
}

export type PublicCampaign = PartnerCampaign;

export function fetchPublicCampaigns(filters?: {
  category?: string;
  q?: string;
  type?: string;
  id?: number | string;
  code?: string;
}) {
  return publicApiGet<{ items: PublicCampaign[]; categories: string[]; dbReady: boolean }>('campaigns.php', {
    category: filters?.category ?? '',
    q: filters?.q ?? '',
    type: filters?.type ?? '',
    id: filters?.id != null ? String(filters.id) : '',
    code: filters?.code ?? '',
  });
}

export async function fetchPublicCpaCampaign(codeOrId: string) {
  const key = codeOrId.trim();
  if (!key) {
    throw new Error('캠페인 코드가 없습니다.');
  }
  const byCode = await fetchPublicCampaigns({ type: 'cpa', code: key });
  if (byCode.items[0]) {
    return byCode.items[0];
  }
  if (/^\d+$/.test(key)) {
    const byId = await fetchPublicCampaigns({ type: 'cpa', id: key });
    if (byId.items[0]) {
      return byId.items[0];
    }
  }
  throw new Error('캠페인을 찾을 수 없습니다.');
}

export type PartnerSettlementSummary = {
  balance: number;
  pendingAmount: number;
  availableAmount: number;
  paidTotal: number;
  monthConfirmed: number;
  minAmount: number;
  bankName: string;
  bankAccount: string;
  bankHolder: string;
};

export type PartnerSettlementItem = {
  id: number;
  code: string;
  date: string;
  reqAmount: number;
  appAmount: number;
  status: string;
  statusCode: string;
  payDate: string;
  memo: string;
};

export function fetchPartnerSettlements() {
  return partnerApiGet<{ summary: PartnerSettlementSummary; items: PartnerSettlementItem[]; dbReady: boolean }>('settlements.php');
}

export function requestPartnerSettlement(payload: { amount: number; memo?: string; bankName?: string; bankAccount?: string; bankHolder?: string }) {
  return partnerApiPost<{ message: string; settlement: PartnerSettlementItem | null; summary: PartnerSettlementSummary }>('settlements.php', payload);
}

export type PartnerAnalyticsResponse = {
  summary: {
    totalClicks: number;
    totalDb: number;
    approvedDb: number;
    rejectedDb: number;
    avgConvRate: number;
    avgApprovalRate: number;
  };
  chart7d: Array<{ date: string; click: number; db: number; approval: number }>;
  channels: Array<{ channel: string; clicks: number; dbs: number; approved: number; percentage: number }>;
  campaigns: Array<{ campaign: string; clicks: number; received: number; approved: number; appRate: string; confRev: number }>;
  dbReady: boolean;
};

export function fetchPartnerAnalytics() {
  return partnerApiGet<PartnerAnalyticsResponse>('analytics.php');
}

export type PartnerReportResponse = {
  summary: {
    estRevenue: number;
    confRevenue: number;
    availableAmount: number;
    rejectedAmount: number;
  };
  breakdown: Array<{ label: string; value: number }>;
  monthly: Array<{ month: string; value: number; pct: number }>;
  campaigns: Array<{ campaign: string; clicks: number; received: number; approved: number; appRate: string; confRev: number }>;
  dbReady: boolean;
};

export function fetchPartnerReport() {
  return partnerApiGet<PartnerReportResponse>('report.php');
}

export type AdminSettlement = {
  id: number;
  code: string;
  date: string;
  partnerCode: string;
  partnerName: string;
  requestAmount: number;
  approvedAmount: number;
  bank: string;
  account: string;
  accountHolder: string;
  status: string;
  statusCode: string;
  memo: string;
};

export type AdminSettlementSummary = {
  pending: number;
  pendingAmount: number;
  todayApproved: number;
  monthPaid: number;
  hold: number;
  rejected: number;
};

export function fetchAdminSettlements(filters?: { status?: string; q?: string }) {
  return adminApiGet<{ items: AdminSettlement[]; summary: AdminSettlementSummary; dbReady: boolean }>('settlements.php', {
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function updateAdminSettlement(payload: { action: 'review' | 'approve' | 'pay' | 'hold' | 'reject'; stId: number; approvedAmount?: number; memo?: string }) {
  return adminApiPost<{ message: string; settlement: AdminSettlement | null; summary: AdminSettlementSummary }>('settlements.php', payload);
}

export type AdminInspection = {
  id: string;
  cvId: number;
  date: string;
  campaign: string;
  advertiser: string;
  partner: string;
  customer: string;
  phone: string;
  reason: string;
  comment: string;
  objection: boolean;
  objectionComment: string;
  status: string;
  statusCode: string;
  price: number;
};

export type AdminInspectionSummary = {
  pending: number;
  todayCancel: number;
  confirmed: number;
  restored: number;
  appeals: number;
  cancelRate: number;
};

export function fetchAdminInspections(filters?: { status?: string; q?: string }) {
  return adminApiGet<{ items: AdminInspection[]; summary: AdminInspectionSummary; dbReady: boolean }>('inspections.php', {
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function updateAdminInspection(payload: { action: 'confirm' | 'restore'; cvId: number; memo?: string }) {
  return adminApiPost<{ message: string; conversion: AdminInspection | null; summary: AdminInspectionSummary }>('inspections.php', payload);
}

export type InquiryItem = {
  id: string;
  iqId: number;
  date: string;
  center: string;
  centerCode: string;
  author: string;
  category: string;
  title: string;
  campaign: string;
  cvCode: string;
  status: string;
  statusCode: string;
  replyDate: string;
  content?: string;
  reply?: string;
  adminMemo?: string;
};

export type InquirySummary = {
  total: number;
  waiting: number;
  processing: number;
  closed: number;
  today: number;
};

export function fetchPartnerInquiries() {
  return partnerApiGet<{ summary: InquirySummary; items: InquiryItem[]; dbReady: boolean }>('inquiries.php');
}

export function createPartnerInquiry(payload: { category: string; subject: string; body: string; campaign?: string; cvCode?: string }) {
  return partnerApiPost<{ message: string; item: InquiryItem; summary: InquirySummary }>('inquiries.php', payload);
}

export function fetchMerchantInquiries() {
  return merchantApiGet<{ summary: InquirySummary; items: InquiryItem[]; dbReady: boolean }>('inquiries.php');
}

export function createMerchantInquiry(payload: { category: string; subject: string; body: string; campaign?: string; cvCode?: string }) {
  return merchantApiPost<{ message: string; item: InquiryItem; summary: InquirySummary }>('inquiries.php', payload);
}

export function fetchAdminInquiries(filters?: { center?: string; status?: string; q?: string }) {
  return adminApiGet<{ summary: InquirySummary; items: InquiryItem[]; dbReady: boolean }>('inquiries.php', {
    center: filters?.center ?? '',
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function fetchAdminInquiryDetail(iqId: number) {
  return adminApiGet<{ item: InquiryItem }>('inquiries.php', { id: String(iqId) });
}

export function updateAdminInquiry(payload: { iqId: number; action: 'reply' | 'status' | 'close'; reply?: string; status?: string; adminMemo?: string }) {
  return adminApiPost<{ message: string; item: InquiryItem; summary: InquirySummary }>('inquiries.php', payload);
}

export type AdminSettingsData = {
  general: Record<string, string>;
  cpa: Record<string, string | number | boolean>;
  billing: Record<string, string | number>;
  partner: Record<string, string | number | boolean>;
  cancel: Record<string, boolean>;
  api: Record<string, string | number | boolean>;
};

export function fetchAdminSettings() {
  return adminApiGet<{ settings: AdminSettingsData; raw: Record<string, string>; dbReady: boolean }>('settings.php');
}

export function saveAdminSettings(settings: AdminSettingsData | Record<string, unknown>) {
  return adminApiPost<{ message: string; settings: AdminSettingsData; raw: Record<string, string> }>('settings.php', { settings });
}

export function resetAdminSettings() {
  return adminApiPost<{ message: string; settings: AdminSettingsData; raw: Record<string, string> }>('settings.php', { action: 'reset' });
}

export type ApiLogItem = {
  id: string;
  alId: number;
  time: string;
  client: string;
  direction: string;
  endpoint: string;
  extId: string;
  intId: string;
  statusCode: number;
  status: string;
  statusCodeRaw: string;
  error: string;
  requestBody?: string;
  responseBody?: string;
};

export type ApiClientItem = {
  id: number;
  code: string;
  name: string;
  type: string;
  apiKey: string;
  allowedIps: string;
  status: string;
  statusCode: string;
  lastCallAt: string;
};

export type ApiIntegrationSummary = {
  todayTotal: number;
  todaySuccess: number;
  todayFailed: number;
  todayDuplicate: number;
  dbshareTotal: number;
  lastReceiveTime: string;
};

export function fetchAdminIntegrations(filters?: { status?: string; client?: string; q?: string; errors?: boolean }) {
  return adminApiGet<{ summary: ApiIntegrationSummary; clients: ApiClientItem[]; dbshare: ApiClientItem | null; items: ApiLogItem[]; dbReady: boolean }>(
    'integrations.php',
    {
      status: filters?.status ?? '',
      client: filters?.client ?? '',
      q: filters?.q ?? '',
      errors: filters?.errors ? '1' : '',
    },
  );
}

export function fetchAdminIntegrationDetail(alId: number) {
  return adminApiGet<{ item: ApiLogItem }>('integrations.php', { id: String(alId) });
}

export function updateAdminIntegration(payload: { action: string; acId?: number; name?: string; type?: string; allowedIps?: string }) {
  return adminApiPost<{ message: string; client?: ApiClientItem }>('integrations.php', payload);
}

export type PartnerCancelSummary = {
  total: number;
  week: number;
  monthRate: number;
  topReason: string;
  reasons: Array<{ reason: string; count: number; percentage: number }>;
};

export function fetchPartnerCanceledDbs(filters?: { q?: string }) {
  return partnerApiGet<{ items: PartnerConversion[]; summary: PartnerDashboardResponse['summary']; cancelSummary: PartnerCancelSummary; total: number }>(
    'conversions.php',
    { rejected: '1', q: filters?.q ?? '' },
  );
}

export function submitPartnerAppeal(payload: { cvId: number; appeal: string }) {
  return partnerApiPost<{ message: string; conversion: PartnerConversion | null }>('conversions.php', { action: 'appeal', ...payload });
}

export type MerchantReportResponse = {
  summary: {
    total: number;
    approved: number;
    rejected: number;
    avgRate: number;
    totalSpend: number;
    avgPrice: number;
  };
  dbChart7d: Array<{ date: string; received: number; approved: number; rejected: number }>;
  spendChart7d: Array<{ date: string; holdSpend: number; confSpend: number; refund: number }>;
  campaigns: Array<{
    id: number;
    name: string;
    total: number;
    approved: number;
    canceled: number;
    approvalRate: number;
    cancelRate: number;
    spend: number;
    avgPrice: number;
    status: string;
  }>;
  partners: Array<{
    code: string;
    name: string;
    total: number;
    approved: number;
    canceled: number;
    approvalRate: number;
    spend: number;
    note: string;
  }>;
  dbReady: boolean;
};

export function fetchMerchantReports() {
  return merchantApiGet<MerchantReportResponse>('reports.php');
}

export type AdminWalletSummary = {
  totalBalance: number;
  totalPending: number;
  todayCharge: number;
  todaySpend: number;
  todayRefund: number;
  lowBalance: number;
};

export type AdminMerchantBalance = {
  id: number;
  name: string;
  code: string;
  balance: number;
  pending: number;
  available: number;
  totalCharged: number;
  totalUsed: number;
  totalRefund: number;
  lastCharged: string;
  status: string;
};

export type AdminWalletTransaction = {
  id: number;
  date: string;
  merchant: string;
  mtId: number;
  type: string;
  typeCode: string;
  dbCode: string;
  campaign: string;
  amount: number;
  balance: number;
  processor: string;
  memo: string;
  status: string;
};

export function fetchAdminWalletSummary() {
  return adminApiGet<{ summary: AdminWalletSummary; items: AdminPendingCharge[]; pending: number; dbReady: boolean }>('wallet.php');
}

export function fetchAdminWalletBalances(q?: string) {
  return adminApiGet<{ items: AdminMerchantBalance[]; summary: AdminWalletSummary; dbReady: boolean }>('wallet.php', {
    view: 'balances',
    q: q ?? '',
  });
}

export function fetchAdminWalletHistory(filters?: { q?: string; type?: string }) {
  return adminApiGet<{ items: AdminWalletTransaction[]; summary: AdminWalletSummary; dbReady: boolean }>('wallet.php', {
    view: 'history',
    q: filters?.q ?? '',
    type: filters?.type ?? '',
  });
}

export function adjustAdminWallet(payload: { mtId: number; type: string; amount: number; memo?: string }) {
  return adminApiPost<{ message: string; summary: AdminWalletSummary }>('wallet.php', { action: 'adjust', ...payload });
}

export type PublicEventSummaryItem = { label: string; value: string; suffix: string; icon: string };
export type PublicEventItem = {
  id: string;
  badges: string[];
  title: string;
  desc: string;
  period: string;
  product: string;
  benefit: string;
  ribbon: string;
};

export type PublicEventPromoCpa = {
  event: string;
  title: string;
  category: string;
  approvalRate: string;
  oldPrice: number;
  price: number;
  bonus: string;
  highlight: boolean;
};

export type PublicRankingItem = {
  rank: number;
  partner: string;
  dbs: number;
  reward: string;
  earnings?: string;
  tone?: string;
};

export type PublicRankingSummary = {
  topDbs: number;
  myRank: number;
  remainingToTop10: number;
  myBonus: string;
  top10BonusHint: string;
};

export type PublicRankingMy = {
  rank: number;
  dbs: number;
  earnings: number;
  remainingToTop10: number;
  bonus: string;
};

export type PublicRankingTier = {
  label: string;
  reward: string;
  tone?: string;
};

export type PublicRanking = {
  summary: PublicRankingSummary;
  top: PublicRankingItem[];
  list: PublicRankingItem[];
  my: PublicRankingMy | null;
  tiers: PublicRankingTier[];
};

export type PublicEventsResponse = {
  summary: PublicEventSummaryItem[];
  items: PublicEventItem[];
  recommendations: Array<Record<string, unknown>>;
  promoCpa: PublicEventPromoCpa[];
  ranking: PublicRanking;
  rankingTop: PublicRankingItem[];
  rankingList: PublicRankingItem[];
  dbReady: boolean;
};

export type PublicEventProgress = {
  joined: boolean;
  current: number;
  target: number;
  pct: number;
  reward: string;
  alert: string;
};

export type PublicEventRule = { text: string; critical: boolean };
export type PublicEventPromoCopy = { title: string; text: string };
export type PublicEventPromoTab = { id: string; label: string; copies: PublicEventPromoCopy[] };

export type PublicEventDetail = PublicEventItem & {
  type: string;
  status: string;
  statusCode: string;
  condition: string;
  campaigns: string;
  products: string[];
  rules: PublicEventRule[];
  promoTabs: PublicEventPromoTab[];
  progress: PublicEventProgress;
  dbReady: boolean;
};

export function fetchPublicEvents(q?: string) {
  return publicApiGet<PublicEventsResponse>('events.php', { q: q ?? '' });
}

export function fetchPublicEventDetail(code: string) {
  return publicApiGet<PublicEventDetail>('events.php', { code });
}

export function joinPartnerEvent(payload: { evCode?: string; evId?: number }) {
  return partnerApiPost<{ message: string; joined: boolean }>('events.php', { action: 'join', ...payload });
}

export type AdminEventReward = {
  id: number;
  evId: number;
  eventCode: string;
  eventTitle: string;
  ptId: number;
  partner: string;
  name: string;
  amount: number;
  status: string;
  condition: string;
  memo: string;
  createdAt: string;
  paidAt: string;
};

export type AdminEventParticipant = {
  id: number;
  evId: number;
  ptId: number;
  partner: string;
  name: string;
  status: string;
  approved: number;
  joinedAt: string;
};

export function fetchAdminEventRewards(filters?: { status?: string; evId?: number }) {
  return adminApiGet<{ items: AdminEventReward[]; dbReady: boolean }>('events.php', {
    view: 'rewards',
    status: filters?.status ?? '',
    evId: String(filters?.evId ?? ''),
  });
}

export function fetchAdminEventParticipants(evId: number) {
  return adminApiGet<{ items: AdminEventParticipant[]; dbReady: boolean }>('events.php', {
    view: 'participants',
    evId: String(evId),
  });
}

export function createAdminEventReward(payload: { evId?: number; ptId: number; amount: number; condition?: string }) {
  return adminApiPost<{ message: string; id: number }>('events.php', { action: 'create_reward', ...payload });
}

export function updateAdminEventReward(payload: { action: 'pay_reward' | 'reject_reward'; erId: number; memo?: string }) {
  return adminApiPost<{ message: string }>('events.php', payload);
}

export function autoAdminEventRankingRewards(period?: string) {
  return adminApiPost<{ message: string; created: number }>('events.php', { action: 'auto_ranking_rewards', period: period ?? '' });
}

export type LcNotificationCenter = 'admin' | 'partner' | 'merchant';

export type LcNotification = {
  id: number;
  center: string;
  userId: number;
  type: string;
  title: string;
  body: string;
  link: string;
  refType: string;
  refId: number;
  read: boolean;
  readAt: string;
  createdAt: string;
};

export function fetchPartnerNotifications() {
  return partnerApiGet<{ items: LcNotification[]; unread: number; total: number }>('notifications.php');
}

export function markPartnerNotificationsRead(id?: number) {
  return partnerApiPost<{ message: string }>('notifications.php', { action: 'read', id: id ?? 0 });
}

export function fetchMerchantNotifications() {
  return merchantApiGet<{ items: LcNotification[]; unread: number; total: number }>('notifications.php');
}

export function markMerchantNotificationsRead(id?: number) {
  return merchantApiPost<{ message: string }>('notifications.php', { action: 'read', id: id ?? 0 });
}

export function fetchAdminNotifications() {
  return adminApiGet<{ items: LcNotification[]; unread: number; total: number }>('notifications.php');
}

export function markAdminNotificationsRead(id?: number) {
  return adminApiPost<{ message: string }>('notifications.php', { action: 'read', id: id ?? 0 });
}

export type AdminLogItem = {
  id: number;
  memberId: string;
  action: string;
  targetType: string;
  targetId: number;
  summary: string;
  payload: Record<string, unknown>;
  ip: string;
  createdAt: string;
};

export function fetchAdminLogs(filters?: { q?: string; action?: string; limit?: number }) {
  return adminApiGet<{ items: AdminLogItem[]; total: number; dbReady: boolean }>('logs.php', {
    q: filters?.q ?? '',
    action: filters?.action ?? '',
    limit: String(filters?.limit ?? 50),
  });
}

export type AdminEventSummary = {
  total: number;
  active: number;
  closing: number;
  scheduled: number;
  ended: number;
};

export type AdminEvent = {
  id: number;
  code: string;
  title: string;
  type: string;
  desc: string;
  period: string;
  product: string;
  benefit: string;
  badges: string[];
  ribbon: string;
  status: string;
  statusCode: string;
  target: string;
  campaigns: string;
  campaignIds: string;
  partners: number;
  received: number;
  approved: number;
  rewardPending: string;
  featured: boolean;
  sort: number;
};

export function fetchAdminEvents(filters?: { q?: string; status?: string }) {
  return adminApiGet<{ items: AdminEvent[]; summary: AdminEventSummary; dbReady: boolean }>('events.php', {
    q: filters?.q ?? '',
    status: filters?.status ?? '',
  });
}

export function saveAdminEvent(payload: Record<string, unknown>) {
  return adminApiPost<{ message: string; event: AdminEvent; summary: AdminEventSummary }>('events.php', payload);
}

export function updateAdminEventStatus(payload: { action: string; evId: number }) {
  return adminApiPost<{ message: string; event: AdminEvent; summary: AdminEventSummary }>('events.php', payload);
}

export type NoticePermissions = {
  canWrite: boolean;
  canEdit: boolean;
  canDelete: boolean;
};

export type NoticeListItem = {
  id: number;
  subject: string;
  author: string;
  memberId: string;
  date: string;
  datetime: string;
  hit: number;
  isNotice: boolean;
  canEdit: boolean;
  canDelete: boolean;
};

export type NoticeDetail = NoticeListItem & {
  contentHtml: string;
  contentPlain: string;
  isHtml: boolean;
  prevId: number;
  nextId: number;
};

export type NoticeListResponse = {
  items: NoticeListItem[];
  total: number;
  page: number;
  totalPages: number;
  perPage: number;
  boardReady: boolean;
  permissions: NoticePermissions;
  boardTitle: string;
};

export function fetchNoticeList(filters?: { page?: number; q?: string; perPage?: number }) {
  return publicApiGet<NoticeListResponse>('notice.php', {
    page: String(filters?.page ?? 1),
    q: filters?.q ?? '',
    perPage: String(filters?.perPage ?? 15),
  });
}

export function fetchNoticeDetail(id: number) {
  return publicApiGet<{ item: NoticeDetail; permissions: NoticePermissions; boardReady: boolean }>('notice.php', {
    id: String(id),
  });
}

export function saveNotice(payload: { subject: string; content: string; isNotice?: boolean; id?: number; action?: string }) {
  return publicApiPost<{ message: string; item: NoticeDetail }>('notice.php', payload);
}

export function deleteNotice(id: number) {
  return publicApiPost<{ message: string }>('notice.php', { action: 'delete', id });
}

export type AiStatus = {
  available: boolean;
  model: string;
  limits: { chat: number; promo: number; summary: number };
  message: string;
};

export type AiPromoCopy = { id: string; label: string; text: string };

export function fetchAiStatus() {
  return publicApiGet<AiStatus>('ai.php');
}

export function sendAiChat(payload: { message: string; history?: Array<{ role: string; text: string }>; context?: Record<string, string> }) {
  return publicApiPost<{ reply: string; fallback: boolean }>('ai.php', { action: 'chat', ...payload });
}

export function generatePartnerPromo(payload: {
  campaignId?: number;
  title?: string;
  category?: string;
  price?: string;
  approvalRate?: string;
  allowedChannels?: string;
  forbiddenChannels?: string;
  channel?: string;
  eventTitle?: string;
}) {
  return partnerApiPost<{ copies: AiPromoCopy[]; fallback: boolean; message?: string }>('ai.php', { action: 'promo', ...payload });
}

export function fetchAdminAiSummary() {
  return adminApiPost<{ summary: string; fallback: boolean }>('ai.php', { action: 'summary' });
}

export function fetchMerchantAiSummary() {
  return merchantApiPost<{ summary: string; fallback: boolean }>('ai.php', { action: 'summary' });
}

export function generateMerchantCampaignPromo(payload: {
  cpId: number;
  title?: string;
  targetAudience?: string;
  category?: string;
}) {
  return merchantApiPost<{ copies: AiPromoCopy[]; fallback: boolean; message?: string }>('ai.php', {
    action: 'promo',
    ...payload,
  });
}

export type AiImageGeneratePayload = {
  mood?: string;
  includeText?: boolean;
  overlayText?: string;
  extra?: string;
};

export function generateMerchantPromoImageAi(
  payload: {
    cpId: number;
    width?: number;
    height?: number;
    imageTitle?: string;
  } & AiImageGeneratePayload,
) {
  return merchantApiPost<{
    message: string;
    asset: {
      id: number;
      downloadUrl: string;
      originalFilename: string;
      imageTitle?: string;
    } | null;
    prompt?: string;
  }>('ai.php', { action: 'generate_promo_image', ...payload });
}

/* ─────────────────────────── 콜디비 (Call DB) ─────────────────────────── */

export type CallNumber = {
  cnId: number;
  number: string;
  provider: string;
  status: string;
  memo: string;
  createdAt: string;
};

/** 콜디비 번호 표시: 050369821000 → 0503-6982-1000, 앞자리 0 복구 */
export function formatCallPhone(num: string): string {
  let d = String(num || '').replace(/\D/g, '');
  if (/^50[0-9]\d{8}$/.test(d)) d = `0${d}`;
  if (d.length === 12 && d.startsWith('050')) {
    return `${d.slice(0, 4)}-${d.slice(4, 8)}-${d.slice(8)}`;
  }
  if (d.length === 11 && (d.startsWith('010') || d.startsWith('070'))) {
    return `${d.slice(0, 3)}-${d.slice(3, 7)}-${d.slice(7)}`;
  }
  return d || String(num || '');
}

export type CallRequest = {
  carId: number;
  ptId: number;
  partner: string;
  cpId: number;
  campaign: string;
  status: string;
  virtualNumber: string;
  requestMemo: string;
  adminMemo: string;
  createdAt: string;
  processedAt: string;
};

export type CallLog = {
  clogId: number;
  virtualNumber: string;
  caller: string;
  campaign: string;
  partner: string;
  startedAt: string;
  duration: number;
  result: string;
  cvId: number;
  hasRecording: boolean;
  recordingUrl?: string;
};

export type CallSettings = {
  cpId: number;
  enabled: boolean;
  alias: string;
  forward1: string;
  forward2: string;
  adminEnabled: boolean;
  recordingMode: string;
};

// 관리자
export function fetchAdminCallNumbers(filters?: { status?: string; q?: string }) {
  return adminApiGet<{ items: CallNumber[]; dbReady: boolean }>('call.php', {
    view: 'numbers',
    status: filters?.status ?? '',
    q: filters?.q ?? '',
  });
}

export function fetchAdminCallRequests(status?: string) {
  return adminApiGet<{ items: CallRequest[]; dbReady: boolean }>('call.php', { view: 'requests', status: status ?? '' });
}

export function fetchAdminCallLogs(filters?: { result?: string; unmatched?: boolean }) {
  return adminApiGet<{ items: CallLog[]; dbReady: boolean }>('call.php', {
    view: 'logs',
    result: filters?.result ?? '',
    unmatched: filters?.unmatched ? '1' : '',
  });
}

export function createAdminCallNumber(payload: { number: string; provider?: string; providerNumberId?: string; memo?: string }) {
  return adminApiPost<{ message: string; cnId?: number }>('call.php', { action: 'create_number', ...payload });
}

export function createAdminCallNumbersBulk(payload: { numbers: string; memo?: string }) {
  return adminApiPost<{ message: string; created?: number; skipped?: number; errors?: string[] }>('call.php', {
    action: 'create_numbers_bulk',
    ...payload,
  });
}

export function provisionAdminCallNumber(payload?: { areaCode?: string; memo?: string }) {
  return adminApiPost<{ message: string; cnId?: number }>('call.php', { action: 'provision_number', ...(payload ?? {}) });
}

export function updateAdminCallNumber(payload: { cnId: number; status?: string; memo?: string }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'update_number', ...payload });
}

export function deleteAdminCallNumber(payload: { cnId: number }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'delete_number', ...payload });
}

export function assignAdminCallRequest(payload: { carId: number; cnId: number; adminMemo?: string }) {
  return adminApiPost<{ message: string; number?: string }>('call.php', { action: 'assign_request', ...payload });
}

export function rejectAdminCallRequest(payload: { carId: number; adminMemo?: string }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'reject_request', ...payload });
}

export function revokeAdminCallRequest(payload: { carId: number; adminMemo?: string }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'revoke_request', ...payload });
}

export function fetchAdminCallSettings(cpId: number) {
  return adminApiGet<{ settings: Record<string, unknown> }>('call.php', { view: 'settings', cpId: String(cpId) });
}

export function saveAdminCallSettings(payload: Record<string, unknown> & { cpId: number }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'save_settings', ...payload });
}

export function fetchAdminCallRecording(clogId: number) {
  return adminApiGet<{ url: string }>('call.php', { view: 'recording', clogId: String(clogId) });
}

export function finalizeAdminConversion(payload: { cvId: number; finalAction: 'approve' | 'reject' | 'lock' | 'unlock'; memo?: string }) {
  return adminApiPost<{ message: string }>('call.php', { action: 'final_status', ...payload });
}

// 광고주
export type MerchantCallCampaign = {
  cpId: number;
  campaign: string;
  code: string;
  enabled: boolean;
  adminEnabled: boolean;
  alias: string;
  forward1: string;
  forward2: string;
  recordingMode: string;
};

export function fetchMerchantCallCampaigns() {
  return merchantApiGet<{ items: MerchantCallCampaign[]; dbReady: boolean }>('call.php', { view: 'campaigns' });
}

export function saveMerchantCallSettings(payload: { cpId: number; enabled: boolean; alias?: string; forward1?: string; forward2?: string }) {
  return merchantApiPost<{ message: string }>('call.php', { action: 'save_settings', ...payload });
}

export function toggleMerchantCall(payload: { cpId: number; enabled: boolean }) {
  return merchantApiPost<{ message: string }>('call.php', { action: 'toggle', ...payload });
}

// 파트너
export function fetchPartnerCallRequests() {
  return partnerApiGet<{ items: CallRequest[]; dbReady: boolean }>('call.php', { view: 'requests' });
}

export type PartnerAvailableCallNumber = {
  cnId: number;
  number: string;
  memo: string;
};

export function fetchPartnerAvailableCallNumbers() {
  return partnerApiGet<{ items: PartnerAvailableCallNumber[]; dbReady: boolean }>('call.php', {
    view: 'available_numbers',
  });
}

export function fetchPartnerCallLogs() {
  return partnerApiGet<{ items: CallLog[]; dbReady: boolean }>('call.php', { view: 'logs' });
}

export function requestPartnerCallNumber(payload: { cpId: number; cnId?: number; memo?: string }) {
  return partnerApiPost<{ message: string; carId?: number; number?: string }>('call.php', {
    action: payload.cnId ? 'claim' : 'request',
    ...payload,
  });
}

export function claimPartnerCallNumber(payload: { cpId: number; cnId: number; memo?: string }) {
  return partnerApiPost<{ message: string; carId?: number; number?: string }>('call.php', {
    action: 'claim',
    ...payload,
  });
}
export type MerchantContractParty = {
  companyName: string;
  representativeName: string;
  businessNumber: string;
  companyAddress: string;
  companyPhone: string;
};

export type MerchantContractForm = {
  companyName: string;
  representativeName: string;
  businessNumber: string;
  companyAddress: string;
  companyPhone: string;
  contactName: string;
  contactPhone: string;
  contactEmail: string;
  signerName: string;
  signerPosition: string;
  signerPhone: string;
  signerEmail: string;
  negotiatedTerms?: string;
  specialClauses?: string;
  step: number;
  agreements: {
    readAll: boolean;
    hasAuthority: boolean;
    electronic: boolean;
    noModify: boolean;
  };
  agreementCheckedAt?: string;
};

export type MerchantContractSummary = {
  id: number;
  advertiserId: number;
  contractCode: string;
  contractVersion: string;
  status: string;
  statusLabel: string;
  companyName: string;
  signerName: string;
  signedAt: string;
  createdAt: string;
  updatedAt: string;
  pdfDownloadUrl: string;
};

export type MerchantContractState = {
  contractVersion: string;
  status: string;
  statusLabel: string;
  isSigned: boolean;
  isApprovalPending: boolean;
  isRejected: boolean;
  rejectionReason?: string;
  canWrite: boolean;
  requiresContract: boolean;
  partyB: MerchantContractParty;
  form: MerchantContractForm;
  contractHtml: string;
  contract: MerchantContractSummary | null;
  documentPreviewUrl: string;
  documentPdfUrl: string;
  signedPdfDownloadUrl: string;
  csrfToken: string;
  hasSignature: boolean;
};

export function fetchMerchantContract() {
  return merchantApiGet<MerchantContractState>('contract.php');
}

export function saveMerchantContractDraft(payload: Record<string, unknown>) {
  return merchantApiPost<{ message: string; contract: MerchantContractSummary | null; state: MerchantContractState }>(
    'contract.php',
    { action: 'draft', ...payload },
  );
}

export function uploadMerchantContractSignature(payload: { csrfToken: string; signatureDataUrl: string }) {
  return merchantApiPost<{ message: string; hasSignature: boolean; state: MerchantContractState }>('contract.php', {
    action: 'signature',
    ...payload,
  });
}

export function validateMerchantContract(payload: Record<string, unknown>) {
  return merchantApiPost<{ message: string; validated: boolean; state: MerchantContractState }>('contract.php', payload);
}

export function signMerchantContract(payload: Record<string, unknown>) {
  return merchantApiPost<{
    message: string;
    alreadySigned: boolean;
    contract: MerchantContractSummary | null;
    state: MerchantContractState;
  }>('contract.php', payload);
}

export type MerchantContractRead = {
  id: number;
  advertiserId: number;
  contractVersion: string;
  status: string;
  statusLabel: string;
  contractCode: string;
  companyName: string;
  representativeName: string;
  businessNumber: string;
  companyAddress: string;
  companyPhone: string;
  signerName: string;
  signerPosition: string;
  signerPhone: string;
  signerEmail: string;
  negotiatedTerms?: string;
  specialClauses?: string;
  signedAt: string;
  signedIp: string;
  userAgent: string;
  createdAt: string;
  pdfHash: string;
  pdfHashMasked: string;
  agreements: Record<string, boolean>;
  agreementCheckedAt: string;
  partyB: MerchantContractParty;
  contractHtml: string;
  signatureUrl: string;
  documentPreviewUrl: string;
  documentPdfUrl: string;
  isReadOnly: boolean;
  isFullySigned: boolean;
  canAddAddendum?: boolean;
  campaignId?: number;
};

export type MerchantContractAddendum = {
  id: number;
  contractId: number;
  advertiserId: number;
  seq: number;
  title: string;
  body: string;
  status: string;
  createdByType: string;
  createdBy: string;
  voidedAt: string;
  voidReason: string;
  createdAt: string;
  updatedAt: string;
};

export type MerchantContractCampaignItem = {
  id: number;
  code: string;
  name: string;
  type: string;
  status: string;
  statusCode: string;
  cpa: number;
  contractViewable?: boolean;
  contractStatusLabel?: string;
};

export type MerchantContractHistoryItem = {
  id: number;
  contractVersion: string;
  contractCode: string;
  status: string;
  statusLabel: string;
  signedAt: string;
  createdAt: string;
  isFullySigned: boolean;
  isCurrentVersion: boolean;
};

export type MerchantContractReadResponse = {
  contract: MerchantContractRead;
  history: MerchantContractHistoryItem[];
  campaigns?: MerchantContractCampaignItem[];
  campaign?: MerchantContractCampaignItem | null;
  addenda?: MerchantContractAddendum[];
  csrfToken?: string;
};

export function fetchMerchantContractRead(options?: { version?: string; cpId?: number } | string) {
  const query: Record<string, string> = { mode: 'read' };
  if (typeof options === 'string') {
    if (options) query.version = options;
  } else if (options) {
    if (options.version) query.version = options.version;
    if (options.cpId && options.cpId > 0) query.cpId = String(options.cpId);
  }
  return merchantApiGet<MerchantContractReadResponse>('contract.php', query);
}

export type AdminContractListItem = {
  id: number;
  advertiserId: number;
  advertiserCode?: string;
  companyName: string;
  representativeName: string;
  businessNumber: string;
  signerName: string;
  contractVersion: string;
  status: string;
  statusLabel: string;
  contractCode: string;
  signedAt: string;
  createdAt: string;
  isFullySigned: boolean;
};

export type AdminContractSummary = {
  total: number;
  signed: number;
  reviewPending: number;
  rejected: number;
  pending: number;
  inProgress: number;
  cancelled: number;
  expired: number;
  renewal: number;
};

export type AdminContractDetail = {
  contract: MerchantContractRead;
  listItem: AdminContractListItem;
  merchant: { id: number; code: string; company: string; status: string } | null;
  companyCompare: Record<string, { contract: string; current: string; changed: boolean }>;
  companySnapshot: Record<string, unknown> | null;
  history: MerchantContractHistoryItem[];
  addenda?: MerchantContractAddendum[];
  statusLogs: Array<{
    id: number;
    adminId: string;
    oldStatus: string;
    newStatus: string;
    oldLabel: string;
    newLabel: string;
    reason: string;
    ip: string;
    userAgent: string;
    createdAt: string;
  }>;
  signLogs: Array<{
    id: number;
    result: string;
    message: string;
    signedAt: string;
    ip: string;
    userAgent: string;
    pdfHash: string;
    createdAt: string;
  }>;
  documentPreviewUrl: string;
  documentPdfUrl: string;
  signatureUrl: string;
};

export function fetchAdminContracts(filters?: {
  q?: string;
  status?: string;
  version?: string;
  mtId?: number;
  signedFrom?: string;
  signedTo?: string;
  page?: number;
  limit?: number;
}) {
  return adminApiGet<{
    items: AdminContractListItem[];
    total: number;
    page: number;
    limit: number;
    summary: AdminContractSummary;
    dbReady: boolean;
    currentVersion: string;
    customEnsureAdv0008?: {
      ok?: boolean;
      message?: string;
      skipped?: boolean;
      applied?: boolean;
      mcId?: number;
      contractCode?: string;
    };
  }>('contracts.php', {
    q: filters?.q ?? '',
    status: filters?.status ?? '',
    version: filters?.version ?? '',
    mtId: filters?.mtId ? String(filters.mtId) : '',
    signedFrom: filters?.signedFrom ?? '',
    signedTo: filters?.signedTo ?? '',
    page: filters?.page ? String(filters.page) : '1',
    limit: filters?.limit ? String(filters.limit) : '30',
  });
}

export function fetchAdminContractDetail(mcId: number) {
  return adminApiGet<AdminContractDetail>('contracts.php', { mcId: String(mcId) });
}

export function updateAdminContractStatus(payload: {
  mcId: number;
  action: 'approve' | 'reject' | 'cancel' | 'expire' | 'requireRenewal';
  reason: string;
}) {
  return adminApiPost<{ message: string; detail: AdminContractDetail }>('contracts.php', payload);
}

export function addAdminContractAddendum(payload: {
  mcId: number;
  title?: string;
  body: string;
}) {
  return adminApiPost<{ message: string; addendum: MerchantContractAddendum | null; detail: AdminContractDetail }>(
    'contracts.php',
    {
      action: 'add_addendum',
      mcId: payload.mcId,
      title: payload.title ?? '특약사항',
      body: payload.body,
    },
  );
}

export function voidAdminContractAddendum(payload: { addendumId: number; reason?: string }) {
  return adminApiPost<{ message: string; addendum: MerchantContractAddendum | null; detail: AdminContractDetail | null }>(
    'contracts.php',
    {
      action: 'void_addendum',
      addendumId: payload.addendumId,
      reason: payload.reason ?? '',
    },
  );
}

export function addMerchantContractAddendum(payload: {
  csrfToken: string;
  body: string;
  title?: string;
  version?: string;
}) {
  return merchantApiPost<{
    message: string;
    addendum: MerchantContractAddendum | null;
    data: MerchantContractReadResponse | null;
  }>('contract.php', {
    action: 'add_addendum',
    csrfToken: payload.csrfToken,
    body: payload.body,
    title: payload.title ?? '특약사항',
    version: payload.version ?? '',
  });
}

export function seedAdminDemoContract(mtId?: number) {
  return adminApiPost<{ message: string; skipped?: boolean; contractCode?: string; mtId?: number }>('contracts.php', {
    action: 'seed_demo',
    mtId: mtId ?? 0,
  });
}

export function applyAdminCustomContractDocument(payload?: {
  mtId?: number;
  mtCode?: string;
  documentKey?: string;
  force?: boolean;
}) {
  return adminApiPost<{
    message: string;
    skipped?: boolean;
    contractCode?: string;
    mtId?: number;
    mcId?: number;
    detail?: AdminContractDetail | null;
  }>('contracts.php', {
    action: 'apply_custom_document',
    mtId: payload?.mtId ?? 0,
    mtCode: payload?.mtCode ?? 'ADV-0008',
    documentKey: payload?.documentKey ?? 'adv-0008-moduicheolge',
    force: payload?.force ?? true,
  });
}
