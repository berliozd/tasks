<x-mail::message>
# Your daily task report

Hi {{ $userName }},

## Completed today ({{ $completedGroups->sum(fn ($group) => $group['tasks']->count()) }})

@forelse ($completedGroups as $group)
@if ($group['color'])
<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:{{ $group['color'] }};margin-right:4px;"></span>
@endif
**{{ $group['label'] }}**

@foreach ($group['tasks'] as $task)
- {{ $task->label }}
@endforeach

@empty
Nothing completed today.
@endforelse

## To do tomorrow ({{ $dueTomorrowGroups->sum(fn ($group) => $group['tasks']->count()) }})

@forelse ($dueTomorrowGroups as $group)
@if ($group['color'])
<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:{{ $group['color'] }};margin-right:4px;"></span>
@endif
**{{ $group['label'] }}**

@foreach ($group['tasks'] as $task)
- {{ $task->label }}
@endforeach

@empty
Nothing scheduled for tomorrow.
@endforelse

<x-mail::subcopy>
This is your daily task report from {{ $appName }}.
</x-mail::subcopy>
</x-mail::message>
