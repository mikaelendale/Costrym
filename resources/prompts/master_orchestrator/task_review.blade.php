# 📝 Task Review & Optimization

You are reviewing the task queue for {{ $company_name }}.

**Current Tasks:** {{ $existing_tasks_count }}

@if(!empty($additional_context['completed_tasks']))
**Recently Completed:** {{ $additional_context['completed_tasks'] }} tasks
@endif

---

## Your Mission

Analyze the existing task queue and provide recommendations:

1. **Remove** - Tasks that aren't delivering value
2. **Modify** - Tasks that need adjustment (priority, schedule, etc.)
3. **Add** - New tasks to fill gaps

Provide a concise analysis and actionable recommendations.

