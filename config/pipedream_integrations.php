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
 * - 'category': Integration category (accounting, payment, communication, etc.)
 * - 'required': Whether this integration is required/essential
 * - 'pipedream_app_id': Optional Pipedream app ID if different from app_id
 */

return [
    // Essential Accounting Integrations
    'quickbooks' => [
        'name' => 'QuickBooks Online',
        'app_id' => 'quickbooks',
        'category' => 'accounting',
        'required' => true,
    ],
    'xero_accounting_api' => [
        'name' => 'Xero',
        'app_id' => 'xero_accounting_api',
        'category' => 'accounting',
        'required' => true,
    ],
    'zoho' => [
        'name' => 'Zoho Books',
        'app_id' => 'zoho_books',
        'category' => 'accounting',
        'required' => true,
    ],
    'freshbooks' => [
        'name' => 'FreshBooks',
        'app_id' => 'freshbooks',
        'category' => 'accounting',
        'required' => true,
    ],
    'wave' => [
        'name' => 'Wave Accounting',
        'app_id' => 'wave',
        'category' => 'accounting',
        'required' => true,
    ],
    'sage_intacct' => [
        'name' => 'Sage Intacct',
        'app_id' => 'sage_intacct',
        'category' => 'accounting',
        'required' => true,
    ],
    'netsuite' => [
        'name' => 'NetSuite',
        'app_id' => 'netsuite',
        'category' => 'accounting',
        'required' => true,
    ],
    'odoo' => [
        'name' => 'Odoo Accounting',
        'app_id' => 'odoo',
        'category' => 'accounting',
        'required' => true,
    ],

    // Additional Payment Integrations
    'stripe' => [
        'name' => 'Stripe',
        'app_id' => 'stripe',
        'category' => 'payment',
        'required' => false,
    ],
    'paypal' => [
        'name' => 'PayPal',
        'app_id' => 'paypal',
        'category' => 'payment',
        'required' => false,
    ],
    'square' => [
        'name' => 'Square',
        'app_id' => 'square',
        'category' => 'payment',
        'required' => false,
    ],
    'paddle' => [
        'name' => 'Paddle',
        'app_id' => 'paddle',
        'category' => 'payment',
        'required' => false,
    ],
    'adyen' => [
        'name' => 'Adyen',
        'app_id' => 'adyen',
        'category' => 'payment',
        'required' => false,
    ],
    'checkout_com' => [
        'name' => 'Checkout.com',
        'app_id' => 'checkout_com',
        'category' => 'payment',
        'required' => false,
    ],

    // Optional Communication/Productivity Integrations
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
