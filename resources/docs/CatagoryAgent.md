# What it does

- Get expenses
- Catagories them based on the the 18 master catagories
- Tags them as direct, Indirect, Variable and Fixed

**Master Category List:**

- **Marketing:** (e.g., Google Ads, Facebook Ads, Mailchimp, SEO tools)
- **Sales:** (e.g., Salesforce, HubSpot, ZoomInfo, Sales Commissions)
- **Cloud & Infrastructure:** (e.g., AWS, GCP, Azure, Vercel, DigitalOcean)
- **Software & Subscriptions (SaaS):** (e.g., Slack, Notion, Figma, Office 365)
- **Payroll & Compensation:** (e.g., Gusto, Rippling, Salaries, Bonuses)
- **Contractors & Freelancers:** (e.g., Upwork, Agencies, Consultants)
- **Operations:** (e.g., Logistics, Shipping, Warehousing, Manufacturing services, Procurement)
- **Office & Facilities:** (e.g., WeWork, Rent, Utilities, Office Supplies)
- **Hardware & Equipment:** (e.g., Apple, Dell, Server purchases)
- **Financial / Payment Fees:** (e.g., Stripe Fees, Bank Fees, PayPal Fees)
- **Legal & Professional:** (e.g., Law Firms, Accounting Services, Consultants)
- **Insurance:** (e.g., General liability, Cyber insurance, Health insurance contributions, Workers' comp)
- **Travel & Entertainment:** (e.g., Flights, Hotels, Meals, Team events)
- **Customer Support & Success:** (e.g., Zendesk, Intercom, Support team salaries)
- **Research & Development (R&D) / Product Development:** (e.g., Labs, Prototyping, Research tools, Testing, Experiments)
- **Depreciation & Amortization:** (e.g., Fixed asset depreciation, Capitalized software amortization)
- **Taxes:** (e.g., Income tax, VAT / GST, Property tax, Payroll taxes)
- **Miscellaneous / Other:** (e.g., Unclassified spend, One-off items)

## Output

```json
{
    "categorized_response": {
        "summary": "string",
        "expenses": [
            {
                "name": "string",
                "tags": ["string"],
                "category": "string"
            },
            {
                "name": "string",
                "tags": ["string"],
                "category": "string"
            }
        ]
    }
}
```
