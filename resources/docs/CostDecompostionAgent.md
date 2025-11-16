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
    "cost_decomposition_response": {
        "summary": "string",
        "product_decompositions": [
            {
                "product_name": "string",
                "associated_direct_costs": [
                    {
                        "name": "string",
                        "category": "string",
                        "quantity_required_per_product": 0,
                        "tags": ["Direct", "Variable"]
                    }
                ]
            }
        ]
    }
}
```

Notes:
The JSON must be the only content returned.
The quantity_required_per_product field must be populated for every allocated cost.

## Sample

```json
{
    "company_context": "car manufacturing",
    "products_list": ["electric cars", "autoparts", "commercial vehicles", "motorcycles"],
    "direct_costs_list_json": {
        "steel_coils": {
            "category": "Raw Materials",
            "tag": "direct",
            "supplier": "Steel Dynamics Inc.",
            "unit_of_measure": "ton",
            "cost_per_unit": 700,
            "quantity_per_vehicle": 1.2
        },
        "aluminum_alloy": {
            "category": "Raw Materials",
            "tag": "direct",
            "supplier": "Alcoa Corporation",
            "unit_of_measure": "ton",
            "cost_per_unit": 2500,
            "quantity_per_vehicle": 0.5
        },
        "lithium_ion_battery_cells": {
            "category": "Components",
            "tag": "direct",
            "supplier": "CATL",
            "unit_of_measure": "kWh",
            "cost_per_unit": 130,
            "quantity_per_vehicle": 75
        },
        "electric_motor": {
            "category": "Components",
            "tag": "direct",
            "supplier": "Bosch",
            "unit_of_measure": "unit",
            "cost_per_unit": 1800,
            "quantity_per_vehicle": 1
        },
        "semiconductor_chips": {
            "category": "Electronics",
            "tag": "direct",
            "supplier": "TSMC",
            "unit_of_measure": "unit",
            "cost_per_unit": 25,
            "quantity_per_vehicle": 150
        },
        "tires": {
            "category": "Components",
            "tag": "direct",
            "supplier": "Michelin",
            "unit_of_measure": "set",
            "cost_per_unit": 600,
            "quantity_per_vehicle": 1
        },
        "assembly_line_labor": {
            "category": "Labor",
            "tag": "direct",
            "trade": "General Assembly",
            "hourly_wage": 35,
            "hours_per_vehicle": 40
        },
        "welding_technician_labor": {
            "category": "Labor",
            "tag": "direct",
            "trade": "Welding",
            "hourly_wage": 45,
            "hours_per_vehicle": 8
        },
        "paint": {
            "category": "Consumables",
            "tag": "direct",
            "supplier": "PPG Industries",
            "unit_of_measure": "gallon",
            "cost_per_unit": 50,
            "quantity_per_vehicle": 3
        },
        "screws": {
            "category": "Hardware & Equipment",
            "tag": "direct",
            "type": "Hex Bolt M6x1.0",
            "supplier": "Fastenal",
            "unit_of_measure": "1000 units",
            "cost_per_unit": 50,
            "quantity_per_vehicle": 2
        },
        "adhesives_and_sealants": {
            "category": "Consumables",
            "tag": "direct",
            "supplier": "3M",
            "unit_of_measure": "liter",
            "cost_per_unit": 20,
            "quantity_per_vehicle": 5
        },
        "chassis_frame": {
            "category": "Components",
            "tag": "direct",
            "supplier": "Magna International",
            "unit_of_measure": "unit",
            "cost_per_unit": 1500,
            "quantity_per_vehicle": 1
        },
        "wiring_harness": {
            "category": "Electronics",
            "tag": "direct",
            "supplier": "Yazaki Corporation",
            "unit_of_measure": "unit",
            "cost_per_unit": 400,
            "quantity_per_vehicle": 1
        }
    },
    "indirect_costs_list_json": {
        "factory_rent": {
            "category": "Overhead",
            "tag": "indirect",
            "monthly_cost": 150000
        },
        "utilities_factory": {
            "category": "Overhead",
            "tag": "indirect",
            "monthly_cost": 35000
        },
        "production_supervisor_salary": {
            "category": "Labor",
            "tag": "indirect",
            "annual_salary": 80000
        },
        "quality_control_inspector_salary": {
            "category": "Labor",
            "tag": "indirect",
            "annual_salary": 65000
        },
        "machinery_depreciation": {
            "category": "Overhead",
            "tag": "indirect",
            "monthly_depreciation": 50000
        },
        "rd_for_new_models": {
            "category": "R&D",
            "tag": "indirect",
            "annual_budget": 5000000
        },
        "marketing_and_advertising": {
            "category": "Marketing",
            "tag": "indirect",
            "campaign": "New EV Sedan Launch",
            "quarterly_budget": 1200000
        },
        "freight_and_logistics_inbound": {
            "category": "Logistics",
            "tag": "indirect",
            "description": "Shipping of raw materials and components to the factory",
            "monthly_cost": 75000
        }
    }
}
```

Response

```json
{
    "summary": "9 direct costs have been allocated across 4 products, based on logical estimations of material and labor requirements for manufacturing electric cars and their components.",
    "product_decompositions": [
        {
            "product_name": "electric cars",
            "associated_direct_costs": [
                {
                    "name": "steel_coils",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 1.2,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "aluminum_alloy",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 0.5,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "lithium_ion_battery_cells",
                    "category": "Components",
                    "quantity_required_per_product": 75,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "electric_motor",
                    "category": "Components",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "semiconductor_chips",
                    "category": "Electronics",
                    "quantity_required_per_product": 150,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "tires",
                    "category": "Components",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "assembly_line_labor",
                    "category": "Labor",
                    "quantity_required_per_product": 40,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "welding_technician_labor",
                    "category": "Labor",
                    "quantity_required_per_product": 8,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "paint",
                    "category": "Consumables",
                    "quantity_required_per_product": 3,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "screws",
                    "category": "Hardware & Equipment",
                    "quantity_required_per_product": 2,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "adhesives_and_sealants",
                    "category": "Consumables",
                    "quantity_required_per_product": 5,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "chassis_frame",
                    "category": "Components",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "wiring_harness",
                    "category": "Electronics",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                }
            ]
        },
        {
            "product_name": "autoparts",
            "associated_direct_costs": [
                {
                    "name": "steel_coils",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 1.2,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "aluminum_alloy",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 0.5,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "electric_motor",
                    "category": "Components",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "tires",
                    "category": "Components",
                    "quantity_required_per_product": 1,
                    "tags": ["Direct", "Variable"]
                }
            ]
        },
        {
            "product_name": "commercial vehicles",
            "associated_direct_costs": [
                {
                    "name": "steel_coils",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 1.2,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "aluminum_alloy",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 0.5,
                    "tags": ["Direct", "Variable"]
                }
            ]
        },
        {
            "product_name": "motorcycles",
            "associated_direct_costs": [
                {
                    "name": "steel_coils",

                    "category": "Raw Materials",
                    "quantity_required_per_product": 1.2,
                    "tags": ["Direct", "Variable"]
                },
                {
                    "name": "aluminum_alloy",
                    "category": "Raw Materials",
                    "quantity_required_per_product": 0.5,
                    "tags": ["Direct", "Variable"]
                }
            ]
        }
    ]
}
```
