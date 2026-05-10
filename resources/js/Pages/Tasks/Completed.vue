<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {ref, watch} from 'vue';
import {format} from 'date-fns';
import {usePage} from '@inertiajs/vue3';

const period = ref('day'); // day|week|month
const loading = ref(false);
const tasks = ref([]);

const yesterdayYmd = () => {
    const d = new Date();
    d.setDate(d.getDate() - 1);
    return format(d, 'yyyy-MM-dd');
};

const endDate = ref(yesterdayYmd());

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
            <h2 class="font-semibold text-xl leading-tight">Completed tasks</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4 mt-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex rounded border border-gray-300 overflow-hidden">
                        <button class="px-3 py-1 text-sm"
                                :class="period === 'day' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700'"
                                @click="period = 'day'">
                            Day
                        </button>
                        <button class="px-3 py-1 text-sm border-l border-gray-300"
                                :class="period === 'week' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700'"
                                @click="period = 'week'">
                            Week
                        </button>
                        <button class="px-3 py-1 text-sm border-l border-gray-300"
                                :class="period === 'month' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700'"
                                @click="period = 'month'">
                            Month
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-sm text-gray-600">End date</div>
                        <input type="date" v-model="endDate" class="rounded border-gray-300 text-sm"/>
                    </div>

                    <div v-if="loading" class="text-sm text-gray-400">Loading...</div>
                </div>
            </div>

            <div class="bg-gray-200 shadow sm:rounded-lg mt-4">
                <div v-if="!loading && !tasks.length" class="p-4 text-sm text-gray-500">
                    No completed tasks.
                </div>
                <div v-for="task in tasks" :key="task.id" class="border-b border-gray-300 p-3 flex justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm text-gray-900 truncate">{{ task.label }}</div>
                        <div class="mt-1 flex gap-2">
                            <div v-for="flag in (task.flags ?? [])" :key="flag.id"
                                 class="w-4 h-4 border border-gray-700"
                                 :style="{ 'background-color': flag.color }"
                                 :title="flag.name"/>
                        </div>
                    </div>
                    <div class="shrink-0 text-xs text-gray-500">
                        {{ task.completed_at ? formatDateTime(task.completed_at) : '' }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

