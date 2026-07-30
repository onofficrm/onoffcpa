import type { ReactNode } from 'react';

export type CenterAccent = 'cyan' | 'emerald' | 'slate';

export type SummaryCardProps = {
  title: string;
  value: string | number;
  suffix?: string;
  icon?: ReactNode;
  highlight?: boolean;
  dark?: boolean;
  color?: 'slate' | 'cyan' | 'blue' | 'emerald' | 'rose' | 'red' | 'yellow' | 'purple' | 'amber' | 'indigo' | 'violet';
  subtitle?: string;
  caption?: string;
  trend?: number;
  trendLabel?: string;
  loading?: boolean;
};
