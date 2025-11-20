<?php

/**
 * Pipedream Integrations Configuration
 *
 * Defines which integrations are available in the system.
 * Only components for these integrations will be synced from Pipedream.
 *
 * Structure:
 * - 'app_id': The Pipedream app identifier (slug)
 * - 'name': Human-readable name
 * - 'category': Integration category (accounting, expense, communication, etc.)
 * - 'required': Whether this integration is required/essential
 * - 'pipedream_app_id': Optional Pipedream app ID if different from app_id
 */

return [
    // Essential Accounting Integrations
    'xero_accounting_api' => [
        'name' => 'Xero',
        'app_id' => 'xero_accounting_api',
        'category' => 'accounting',
        'required' => true,
    ],
    'zoho_books' => [
        'name' => 'Zoho Books',
        'app_id' => 'zoho_books',
        'category' => 'accounting',
        'required' => true,
    ],
    'quickbooks' => [
        'name' => 'QuickBooks Online',
        'app_id' => 'quickbooks',
        'category' => 'accounting',
        'required' => true,
    ],
    'sevdesk' => [
        'name' => 'Sevdesk',
        'app_id' => 'sevdesk',
        'category' => 'accounting',
        'required' => true,
    ],

    // Expense Management
    'expensify' => [
        'name' => 'Expensify',
        'app_id' => 'expensify',
        'category' => 'expense',
        'required' => false,
    ],

    // Communication & Productivity
    'gmail' => [
        'name' => 'Gmail',
        'app_id' => 'gmail',
        'category' => 'communication',
        'required' => false,
    ],
    'notion' => [
        'name' => 'Notion',
        'app_id' => 'notion',
        'category' => 'productivity',
        'required' => false,
    ],
];
