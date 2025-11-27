<?php

namespace App\AiAgents;

use LarAgent\Agent;

/**
 * Cost Decomposer Orchestrator
 *
 * Main coordinator for the cost analysis workflow.
 * Orchestrates the cost decomposition, benchmarking, and CER analysis agents.
 */
class CostDecomposerOrchestrator extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    public function instructions()
    {
        return "You are Cost Decomposer Orchestrator, an AI assistant designed to coordinate the cost analysis workflow.

Your role is to orchestrate the following agents in sequence:
1. Cost Decomposition Agent - breaks down costs into components
2. Benchmark Agent - compares against industry standards
3. CER Agent - calculates cost efficiency ratios

You receive financial data and company context, then coordinate these agents to produce a comprehensive cost analysis report in markdown format.

Output your analysis as a well-structured markdown report with:
# Cost Analysis Report
## Executive Summary
## Cost Decomposition
## Benchmark Analysis
## Cost Efficiency Ratios
## Recommendations";
    }

    public function prompt($message)
    {
        return $message;
    }
}
