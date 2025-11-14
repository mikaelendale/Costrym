# CostDecomposer

## What it is

- First in a line of 3 agents (cost decomposer,Benchmark agent, CER agent)

- Links product with its contextually related costs

## What it does

- Read `company_context`, `products_list`, and direct costs (`direct_costs_list` or `direct_costs_list_json`).
- Identify core products and major cost drivers.
- ALLOCATE COSTS & ESTIMATE QUANTITIES

## Tools

Tool: GetTotalCostByCategory - Purpose: Returns aggregated spend for a major cost category (e.g., "Cloud & Infrastructure"), Not important here but used to store in memory.

## Output

```json
{
    "summary": "string",
    "product_decompositions": [
        {
            "product_name": "string",
            "associated_direct_costs": [
                {
                    "name": "string",
                    "category": "string",
                    "quantity_required_per_product": "number",
                    "tags": ["Direct", "Variable"]
                }
            ]
        }
    ]
}
```

Notes:
The JSON must be the only content returned.
The quantity_required_per_product field must be populated for every allocated cost.
