<?php

namespace App\Repositories;

use App\Models\CategoryModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CategoryRepository
{
    public function createCategory(array $data)
    {

        Log::info('Creating category with data', ['data' => $data]);

        $category = CategoryModel::create($data);
        Log::info('Created category', ['category' => $category]);

        return $category;
    }

    public function getCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        Log::info('getCategories called with filters', ['data' => $filters]);
        $query = CategoryModel::query()->orderBy('created_at', 'desc');

        if (! empty($filters['name'])) {
            $query->where('name', 'LIKE', '%'.$filters['name'].'%');
        }

        if (! empty($filters['description'])) {
            $query->where('description', $filters['description']);
        }

        return $query->paginate($perPage);
    }

    // get only name and description of all categories
    public function getCategoryNamesAndDescriptions()
    {
        Log::info('Fetching category names and descriptions');
        $categories = [
            [
                'name' => 'Marketing',
                'description' => 'All expenses related to promoting the business and acquiring customers, including digital advertising (Google Ads, Facebook Ads), email marketing platforms (Mailchimp), search engine optimization (SEO tools), content creation, branding, influencer partnerships, and campaign analytics tools.',
            ],
            [
                'name' => 'Sales',
                'description' => 'Costs associated with driving revenue and managing the sales pipeline, such as CRM software (Salesforce, HubSpot), lead generation tools (ZoomInfo), sales commissions, prospecting platforms, sales enablement software, and expenses for sales team incentives and training.',
            ],
            [
                'name' => 'Cloud & Infrastructure',
                'description' => 'Spending on cloud computing, hosting, and IT infrastructure, including services like AWS, GCP, Azure for servers and storage, deployment platforms (Vercel, DigitalOcean), networking, security, backup solutions, and infrastructure monitoring tools.',
            ],
            [
                'name' => 'Software & Subscriptions (SaaS)',
                'description' => 'Recurring costs for software-as-a-service products used across the organization, such as collaboration tools (Slack, Notion), design platforms (Figma), productivity suites (Office 365), project management, HR, finance, and other SaaS applications essential for daily operations.',
            ],
            [
                'name' => 'Payroll & Compensation',
                'description' => 'All employee-related compensation, including salaries, wages, bonuses, payroll processing fees (Gusto, Rippling), overtime, allowances, and other direct payments to staff, as well as employer contributions to benefits and retirement plans.',
            ],
            [
                'name' => 'Contractors & Freelancers',
                'description' => 'Payments to external service providers, including independent contractors, freelancers, agencies, and consultants hired for specialized tasks, project-based work, or temporary support, typically sourced via platforms like Upwork or direct contracts.',
            ],
            [
                'name' => 'Operations',
                'description' => 'Expenses that support the core functioning of the business, such as logistics, shipping, warehousing, manufacturing services, procurement of goods and materials, supply chain management, and operational process optimization.',
            ],
            [
                'name' => 'Office & Facilities',
                'description' => 'Costs related to physical workspaces, including rent (WeWork, traditional leases), utilities (electricity, water, internet), office supplies, furniture, facility maintenance, cleaning services, and property management.',
            ],
            [
                'name' => 'Hardware & Equipment',
                'description' => 'Purchases and maintenance of physical technology and equipment, such as computers (Apple, Dell), servers, networking devices, peripherals, and other capital assets required for business operations and employee productivity.',
            ],
            [
                'name' => 'Financial / Payment Fees',
                'description' => 'Banking and payment processing charges, including transaction fees from Stripe, PayPal, credit card processors, wire transfer costs, account maintenance fees, currency conversion, and other financial service provider charges.',
            ],
            [
                'name' => 'Legal & Professional',
                'description' => 'Spending on legal counsel, law firms, accounting services, regulatory compliance, audit fees, business consulting, intellectual property protection, contract review, and other professional advisory services.',
            ],
            [
                'name' => 'Insurance',
                'description' => 'Premiums and contributions for various insurance policies, such as general liability, cyber insurance, health insurance for employees, workers\' compensation, property insurance, directors and officers (D&O) insurance, and other risk management products.',
            ],
            [
                'name' => 'Travel & Entertainment',
                'description' => 'Costs incurred for business travel (flights, hotels, transportation), meals, client entertainment, team-building events, conferences, offsites, and other activities aimed at business development or employee engagement.',
            ],
            [
                'name' => 'Customer Support & Success',
                'description' => 'Expenses related to supporting and retaining customers, including support software (Zendesk, Intercom), salaries for support and success teams, training, customer onboarding, helpdesk tools, and customer feedback systems.',
            ],
            [
                'name' => 'Research & Development (R&D) / Product Development',
                'description' => 'Investments in innovation and product improvement, such as laboratory costs, prototyping, research tools, testing and quality assurance, experiments, product design, and salaries for R&D staff and engineers.',
            ],
            [
                'name' => 'Depreciation & Amortization',
                'description' => 'Accounting entries for the gradual expense of fixed assets (depreciation of equipment, furniture) and intangible assets (amortization of capitalized software, patents) over their useful life, reflecting asset value reduction.',
            ],
            [
                'name' => 'Taxes',
                'description' => 'All tax-related payments, including income tax, VAT/GST, property tax, payroll taxes, sales tax, and other government levies or statutory contributions required by local, state, or federal authorities.',
            ],
            [
                'name' => 'Miscellaneous / Other',
                'description' => 'Unclassified or irregular expenses that do not fit into other categories, such as one-off purchases, unexpected costs, minor incidentals, or experimental spend awaiting categorization.',
            ],
        ];

        return $categories;
    }

    public function addExpenseToCategory($name, $expenseData): ?array
    {
        Log::info('addExpenseToCategory invoked', [
            'category' => $name,
            'has_expense' => is_array($expenseData),
            'txn_id' => $expenseData['txn_id'] ?? null,
        ]);

        if (! $name) {
            Log::debug('Skipping expense without category name');

            return null;
        }

        try {
            $category = CategoryModel::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $name,
                    'meta_data' => [],
                    'expenses' => [],
                ]
            );

            $existing = $category->expenses ?? [];
            $existing = is_array($existing) ? $existing : [];

            // If previous structure was associative, flatten it to a list of values
            $keys = array_keys($existing);
            $isList = $keys === range(0, count($keys) - 1);
            if (! $isList) {
                $existing = array_values($existing);
            }

            $existing[] = $expenseData;
            $category->expenses = $existing;
            $category->save();

            Log::info('Added expense to category', [
                'category' => $name,
                'new_total' => count($existing),
            ]);

            return $category->expenses;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Failed adding expense to category', [
                'category' => $name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
