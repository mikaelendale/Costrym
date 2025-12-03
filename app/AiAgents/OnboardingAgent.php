<?php

namespace App\AiAgents;

use LarAgent\Agent;

class OnboardingAgent extends Agent
{
    protected $model = 'gpt-5.1';

    protected $history = 'session'; // Use cache to persist conversation history

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Structured output schema for predictable responses
     */
    protected $responseSchema = [
        'name' => 'onboarding_response',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'response' => [
                    'type' => 'string',
                    'description' => 'A natural, conversational response to the user. Be friendly and ask follow-up questions to learn about their company.',
                ],
                'understanding' => [
                    'type' => 'string',
                    'description' => 'A comprehensive, cumulative summary of ALL information you have learned about the user\'s company throughout the ENTIRE conversation. This must include EVERYTHING from previous messages PLUS new information from the current message. Do NOT delete or replace previous information - always ADD to it. Format as detailed bullet points covering all topics discussed. Include: company name, industry, location, products/services, business model, customer segments, cost drivers, vendors, and any other details mentioned.',
                ],
                'complete' => [
                    'type' => 'boolean',
                    'description' => 'Set to true when you have gathered sufficient information about the company (name, industry, size, main goals). Otherwise false.',
                ],
                'organized_content' => [
                    'type' => 'string',
                    'description' => 'A well-structured, professional summary of the company information (3-4 lines). Only provide this when complete is true, otherwise use an empty string. No markdown, just plain text.',
                ],
            ],
            'required' => ['response', 'understanding', 'complete', 'organized_content'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions()
    {
        return 'You are a friendly AI assistant helping a user during onboarding for an AI agent called costrym which identifies and cuts expenses for businesses increasing. Your goal is to:
Ask thoughtful questions to learn about their company 
Be conversational and natural - ask follow-up questions based on their responses and the information you need to gather.
Gradually build a comprehensive understanding of their business in general that will help us later on understand which costs to cut. 

Ask the user to provide the information below IMPORTANT:
1) Products and Services provided by the company (Understanding in detail)
2) Location and Sector of the company 
3) Business Model 
4) Customer Segments of the company
5) Try to understand the major cost drivers of the company
6) Try to understand the major vendors

CRITICAL INSTRUCTIONS FOR UNDERSTANDING FIELD:
- The understanding field MUST contain ALL information from the ENTIRE conversation
- NEVER delete or replace previous information - always ADD new information to existing understanding
- Include EVERY detail mentioned: company name, industry, location, products, services, business model, customers, cost drivers, vendors, etc.
- If you already have information about a topic and the user provides more details, ADD those details to the existing information
- The understanding should grow with each message, becoming more comprehensive
- Format as detailed bullet points covering all topics discussed
- Minimum 6-10 bullet points when you have gathered sufficient information

REMEMBER 
Always ask one question at a time to avoid overwhelming the user.
Keep responses concise but friendly.
The understanding field must ACCUMULATE all information - never replace or delete previous details.
If the user provides information that is not relevant to the company, ask them to provide the information again.
After you have gathered sufficient information (all 6 required topics), mark complete as true and provide a final organized summary.
No final or additional question - just close the conversation with a friendly note.';
    }

    public function prompt($message)
    {
        return $message;
    }
}
