<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {computed, ref, watch} from 'vue';
import {addDays, format, parseISO} from 'date-fns';
import {Link} from '@inertiajs/vue3';
import Task from '@/Pages/Tasks/Partials/Task.vue';
import {useCompactMode} from '@/Composables/compactMode.js';

const {compactMode, toggleCompactMode} = useCompactMode();

const period = ref('day'); // day|week|month
const loading = ref(false);
const tasks = ref([]);

const allFlags = ref([]);
const allRecurrences = ref([]);
axios.get(route('flags.index')).then(response => allFlags.value = response.data);
axios.get(route('recurrences.index')).then(response => allRecurrences.value = response.data);

const setActiveTask = (task) => {
    tasks.value.forEach(t => {
        t.editing = t.id === task.id ? !t.editing : false;
    });
};

const yesterdayYmd = () => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return format(d, 'yyyy-MM-dd');
};

const endDate = ref(yesterdayYmd());
const maxEndDate = computed(() => yesterdayYmd());

const periodDays = computed(() => {
    if (period.value === 'week') return 7;
    if (period.value === 'month') return 30;
    return 1;
});

const shiftEndDate = (deltaDays) => {
    const current = parseISO(endDate.value);
    const next = addDays(current, deltaDays);
    const nextYmd = format(next, 'yyyy-MM-dd');
    endDate.value = nextYmd > maxEndDate.value ? maxEndDate.value : nextYmd;
};

const fetchCompleted = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('tasks.completed'), {
            params: {
                period: period.value,
                end_date: endDate.value,
            },
        });
        tasks.value = response.data ?? [];
    } finally {
        loading.value = false;
    }
};

watch([period, endDate], fetchCompleted, {immediate: true});
</script>

<template>
    <AppLayout title="Completed tasks">
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl leading-tight text-slate-900">Completed tasks</h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('tasks')" class="text-sm text-gray-500 hover:text-gray-700">
                        Tasks
                    </Link>
                    <Link :href="route('future-tasks')" class="text-sm text-gray-500 hover:text-gray-700">
                        Future
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="surface-card p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1">
                        <button
                            class="btn btn-ghost btn-sm btn-square tooltip tooltip-bottom"
                            type="button"
                            data-tip="Previous"
                            aria-label="Previous"
                            @click="shiftEndDate(-periodDays)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-chevron-left">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                        <button
                            class="btn btn-ghost btn-sm btn-square tooltip tooltip-bottom disabled:opacity-40"
                            type="button"
                            data-tip="Next"
                            aria-label="Next"
                            :disabled="endDate >= maxEndDate"
                            @click="shiftEndDate(periodDays)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-chevron-right">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                        <button class="px-3 py-1 text-sm font-medium transition"
                                :class="period === 'day' ? 'bg-brand-navy text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                @click="period = 'day'">
                            Day
                        </button>
                        <button class="px-3 py-1 text-sm font-medium border-l border-gray-200 transition"
                                :class="period === 'week' ? 'bg-brand-navy text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                @click="period = 'week'">
                            Week
                        </button>
                        <button class="px-3 py-1 text-sm font-medium border-l border-gray-200 transition"
                                :class="period === 'month' ? 'bg-brand-navy text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                @click="period = 'month'">
                            Month
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-sm text-gray-600">End date</div>
                        <input type="date" v-model="endDate" :max="maxEndDate"
                               class="rounded-lg border-gray-300 text-sm focus:border-brand-accent focus:ring-brand-accent transition"/>
                    </div>

                    <div v-if="loading" class="text-sm text-gray-400">Loading...</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-1 px-1 mt-4 mb-2">
                <button type="button" @click="toggleCompactMode"
                        :title="compactMode ? 'Switch to comfortable view' : 'Switch to compact view'"
                        class="btn btn-ghost btn-xs gap-1 normal-case"
                        :class="compactMode ? 'text-brand-accent-dark' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                    <span class="hidden sm:inline">Compact mode</span>
                </button>
            </div>

            <div class="surface-card overflow-hidden">
                <div v-if="!loading && !tasks.length" class="p-8 text-center text-sm text-gray-400">
                    No completed tasks.
                </div>
                <div v-else class="flex flex-col p-2" :class="compactMode ? 'gap-1.5' : 'gap-2'">
                    <Task v-for="task in tasks" :key="task.id" :task="task"
                          @toggle-editing="setActiveTask" :all-flags="allFlags"
                          :all-recurrences="allRecurrences" :readonly="true" :compact="compactMode"
                          :strike-through-completed="false"/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
