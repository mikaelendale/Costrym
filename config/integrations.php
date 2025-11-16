<?php

/**
 * Integration Configuration
 * 
 * Defines available integrations and their properties
 * Each integration specifies app name, display name, and connection method
 */

return [
    'available' => [
        'gmail' => [
            'name' => 'Gmail',
            'display_name' => 'Gmail',
            'description' => 'Connect your Gmail account to access emails and manage messages',
            'icon' => 'gmail',
            'category' => 'communication',
            'requires_pipedream' => true,
        ],
        'slack' => [
            'name' => 'Slack',
            'display_name' => 'Slack',
            'description' => 'Connect your Slack workspace to send messages and manage channels',
            'icon' => 'slack',
            'category' => 'communication',
            'requires_pipedream' => true,
        ],
        'github' => [
            'name' => 'GitHub',
            'display_name' => 'GitHub',
            'description' => 'Connect your GitHub account to manage repositories and issues',
            'icon' => 'github',
            'category' => 'development',
            'requires_pipedream' => true,
        ],
        'stripe' => [
            'name' => 'Stripe',
            'display_name' => 'Stripe',
            'description' => 'Connect your Stripe account to manage payments and subscriptions',
            'icon' => 'stripe',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
        'quickbooks' => [
            'name' => 'Quickbooks',
            'display_name' => 'QuickBooks',
            'description' => 'Connect your QuickBooks account to manage accounting and finances',
            'icon' => 'quickbooks',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
        'xero' => [
            'name' => 'Xero',
            'display_name' => 'Xero',
            'description' => 'Connect your Xero account for accounting and financial management',
            'icon' => 'xero',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
        'zoho' => [
            'name' => 'Zoho Books',
            'display_name' => 'Zoho Books',
            'description' => 'Connect your Zoho Books account for accounting and invoicing',
            'icon' => 'zoho',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
        'paypal' => [
            'name' => 'PayPal',
            'display_name' => 'PayPal',
            'description' => 'Connect your PayPal account to manage payments and transactions',
            'icon' => 'paypal',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
        'notion' => [
            'name' => 'Notion',
            'display_name' => 'Notion',
            'description' => 'Connect your Notion workspace to access pages and databases',
            'icon' => 'notion',
            'category' => 'productivity',
            'requires_pipedream' => true,
        ],
        'plaid' => [
            'name' => 'Plaid',
            'display_name' => 'Plaid',
            'description' => 'Connect your bank accounts through Plaid for financial data access',
            'icon' => 'plaid',
            'category' => 'finance',
            'requires_pipedream' => true,
        ],
    ],

    /**
     * Integration categories for grouping
     */
    'categories' => [
        'communication' => 'Communication',
        'development' => 'Development',
        'finance' => 'Finance & Accounting',
        'productivity' => 'Productivity',
    ],

    /**
     * Default integration settings
     */
    'defaults' => [
        'requires_pipedream' => true,
        'auto_sync' => false,
        'sync_interval' => 3600, // seconds
    ],
];

