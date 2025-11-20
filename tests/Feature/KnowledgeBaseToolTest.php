<?php

use App\Agents\MasterOrchestrator;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Tools\KnowledgeBaseTool;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'email' => 'test@knowledgebase.com',
    ]);

    // Create knowledge base data
    KnowledgeBase::create([
        'user_id' => $this->user->id,
        'context' => [
            'company_name' => 'Test Corp',
            'industry' => 'Technology',
            'employee_count' => 50,
            'annual_revenue' => '$2M',
            'financial_goals' => [
                'reduce_opex_by' => '10%',
                'increase_mrr_by' => '20%',
            ],
            'products' => [
                ['name' => 'Product A', 'price' => '$99/month'],
                ['name' => 'Product B', 'price' => '$199/month'],
            ],
            'priorities' => [
                'Reduce costs',
                'Increase revenue',
            ],
        ],
    ]);
});

it('can retrieve all knowledge base context', function () {
    $tool = new KnowledgeBaseTool($this->user->id);
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute([], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data)->toHaveKey('data')
        ->and($data['data'])->toHaveKey('company_name', 'Test Corp')
        ->and($data['data'])->toHaveKey('industry', 'Technology')
        ->and($data['data'])->toHaveKey('employee_count', 50);
});

it('can filter context by query', function () {
    $tool = new KnowledgeBaseTool($this->user->id);
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute(['query' => 'financial'], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data)->toHaveKey('query', 'financial')
        ->and($data['data'])->toHaveKey('financial_goals')
        ->and($data['data']['financial_goals'])->toHaveKey('reduce_opex_by', '10%');
});

it('can search for company information', function () {
    $tool = new KnowledgeBaseTool($this->user->id);
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute(['query' => 'company'], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data['data'])->toHaveKey('company_name', 'Test Corp');
});

it('can search for products', function () {
    $tool = new KnowledgeBaseTool($this->user->id);
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute(['query' => 'product'], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data['data'])->toHaveKey('products')
        ->and($data['data']['products'])->toHaveCount(2);
});

it('can retrieve user_id from context if not provided in constructor', function () {
    $tool = new KnowledgeBaseTool;
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $context->setState('user_id', $this->user->id);
    $memory = new AgentMemory($agent);

    $result = $tool->execute([], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data['data'])->toHaveKey('company_name', 'Test Corp');
});

it('returns error when user_id is not found', function () {
    $tool = new KnowledgeBaseTool;
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute([], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', false)
        ->and($data)->toHaveKey('error')
        ->and($data['error'])->toContain('User ID not found');
});

it('returns empty data when no knowledge base entries exist', function () {
    $newUser = User::factory()->create();
    $tool = new KnowledgeBaseTool($newUser->id);
    $agent = new MasterOrchestrator;
    $context = new AgentContext('test_session');
    $memory = new AgentMemory($agent);

    $result = $tool->execute([], $context, $memory);
    $data = json_decode($result, true);

    expect($data)->toHaveKey('success', true)
        ->and($data['data'])->toBeEmpty()
        ->and($data)->toHaveKey('message');
});

it('has correct tool definition for LLM', function () {
    $tool = new KnowledgeBaseTool;
    $definition = $tool->definition();

    expect($definition)->toHaveKey('name', 'knowledge_base')
        ->and($definition)->toHaveKey('description')
        ->and($definition)->toHaveKey('parameters')
        ->and($definition['parameters'])->toHaveKey('properties')
        ->and($definition['parameters']['properties'])->toHaveKey('query');
});
