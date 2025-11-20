# 💰 Cost Analysis Request

Perform a comprehensive cost analysis for {{ $company_name }}.

@if(!empty($additional_context['focus_area']))
**Focus Area:** {{ $additional_context['focus_area'] }}
@endif

---

## Business Context

- **Industry:** {{ $industry }}
- **Financial Goals:** 
@if(!empty($financial_goals))
@foreach($financial_goals as $goal => $target)
  - {{ ucfirst(str_replace('_', ' ', $goal)) }}: {{ $target }}
@endforeach
@endif

---

## Analysis Required

Provide:
1. **Key Cost Drivers** - What's driving costs up?
2. **Optimization Opportunities** - Where can we save?
3. **Recommended Actions** - Specific steps to take
4. **Estimated Impact** - How much can be saved?

Be specific, actionable, and data-driven.

