import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { getLcAuth, shouldEnforceMerchantContract, isMerchantContractBlocked } from '../../lib/auth';
import { AdvertiserContractGate } from './AdvertiserContractGate';

export function AdvertiserContractAccessGuard() {
  const auth = getLcAuth();
  const location = useLocation();

  if (!shouldEnforceMerchantContract(auth)) {
    return <Outlet />;
  }

  if (!isMerchantContractBlocked(auth)) {
    return <Outlet />;
  }

  const path = location.pathname.replace(/\/$/, '') || '/';
  if (path === '/advertiser') {
    return <Navigate to="/advertiser/contract" replace />;
  }

  return <AdvertiserContractGate />;
}
