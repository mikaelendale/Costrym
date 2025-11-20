<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Marketing',
                'description' => 'All expenses related to promoting the business and acquiring customers, including digital advertising (Google Ads, Facebook Ads), email marketing platforms (Mailchimp), search engine optimization (SEO tools), content creation, branding, influencer partnerships, and campaign analytics tools.',
                'display_order' => 1,
            ],
            [
                'name' => 'Sales',
                'description' => 'Costs associated with driving revenue and managing the sales pipeline, such as CRM software (Salesforce, HubSpot), lead generation tools (ZoomInfo), sales commissions, prospecting platforms, sales enablement software, and expenses for sales team incentives and training.',
                'display_order' => 2,
            ],
            [
                'name' => 'Cloud & Infrastructure',
                'description' => 'Spending on cloud computing, hosting, and IT infrastructure, including services like AWS, GCP, Azure for servers and storage, deployment platforms (Vercel, DigitalOcean), networking, security, backup solutions, and infrastructure monitoring tools.',
                'display_order' => 3,
            ],
            [
                'name' => 'Software & Subscriptions (SaaS)',
                'description' => 'Recurring costs for software-as-a-service products used across the organization, such as collaboration tools (Slack, Notion), design platforms (Figma), productivity suites (Office 365), project management, HR, finance, and other SaaS applications essential for daily operations.',
                'display_order' => 4,
            ],
            [
                'name' => 'Payroll & Compensation',
                'description' => 'All employee-related compensation, including salaries, wages, bonuses, payroll processing fees (Gusto, Rippling), overtime, allowances, and other direct payments to staff, as well as employer contributions to benefits and retirement plans.',
                'display_order' => 5,
            ],
            [
                'name' => 'Contractors & Freelancers',
                'description' => 'Payments to external service providers, including independent contractors, freelancers, agencies, and consultants hired for specialized tasks, project-based work, or temporary support, typically sourced via platforms like Upwork or direct contracts.',
                'display_order' => 6,
            ],
            [
                'name' => 'Operations',
                'description' => 'Expenses that support the core functioning of the business, such as logistics, shipping, warehousing, manufacturing services, procurement of goods and materials, supply chain management, and operational process optimization.',
                'display_order' => 7,
            ],
            [
                'name' => 'Office & Facilities',
                'description' => 'Costs related to physical workspaces, including rent (WeWork, traditional leases), utilities (electricity, water, internet), office supplies, furniture, facility maintenance, cleaning services, and property management.',
                'display_order' => 8,
            ],
            [
                'name' => 'Hardware & Equipment',
                'description' => 'Purchases and maintenance of physical technology and equipment, such as computers (Apple, Dell), servers, networking devices, peripherals, and other capital assets required for business operations and employee productivity.',
                'display_order' => 9,
            ],
            [
                'name' => 'Financial / Payment Fees',
                'description' => 'Banking and payment processing charges, including transaction fees from Stripe, PayPal, credit card processors, wire transfer costs, account maintenance fees, currency conversion, and other financial service provider charges.',
                'display_order' => 10,
            ],
            [
                'name' => 'Legal & Professional',
                'description' => 'Spending on legal counsel, law firms, accounting services, regulatory compliance, audit fees, business consulting, intellectual property protection, contract review, and other professional advisory services.',
                'display_order' => 11,
            ],
            [
                'name' => 'Insurance',
                'description' => 'Premiums and contributions for various insurance policies, such as general liability, cyber insurance, health insurance for employees, workers\' compensation, property insurance, directors and officers (D&O) insurance, and other risk management products.',
                'display_order' => 12,
            ],
            [
                'name' => 'Travel & Entertainment',
                'description' => 'Costs incurred for business travel (flights, hotels, transportation), meals, client entertainment, team-building events, conferences, offsites, and other activities aimed at business development or employee engagement.',
                'display_order' => 13,
            ],
            [
                'name' => 'Customer Support & Success',
                'description' => 'Expenses related to supporting and retaining customers, including support software (Zendesk, Intercom), salaries for support and success teams, training, customer onboarding, helpdesk tools, and customer feedback systems.',
                'display_order' => 14,
            ],
            [
                'name' => 'Research & Development (R&D) / Product Development',
                'description' => 'Investments in innovation and product improvement, such as laboratory costs, prototyping, research tools, testing and quality assurance, experiments, product design, and salaries for R&D staff and engineers.',
                'display_order' => 15,
            ],
            [
                'name' => 'Depreciation & Amortization',
                'description' => 'Accounting entries for the gradual expense of fixed assets (depreciation of equipment, furniture) and intangible assets (amortization of capitalized software, patents) over their useful life, reflecting asset value reduction.',
                'display_order' => 16,
            ],
            [
                'name' => 'Taxes',
                'description' => 'All tax-related payments, including income tax, VAT/GST, property tax, payroll taxes, sales tax, and other government levies or statutory contributions required by local, state, or federal authorities.',
                'display_order' => 17,
            ],
            [
                'name' => 'Miscellaneous / Other',
                'description' => 'Unclassified or irregular expenses that do not fit into other categories, such as one-off purchases, unexpected costs, minor incidentals, or experimental spend awaiting categorization.',
                'display_order' => 18,
            ],
        ];

        foreach ($categories as $category) {
            FinancialCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
