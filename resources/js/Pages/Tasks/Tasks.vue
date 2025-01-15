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
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import CompleteTaskModal from "@/Pages/Tasks/Partials/CompleteTaskModal.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";

const newTaskLabel = ref('');
const props = defineProps({todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
const lastSaved = ref(new Date());
let storedReactiveTasks = null;
let watchActive = false;
const reactiveTasks = reactive({});
const belowList = ref(null);

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
            scrollTo(belowList)
        }
    )
}

const taskIsLate = (task) => {
    if (task.scheduled_at === undefined) return false;
    if (task.completed_at !== null) return false;
    return format(new Date(task.scheduled_at), 'yyyy-MM-dd') !== format(new Date(), 'yyyy-MM-dd');
}

const refreshTasks = () => {
    axios.get(route('tasks.index'))
        .then(response => {
            reactiveTasks.value = response.data;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
        }).then(() => {
        watchActive = true
    })
}
refreshTasks();

watch(reactiveTasks, () => {
    if (watchActive) saveReactiveTasks();
})

const formatDateTime = (date) => {
    if (date === undefined) {
        return '';
    }
    return format(
        date,
        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm:ss' : 'dd/MM/yyyy HH:mm:ss'
    )
}
</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Tasks</h2>
        </template>
        <div class="">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="min-h-6 ">
                    <SavedLabel/>
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
                <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-2">

                    <div v-for="(task, index) in reactiveTasks.value" class="border border-gray-400 m-4"
                         :class="task.completed_at?'bg-gray-300':''" :key="index">
                        <div class="p-2" :class="taskIsLate(task)?'border-t-2 border-red-400':''">
                            <div class="flex justify-between gap-2">
                                <div class="flex">
                                    <CompleteTaskModal :task="task"/>
                                </div>
                                <div class="w-full">
                                    <div @click="task.editing = !task.editing" class="cursor-pointer w-full">
                                        {{ task.label }}
                                    </div>
                                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400 underline">
                                        Completed on:{{ formatDateTime(task.completed_at) }}
                                    </div>
                                </div>
                                <DeleteModal :task="task" @deleted="refreshTasks()"/>
                            </div>
                            <div :class="task.editing?'':'hidden'" class="m-2">
                                <div>
                                    <div class="text-secondary text-xs">Label :</div>
                                    <textarea v-model="task.label"
                                              class="text-primary-content rounded-md shadow-sm w-full"
                                              :disabled="task.completed_at!==null"/>
                                </div>
                                <div>
                                    <div class="text-secondary text-xs">Description :</div>
                                    <textarea v-model="task.description"
                                              class="text-primary-content rounded-md shadow-sm w-full"
                                              :disabled="task.completed_at!==null"/>
                                </div>
                                <div class="text-xs text-gray-400 underline">
                                    Scheduled on:{{ formatDateTime(task.scheduled_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="min-h-6" ref="belowList">
                    <SavedLabel/>
                </div>
                <DebuggingTasks :todayTasks="todayTasks" :lateTasks="lateTasks"
                                :completedTodayTasks="completedTodayTasks"
                                :class="usePage().props.environment === 'production'?'hidden':''"/>
            </div>
        </div>
    </AppLayout>
</template>
