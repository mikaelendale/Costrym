# Approval Agent

## What it is

- The last agent after automation step
- An Approval & Notification Specialist AI — the final checkpoint between automated workflow plans and human decision-makers.
- Expert at summarizing complex, technical workflows into clear, concise, trustworthy notifications for quick Approve/Reject decisions.

## What it does

- Ingests a complete Automation Workflow Plan (from the Automation Planning Agent).
- Analyzes the plan: `task_name`, plan summary, autonomy level, and all workflow steps (paying special attention to `expected_impact`, `risk`, and any financial/sensitive actions).
- Synthesizes a high‑level, human‑readable summary that answers:
    1. What is the core objective?
    2. What is the total expected value?
    3. What is the most significant risk (and mitigation, if any)?
- Formulates the Notification Payload:
    - `notification_title`: short, attention‑grabbing; use emojis (e.g., 🚨 anomalies, 💡 optimizations); include `task_name`.
    - `notification_body`: friendly, confident, value‑led copy in 1–2 short paragraphs.
    - `notification_update_summary`: brief update since last communication (if any), in positive and clear tone.
- Assembles final JSON containing the notification payload and compact details for execution systems.

## Tools

- None (reads the workflow plan and produces a notification JSON). If needed, can be extended to call comms systems in downstream pipelines.

## Output

```json
{
    "approval_agent_response": {
        "approval_requests": [
            {
                "task_name": "string",
                "notification_payload": {
                    "notification_title": "string",
                    "notification_body": "string",
                    "notification_update_summary": "string"
                },
                "step_details": [
                    {
                        "step": "number",
                        "what_to_do": "string",
                        "why_recommended": "string",
                        "expected_impact": "string",
                        "tool_dependencies": "string",
                        "risk": "string"
                    }
                ]
            }
        ]
    }
}
```

Notes:

- The JSON must be the only content returned.
- Keep copy human, direct, and value‑led; avoid jargon. Use emojis sparingly and purposefully in the title.
- Highlight financial transactions or sensitive data as risk callouts in the summary/body when applicable.
- Ensure `details` reflect the most critical step(s) or the overall action distilled from the plan (not a full dump).
- Align field names with the planning output (e.g., `task_name`) and preserve consistent category naming across agents.

Response

