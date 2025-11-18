import BillingPage from '@/components/billing';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { BreadcrumbItem } from '@/types';
import type React from 'react';

type BillingSectionProps = {
    subscription: any;
    plans: any;
    billingHistory: any;
    currentPlan: any;
};
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
];

const BillingSection: React.FC<BillingSectionProps> = () => {
    // Destructure usage from props
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <SettingsLayout>
                <BillingPage /> {/* Pass the usage prop */}
            </SettingsLayout>
        </AppLayout>
    );
};

export default BillingSection;
