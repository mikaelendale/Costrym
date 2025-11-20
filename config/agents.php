<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available AI Agents
    |--------------------------------------------------------------------------
    |
    | This configuration defines all available AI agents in the system.
    | These agents can be dynamically selected and orchestrated by the
    | MasterOrchestrator for task execution.
    |
    */

    'available_agents' => [
        // ========================================
        // DATA INGESTION & ORGANIZATION
        // ========================================
        // 'integration_ingestor' => [
        //     'class' => \App\Agents\IntegrationIngestor::class,
        //     'name' => 'Integration Ingestor',
        //     'description' => 'Fetches and ingests data from external integrations like Xero, QuickBooks, etc.',
        //     'capabilities' => [
        //         'data_ingestion',
        //         'api_integration',
        //         'fetch_data',
        //         'xero',
        //         'quickbooks',
        //         'accounting_data',
        //         'financial_data',
        //     ],
        //     'pipeline_stage' => 'ingestion',
        //     'enabled' => true,
        // ],

        // 'categorizer_agent' => [
        //     'class' => \App\Agents\CategorizerAgent::class,
        //     'name' => 'Categorizer Agent',
        //     'description' => 'Categorizes financial transactions into predefined categories',
        //     'capabilities' => [
        //         'categorization',
        //         'classification',
        //         'expense_categorization',
        //         'transaction_categorization',
        //         'organize',
        //     ],
        //     'pipeline_stage' => 'organization',
        //     'enabled' => true,
        // ],

        // ========================================
        // DEEP FINANCIAL ANALYSIS (Pipeline Agents)
        // ========================================
        'baseline_agent' => [
            'class' => \App\Agents\BaseLineAgent::class,
            'name' => 'Baseline Agent',
            'description' => 'Analyzes company spending patterns to define baselines, identify recurring costs, and major expense drivers',
            'capabilities' => [
                'baseline_analysis',
                'spending_patterns',
                'recurring_costs',
                'expense_drivers',
                'historical_analysis',
                'trend_detection',
            ],
            'pipeline_stage' => 'analysis',
            'pipeline_order' => 1,
            'enabled' => true,
        ],

        'cost_decomposition_agent' => [
            'class' => \App\Agents\CostDecompositionAgent::class,
            'name' => 'Cost Decomposition Agent',
            'description' => 'Breaks products into their direct cost components and estimates required quantities',
            'capabilities' => [
                'cost_breakdown',
                'product_decomposition',
                'component_analysis',
                'quantity_estimation',
                'direct_cost_analysis',
            ],
            'pipeline_stage' => 'analysis',
            'pipeline_order' => 2,
            'enabled' => true,
        ],

        'benchmarking_agent' => [
            'class' => \App\Agents\BenchmarkingAgent::class,
            'name' => 'Benchmarking Agent',
            'description' => 'Builds research-backed should-cost OPEX model using industry data and web research',
            'capabilities' => [
                'benchmarking',
                'should_cost_modeling',
                'industry_research',
                'opex_analysis',
                'competitive_analysis',
                'market_research',
            ],
            'pipeline_stage' => 'analysis',
            'pipeline_order' => 3,
            'enabled' => true,
        ],

        'cer_agent' => [
            'class' => \App\Agents\CERAgent::class,
            'name' => 'CER Agent',
            'description' => 'Computes cost efficiency ratios: actual OPEX% vs benchmark per category',
            'capabilities' => [
                'efficiency_ratios',
                'cer_calculation',
                'benchmark_comparison',
                'performance_metrics',
                'variance_analysis',
            ],
            'pipeline_stage' => 'analysis',
            'pipeline_order' => 4,
            'enabled' => true,
        ],

        'cost_value_aligner_agent' => [
            'class' => \App\Agents\CostValueAlignerAgent::class,
            'name' => 'Cost-Value Aligner Agent',
            'description' => 'Orchestrates value mapping and smart reduction to align costs with business value',
            'capabilities' => [
                'value_alignment',
                'strategic_analysis',
                'value_mapping',
                'smart_reduction',
                'cost_benefit_analysis',
                'second_order_effects',
            ],
            'sub_agents' => [
                \App\Agents\ValueMapper::class,
                \App\Agents\SmartReducer::class,
            ],
            'pipeline_stage' => 'optimization',
            'pipeline_order' => 5,
            'enabled' => true,
        ],

        // ========================================
        // EXECUTION & REPORTING
        // ========================================
        'cost_optimizer_agent' => [
            'class' => \App\Agents\CostOptomizerAgent\CostOptomizerAgent::class,
            'name' => 'Cost Optimizer Agent',
            'description' => 'Analyzes costs and identifies optimization opportunities',
            'capabilities' => [
                'cost_optimization',
                'cost_reduction',
                'savings',
                'expense_reduction',
                'budget_optimization',
                'analyze_costs',
                'find_savings',
            ],
            'pipeline_stage' => 'execution',
            'enabled' => true,
        ],

        'notion_agent' => [
            'class' => \App\Agents\NotionAgent::class,
            'name' => 'Notion Agent',
            'description' => 'Creates documentation, reports, and manages Notion content',
            'capabilities' => [
                'notion',
                'documentation',
                'notes',
                'reporting',
                'create_reports',
                'write_documentation',
                'manage_content',
            ],
            'pipeline_stage' => 'reporting',
            'enabled' => true,
        ],

        'automation_orchestrator' => [
            'class' => \App\Agents\AutomationOrcastrator::class,
            'name' => 'Automation Orchestrator',
            'description' => 'Plans and executes automated workflows',
            'capabilities' => [
                'automation',
                'workflow_automation',
                'process_automation',
                'scheduling',
                'orchestration',
            ],
            'pipeline_stage' => 'execution',
            'enabled' => true,
        ],

        // ========================================
        // SUPPORT AGENTS
        // ========================================
        'onboarding_agent' => [
            'class' => \App\Agents\OnboardingAgent::class,
            'name' => 'Onboarding Agent',
            'description' => 'Helps with user onboarding and initial system setup',
            'capabilities' => [
                'onboarding',
                'user_setup',
                'initial_setup',
                'configuration',
                'guide_user',
            ],
            'enabled' => false, // Disabled for task execution
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Orchestrator Settings
    |--------------------------------------------------------------------------
    */

    'master_orchestrator' => [
        'enabled' => true,
        'class' => \App\Agents\MasterOrchestrator::class,
        'description' => 'Central coordinator that can delegate to any available agent',
        'max_delegation_depth' => 3, // Prevent infinite delegation loops
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Orchestrator Executor
    |--------------------------------------------------------------------------
    |
    | Specialized agent for task execution. This agent focuses specifically on
    | executing approved tasks by coordinating and delegating to specialized agents.
    |
    */

    'master_orchestrator_executor' => [
        'enabled' => true,
        'class' => \App\Agents\MasterOrchestratorExecutor::class,
        'description' => 'Specialized executor for approved tasks, coordinates and delegates to specialized agents',
        'use_for_task_execution' => true, // Use this agent for executing approved tasks
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Execution Settings
    |--------------------------------------------------------------------------
    */

    'task_execution' => [
        'use_master_orchestrator' => true, // Use MasterOrchestrator as executor
        'direct_agent_execution' => false, // Allow direct agent execution
        'max_execution_time' => 300, // 5 minutes
        'max_retries' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Pipelines (Bus Chains)
    |--------------------------------------------------------------------------
    |
    | Define sequential agent pipelines where each agent's output becomes
    | the next agent's input. Used for complex multi-stage analysis.
    |
    */

    'pipelines' => [
        'deep_cost_analysis' => [
            'name' => 'Deep Cost Analysis Pipeline',
            'description' => 'Complete financial analysis from baseline through optimization',
            'stages' => [
                [
                    'agent' => 'baseline_agent',
                    'description' => 'Establish spending baselines',
                    'output_key' => 'baseline_data',
                ],
                [
                    'agent' => 'cost_decomposition_agent',
                    'description' => 'Break down cost components',
                    'output_key' => 'decomposition_data',
                ],
                [
                    'agent' => 'benchmarking_agent',
                    'description' => 'Build should-cost model',
                    'output_key' => 'benchmark_data',
                ],
                [
                    'agent' => 'cer_agent',
                    'description' => 'Calculate efficiency ratios',
                    'output_key' => 'cer_data',
                ],
                [
                    'agent' => 'cost_value_aligner_agent',
                    'description' => 'Align costs with value',
                    'output_key' => 'alignment_data',
                ],
            ],
            'enabled' => true,
        ],

        'quick_cost_analysis' => [
            'name' => 'Quick Cost Analysis',
            'description' => 'Fast cost analysis for immediate opportunities',
            'stages' => [
                [
                    'agent' => 'baseline_agent',
                    'description' => 'Analyze current spending',
                    'output_key' => 'baseline_data',
                ],
                [
                    'agent' => 'cost_optimizer_agent',
                    'description' => 'Find quick wins',
                    'output_key' => 'optimization_data',
                ],
            ],
            'enabled' => true,
        ],

        'simple_test_pipeline' => [
            'name' => 'Simple Test Pipeline',
            'description' => 'Test pipeline with basic agents',
            'stages' => [
                [
                    'agent' => 'cost_optimizer_agent',
                    'description' => 'Analyze costs and find savings',
                    'output_key' => 'analysis_data',
                ],
                [
                    'agent' => 'notion_agent',
                    'description' => 'Create report',
                    'output_key' => 'report_data',
                ],
            ],
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Execution Settings
    |--------------------------------------------------------------------------
    */

    'pipeline_execution' => [
        'pass_full_context' => true, // Pass all previous outputs to each agent
        'fail_on_error' => false, // Continue pipeline even if one agent fails
        'log_intermediate_results' => true,
        'save_to_automations' => true, // Save each stage as automation MD
    ],
];
