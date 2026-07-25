/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { RootLayout } from './layouts/RootLayout';
import { Home } from './pages/Home';
import { Events } from './pages/Events';
import { EventDetail } from './pages/EventDetail';
import { CenterSelect } from './pages/CenterSelect';
import { CpaList } from './pages/cpa/CpaList';
import { PartnerDashboard } from './pages/partner/Dashboard';
import { PartnerSearch } from './pages/partner/PartnerSearch';
import { PartnerLinks } from './pages/partner/PartnerLinks';
import { PartnerDbStatus } from './pages/partner/PartnerDbStatus';
import { PartnerDbCancel } from './pages/partner/PartnerDbCancel';
import { PartnerAnalytics } from './pages/partner/PartnerAnalytics';
import { PartnerSettlement } from './pages/partner/PartnerSettlement';
import { PartnerSupport } from './pages/partner/PartnerSupport';

import { AdvertiserCampaigns } from './pages/advertiser/AdvertiserCampaigns';
import { AdvertiserDb } from './pages/advertiser/AdvertiserDb';
import { AdvertiserBilling } from './pages/advertiser/AdvertiserBilling';
import { AdvertiserReports } from './pages/advertiser/AdvertiserReports';
import { AdvertiserSupport } from './pages/advertiser/AdvertiserSupport';

import { AdvertiserDashboard } from './pages/advertiser/Dashboard';
import { AdminDashboard } from './pages/admin/Dashboard';
import { AdminPartners } from './pages/admin/AdminPartners';
import { AdminAdvertisers } from './pages/admin/AdminAdvertisers';
import { AdminCampaigns } from './pages/admin/AdminCampaigns';
import { AdminInspections } from './pages/admin/AdminInspections';
import { AdminBilling } from './pages/admin/AdminBilling';
import { AdminSettlements } from './pages/admin/AdminSettlements';
import { AdminApi } from './pages/admin/AdminApi';
import { AdminSupport } from './pages/admin/AdminSupport';
import { AdminSettings } from './pages/admin/AdminSettings';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<RootLayout />}>
          <Route index element={<Home />} />
          <Route path="select-center" element={<CenterSelect />} />
          <Route path="cpa-list" element={<CpaList />} />
          <Route path="events" element={<Events />} />
          <Route path="events/detail" element={<EventDetail />} />
          <Route path="partner" element={<PartnerDashboard />} />
          <Route path="partner/search" element={<PartnerSearch />} />
          <Route path="partner/links" element={<PartnerLinks />} />
          <Route path="partner/db-status" element={<PartnerDbStatus />} />
          <Route path="partner/db-cancel" element={<PartnerDbCancel />} />
          <Route path="partner/analytics" element={<PartnerAnalytics />} />
          <Route path="partner/settlement" element={<PartnerSettlement />} />
          <Route path="partner/support" element={<PartnerSupport />} />
          <Route path="advertiser" element={<AdvertiserDashboard />} />
          <Route path="advertiser/campaigns" element={<AdvertiserCampaigns />} />
          <Route path="advertiser/db" element={<AdvertiserDb />} />
          <Route path="advertiser/billing" element={<AdvertiserBilling />} />
          <Route path="advertiser/reports" element={<AdvertiserReports />} />
          <Route path="advertiser/support" element={<AdvertiserSupport />} />
          <Route path="admin" element={<AdminDashboard />} />
          <Route path="admin/partners" element={<AdminPartners />} />
          <Route path="admin/advertisers" element={<AdminAdvertisers />} />
          <Route path="admin/campaigns" element={<AdminCampaigns />} />
          <Route path="admin/inspections" element={<AdminInspections />} />
          <Route path="admin/billing" element={<AdminBilling />} />
          <Route path="admin/settlements" element={<AdminSettlements />} />
          <Route path="admin/api" element={<AdminApi />} />
          <Route path="admin/support" element={<AdminSupport />} />
          <Route path="admin/settings" element={<AdminSettings />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
