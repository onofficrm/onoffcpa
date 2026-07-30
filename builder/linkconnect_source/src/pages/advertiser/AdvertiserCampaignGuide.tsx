import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import {
  ImageUploader,
  SectionCard,
  StringListInput,
  TagInput,
} from '../../components/advertiser/promoGuide/PromoGuideFields';
import { OnboardingStepIndicator } from '../../components/advertiser/OnboardingStepIndicator';
import {
  clampPromoGuideWizardStep,
  clearPromoGuideWizardStep,
  PROMO_GUIDE_WIZARD_STEPS,
  readPromoGuideWizardStep,
  validatePromoGuideWizardStep,
  writePromoGuideWizardStep,
} from '../../lib/advertiserOnboarding';
import {
  buildPromoGuidePayload,
  EMPTY_PROMO_GUIDE_FORM,
  formFromPromoGuideApi,
  promoGuideApprovalLabel,
  PROMO_GUIDE_LIMITS_DEFAULT,
  promoGuideStatusLabel,
  promoGuideStatusStyle,
  PromoGuideApprovalType,
  PromoGuideFormState,
  PromoGuideLimits,
  PromoGuideStatus,
  validatePromoGuideForm,
} from '../../lib/campaignPromoGuide';
import {
  createMerchantPromoGuide,
  deleteMerchantPromoGuideImage,
  fetchMerchantPromoGuide,
  MerchantPromoGuideData,
  MerchantPromoGuideImage,
  PartnerApiError,
  publishMerchantPromoGuide,
  saveMerchantPromoGuideDraft,
  sortMerchantPromoGuideImages,
  submitMerchantPromoGuideReview,
  updateMerchantPromoGuide,
  updateMerchantPromoGuideImageTitle,
  uploadMerchantPromoGuideImage,
  generateMerchantPromoImageAi,
} from '../../lib/api';
import { ArrowLeft, CheckCircle2, Loader2 } from 'lucide-react';
import { AdvertiserCampaignAiPanel } from '../../components/advertiser/AdvertiserCampaignAiPanel';
import {
  AiImageGenerateModal,
  type AiImageGenerateOptions,
} from '../../components/ai/AiImageGenerateModal';
import {
  formatPromoAssetSize,
  type PromoAssetSizePreset,
  suggestTitleForPreset,
} from '../../components/advertiser/promoGuide/PromoAssetSizeGuide';

const APPROVAL_OPTIONS: PromoGuideApprovalType[] = ['free', 'first_review', 'all_review'];

type SaveMode = 'draft' | 'update' | 'review' | 'publish';

