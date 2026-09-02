<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {reactive, ref, watch} from "vue";
import SavedLabel from "@/Components/SavedLabel.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";
import {useStore} from "@/Composables/store.js";
import debounce from "lodash/debounce";

const reactiveTasks = reactive({});
let storedReactiveTasks = null;
let watchActive = false;

const allFlags = ref([]);
const allRecurrences = ref([]);

const refreshTasks = () => {
    axios.get(route('tasks.future'))
        .then(response => {
            reactiveTasks.value = response.data;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
        }).then(() => {
            watchActive = true;
        });
}
refreshTasks();

axios.get(route('flags.index')).then(response => allFlags.value = response.data);
axios.get(route('recurrences.index')).then(response => allRecurrences.value = response.data);

const cleanTask = (task) => {
    task = JSON.parse(JSON.stringify(task));
    const {id, label, description, completed_at, scheduled_at, recurrence_id} = task;
    return {id, label, description, completed_at, scheduled_at, recurrence_id};
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), {
        label: task.label,
        description: task.description,
        completed_at: task.completed_at,
        scheduled_at: task.scheduled_at,
        recurrence_id: task.recurrence_id,
    }).then((response) => {
        if (response?.data) {
            Object.assign(task, {
                updated_at: response.data.updated_at,
                scheduled_at: response.data.scheduled_at,
                completed_at: response.data.completed_at,
                recurrence_id: response.data.recurrence_id,
                label: response.data.label,
                description: response.data.description,
            });
        }
        useStore().setSaved('Saved!');
    });
}
const debouncedSave = debounce(updateTask, 1500);

const saveReactiveTasks = () => {
    reactiveTasks.value.forEach(task => {
        if (!task.id) return;
        const storedTask = storedReactiveTasks.find(t => t.id === task.id);
        if (JSON.stringify(cleanTask(storedTask)) !== JSON.stringify(cleanTask(task))) {
            debouncedSave(task);
        }
    });
    storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
}

watch(reactiveTasks, () => {
    if (watchActive) saveReactiveTasks();
});

const setActiveTask = (task) => {
    (reactiveTasks.value ?? []).forEach(t => {
        t.editing = t.id === task.id ? !t.editing : false;
    });
}
</script>

<template>
    <AppLayout title="Future tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Future tasks</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="min-h-6">
                <SavedLabel/>
            </div>

            <div class="surface-card mb-2 overflow-hidden">
                <div class="flex flex-col gap-2 p-2">
                    <Task v-for="task in (reactiveTasks.value ?? [])" :key="task.id" :task="task"
                          @deleted="refreshTasks()" @changed="refreshTasks()"
                          @toggle-editing="setActiveTask" :all-flags="allFlags"
                          :all-recurrences="allRecurrences"/>
                    <div v-if="!(reactiveTasks.value ?? []).length" class="px-4 py-10 text-center text-sm text-gray-400">
                        No future tasks scheduled.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
