import { useEffect } from 'react';
import { AdvertiserIntro } from '../components/AdvertiserIntro';
import { CategoryLinks } from '../components/CategoryLinks';
import { CPAList } from '../components/CPAList';
import { CPSList } from '../components/CPSList';
import { EventBoard } from '../components/EventBoard';
import { Features } from '../components/Features';
import { FinalCTA } from '../components/FinalCTA';
import { Hero } from '../components/Hero';
import { PartnerIntro } from '../components/PartnerIntro';
import { consumeScrollTarget, scrollToSection } from '../lib/navigation';

export function Home() {
  useEffect(() => {
    const target = consumeScrollTarget();
    if (target) {
      window.requestAnimationFrame(() => scrollToSection(target));
    }
  }, []);

  return (
    <main>
      <Hero />
      <CategoryLinks />
      <CPAList />
      <CPSList />
      <Features />
      <PartnerIntro />
      <AdvertiserIntro />
      <EventBoard />
      <FinalCTA />
    </main>
  );
}
