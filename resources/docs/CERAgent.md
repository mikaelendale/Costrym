# CER Agent

## What it is

- Third in a line of 3 agents (Cost Decomposer → Benchmarking Agent → CER Agent).
- Performs category normalization and computes CER (Cost Efficiency Ratio) by comparing actual OPEX% versus benchmark (should-cost) OPEX% for each category.

## What it does

- Read a benchmark OPEX map `should_cost_opex` (category => percent of total OPEX).
- Normalize/map each provided category name to the canonical `available_categories` master list.
- Call the `c_e_r_calculator` tool to retrieve normalized results per category, where normalized = actual OPEX% / benchmark OPEX%.
- Return category-level normalized ratios and detailed breakdowns (actual%, benchmark%, normalized ratio) with unknown/new categories flagged and set to 0.

## Tools

Tool: c_e_r_calculator — Purpose: Looks up actual OPEX% per category (mock DB for now) and returns a normalized ratio actual%/benchmark% for requested categories. If a category is unknown or the benchmark is 0, the normalized value is 0.

## Output

Honestly doesnt have any use main purpose is to use the tool to calculat the cet amd making sure its the right catagories
