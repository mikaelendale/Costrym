<?php

namespace App\Http\Controllers;

use App\Agents\BaseLineAgent;
use App\Agents\CategorizerAgent;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Vizra\VizraADK\Facades\Workflow;
use Vizra\VizraADK\Services\AgentRegistry;
use Vizra\VizraADK\Services\StateManager;
use Vizra\VizraADK\System\AgentContext;

class WorkflowController extends Controller
{
    private $mockCompanyProfile = [

        'company_context' => [
            'company_profile' => [
                'name' => 'MetricStream Analytics',
                'tagline' => 'Turning Marketing Data into Predictable Revenue.',
                'mission' => 'To provide mid-market and enterprise marketing teams with a unified, AI-driven platform for comprehensive cross-channel performance tracking, budget optimization, and predictive forecasting.',
                'year_founded' => 2020,
                'location' => 'San Francisco, CA (Headquarters), Remote-first development and sales teams across North America and Europe.',
                'number_of_employees' => 95,
                'current_revenue_metrics' => [
                    'annual_recurring_revenue_arr_usd' => 12500000,
                    'monthly_recurring_revenue_mrr_usd' => 1041667,
                    'customer_churn_rate_monthly' => '0.8%',
                    'gross_margin' => '75%',
                ],
            ],
            'product_service' => [
                'name' => 'MetricStream Velocity Platform',
                'type' => 'B2B SaaS - Marketing Analytics & Attribution',
                'detailed_features' => [
                    [
                        'feature_name' => 'Unified Data Connector',
                        'description' => 'One-click integration with 50+ marketing platforms (Google Ads, Facebook, HubSpot, Salesforce, etc.) for a single source of truth.',
                        'category' => 'Data Integration',
                    ],
                    [
                        'feature_name' => 'Multi-Touch Attribution Modeling',
                        'description' => 'Supports various attribution models (First Touch, Last Touch, U-Shaped, W-Shaped, and Custom Algorithmic Models) to accurately map ROI.',
                        'category' => 'Analytics',
                    ],
                    [
                        'feature_name' => 'AI-Powered Budget Allocation',
                        'description' => 'Predictive algorithms suggest optimal daily/weekly budget adjustments across channels to hit target KPIs, based on real-time performance.',
                        'category' => 'Optimization',
                    ],
                    [
                        'feature_name' => 'Predictive LTV Forecasting',
                        'description' => 'Forecasts Customer Lifetime Value (LTV) 6 and 12 months out for new cohorts, improving financial planning.',
                        'category' => 'Forecasting',
                    ],
                    [
                        'feature_name' => 'Custom Executive Dashboards',
                        'description' => 'Drag-and-drop interface for building department-specific and C-suite reporting dashboards.',
                        'category' => 'Reporting',
                    ],
                ],
                'pricing_model' => 'Tiered Subscription (Per-Feature/Usage Hybrid - Based on Data Volume & Number of Channels)',
                'pricing_tiers_annual_billing' => [
                    [
                        'tier_name' => 'Professional',
                        'price_usd_per_month' => 999,
                        'target_customer' => 'Mid-Market (3-5 Marketing Users)',
                        'key_limits' => 'Up to 10 Data Connectors, 50 Million Marketing Events/Month, Standard Attribution Models.',
                        'included_support' => 'Email/Chat Support (9-to-5)',
                    ],
                    [
                        'tier_name' => 'Business',
                        'price_usd_per_month' => 2499,
                        'target_customer' => 'Scaling Teams (5-15 Marketing Users)',
                        'key_limits' => 'Up to 25 Data Connectors, 250 Million Marketing Events/Month, AI Budget Allocation (Limited Channels).',
                        'included_support' => '24/7 Priority Chat/Email, Dedicated Onboarding Specialist',
                    ],
                    [
                        'tier_name' => 'Enterprise',
                        'price_usd_per_month' => 4999,
                        'target_customer' => 'Large Enterprises (15+ Marketing Users)',
                        'key_limits' => 'Unlimited Data Connectors, Unlimited Marketing Events, Full Algorithmic Attribution, Predictive LTV Forecasting, AI Budget Allocation (Full).',
                        'included_support' => 'Dedicated Account Manager, Quarterly Business Reviews, SLA-backed support, Custom Integration.',
                    ],
                ],
            ],
            'target_market' => 'B2B & B2C Companies in Tech, E-commerce, and Finance.',
            'target_user_personas' => [
                'Marketing Analysts (Primary User): Needs data accuracy, custom modeling, and granular reporting.',
                'Marketing Directors/VPs (Key Buyer): Needs executive-level dashboards, budget optimization insights, and measurable ROI improvements.',
                'C-Suite Executives (Sponsor): Needs high-level financial impact and predictive growth reports.',
            ],
            'business_model' => 'Subscription-based Software as a Service (SaaS). Revenue is primarily driven by recurring monthly/annual subscription fees. Additional revenue from Professional Services (Custom Integration/Data Science Consulting) on Enterprise deals.',
        ],
    ];

