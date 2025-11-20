<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Expense Categories
            [
                'name' => 'Office & Administration',
                'type' => 'expense',
                'color' => '#3B82F6',
                'icon' => 'briefcase',
                'ai_keywords' => ['office', 'supplies', 'stationery', 'printer', 'paper', 'desk', 'chair', 'admin'],
            ],
            [
                'name' => 'Software & Subscriptions',
                'type' => 'expense',
                'color' => '#8B5CF6',
                'icon' => 'code',
                'ai_keywords' => ['software', 'saas', 'subscription', 'license', 'cloud', 'hosting', 'domain', 'api'],
            ],
            [
                'name' => 'Marketing & Advertising',
                'type' => 'expense',
                'color' => '#EC4899',
                'icon' => 'megaphone',
                'ai_keywords' => ['marketing', 'advertising', 'ads', 'campaign', 'promotion', 'seo', 'sem', 'social media'],
            ],
            [
                'name' => 'Travel & Transportation',
                'type' => 'expense',
                'color' => '#10B981',
                'icon' => 'plane',
                'ai_keywords' => ['travel', 'flight', 'hotel', 'transportation', 'taxi', 'uber', 'rental car', 'mileage'],
            ],
            [
                'name' => 'Meals & Entertainment',
                'type' => 'expense',
                'color' => '#F59E0B',
                'icon' => 'utensils',
                'ai_keywords' => ['meal', 'restaurant', 'lunch', 'dinner', 'catering', 'coffee', 'entertainment'],
            ],
            [
                'name' => 'Utilities',
                'type' => 'expense',
                'color' => '#6366F1',
                'icon' => 'lightbulb',
                'ai_keywords' => ['utility', 'electricity', 'water', 'gas', 'internet', 'phone', 'mobile'],
            ],
            [
                'name' => 'Professional Services',
                'type' => 'expense',
                'color' => '#06B6D4',
                'icon' => 'user-tie',
                'ai_keywords' => ['consultant', 'lawyer', 'accountant', 'professional', 'service', 'freelancer', 'contractor'],
            ],
            [
                'name' => 'Rent & Facilities',
                'type' => 'expense',
                'color' => '#EF4444',
                'icon' => 'building',
                'ai_keywords' => ['rent', 'lease', 'facility', 'coworking', 'office space', 'property'],
            ],
            [
                'name' => 'Insurance',
                'type' => 'expense',
                'color' => '#14B8A6',
                'icon' => 'shield',
                'ai_keywords' => ['insurance', 'policy', 'coverage', 'premium', 'liability', 'health insurance'],
            ],
            [
                'name' => 'Payroll & Benefits',
                'type' => 'expense',
                'color' => '#A855F7',
                'icon' => 'users',
                'ai_keywords' => ['salary', 'payroll', 'wages', 'benefits', 'bonus', 'commission', 'employee'],
            ],
            [
                'name' => 'Taxes & Fees',
                'type' => 'expense',
                'color' => '#F97316',
                'icon' => 'receipt',
                'ai_keywords' => ['tax', 'vat', 'gst', 'fee', 'penalty', 'government', 'duty'],
            ],
            [
                'name' => 'Equipment & Hardware',
                'type' => 'expense',
                'color' => '#84CC16',
                'icon' => 'laptop',
                'ai_keywords' => ['equipment', 'hardware', 'computer', 'laptop', 'monitor', 'server', 'device'],
            ],
            [
                'name' => 'Bank Charges & Fees',
                'type' => 'expense',
                'color' => '#DC2626',
                'icon' => 'credit-card',
                'ai_keywords' => ['bank', 'charge', 'fee', 'transaction', 'wire', 'atm', 'overdraft'],
            ],
            [
                'name' => 'Miscellaneous',
                'type' => 'expense',
                'color' => '#64748B',
                'icon' => 'ellipsis',
                'ai_keywords' => ['misc', 'other', 'miscellaneous', 'various', 'general'],
            ],

            // Income Categories
            [
                'name' => 'Sales Revenue',
                'type' => 'income',
                'color' => '#10B981',
                'icon' => 'dollar-sign',
                'ai_keywords' => ['sales', 'revenue', 'income', 'payment', 'invoice', 'customer'],
            ],
            [
                'name' => 'Service Revenue',
                'type' => 'income',
                'color' => '#3B82F6',
                'icon' => 'briefcase',
                'ai_keywords' => ['service', 'consulting', 'contract', 'project', 'professional'],
            ],
            [
                'name' => 'Interest Income',
                'type' => 'income',
                'color' => '#8B5CF6',
                'icon' => 'chart-line',
                'ai_keywords' => ['interest', 'savings', 'investment', 'dividend', 'return'],
            ],
            [
                'name' => 'Other Income',
                'type' => 'income',
                'color' => '#64748B',
                'icon' => 'plus',
                'ai_keywords' => ['other', 'misc', 'refund', 'reimbursement', 'credit'],
            ],
        ];

        foreach ($categories as $category) {
            FinancialCategory::create([
                'user_id' => null, // System category
                'name' => $category['name'],
                'type' => $category['type'],
                'color' => $category['color'],
                'icon' => $category['icon'],
                'ai_keywords' => $category['ai_keywords'],
            ]);
        }
    }
}
