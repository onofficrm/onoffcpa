import { Check } from 'lucide-react';

export type OnboardingStepItem = {
  id: number;
  label: string;
  description?: string;
};

type Props = {
  steps: readonly OnboardingStepItem[];
  currentStep: number;
  onStepClick?: (stepId: number) => void;
  allowJumpToCompleted?: boolean;
};

export function OnboardingStepIndicator({
  steps,
  currentStep,
  onStepClick,
  allowJumpToCompleted = true,
}: Props) {
  const cols =
    steps.length <= 3
      ? 'md:grid-cols-3'
      : steps.length === 4
        ? 'md:grid-cols-4'
        : 'md:grid-cols-3 lg:grid-cols-5';

  return (
    <ol className={`grid grid-cols-1 ${cols} gap-3 mb-8`}>
      {steps.map((step) => {
        const active = currentStep === step.id;
        const done = currentStep > step.id;
        const clickable = Boolean(onStepClick) && (active || (allowJumpToCompleted && done));

        return (
          <li key={step.id}>
            <button
              type="button"
              disabled={!clickable}
              onClick={() => clickable && onStepClick?.(step.id)}
              className={`w-full text-left rounded-xl border px-4 py-3 transition-colors ${
                active
                  ? 'border-cyan-500 bg-cyan-50'
                  : done
                    ? 'border-emerald-200 bg-emerald-50'
                    : 'border-slate-200 bg-white'
              } ${clickable ? 'cursor-pointer hover:border-cyan-400' : 'cursor-default'}`}
            >
              <div className="flex items-center gap-2">
                <span
                  className={`inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                    active
                      ? 'bg-cyan-600 text-white'
                      : done
                        ? 'bg-emerald-600 text-white'
                        : 'bg-slate-200 text-slate-600'
                  }`}
                >
                  {done ? <Check size={14} /> : step.id}
                </span>
                <div className="min-w-0">
                  <span className={`block text-sm font-semibold ${active ? 'text-cyan-900' : 'text-slate-700'}`}>
                    {step.label}
                  </span>
                  {step.description ? (
                    <span className="block text-[11px] text-slate-500 mt-0.5 truncate">{step.description}</span>
                  ) : null}
                </div>
              </div>
            </button>
          </li>
        );
      })}
    </ol>
  );
}
