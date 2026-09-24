<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {computed, ref, watch} from 'vue';
import {addDays, format, parseISO} from 'date-fns';
import {Link, usePage} from '@inertiajs/vue3';
import FlagSwatches from '@/Components/FlagSwatches.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const period = ref('day'); // day|week|month
const loading = ref(false);
const tasks = ref([]);

const showDetail = ref(false);
const selectedTask = ref(null);

const openDetail = (task) => {
    selectedTask.value = task;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
    selectedTask.value = null;
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

const formatDateTime = (date) => {
    if (!date) return '';
    return format(
        new Date(date),
        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm:ss' : 'dd/MM/yyyy HH:mm:ss'
    );
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

            <div class="surface-card mt-4 overflow-hidden">
                <div v-if="!loading && !tasks.length" class="p-8 text-center text-sm text-gray-400">
                    No completed tasks.
                </div>
                <div v-else class="divide-y divide-gray-100">
                    <div v-for="task in tasks" :key="task.id"
                         class="p-3 flex items-center justify-between gap-4 cursor-pointer hover:bg-brand-surface transition"
                         @click="openDetail(task)">
                        <div class="min-w-0 flex items-center gap-1.5">
                            <div class="text-sm text-gray-900 truncate">{{ task.label }}</div>
                            <a v-if="(task.links ?? []).length" :href="task.links[0].url" target="_blank" rel="noopener"
                               @click.stop :title="task.links[0].url"
                               class="shrink-0 text-gray-400 hover:text-brand-accent-dark transition">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="lucide lucide-link">
                                    <path d="M9 17H7A5 5 0 0 1 7 7h2"/>
                                    <path d="M15 7h2a5 5 0 1 1 0 10h-2"/>
                                    <line x1="8" x2="16" y1="12" y2="12"/>
                                </svg>
                            </a>
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            <div class="w-24 flex justify-end">
                                <FlagSwatches :flags="task.flags" size-class="w-4 h-4" gap-class="gap-2"/>
                            </div>
                            <div class="w-32 text-xs text-gray-500 text-right">
                                {{ task.completed_at ? formatDateTime(task.completed_at) : '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showDetail" @close="closeDetail">
            <div v-if="selectedTask" class="p-6 flex flex-col gap-4 max-h-[85vh] overflow-y-auto">
                <div class="text-lg font-medium text-gray-900">{{ selectedTask.label }}</div>

                <FlagSwatches v-if="(selectedTask.flags ?? []).length" :flags="selectedTask.flags"
                              size-class="w-4 h-4" gap-class="gap-2"/>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-gray-500">
                    <div v-if="selectedTask.scheduled_at">
                        Scheduled: {{ formatDateTime(selectedTask.scheduled_at) }}
                    </div>
                    <div v-if="selectedTask.completed_at">
                        Completed: {{ formatDateTime(selectedTask.completed_at) }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-medium text-gray-500 mb-1">Description</div>
                    <div v-if="selectedTask.description" class="text-sm text-gray-700 whitespace-pre-wrap">
                        {{ selectedTask.description }}
                    </div>
                    <div v-else class="text-sm text-gray-400">No description.</div>
                </div>

                <div v-if="(selectedTask.links ?? []).length" class="flex flex-col gap-1">
                    <div class="text-xs font-medium text-gray-500 mb-1">Links</div>
                    <a v-for="link in selectedTask.links" :key="link.id" :href="link.url" target="_blank" rel="noopener"
                       class="text-sm text-brand-accent-dark hover:text-brand-accent hover:underline truncate">
                        {{ link.label || link.url }}
                    </a>
                </div>

                <div class="flex justify-end pt-2">
                    <SecondaryButton @click="closeDetail">Close</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
