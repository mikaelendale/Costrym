import Hero from '@/components/Hero';
import IntegrateWith from '@/components/IntegrateWith';
import { CTASection } from '@/components/Landing/CTASection';
import { EnterpriseContent } from '@/components/Landing/EnterpriseContent';
import { FeaturesSection } from '@/components/Landing/FeaturesSection';
import { Footer } from '@/components/Landing/Footer';
import { PromiseSection } from '@/components/Landing/PromiseSection';
import { RequirementsSection } from '@/components/Landing/RequirementsSection';
import { WhyCostrymSection } from '@/components/Landing/WhyCostrymSection';
import StatsSection from '@/components/stats';
import MainLandingLayout from '@/layouts/MainLandingLayout';

export default function Welcome() {
    return (
        <MainLandingLayout>
            <Hero />
            <IntegrateWith />
            <StatsSection />
            {/* <WhatWeDo /> */}
            {/* <SelectSector /> */}
            <WhyCostrymSection />
            <PromiseSection />
            <EnterpriseContent />
            <FeaturesSection />
            <RequirementsSection />
            <CTASection />
            <Footer />
        </MainLandingLayout>
    );
}
