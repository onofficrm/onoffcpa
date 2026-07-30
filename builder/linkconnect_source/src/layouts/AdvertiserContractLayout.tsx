import React from 'react';
import { Link } from 'react-router-dom';
import { ImpersonateBanner } from '../components/ImpersonateBanner';
import { CenterTopBar } from '../components/CenterTopBar';
import { MemberAuthMenu } from '../components/MemberAuthMenu';
import { getLcAuth } from '../lib/auth';

export function AdvertiserContractLayout({
  children,
  title,
  subtitle,
}: {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
}) {
  const auth = getLcAuth();

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      <ImpersonateBanner />
      <CenterTopBar center="advertiser" />
      <main className="flex-1 w-full max-w-5xl mx-auto px-4 md:px-8 py-6 md:py-10">
        <header className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <p className="text-sm text-cyan-700 font-semibold mb-1">CPA 광고주 계약</p>
            <h1 className="text-2xl md:text-3xl font-bold text-slate-900">{title}</h1>
            {subtitle ? <p className="text-slate-500 mt-1">{subtitle}</p> : null}
          </div>
          <div className="flex items-center gap-3">
            {auth.isMerchant ? (
              <Link
                to="/advertiser"
                className="text-sm text-slate-600 hover:text-cyan-700 border border-slate-200 bg-white px-4 py-2 rounded-xl"
              >
                광고주센터로
              </Link>
            ) : null}
            <MemberAuthMenu variant="compact" logoutReturnPath="/advertiser/contract" />
          </div>
        </header>
        {children}
      </main>
    </div>
  );
}