export function AdvertiserCampaignGuide() {
  const navigate = useNavigate();
  const { cpId: cpIdParam } = useParams<{ cpId: string }>();
  const [searchParams, setSearchParams] = useSearchParams();
  const cpId = Number(cpIdParam);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [aiGenerating, setAiGenerating] = useState(false);
  const [loadError, setLoadError] = useState('');
  const [success, setSuccess] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [form, setForm] = useState<PromoGuideFormState>(EMPTY_PROMO_GUIDE_FORM);
  const [images, setImages] = useState<MerchantPromoGuideImage[]>([]);
  const [guideMeta, setGuideMeta] = useState<MerchantPromoGuideData | null>(null);
  const [csrfToken, setCsrfToken] = useState('');
  const [exists, setExists] = useState(false);
  const [campaignName, setCampaignName] = useState('');
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [aiImageTarget, setAiImageTarget] = useState<{
    preset: PromoAssetSizePreset;
    imageTitle: string;
  } | null>(null);

  const limits: PromoGuideLimits = useMemo(() => {
    const raw = guideMeta?.limits;
    if (!raw) return PROMO_GUIDE_LIMITS_DEFAULT;
    return {
      promotion_points: raw.promotion_points,
      recommended_keywords: raw.recommended_keywords,
      forbidden_words: raw.forbidden_words,
      precautions: raw.precautions,
      valid_db_rules: raw.valid_db_rules,
      invalid_db_rules: raw.invalid_db_rules,
      images: raw.images,
    };
  }, [guideMeta?.limits]);

  const maxImageBytes = guideMeta?.maxImageBytes ?? 2097152;
  const skipReview = Boolean(guideMeta?.skipReview);
  const guideStatus = (
    exists && guideMeta?.guideStatus ? guideMeta.guideStatus : exists ? 'draft' : 'none'
  ) as PromoGuideStatus | 'none';
  const revisionReason = guideMeta?.revisionReason ?? '';
  const readOnly = guideStatus === 'review';
  const isPublishedEdit = guideStatus === 'published';

  const syncStep = useCallback(
    (next: number, replaceUrl = true) => {
      const clamped = clampPromoGuideWizardStep(next);
      setStep(clamped);
      if (Number.isFinite(cpId) && cpId > 0) {
        writePromoGuideWizardStep(cpId, clamped);
      }
      if (replaceUrl) {
        setSearchParams({ step: String(clamped) }, { replace: true });
      }
    },
    [cpId, setSearchParams],
  );

  useEffect(() => {
    if (!Number.isFinite(cpId) || cpId <= 0) return;
    const fromQuery = Number(searchParams.get('step'));
    if (fromQuery === 1 || fromQuery === 2 || fromQuery === 3) {
      setStep(fromQuery);
      writePromoGuideWizardStep(cpId, fromQuery);
      return;
    }
    const stored = readPromoGuideWizardStep(cpId);
    setStep(stored);
    setSearchParams({ step: String(stored) }, { replace: true });
  }, [cpId]); // eslint-disable-line react-hooks/exhaustive-deps -- init once per cpId

  const applyGuideData = useCallback((data: MerchantPromoGuideData) => {
    setGuideMeta(data);
    const hasGuide = Boolean(data.exists) || Boolean(data.guideId) || Boolean(data.id);
    setExists(hasGuide);
    setCampaignName(data.campaignName ?? '');
    if (data.csrfToken) setCsrfToken(data.csrfToken);
    if (hasGuide) {
      setForm(
        formFromPromoGuideApi({
          promotionPoints: data.promotionPoints,
          recommendedKeywords: data.recommendedKeywords,
          forbiddenWords: data.forbiddenWords,
          precautions: data.precautions,
          validDbRules: data.validDbRules,
          invalidDbRules: data.invalidDbRules,
          approvalType: data.approvalType,
        }),
      );
      setImages(data.images ?? []);
    }
  }, []);

  const loadGuide = useCallback(async () => {
    if (!Number.isFinite(cpId) || cpId <= 0) {
      setLoadError('유효하지 않은 광고상품입니다.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setLoadError('');
    try {
      const data = await fetchMerchantPromoGuide(cpId);
      applyGuideData(data);
    } catch (err) {
      if (err instanceof PartnerApiError && (err.status === 403 || err.status === 404)) {
        setLoadError(err.message || '해당 광고상품에 접근할 수 없습니다.');
      } else {
        setLoadError(err instanceof Error ? err.message : '홍보 가이드를 불러오지 못했습니다.');
      }
    } finally {
      setLoading(false);
    }
  }, [applyGuideData, cpId]);

  useEffect(() => {
    loadGuide();
  }, [loadGuide]);

  const ensureGuideExists = async (token: string) => {
    if (exists) return token;
    const created = await createMerchantPromoGuide(cpId, token);
    if (created.csrfToken) setCsrfToken(created.csrfToken);
    setExists(true);
    if (created.guide) {
      applyGuideData({
        ...created.guide,
        exists: true,
        csrfToken: created.csrfToken ?? token,
        campaignName: created.guide.campaignName || campaignName,
        skipReview,
      });
    }
    return created.csrfToken ?? token;
  };

  const handleSave = async (mode: SaveMode, options?: { silent?: boolean }) => {
    if (saving || uploading) return false;

    setSuccess('');
    const clientErrors = mode === 'draft' ? {} : validatePromoGuideForm(form, limits);
    setFieldErrors(clientErrors);
    if (Object.keys(clientErrors).length > 0) {
      return false;
    }

    setSaving(true);
    try {
      let token = csrfToken;
      if (!token) {
        const fresh = await fetchMerchantPromoGuide(cpId);
        token = fresh.csrfToken ?? '';
        setCsrfToken(token);
      }
      token = await ensureGuideExists(token);

      const payload = buildPromoGuidePayload(form, limits);
      let response;

      if (mode === 'draft') {
        response = await saveMerchantPromoGuideDraft(cpId, token, payload);
      } else if (mode === 'update') {
        response = await updateMerchantPromoGuide(cpId, token, payload);
      } else if (mode === 'publish' && skipReview) {
        response = await publishMerchantPromoGuide(cpId, token, payload);
      } else {
        response = await submitMerchantPromoGuideReview(cpId, token, payload);
      }

      if (response.csrfToken) setCsrfToken(response.csrfToken);
      if (response.guide) {
        applyGuideData({ ...response.guide, exists: true, skipReview, campaignName });
      }
      if (!options?.silent) {
        setSuccess(response.message);
      }
      setLoadError('');
      if (mode === 'review' || mode === 'publish') {
        clearPromoGuideWizardStep(cpId);
      }
      return true;
    } catch (err) {
      if (err instanceof PartnerApiError) {
        const extra = (err as PartnerApiError & { fieldErrors?: Record<string, string> }).fieldErrors;
        if (extra) setFieldErrors(extra);
        setLoadError(err.message);
      } else {
        setLoadError(err instanceof Error ? err.message : '저장에 실패했습니다.');
      }
      return false;
    } finally {
      setSaving(false);
    }
  };

  const handleUpload = async (files: FileList | File[], imageTitle = '') => {
    if (readOnly || uploading || saving) return;
    const list = Array.from(files);
    if (images.length + list.length > limits.images) {
      setFieldErrors((prev) => ({
        ...prev,
        images: `이미지는 최대 ${limits.images}개까지 등록할 수 있습니다.`,
      }));
      return;
    }

    setUploading(true);
    setSuccess('');
    setFieldErrors((prev) => {
      const next = { ...prev };
      delete next.images;
      return next;
    });

    try {
      let token = csrfToken || (await fetchMerchantPromoGuide(cpId)).csrfToken || '';
      token = await ensureGuideExists(token);

      for (const file of list) {
        if (images.length >= limits.images) break;
        const title = imageTitle || '';
        const res = await uploadMerchantPromoGuideImage(cpId, token, file, title);
        if (res.csrfToken) {
          token = res.csrfToken;
          setCsrfToken(token);
        }
        if (res.asset) {
          setImages((prev) => [...prev, res.asset]);
        }
      }
      setSuccess(imageTitle ? `${imageTitle} 이미지가 업로드되었습니다.` : '이미지가 업로드되었습니다.');
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : '이미지 업로드에 실패했습니다.');
    } finally {
      setUploading(false);
    }
  };

  const openAiGenerateModal = (preset: PromoAssetSizePreset, imageTitle: string) => {
    if (readOnly || uploading || aiGenerating || !cpId) return;
    if (images.length >= limits.images) {
      setFieldErrors((prev) => ({
        ...prev,
        images: `이미지는 최대 ${limits.images}개까지 등록할 수 있습니다.`,
      }));
      return;
    }
    setAiImageTarget({ preset, imageTitle });
  };

  const handleAiGenerate = async (options: AiImageGenerateOptions) => {
    if (!aiImageTarget || readOnly || uploading || aiGenerating || !cpId) return;
    const { preset, imageTitle } = aiImageTarget;

    setAiGenerating(true);
    setSuccess('');
    setFieldErrors((prev) => {
      const next = { ...prev };
      delete next.images;
      return next;
    });

    try {
      let token = csrfToken || (await fetchMerchantPromoGuide(cpId)).csrfToken || '';
      token = await ensureGuideExists(token);
      setCsrfToken(token);

      const res = await generateMerchantPromoImageAi({
        cpId,
        width: preset.freeFormat ? 0 : preset.width,
        height: preset.freeFormat ? 0 : preset.height,
        imageTitle,
        mood: options.mood,
        includeText: options.includeText,
        overlayText: options.overlayText,
        extra: options.extra,
      });
      if (res.asset) {
        const asset = res.asset;
        setImages((prev) => [
          ...prev,
          {
            id: asset.id,
            assetId: asset.id,
            downloadUrl: asset.downloadUrl,
            originalFilename: asset.originalFilename,
            imageTitle: asset.imageTitle || imageTitle,
            mimeType: 'image/jpeg',
            fileSize: 0,
            sortOrder: prev.length,
          },
        ]);
      }
      setSuccess(res.message || 'AI 이미지가 생성되었습니다.');
      setAiImageTarget(null);
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : 'AI 이미지 생성에 실패했습니다.');
    } finally {
      setAiGenerating(false);
    }
  };

  const handleDeleteImage = async (assetId: number) => {
    if (readOnly) return;
    try {
      const res = await deleteMerchantPromoGuideImage(assetId, csrfToken);
      if (res.csrfToken) setCsrfToken(res.csrfToken);
      setImages((prev) => prev.filter((img) => img.id !== assetId));
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : '이미지 삭제에 실패했습니다.');
    }
  };

  const handleSortImages = async (ids: number[]) => {
    setImages((prev) => {
      const map = new Map(prev.map((img) => [img.id, img]));
      return ids.map((id) => map.get(id)).filter(Boolean) as MerchantPromoGuideImage[];
    });

    if (readOnly) return;
    try {
      const res = await sortMerchantPromoGuideImages(cpId, csrfToken, ids);
      if (res.csrfToken) setCsrfToken(res.csrfToken);
      if (res.guide?.images) setImages(res.guide.images);
    } catch {
      loadGuide();
    }
  };

  const handleTitleBlur = async (assetId: number, title: string) => {
    if (readOnly) return;
    try {
      const res = await updateMerchantPromoGuideImageTitle(assetId, csrfToken, title);
      if (res.csrfToken) setCsrfToken(res.csrfToken);
    } catch {
      // silent — title will resync on reload
    }
  };

  const goNext = async () => {
    if (readOnly) {
      syncStep(step + 1);
      return;
    }
    const errors = validatePromoGuideWizardStep(step, form, limits);
    setFieldErrors(errors);
    if (Object.keys(errors).length > 0) return;

    const ok = await handleSave('draft', { silent: true });
    if (!ok) return;
    setSuccess('임시저장되었습니다. 다음 단계로 이동합니다.');
    syncStep(step + 1);
  };

  const goPrev = () => {
    syncStep(step - 1);
  };

  const saveAndExit = async () => {
    const ok = await handleSave('draft');
    if (ok) navigate('/advertiser/onboarding');
  };

  const pointCount = form.promotionPoints.filter((v) => v.trim()).length;

  if (!Number.isFinite(cpId) || cpId <= 0) {
    return (
      <AdvertiserLayout activeMenu="campaigns" title="파트너 홍보 가이드">
        <p className="text-red-600">유효하지 않은 광고상품입니다.</p>
      </AdvertiserLayout>
    );
  }

  return (
    <AdvertiserLayout activeMenu="campaigns" title="파트너 홍보 가이드">
      <div className="mb-6 flex flex-wrap items-center gap-x-4 gap-y-2">
        <Link
          to="/advertiser/onboarding"
          className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-cyan-700"
        >
          <ArrowLeft size={16} /> 온보딩으로 돌아가기
        </Link>
        <Link to="/advertiser/campaigns" className="text-sm font-semibold text-slate-400 hover:text-cyan-700">
          광고상품 목록
        </Link>
      </div>

      <div className="mb-6 flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
          {campaignName ? (
            <p className="text-sm font-semibold text-cyan-700 mb-1">{campaignName}</p>
          ) : null}
          <p className="text-slate-500 text-sm max-w-2xl">
            파트너가 광고상품을 홍보할 때 확인하는 핵심 정보입니다. 단계별로 입력해 주세요.
          </p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white px-4 py-3 shrink-0">
          <p className="text-xs text-slate-500 mb-1">홍보 가이드 상태</p>
          <span className={`inline-flex px-2.5 py-1 rounded-lg text-sm font-bold border ${promoGuideStatusStyle(guideStatus)}`}>
            {promoGuideStatusLabel(guideStatus)}
          </span>
        </div>
      </div>

      <OnboardingStepIndicator
        steps={PROMO_GUIDE_WIZARD_STEPS}
        currentStep={step}
        onStepClick={(id) => {
          if (readOnly || id < step) syncStep(id);
        }}
      />

      {loading ? (
        <div className="flex items-center justify-center py-20 text-slate-500 gap-2">
          <Loader2 className="animate-spin" size={20} /> 불러오는 중...
        </div>
      ) : loadError && !guideMeta ? (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{loadError}</div>
      ) : (
        <div className="space-y-5 pb-28">
          {loadError ? (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{loadError}</div>
          ) : null}
          {success ? (
            <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
              <CheckCircle2 size={18} /> {success}
            </div>
          ) : null}

          {readOnly ? (
            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
              관리자 검토 중입니다. 검토가 완료될 때까지 내용을 수정할 수 없습니다.
            </div>
          ) : null}

          {guideStatus === 'revision' && revisionReason ? (
            <div className="rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
              <p className="font-bold mb-1">관리자 수정 요청</p>
              <p className="whitespace-pre-wrap break-words">{revisionReason}</p>
            </div>
          ) : null}

          {isPublishedEdit ? (
            <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
              파트너에게 공개 중입니다. 단순 수정은 <strong>임시저장</strong>으로 공개 상태를 유지하고,
              금지어·DB 기준 등 중요 변경은 <strong>수정 후 다시 검토 요청</strong>을 이용해 주세요.
            </div>
          ) : null}

          {step === 1 ? (
            <>
              <SectionCard
                title="핵심 홍보 포인트"
                description="파트너가 광고할 때 강조할 핵심 메시지를 짧은 한 문장으로 입력해 주세요."
                hint="예: 저신용자도 상담 가능 · 개인회생 중 차량 구매 상담 · 전국 상담 가능"
                count={pointCount}
                max={limits.promotion_points}
                error={fieldErrors.promotionPoints}
              >
                <StringListInput
                  items={form.promotionPoints}
                  max={limits.promotion_points}
                  placeholder="예: 저신용자도 상담 가능"
                  disabled={readOnly}
                  onChange={(items) => setForm((prev) => ({ ...prev, promotionPoints: items }))}
                />
              </SectionCard>

              <SectionCard
                title="추천키워드"
                description="검색·블로그 등에 쓸 키워드를 등록해 주세요. (선택)"
                count={form.recommendedKeywords.length}
                max={limits.recommended_keywords}
                error={fieldErrors.recommendedKeywords}
              >
                <TagInput
                  tags={form.recommendedKeywords}
                  max={limits.recommended_keywords}
                  placeholder="키워드 입력 후 엔터"
                  disabled={readOnly}
                  onChange={(tags) => setForm((prev) => ({ ...prev, recommendedKeywords: tags }))}
                />
              </SectionCard>

              <SectionCard
                title="금지어 및 금지표현"
                hint="파트너가 절대 사용하면 안 되는 표현만 등록해 주세요. 예: 무조건 승인, 100% 승인"
                count={form.forbiddenWords.length}
                max={limits.forbidden_words}
                error={fieldErrors.forbiddenWords}
              >
                <TagInput
                  tags={form.forbiddenWords}
                  max={limits.forbidden_words}
                  placeholder="금지 표현 입력"
                  disabled={readOnly}
                  onChange={(tags) => setForm((prev) => ({ ...prev, forbiddenWords: tags }))}
                />
              </SectionCard>

              <AdvertiserCampaignAiPanel
                cpId={cpId}
                campaignName={campaignName || '광고상품'}
                readOnly={readOnly}
                onApplyPromotionPoints={(points) => {
                  setForm((prev) => {
                    const merged = [...prev.promotionPoints];
                    points.forEach((point) => {
                      const emptyIdx = merged.findIndex((v) => !v.trim());
                      if (emptyIdx >= 0) {
                        merged[emptyIdx] = point;
                      } else if (merged.length < limits.promotion_points) {
                        merged.push(point);
                      }
                    });
                    return { ...prev, promotionPoints: merged };
                  });
                }}
              />
            </>
          ) : null}

          {step === 2 ? (
            <SectionCard
              title="사이즈별 이미지 · 배너 등록"
              description="자유형식(규격 무관) 또는 네이버·웹·구글 권장 사이즈를 고른 뒤 이미지를 업로드하세요."
              hint="기본은 자유형식입니다. 권장 사이즈를 고르면 제목이 자동으로 맞춰집니다. 지금은 건너뛰고 나중에 등록해도 됩니다."
              count={images.length}
              max={limits.images}
              error={fieldErrors.images}
            >
              <ImageUploader
                images={images.map((img) => ({
                  id: img.id,
                  imageTitle: img.imageTitle,
                  downloadUrl: img.downloadUrl,
                  originalFilename: img.originalFilename,
                }))}
                max={limits.images}
                maxBytes={maxImageBytes}
                disabled={readOnly}
                uploading={uploading}
                aiGenerating={aiGenerating}
                onUpload={handleUpload}
                onAiGenerate={openAiGenerateModal}
                onDelete={handleDeleteImage}
                onSort={handleSortImages}
                onTitleChange={(id, title) => {
                  setImages((prev) => prev.map((img) => (img.id === id ? { ...img, imageTitle: title } : img)));
                }}
                onTitleBlur={handleTitleBlur}
              />
            </SectionCard>
          ) : null}

          {step === 3 ? (
            <>
              <SectionCard
                title="홍보 시 유의사항"
                hint="예: 공식 직원인 것처럼 홍보하지 마세요. · 무조건 승인된다는 표현을 사용할 수 없습니다."
                count={form.precautions.filter((v) => v.trim()).length}
                max={limits.precautions}
                error={fieldErrors.precautions}
              >
                <StringListInput
                  items={form.precautions}
                  max={limits.precautions}
                  placeholder="유의사항 입력"
                  disabled={readOnly}
                  onChange={(items) => setForm((prev) => ({ ...prev, precautions: items }))}
                />
              </SectionCard>

              <SectionCard title="DB 인정 기준" error={fieldErrors.validDbRules || fieldErrors.invalidDbRules}>
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <h3 className="text-sm font-bold text-slate-800 mb-2">
                      유효 DB 기준{' '}
                      <span className="text-slate-400 font-normal">
                        ({form.validDbRules.filter((v) => v.trim()).length}/{limits.valid_db_rules})
                      </span>
                    </h3>
                    <StringListInput
                      items={form.validDbRules}
                      max={limits.valid_db_rules}
                      placeholder="예: 본인이 직접 신청한 정상 연락처"
                      disabled={readOnly}
                      onChange={(items) => setForm((prev) => ({ ...prev, validDbRules: items }))}
                    />
                  </div>
                  <div>
                    <h3 className="text-sm font-bold text-slate-800 mb-2">
                      무효 DB 기준{' '}
                      <span className="text-slate-400 font-normal">
                        ({form.invalidDbRules.filter((v) => v.trim()).length}/{limits.invalid_db_rules})
                      </span>
                    </h3>
                    <StringListInput
                      items={form.invalidDbRules}
                      max={limits.invalid_db_rules}
                      placeholder="예: 결번 또는 타인 전화번호"
                      disabled={readOnly}
                      onChange={(items) => setForm((prev) => ({ ...prev, invalidDbRules: items }))}
                    />
                  </div>
                </div>
              </SectionCard>

              <SectionCard title="광고물 승인 방식">
                <div className="space-y-3">
                  {APPROVAL_OPTIONS.map((value) => (
                    <label
                      key={value}
                      className={`flex items-start gap-3 rounded-xl border px-4 py-3 cursor-pointer ${
                        form.approvalType === value ? 'border-cyan-400 bg-cyan-50' : 'border-slate-200 bg-white'
                      } ${readOnly ? 'opacity-70 cursor-not-allowed' : ''}`}
                    >
                      <input
                        type="radio"
                        name="approvalType"
                        value={value}
                        checked={form.approvalType === value}
                        disabled={readOnly}
                        onChange={() => setForm((prev) => ({ ...prev, approvalType: value }))}
                        className="mt-1"
                      />
                      <span className="text-sm font-medium text-slate-800">{promoGuideApprovalLabel(value)}</span>
                    </label>
                  ))}
                </div>
              </SectionCard>
            </>
          ) : null}
        </div>
      )}

      {!loading && guideMeta !== null ? (
        <div className="fixed bottom-0 left-0 right-0 md:left-64 z-20 border-t border-slate-200 bg-white/95 backdrop-blur px-4 py-4">
          <div className="max-w-5xl mx-auto flex flex-col sm:flex-row gap-3 sm:justify-between">
            <div className="flex flex-wrap gap-2">
              {step > 1 ? (
                <button
                  type="button"
                  disabled={saving || uploading}
                  onClick={goPrev}
                  className="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50 disabled:opacity-50"
                >
                  이전
                </button>
              ) : null}
              {!readOnly ? (
                <button
                  type="button"
                  disabled={saving || uploading}
                  onClick={saveAndExit}
                  className="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50 disabled:opacity-50"
                >
                  {saving ? '저장 중...' : '저장하고 나가기'}
                </button>
              ) : null}
            </div>
            <div className="flex flex-wrap gap-2 sm:justify-end">
              {step < 3 ? (
                <button
                  type="button"
                  disabled={saving || uploading}
                  onClick={goNext}
                  className="px-5 py-3 rounded-xl bg-cyan-600 text-white font-bold text-sm hover:bg-cyan-500 disabled:opacity-50"
                >
                  {saving ? '저장 중...' : step === 2 && images.length === 0 ? '건너뛰고 다음' : '다음'}
                </button>
              ) : (
                <>
                  {!readOnly ? (
                    <button
                      type="button"
                      disabled={saving || uploading}
                      onClick={() => handleSave('draft')}
                      className="px-5 py-3 rounded-xl border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-50 disabled:opacity-50"
                    >
                      {saving ? '저장 중...' : isPublishedEdit ? '공개 유지 저장' : '임시저장'}
                    </button>
                  ) : null}
                  {!readOnly && !isPublishedEdit && skipReview ? (
                    <button
                      type="button"
                      disabled={saving || uploading}
                      onClick={() => handleSave('publish')}
                      className="px-5 py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-500 disabled:opacity-50"
                    >
                      {saving ? '처리 중...' : '파트너에 공개'}
                    </button>
                  ) : !readOnly && !isPublishedEdit ? (
                    <button
                      type="button"
                      disabled={saving || uploading}
                      onClick={() => handleSave('review')}
                      className="px-5 py-3 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-400 disabled:opacity-50"
                    >
                      {saving ? '요청 중...' : '관리자 검토 요청'}
                    </button>
                  ) : null}
                  {!readOnly && isPublishedEdit ? (
                    <button
                      type="button"
                      disabled={saving || uploading}
                      onClick={() => handleSave('review')}
                      className="px-5 py-3 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-400 disabled:opacity-50"
                    >
                      {saving ? '요청 중...' : '수정 후 다시 검토 요청'}
                    </button>
                  ) : null}
                  {!readOnly && !isPublishedEdit ? (
                    <button
                      type="button"
                      disabled={saving || uploading}
                      onClick={() => handleSave('update')}
                      className="px-5 py-3 rounded-xl bg-cyan-600 text-white font-bold text-sm hover:bg-cyan-500 disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                      {saving ? <Loader2 size={16} className="animate-spin" /> : null}
                      변경내용 저장
                    </button>
                  ) : null}
                </>
              )}
            </div>
          </div>
        </div>
      ) : null}

      <AiImageGenerateModal
        open={Boolean(aiImageTarget)}
        title="AI 홍보 이미지 생성"
        subtitle={
          aiImageTarget
            ? `${aiImageTarget.preset.title} · ${formatPromoAssetSize(aiImageTarget.preset)}`
            : undefined
        }
        busy={aiGenerating}
        defaultOverlayText={
          campaignName ||
          (aiImageTarget ? suggestTitleForPreset(aiImageTarget.preset) : '')
        }
        onClose={() => {
          if (!aiGenerating) setAiImageTarget(null);
        }}
        onConfirm={handleAiGenerate}
      />
    </AdvertiserLayout>
  );
}
