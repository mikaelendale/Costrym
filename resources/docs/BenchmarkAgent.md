# Benchmarking Agent

## What it is

- Second in a line of 3 agents (Cost Decomposer → Benchmarking Agent → CER Agent).
- Builds a holistic, bottom-up "should-cost" OPEX model for the company based on its context.

## What it does

- Read `company_context` (product, customer_market, revenue, business_model, location).
- Plan research questions and use the FireCrawler tool extensively to gather real-world benchmarks (e.g., typical team sizes and compensation, CAC, infrastructure needs, G&A, office/facilities, tools, fees).
- Construct a single, minimal should-cost model that allocates percentages of total OPEX across all relevant cost areas.
- Provide a concise justification for each cost area grounded in the FireCrawler research.

## Tools

Tool: FireCrawler — Purpose: Perform required web research to source benchmarks, prices, and references to support the should-cost model.

## Output

```json
{
    "benchmarking_agent_response": {
        "summary": "string",
        "should_cost_model": [
            {
                "cost_area": "string",
                "should_cost_percent_of_opex": "string",
                "justification": "string"
            }
        ]
    }
}
```

Notes:

- The JSON must be the only content returned.
- Every `cost_area` must include a research-backed `justification`.
- `should_cost_percent_of_opex` values should be strings like "12%" and represent an optimized share of total OPEX for that area.
- Use FireCrawler for all factual claims; tailor cost areas to the company's context (e.g., Marketing, Sales, Cloud & Infrastructure, SaaS, Payroll & Compensation, Contractors, Operations, Office & Facilities, Hardware, Payment Fees, Legal & Professional, Insurance, Travel, Support & Success, R&D, D&A, Taxes, Misc/Other).

## Sample

### **Company Context: AuraHome Inc.**

AuraHome Inc. operates in the **Smart Home & Consumer Electronics** industry. Its core mission is to create healthier and more efficient living spaces through intelligent automation. The company designs premium smart home devices in-house, outsources the manufacturing, and develops its own cloud platform and mobile apps.

Their business model is a hybrid, combining revenue from **one-time hardware sales** with **recurring revenue** from a cloud-based subscription service. They also offer professional installation as an optional, one-time service.

### **Products & Services Provided**

- **Aura Thermostat (Hardware):** A smart thermostat with an OLED display designed to optimize home HVAC systems for energy savings and comfort by learning user preferences.
- **Aura Air Purifier (Hardware):** A smart air purifier that uses laser sensors to detect air pollutants and automatically adjusts its operation, connecting to the central cloud platform.
- **Aura Premium Cloud (SaaS/Subscription):** A monthly subscription service that enhances Aura devices with advanced features like AI-driven scheduling, historical data analytics, and voice assistant integrations.
- **Professional Installation Service (Service):** An optional, one-time service where a certified technician installs and configures Aura hardware in a customer's home.

Respone

Agent benchmarking_agent:

```json
{
    "summary": "AuraHome Inc. is a Smart Home and Consumer Electronics company that designs premium smart home devices and offers related services, including a SaaS cloud platform for enhanced functionality. The business operates under a hybrid model, generating revenue from hardware sales, a subscription service, and professional installation. This analysis aims to outline the ideal operational cost structure for AuraHome Inc.",
    "should_cost_model": [
        {
            "cost_area": "Payroll & Compensation",
            "should_cost_percent_of_opex": "35%",
            "justification": "Typical team sizes for smart home companies range from 20 to 50 employees, consisting of R&D, marketing, sales, customer support, and admin roles. A payroll allocation of 30-40% is standard in the technology sector, reflecting the need for skilled personnel in a competitive market."
        },
        {
            "cost_area": "Marketing",
            "should_cost_percent_of_opex": "15%",
            "justification": "For tech companies, a marketing spend of 10-20% of revenue is common, especially when competing in a crowded marketplace. This includes digital advertising, content marketing, and brand awareness strategies crucial for customer acquisition."
        },
        {
            "cost_area": "Cloud & Infrastructure",
            "should_cost_percent_of_opex": "10%",
            "justification": "SaaS models typically involve hosting costs on platforms like AWS or GCP, alongside expenses for maintaining security and data compliance. Cloud costs for technology firms often constitute around 8-12% of total expenses."
        },
        {
            "cost_area": "Sales",
            "should_cost_percent_of_opex": "10%",
            "justification": "Sales costs, including commissions and software tools like CRM, generally make up about 10-15% of OPEX in consumer electronics. This aligns with Gateway norms as sales functions are crucial in a high-touch market."
        },
        {
            "cost_area": "General & Administrative (G&A)",
            "should_cost_percent_of_opex": "15%",
            "justification": "G&A costs in tech firms are usually around 10-20% of total expenses, covering administrative overheads and operational support roles, aligning authoritatively with norms observed at similar tech companies."
        },
        {
            "cost_area": "Contractors & Freelancers",
            "should_cost_percent_of_opex": "5%",
            "justification": "Given the specialized nature of some smart home technology and installations, utilizing contractors can represent 5-10% of expenses. This provides flexibility while managing workforce needs."
        },
        {
            "cost_area": "Software & Subscriptions (SaaS)",
            "should_cost_percent_of_opex": "3%",
            "justification": "Software subscriptions contribute moderately to operational costs, with many tools priced for startups. This area typically sits at 2-5% of total expenditures in tech businesses."
        },
        {
            "cost_area": "Travel & Entertainment",
            "should_cost_percent_of_opex": "2%",
            "justification": "Travel costs typically average 2-3% in tech companies, accounting for client meetings, trade shows, and staff travel. This is a normal range when evaluating necessary business interactions."
        },
        {
            "cost_area": "Hardware & Equipment",
            "should_cost_percent_of_opex": "3%",
            "justification": "Investments in hardware for development and testing purposes, though crucial, are generally limited to about 2-5% of the budget in initial stages, especially in product-based firms."
        },
        {
            "cost_area": "Legal & Professional",
            "should_cost_percent_of_opex": "2%",
            "justification": "Legal and compliance costs represent an essential but smaller fraction of overall expenditures, typically around 2-3%, reflecting consultation with law and finance professionals."
        }
    ]
}
```