```json
{
    "approval_agent_response": {
        "approval_requests": [
            {
                "task_name": "Streamline Customer Support Operations by Reducing Support Staff",
                "notification_payload": {
                    "notification_title": "🚫 Important Decision on Support Staffing!",
                    "notification_body": "We have carefully evaluated the proposed adjustment to our customer support staffing levels. While the idea was aimed at streamlining operations, it has been deemed unsuitable due to potential negative impacts on customer satisfaction and retention. The recommendation is to maintain our current staffing levels for enhanced service quality, which is essential for fostering customer loyalty.",
                    "notification_update_summary": "This plan is ready for your step-by-step review and approval."
                },
                "step_details": []
            },
            {
                "task_name": "Implement Shipping Cost Optimization",
                "notification_payload": {
                    "notification_title": "📦 Optimize Shipping Costs for Better Service!",
                    "notification_body": "We're set to reassess our shipping cost optimization strategies to ensure a balance between savings and customer satisfaction. Each step is designed to provide insights without risking customer loyalty. Your careful review and approval of the upcoming steps will help us implement effective solutions.",
                    "notification_update_summary": "This plan is ready for your step-by-step review and approval."
                },
                "step_details": [
                    {
                        "step": 1,
                        "what_to_do": "Evaluate current shipping practices and identify areas of cost reduction.",
                        "why_recommended": "To gain insights on potential savings without sacrificing service quality.",
                        "expected_impact": "A clearer understanding of cost-saving opportunities while minimizing risks.",
                        "tool_dependencies": "ShippingPlatform",
                        "risk": "Moderate: Insufficient data may lead to poor decision-making."
                    },
                    {
                        "step": 2,
                        "what_to_do": "Develop alternative shipping strategies that balance cost and customer satisfaction.",
                        "why_recommended": "To propose solutions that can achieve cost savings while mitigating churn risks.",
                        "expected_impact": "A set of recommendations for shipping practices that align with customer expectations.",
                        "tool_dependencies": "Analytics",
                        "risk": "Moderate: Recommendations may not be accepted by stakeholders."
                    },
                    {
                        "step": 3,
                        "what_to_do": "Prepare a detailed proposal for the alternative shipping strategy.",
                        "why_recommended": "To present a well-founded case for the new strategy to management.",
                        "expected_impact": "Increased chances of approval for a balanced shipping approach.",
                        "tool_dependencies": "Docs",
                        "risk": "Low: Proposal quality depends on the analysis performed."
                    },
                    {
                        "step": 4,
                        "what_to_do": "Schedule a presentation of the proposal to leadership.",
                        "why_recommended": "To ensure the proposal is delivered and discussed in a timely manner.",
                        "expected_impact": "A structured meeting with leadership to validate the proposal.",
                        "tool_dependencies": "Calendar",
                        "risk": "Low: Potential scheduling conflicts."
                    }
                ]
            },
            {
                "task_name": "Relocate Budget from Low-Performing Meta Ads",
                "notification_payload": {
                    "notification_title": "💰 Enhance ROI by Relocating Ad Budgets!",
                    "notification_body": "We’re initiating a plan to reallocate the advertising budget from low-performing Meta ads to more effective platforms like Google Ads. This strategy aims to optimize our return on investment while ensuring that every dollar spent contributes to our success. Your approval of the steps will guide this beneficial transition.",
                    "notification_update_summary": "This plan is ready for your step-by-step review and approval."
                },
                "step_details": [
                    {
                        "step": 1,
                        "what_to_do": "Analyze performance of current Meta ads.",
                        "why_recommended": "To determine reasons for low performance and establish a baseline for budget reallocation.",
                        "expected_impact": "A comprehensive understanding of ad effectiveness and areas for improvement.",
                        "tool_dependencies": "Analytics",
                        "risk": "Low: Data is readily available."
                    },
                    {
                        "step": 2,
                        "what_to_do": "Calculate new budget allocation based on performance insights.",
                        "why_recommended": "To ensure the reallocated funds are directed towards high-performing channels.",
                        "expected_impact": "A defensible budget reallocation plan grounded in data.",
                        "tool_dependencies": "Finance",
                        "risk": "Low: The strategy is data-driven."
                    },
                    {
                        "step": 3,
                        "what_to_do": "Reallocate advertising budget to Google Ads.",
                        "why_recommended": "To invest in a platform that has demonstrated higher returns.",
                        "expected_impact": "Improved overall advertising efficiency and ROI.",
                        "tool_dependencies": "Finance",
                        "risk": "Low: The action is well-justified."
                    },
                    {
                        "step": 4,
                        "what_to_do": "Monitor the performance of newly allocated ads on Google.",
                        "why_recommended": "To ensure the effectiveness of the new advertising strategy.",
                        "expected_impact": "Continuous improvement in ad performance and ROI.",
                        "tool_dependencies": "Analytics",
                        "risk": "Low: Ongoing monitoring will facilitate timely adjustments."
                    }
                ]
            },
            {
                "task_name": "Optimize Shipping Strategy for Cost Reduction",
                "notification_payload": {
                    "notification_title": "❗ Reassess Shipping Strategies for Optimal Efficiency!",
                    "notification_body": "While there's potential for savings in our shipping strategy, we've identified significant risks of customer churn. This plan emphasizes the need to re-evaluate alternative strategies that balance costs and customer satisfaction. Your insight and approval are vital to ensure we make informed decisions.",
                    "notification_update_summary": "This plan is ready for your step-by-step review and approval."
                },
                "step_details": []
            }
        ]
    }
}
```
