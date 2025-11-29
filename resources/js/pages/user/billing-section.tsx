import BillingPage from '@/components/billing';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { BreadcrumbItem } from '@/types';
import type React from 'react';

type BillingSectionProps = {
    subscription: any;
    customer: any;
    price: any;
    billing: any;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/settings/billing',
    },
];

const BillingSection: React.FC<BillingSectionProps> = ({ subscription, customer, price, billing }) => {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <SettingsLayout>
                <BillingPage />
            </SettingsLayout>
        </AppLayout>
    );
};

export default BillingSection;
