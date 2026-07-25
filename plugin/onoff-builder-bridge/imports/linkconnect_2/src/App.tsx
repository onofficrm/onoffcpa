/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { Header } from "./components/Header";
import { Hero } from "./components/Hero";
import { Features } from "./components/Features";
import { PartnerPreview } from "./components/PartnerPreview";
import { AdvertiserPreview } from "./components/AdvertiserPreview";
import { CampaignList } from "./components/CampaignList";
import { EventsBoard } from "./components/EventsBoard";
import { StatsCounter } from "./components/StatsCounter";
import { CtaSection } from "./components/CtaSection";
import { Footer } from "./components/Footer";

export default function App() {
  return (
    <div className="min-h-screen bg-white font-sans selection:bg-teal-100 selection:text-teal-900">
      <Header />
      <main>
        <Hero />
        <Features />
        <PartnerPreview />
        <AdvertiserPreview />
        <CampaignList />
        <EventsBoard />
        <StatsCounter />
        <CtaSection />
      </main>
      <Footer />
    </div>
  );
}
