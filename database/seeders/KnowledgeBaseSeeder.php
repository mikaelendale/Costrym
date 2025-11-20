<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (or create one if none exists)
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        // Clear existing knowledge base entries for this user
        KnowledgeBase::where('user_id', $user->id)->delete();

        // Create comprehensive business context
        KnowledgeBase::create([
            'user_id' => $user->id,
            'context' => [
                // Company Information
                'company_name' => 'TechFlow Solutions',
                'industry' => 'Software Development & IT Consulting',
                'founded_year' => 2020,
                'location' => 'San Francisco, CA',
                'website' => 'https://techflowsolutions.com',
                'employee_count' => 45,

                // Business Model
                'business_model' => 'B2B SaaS with consulting services',
                'target_market' => 'Mid-size enterprises (50-500 employees)',
                'revenue_model' => [
                    'subscription' => 'Recurring monthly/annual SaaS subscriptions',
                    'consulting' => 'Hourly consulting and implementation services',
                    'training' => 'Training programs and workshops',
                ],

                // Products & Services
                'products' => [
                    [
                        'name' => 'CloudFlow Platform',
                        'type' => 'SaaS',
                        'price' => '$499/month (Pro), $999/month (Enterprise)',
                        'description' => 'Cloud-based workflow automation platform',
                    ],
                    [
                        'name' => 'IT Consulting Services',
                        'type' => 'Service',
                        'price' => '$150-250/hour',
                        'description' => 'Custom software development and IT consulting',
                    ],
                    [
                        'name' => 'Training Programs',
                        'type' => 'Service',
                        'price' => '$2,500/session',
                        'description' => 'On-site and remote training for teams',
                    ],
                ],

                // Financial Information
                'annual_revenue' => '$5.2M',
                'monthly_recurring_revenue' => '$320K',
                'average_deal_size' => '$12,500',
                'customer_lifetime_value' => '$48,000',
                'customer_acquisition_cost' => '$8,500',

                // Financial Goals
                'financial_goals' => [
                    'reduce_opex_by' => '15%',
                    'increase_mrr_by' => '25%',
                    'improve_profit_margin_to' => '30%',
                    'reduce_customer_churn_to' => '5%',
                ],

                // Current Expenses & Cost Structure
                'expense_categories' => [
                    'payroll' => '$180K/month',
                    'cloud_infrastructure' => '$25K/month',
                    'software_subscriptions' => '$12K/month',
                    'marketing' => '$35K/month',
                    'office_rent' => '$15K/month',
                    'professional_services' => '$8K/month',
                ],

                // Team Structure
                'departments' => [
                    'engineering' => 18,
                    'sales' => 10,
                    'marketing' => 6,
                    'customer_success' => 5,
                    'operations' => 4,
                    'finance' => 2,
                ],

                // Technology Stack
                'tech_stack' => [
                    'cloud_provider' => 'AWS',
                    'programming_languages' => ['PHP', 'JavaScript', 'Python'],
                    'frameworks' => ['Laravel', 'React', 'Vue.js'],
                    'databases' => ['PostgreSQL', 'Redis', 'MongoDB'],
                    'tools' => ['GitHub', 'Jira', 'Slack', 'Notion'],
                ],

                // Current Pain Points
                'pain_points' => [
                    'High cloud infrastructure costs',
                    'Too many redundant software subscriptions',
                    'Manual expense tracking processes',
                    'Inefficient vendor management',
                    'Lack of cost visibility across departments',
                ],

                // Priorities
                'priorities' => [
                    'Reduce software subscription costs',
                    'Optimize cloud infrastructure spending',
                    'Automate expense categorization',
                    'Negotiate better vendor contracts',
                    'Improve financial forecasting accuracy',
                ],

                // Key Metrics
                'key_metrics' => [
                    'gross_margin' => '68%',
                    'net_margin' => '18%',
                    'burn_rate' => '$85K/month',
                    'runway' => '18 months',
                    'customer_count' => 127,
                    'average_contract_value' => '$5,988/year',
                ],

                // Integration Preferences
                'accounting_system' => 'Xero',
                'expense_management' => 'Expensify',
                'project_management' => 'Jira',
                'communication' => 'Slack',
                'documentation' => 'Notion',

                // Decision Makers
                'decision_makers' => [
                    'ceo' => 'Sarah Johnson',
                    'cfo' => 'Michael Chen',
                    'cto' => 'David Rodriguez',
                ],

                // Budget Authority
                'budget_approval_levels' => [
                    'under_5k' => 'Department heads',
                    '5k_to_25k' => 'CFO approval required',
                    'over_25k' => 'CEO and CFO approval required',
                ],
            ],
        ]);

        $this->command->info("Knowledge base seeded for user: {$user->email}");
    }
}
