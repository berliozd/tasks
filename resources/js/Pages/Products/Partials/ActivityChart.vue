<script setup>
import {onBeforeUnmount, onMounted, ref, watch} from 'vue';
import {Chart} from 'chart.js/auto';
import {format} from 'date-fns';

const days = ref(30);
const canvas = ref(null);
const loading = ref(true);
let chart = null;

const load = () => {
    loading.value = true;
    axios.get(route('prospect-actions.activity'), {params: {days: days.value}}).then(response => {
        const labels = response.data.map(row => format(new Date(row.date), 'MMM d'));
        const counts = response.data.map(row => row.count);

        if (chart) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = counts;
            chart.update();
            return;
        }

        chart = new Chart(canvas.value, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Actions completed',
                    data: counts,
                    backgroundColor: '#158749',
                    borderRadius: 4,
                    maxBarThickness: 24,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {legend: {display: false}},
                scales: {
                    x: {grid: {display: false}},
                    y: {beginAtZero: true, ticks: {precision: 0}},
                },
            },
        });
    }).finally(() => loading.value = false);
}

watch(days, load);
onMounted(load);
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="surface-card p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-medium text-gray-900">Actions completed over time</div>
            <select v-model.number="days"
                    class="h-8 px-2 text-xs rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                <option :value="7">Last 7 days</option>
                <option :value="30">Last 30 days</option>
                <option :value="90">Last 90 days</option>
            </select>
        </div>
        <div class="h-64 relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">
                Loading…
            </div>
            <canvas ref="canvas"/>
        </div>
    </div>
</template>
