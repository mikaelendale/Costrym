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
            <div className="mx-auto h-full w-full max-w-6xl bg-[var(--background)] pt-2">
                <IntegrateWith />
                <StatsSection />
            </div>
            <WhyCostrymSection />
            <div className="mx-auto h-full w-full max-w-6xl bg-[var(--background)] pt-2">
                <PromiseSection />
                <EnterpriseContent />
                <FeaturesSection />
                <RequirementsSection />
                <CTASection />
                <Footer />
            </div>
        </MainLandingLayout>
    );
}
