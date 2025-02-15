<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {reactive, ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";

const newTaskLabel = ref('');
const props = defineProps({todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
const lastSaved = ref(new Date());
const reactiveTasks = reactive({});
const belowList = ref(null);
const progress = ref(null);
let storedReactiveTasks = null;
let watchActive = false;

const scrollTo = (view) => {
    view.value?.scrollIntoView({behavior: 'smooth'})
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), task).then(
        () => {
            useStore().setSaved('Saved!');
            lastSaved.value = new Date();
        }
    )
}
const debouncedSave = debounce(updateTask, 300);

const saveReactiveTasks = () => {
    reactiveTasks.value.forEach(task => {
        if (task.id === null || !task.id) return;
        let storedTask = storedReactiveTasks.find(storedTask => storedTask.id === task.id);
        if (JSON.stringify(cleanTask(storedTask)) !== JSON.stringify(cleanTask(task))) {
            debouncedSave(task)
        }
    });
    storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
    calculateProgress()
}

const removeProperty = (obj, key) => {
    const {[key]: _, ...newObj} = obj;
    return newObj;
}
const cleanTask = (storedTask) => {
    storedTask = JSON.parse(JSON.stringify(storedTask));
    return removeProperty(storedTask, 'editing');
}

const addTask = () => {
    if (newTaskLabel.value === '') return;
    watchActive = false;
    reactiveTasks.value.push({label: newTaskLabel.value, 'completed_at': null});
    axios.post(route('tasks.store'), {label: newTaskLabel.value})
        .then((response) => {
                reactiveTasks.value[reactiveTasks.value.length - 1] = response.data;
            }
        ).then(() => {
            newTaskLabel.value = '';
            watchActive = true;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
            calculateProgress()
            scrollTo(belowList)
        }
    )
}

const refreshTasks = () => {
    axios.get(route('tasks.index'))
        .then(response => {
            reactiveTasks.value = response.data;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
        }).then(() => {
        watchActive = true
        calculateProgress()
    })
}
refreshTasks();

watch(reactiveTasks, () => {
    if (watchActive) saveReactiveTasks();
})

const calculateProgress = () => {
    let completedTasks = 0;
    let unCompletedTasks = 0;
    reactiveTasks.value.forEach(task => {
        if (task.completed_at === null) {
            unCompletedTasks++;
        } else {
            completedTasks++;
        }
    });
    progress.value = Math.round((completedTasks / (completedTasks + unCompletedTasks)) * 100);
}


const dispatchEvent = () => {
    console.log('dispatching event');
    axios.get(route('event.index'));

}

const allFlags = ref([]);
const getAllFlags = () => {
    axios.get(route('flags.index'))
        .then(response => {
            allFlags.value = response.data;
        });
}
getAllFlags();
</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Tasks</h2>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="min-h-6 ">
                <SavedLabel/>
                <button @click="dispatchEvent" class="rounded bg-gray-500 m-2 hidden">DISP</button>
            </div>
            <div class="text-xs text-gray-400 flex justify-end pr-2">
                Last saved on {{
                    format(
                        lastSaved,
                        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm:ss' : 'dd/MM/yyyy HH:mm:ss'
                    )
                }}
            </div>
            <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-6">
                <div class="border border-gray-400 m-4 p-2 flex justify-between align-center items-center gap-2">
                    <input type="text" v-model="newTaskLabel" placeholder="New task label"
                           class="w-full rounded" @keydown.enter="addTask">
                    <SaveButton @click="addTask"/>
                </div>
            </div>
            <div class="shadow-lg sm:rounded-lg bg-gray-200 my-6 px-2" v-if="!isNaN(progress) && progress > 0">
                <progress class="my-4 progress w-full" :value="progress" max="100">50%</progress>
            </div>
            <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-2">
                <template v-for="task in reactiveTasks.value">
                    <Task :task="task" @deleted="refreshTasks()" @changed="refreshTasks()" :all-flags="allFlags"/>
                </template>
            </div>
            <div class="min-h-6" ref="belowList">
                <SavedLabel/>
            </div>
            <DebuggingTasks :todayTasks="todayTasks" :lateTasks="lateTasks"
                            :completedTodayTasks="completedTodayTasks"
                            :class="usePage().props.environment === 'production'?'hidden':''"/>
        </div>
    </AppLayout>
</template>
