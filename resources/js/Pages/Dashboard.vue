<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from '@inertiajs/vue3';
import {format} from 'date-fns';
import FlagSwatches from '@/Components/FlagSwatches.vue';

const props = defineProps({
    todayTasks: {type: Array, default: () => []},
    lateTasks: {type: Array, default: () => []},
    completedTodayTasks: {type: Array, default: () => []},
    prospection: {
        type: Object,
        default: () => ({directories_count: 0, prospects_count: 0, won_count: 0, status_counts: {}}),
    },
});

const STATUS_LABELS = {
    planned: 'planned', sent: 'sent', replied: 'replied', bounced: 'bounced',
    no_response: 'no response', lost: 'lost',
};
const STATUS_COLORS = {
    replied: 'bg-blue-50 text-blue-700',
    bounced: 'bg-red-50 text-red-700',
    no_response: 'bg-red-50 text-red-700',
    lost: 'bg-red-50 text-red-700',
    sent: 'bg-brand-accent/10 text-brand-accent-dark',
    planned: 'bg-gray-100 text-gray-600',
};
const STATUS_ORDER = ['sent', 'replied', 'lost', 'bounced', 'no_response', 'planned'];

const statusFlags = () => STATUS_ORDER
    .map(status => ({status, count: props.prospection.status_counts?.[status] ?? 0}))
    .filter(s => s.count > 0)
    .map(s => ({...s, label: `${s.count} ${STATUS_LABELS[s.status]}`, colorClass: STATUS_COLORS[s.status]}));

const formatTime = (date) => date ? format(new Date(date), 'HH:mm') : '';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Dashboard</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-6">

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ todayTasks.length }}</div>
                    <div class="text-xs text-gray-500">Tasks due today</div>
                </div>
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold" :class="lateTasks.length ? 'text-red-600' : 'text-slate-900'">
                        {{ lateTasks.length }}
                    </div>
                    <div class="text-xs text-gray-500">Overdue tasks</div>
                </div>
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ completedTodayTasks.length }}</div>
                    <div class="text-xs text-gray-500">Completed today</div>
                </div>
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ prospection.directories_count }}</div>
                    <div class="text-xs text-gray-500">Directories</div>
                </div>
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold text-slate-900">{{ prospection.prospects_count }}</div>
                    <div class="text-xs text-gray-500">Prospects</div>
                </div>
                <div class="surface-card p-4">
                    <div class="text-2xl font-semibold text-blue-700">{{ prospection.won_count }}</div>
                    <div class="text-xs text-gray-500">Won</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <div class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Tasks</div>
                        <Link :href="route('tasks')" class="text-xs font-medium text-brand-navy hover:underline">
                            View all →
                        </Link>
                    </div>

                    <div v-if="!lateTasks.length && !todayTasks.length" class="p-8 text-center text-sm text-gray-400">
                        Nothing due — you're all caught up.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="task in lateTasks" :key="'late-' + task.id"
                             class="flex items-center gap-3 px-4 py-3">
                            <span class="shrink-0 rounded-full bg-red-50 text-red-700 text-xs font-semibold px-2 py-1">
                                Overdue
                            </span>
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ task.label }}</span>
                            <FlagSwatches :flags="task.flags" size-class="w-3 h-3"/>
                        </div>
                        <div v-for="task in todayTasks" :key="'today-' + task.id"
                             class="flex items-center gap-3 px-4 py-3">
                            <span class="shrink-0 text-xs text-gray-400 w-12 tabular-nums">
                                {{ formatTime(task.scheduled_at) }}
                            </span>
                            <span class="text-sm text-gray-900 flex-1 min-w-0 truncate">{{ task.label }}</span>
                            <FlagSwatches :flags="task.flags" size-class="w-3 h-3"/>
                        </div>
                    </div>
                </div>

                <div class="surface-card overflow-hidden">
                    <div class="p-4 flex items-center justify-between border-b border-gray-100">
                        <div class="text-sm font-medium text-gray-900">Prospection</div>
                        <Link :href="route('directories')" class="text-xs font-medium text-brand-navy hover:underline">
                            View directories →
                        </Link>
                    </div>

                    <div class="p-4 flex flex-col gap-4">
                        <div v-if="!prospection.prospects_count" class="text-center text-sm text-gray-400 py-4">
                            No prospects yet.
                        </div>
                        <template v-else>
                            <div class="flex flex-wrap gap-1">
                                <span v-if="prospection.won_count"
                                      class="rounded-full text-xs font-semibold px-2 py-1 bg-blue-50 text-blue-700 border border-blue-600">
                                    {{ prospection.won_count }} won
                                </span>
                                <span v-for="flag in statusFlags()" :key="flag.status"
                                      class="rounded-full text-xs font-semibold px-2 py-1" :class="flag.colorClass">
                                    {{ flag.label }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ prospection.prospects_count }} prospect{{ prospection.prospects_count === 1 ? '' : 's' }}
                                across {{ prospection.directories_count }}
                                director{{ prospection.directories_count === 1 ? 'y' : 'ies' }}
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
