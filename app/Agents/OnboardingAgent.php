<?php

namespace App\Agents;

use Vizra\VizraADK\Agents\BaseLlmAgent;

class OnboardingAgent extends BaseLlmAgent
{
    protected string $name = 'onboarding_agent';

    protected string $description = 'Helps organize and structure company information during onboarding';

    protected string $instructions = 'Rewrite the company info to be concise, well-structured, and professional—no more than 3-4 lines. Summarize key details clearly and directly.
    no additional text or formatting, just the summary. and no emojis. no markdown formatting. just the summary. in a simple and direct way.
    ';

    protected string $model = 'gpt-4o';
}
