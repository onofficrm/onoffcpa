import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, BookOpen, CheckCircle2, FileText, Loader2, Package } from 'lucide-react';
import { AdvertiserLayout } from '../../layouts/AdvertiserLayout';
import { OnboardingStepIndicator } from '../../components/advertiser/OnboardingStepIndicator';
import {
  ADVERTISER_ONBOARDING_PHASES,
  guideNeedsAttention,
  isGuideOnboardingComplete,
} from '../../lib/advertiserOnboarding';
import { promoGuideStatusLabel, promoGuideStatusStyle } from '../../lib/campaignPromoGuide';
import { fetchMerchantCampaigns, MerchantCampaign, PartnerApiError } from '../../lib/api';
import { getLcAuth } from '../../lib/auth';

export function AdvertiserOnboarding() {
  const auth = getLcAuth();
  const contractDone = Boolean(auth.merchantContractSigned);
  const [campaigns, setCampaigns] = useState<MerchantCampaign[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!contractDone) {
      setLoading(false);
      return;
    }

    let cancelled = false;
    fetchMerchantCampaigns()
      .then((data) => {
        if (!cancelled) setCampaigns(data.items);
      })
      .catch((err) => {
        if (!cancelled) {
          if (err instanceof PartnerApiError && err.code === 'CONTRACT_REQUIRED') {
            setError('');
            return;
          }
          setError(err instanceof Error ? err.message : '광고상품을 불러오지 못했습니다.');
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [contractDone]);

  const campaignDone = campaigns.length > 0;
  const pendingGuides = campaigns.filter((c) => guideNeedsAttention(c.guideStatus || (c.guideExists ? 'draft' : '')));
  const completedGuides = campaigns.filter((c) => isGuideOnboardingComplete(c.guideStatus));
  const guidePhaseDone = campaignDone && pendingGuides.length === 0 && completedGuides.length > 0;

  const currentPhase = !contractDone ? 1 : !campaignDone ? 2 : !guidePhaseDone ? 3 : 4;

  const hubSteps = useMemo(
    () =>
      ADVERTISER_ONBOARDING_PHASES.map((phase, index) => ({
        id: index + 1,
        label: phase.label,
        description: phase.description,
      })),
    [],
  );

  const resumeCampaign =
    pendingGuides[0] ??
    campaigns.find((c) => !isGuideOnboardingComplete(c.guideStatus)) ??
    campaigns[0];

  return (
    <AdvertiserLayout activeMenu="campaigns" title="광고주 온보딩">
      <div className="mb-6">
        <p className="text-slate-500 text-sm max-w-2xl">
          계약부터 파트너용 홍보 가이드까지 순서대로 완료하면 파트너 모집을 시작할 수 있습니다.
        </p>
      </div>

      <OnboardingStepIndicator steps={hubSteps} currentStep={Math.min(currentPhase, 3)} />

      {error ? (
        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
      ) : null}

      <div className="space-y-4">
        <PhaseCard
          done={contractDone}
          active={currentPhase === 1}
          icon={<FileText size={20} />}
          title="1. CPA 계약 체결"
          body={
            contractDone
              ? '계약이 완료되었습니다. 체결된 계약서는 언제든 확인할 수 있습니다.'
              : '광고주 정보 확인, 계약서 동의, 전자서명까지 진행해 주세요.'
          }
          action={
            contractDone ? (
              <Link
                to="/advertiser/contract/view"
                className="inline-flex items-center gap-1.5 text-sm font-bold text-slate-600 hover:text-cyan-700"
              >
                계약서 보기
              </Link>
            ) : (
              <Link
                to="/advertiser/contract"
                className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-bold"
              >
                계약 작성 시작 <ArrowRight size={16} />
              </Link>
            )
          }
        />

        <PhaseCard
          done={campaignDone}
          active={currentPhase === 2}
          locked={!contractDone}
          icon={<Package size={20} />}
          title="2. 광고상품 확인"
          body={
            !contractDone
              ? '계약 체결 후 운영할 광고상품을 확인할 수 있습니다.'
              : loading
                ? '광고상품을 불러오는 중...'
                : campaignDone
                  ? `${campaigns.length}개의 광고상품이 준비되어 있습니다. 홍보 가이드를 작성해 주세요.`
                  : '광고상품 등록은 이메일 또는 별도 서식으로 접수합니다. 등록이 완료되면 여기에 표시됩니다.'
          }
          action={
            contractDone && !loading ? (
              <Link
                to="/advertiser/campaigns"
                className="inline-flex items-center gap-1.5 text-sm font-bold text-slate-600 hover:text-cyan-700"
              >
                광고상품 목록 {campaignDone ? null : <ArrowRight size={16} />}
              </Link>
            ) : loading ? (
              <span className="inline-flex items-center gap-2 text-sm text-slate-500">
                <Loader2 size={16} className="animate-spin" /> 확인 중
              </span>
            ) : null
          }
        />

        <PhaseCard
          done={guidePhaseDone}
          active={currentPhase === 3}
          locked={!campaignDone}
          icon={<BookOpen size={20} />}
          title="3. 홍보 가이드 작성"
          body={
            !campaignDone
              ? '광고상품이 준비되면 추천키워드·이미지·운영 규칙을 스텝별로 입력합니다.'
              : guidePhaseDone
                ? '작성한 가이드가 검토·공개 상태입니다. 필요하면 상품별로 수정할 수 있습니다.'
                : `${pendingGuides.length || campaigns.length}개 상품의 홍보 가이드를 이어서 작성해 주세요.`
          }
          action={
            campaignDone && resumeCampaign ? (
              <Link
                to={`/advertiser/campaigns/${resumeCampaign.id}/guide`}
                className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-bold"
              >
                {guideNeedsAttention(resumeCampaign.guideStatus) ? '가이드 이어서 작성' : '가이드 확인'}{' '}
                <ArrowRight size={16} />
              </Link>
            ) : null
          }
        >
          {campaignDone && campaigns.length > 0 ? (
            <ul className="mt-4 space-y-2 border-t border-slate-100 pt-4">
              {campaigns.map((camp) => {
                const status = camp.guideStatus || (camp.guideExists ? 'draft' : '');
                const label = camp.guideStatusLabel || (status ? promoGuideStatusLabel(status) : '미작성');
                return (
                  <li
                    key={camp.id}
                    className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2.5"
                  >
                    <div className="min-w-0">
                      <p className="text-sm font-bold text-slate-900 truncate">{camp.name}</p>
                      <p className="text-xs text-slate-500">{camp.category || camp.type}</p>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                      <span
                        className={`inline-flex px-2 py-1 rounded-md text-xs font-bold border ${
                          status ? promoGuideStatusStyle(status) : 'bg-slate-100 text-slate-600 border-slate-200'
                        }`}
                      >
                        {label}
                      </span>
                      <Link
                        to={`/advertiser/campaigns/${camp.id}/guide`}
                        className="text-xs font-bold text-cyan-700 hover:underline"
                      >
                        {guideNeedsAttention(status) ? '작성' : '관리'}
                      </Link>
                    </div>
                  </li>
                );
              })}
            </ul>
          ) : null}
        </PhaseCard>
      </div>

      {currentPhase > 3 ? (
        <div className="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div className="flex items-start gap-2">
            <CheckCircle2 className="text-emerald-600 shrink-0 mt-0.5" size={22} />
            <div>
              <p className="font-bold text-emerald-900">온보딩이 완료되었습니다</p>
              <p className="text-sm text-emerald-800/90 mt-0.5">광고주센터에서 디비·성과를 확인해 보세요.</p>
            </div>
          </div>
          <Link
            to="/advertiser"
            className="inline-flex justify-center items-center px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold"
          >
            대시보드로 이동
          </Link>
        </div>
      ) : null}
    </AdvertiserLayout>
  );
}

function PhaseCard({
  done,
  active,
  locked,
  icon,
  title,
  body,
  action,
  children,
}: {
  done: boolean;
  active: boolean;
  locked?: boolean;
  icon: ReactNode;
  title: string;
  body: string;
  action?: ReactNode;
  children?: ReactNode;
}) {
  return (
    <section
      className={`rounded-2xl border bg-white p-5 md:p-6 shadow-sm ${
        active ? 'border-cyan-300 ring-1 ring-cyan-100' : done ? 'border-emerald-200' : 'border-slate-200'
      } ${locked ? 'opacity-70' : ''}`}
    >
      <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div className="flex items-start gap-3 min-w-0">
          <div
            className={`mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
              done ? 'bg-emerald-100 text-emerald-700' : active ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-500'
            }`}
          >
            {done ? <CheckCircle2 size={20} /> : icon}
          </div>
          <div className="min-w-0">
            <h2 className="text-base font-bold text-slate-900">{title}</h2>
            <p className="text-sm text-slate-600 mt-1 leading-relaxed">{body}</p>
            {children}
          </div>
        </div>
        {action ? <div className="shrink-0 md:pt-1">{action}</div> : null}
      </div>
    </section>
  );
}
