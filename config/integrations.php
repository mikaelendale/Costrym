<?php

/**
 * Integration Configuration
 *
 * Defines available integrations and their properties
 * Each integration specifies app name, display name, and connection method
 */

return [
    'available' => [
        'xero_accounting_api' => [
            'name' => 'Xero',
            'display_name' => 'Xero',
            'description' => 'Connect your Xero account for accounting and financial management',
            'icon' => 'xero',
            'category' => 'accounting',
            'requires_pipedream' => true,
        ],
        'zoho_books' => [
            'name' => 'Zoho Books',
            'display_name' => 'Zoho Books',
            'description' => 'Connect your Zoho Books account for accounting and invoicing',
            'icon' => 'zoho',
            'category' => 'accounting',
            'requires_pipedream' => true,
        ],
        'quickbooks' => [
            'name' => 'QuickBooks',
            'display_name' => 'QuickBooks Online',
            'description' => 'Connect your QuickBooks account to manage accounting and finances',
            'icon' => 'quickbooks',
            'category' => 'accounting',
            'requires_pipedream' => true,
        ],
        'sevdesk' => [
            'name' => 'Sevdesk',
            'display_name' => 'Sevdesk',
            'description' => 'Connect your Sevdesk account for German accounting and invoicing',
            'icon' => 'sevdesk',
            'category' => 'accounting',
            'requires_pipedream' => true,
        ],
        'expensify' => [
            'name' => 'Expensify',
            'display_name' => 'Expensify',
            'description' => 'Connect your Expensify account for expense tracking and management',
            'icon' => 'expensify',
            'category' => 'expense',
            'requires_pipedream' => true,
        ],
        'gmail' => [
            'name' => 'Gmail',
            'display_name' => 'Gmail',
            'description' => 'Connect your Gmail account to access emails and manage messages',
            'icon' => 'gmail',
            'category' => 'communication',
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
    ],

    /**
     * Integration categories for grouping
     */
    'categories' => [
        'accounting' => 'Accounting',
        'expense' => 'Expense Management',
        'communication' => 'Communication',
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