    private $mockFinancialData = [

        'financial_stats_past_year_usd' => [
            'period' => 'Fiscal Year: Jan 1, 2024 - Dec 31, 2024',
            'total_revenue' => 12500000,
            'revenue_breakdown' => [
                'subscription_revenue_arr' => 11875000,
                'professional_services_one_time' => 625000,
                'total_revenue_note' => 'Subscription revenue represents 95% of total revenue, aligning with the SaaS model.',
            ],
            'total_expenses' => 11500000,
            'net_income_loss' => 1000000,
            'cost_of_revenue_cogs' => [
                'total_cogs' => 3125000,
                'detailed_breakdown' => [
                    [
                        'category' => 'Direct Costs (Variable)',
                        'item' => 'Cloud Hosting & Infrastructure (AWS/GCP)',
                        'amount' => 2000000,
                        'description' => 'Scales directly with data volume and processing. Represents the largest variable cost for a B2B SaaS platform.',
                    ],
                    [
                        'category' => 'Direct Costs (Fixed/Semi-Variable)',
                        'item' => 'Customer Success & Onboarding Personnel',
                        'amount' => 750000,
                        'description' => 'Salaries for CSMs and Onboarding Specialists. Included as a COGS component for retention and service delivery.',
                    ],
                    [
                        'category' => 'Direct Costs (Fixed)',
                        'item' => 'Third-Party Data API/Tooling Fees',
                        'amount' => 375000,
                        'description' => 'Costs for external tools essential for the core product function (e.g., identity resolution APIs).',
                    ],
                ],
            ],
            'operating_expenses' => [
                'total_opex' => 8375000,
                'detailed_breakdown' => [
                    [
                        'category' => 'Sales & Marketing (Indirect/Variable)',
                        'item' => 'Sales & Marketing Personnel (Salaries & Commissions)',
                        'amount' => 3250000,
                        'description' => 'Salaries for Sales Reps, Marketing Managers, and performance-based commissions. Commissions are a variable cost.',
                    ],
                    [
                        'category' => 'Sales & Marketing (Variable)',
                        'item' => 'Digital Advertising & Lead Generation (CAC)',
                        'amount' => 1500000,
                        'description' => 'Paid media spend, content creation, and lead nurturing software.',
                    ],
                    [
                        'category' => 'Research & Development (Indirect/Fixed)',
                        'item' => 'Engineering & Product Personnel (Salaries & Benefits)',
                        'amount' => 2500000,
                        'description' => 'Salaries for software engineers, data scientists, and product managers. Highest fixed cost.',
                    ],
                    [
                        'category' => 'General & Administrative (Indirect/Fixed)',
                        'item' => 'Executive, HR, Finance Personnel (Salaries & Benefits)',
                        'amount' => 625000,
                        'description' => 'Non-department-specific overhead.',
                    ],
                    [
                        'category' => 'General & Administrative (Fixed)',
                        'item' => 'Office Rent & Utilities (HQ)',
                        'amount' => 250000,
                        'description' => 'Fixed monthly overhead.',
                    ],
                    [
                        'category' => 'General & Administrative (Fixed)',
                        'item' => 'Professional Services (Legal, Accounting, Audit)',
                        'amount' => 250000,
                        'description' => 'Annual recurring services.',
                    ],
                ],
            ],
            'key_financial_ratios' => [
                'gross_margin_percentage' => '75.0%',
                'operating_margin_percentage' => '8.8%',
                'cac_customer_acquisition_cost' => 15000,
                'ltv_customer_lifetime_value' => 75000,

                'ltv_to_cac_ratio' => '5.0',
                'customer_base_end_of_year' => 500,
            ],
        ],
    ];

    public function run_workflow(array $companyFinancials, array $companyProfile)
    {
        // Create a workflow-level session context with a stable session ID
        $sessionId = 'workflow_'.uniqid();
        $context = new AgentContext($sessionId);

        // Persist CategorizerAgent's session state BEFORE executing the workflow.
        // Workflows execute steps via Agent::run(..., $context->getSessionId()),
        // which reconstructs a fresh AgentContext from persisted state for that agent/session.
        $stateManager = app(StateManager::class);
        $registry = app(AgentRegistry::class);
        $categorizerAgentName = $registry->resolveAgentName(CategorizerAgent::class);

        // Load or initialize the agent's context bound to this session
        $categorizerCtx = $stateManager->loadContext(
            agentName: $categorizerAgentName,
            sessionId: $sessionId,
            userInput: null,
            userId: null
        );

        // Store as JSON strings (agent expects string context pieces)
        $categorizerCtx->setState('company_profile', json_encode($companyProfile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $categorizerCtx->setState('company_financials', json_encode($companyFinancials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $stateManager->saveContext($categorizerCtx, $categorizerAgentName, false);

        $workflow = Workflow::sequential()->start(CategorizerAgent::class)->then(BaseLineAgent::class);

        $result = $workflow->execute('Categories the following data: ', $categorizerCtx);

        // Be resilient to result shape (object with property vs array)
        $finalResult = $result;
        if (is_object($result) && isset($result->final_result)) {
            $finalResult = $result->final_result;
        } elseif (is_array($result) && array_key_exists('final_result', $result)) {
            $finalResult = $result['final_result'];
        }

        Log::info('Final categorized result', [$finalResult]);
    }

    public function index()
    {

        $mockData = [
            'notification_title' => 'Workflow Approval Needed',
            'body' => 'A new workflow plan has been generated and requires your approval.',
            'update_summary' => 'This workflow aims to optimize costs by automating resource management tasks.',
            'details' => [
                'what_to_do' => 'Review the proposed steps and approve or reject the workflow.',
                'why' => 'To ensure cost efficiency and resource optimization.',
                'impact' => 'Successful implementation will reduce operational costs by 15%.',
                'dependencies' => 'Requires access to cloud resource management tools.',
                'risk' => 'Minimal risk; changes can be rolled back if issues arise.',
            ],
        ];

        $this->run_workflow(companyFinancials: $this->mockFinancialData, companyProfile: $this->mockCompanyProfile);

        return Inertia::render('ai/Index', ['mockData' => $mockData]);
    }
}
