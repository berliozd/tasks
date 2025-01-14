<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EditButton from "@/Components/EditButton.vue";
import CheckButton from "@/Components/CheckButton.vue";
import DeleteButton from "@/Components/DeleteButton.vue";
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";

const newTaskLabel = ref('');
const props = defineProps({tasks: Array, todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
let storedTasks = JSON.parse(JSON.stringify(props.tasks));
let watchActive = true;

const toggleCompleted = (task) => {
    if (task.completed_at === null) {
        task.completed_at = new Date();
    } else {
        task.completed_at = null;
    }
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), task).then(
        useStore().setSaved('Saved!')
    )
}
const debouncedSave = debounce(updateTask, 300);

const saveAllTasks = () => {
    props.tasks.forEach(task => {
        if (task.id === null || !task.id) return;
        let storedTask = storedTasks.find(storedTask => storedTask.id === task.id);
        if (JSON.stringify(cleanTask(storedTask)) !== JSON.stringify(cleanTask(task))) {
            debouncedSave(task)
        }
    });
    storedTasks = JSON.parse(JSON.stringify(props.tasks));
}

const removeProperty = (obj, key) => {
    const {[key]: _, ...newObj} = obj;
    return newObj;
}
const cleanTask = (storedTask) => {
    storedTask = JSON.parse(JSON.stringify(storedTask));
    return removeProperty(storedTask, 'editing');
}

const deleteTask = (task, index) => {
    axios.delete(route('tasks.delete', task.id))
        .then(response => {
            props.tasks.splice(index, 1);
        })
}

const addTask = () => {
    if (newTaskLabel.value === '') return;
    watchActive = false;
    props.tasks.push({label: newTaskLabel.value, 'completed_at': null});
    axios.post(route('tasks.store'), {label: newTaskLabel.value})
        .then((response) => {
                props.tasks[props.tasks.length - 1] = response.data;
            }
        ).then(() => {
            newTaskLabel.value = '';
            watchActive = true;
            storedTasks = JSON.parse(JSON.stringify(props.tasks));
        }
    )
}
const editTask = (task) => {
    task.editing = !task.editing
    props.tasks.forEach(tsk => {
        if (tsk.id === task.id) return;
        tsk.editing = false
    });
}

const taskIsLate = (task) => {
    if (task.scheduled_at === undefined) return false;
    if (task.completed_at !== null) return false;
    return format(new Date(task.scheduled_at), 'yyyy-MM-dd') !== format(new Date(), 'yyyy-MM-dd');
}

watch(props.tasks, () => {
    if (watchActive) saveAllTasks();
})
</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Tasks</h2>
        </template>
        <div class="py-12">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="min-h-6 ">
                    <SavedLabel/>
                </div>
                <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200 mb-6">
                    <div class="border border-gray-400 m-4 p-2 flex justify-between align-center items-center gap-2">
                        <input type="text" v-model="newTaskLabel" placeholder="New task label"
                               class="w-full rounded" @keydown.enter="addTask">
                        <SaveButton @click="addTask"/>
                    </div>
                </div>
                <div class="overflow-hidden shadow-lg sm:rounded-lg bg-gray-200">
                    <div v-for="(task, index) in tasks" class="border border-gray-400 m-4"
                         :class="task.completed_at?'bg-gray-300':''" :key="index">
                        <div class="p-2" :class="taskIsLate(task)?'border-t-2 border-red-400':''">
                            <div class="flex justify-between gap-2">
                                <div class="flex">
                                    <CheckButton :checked="task.completed_at !== null"
                                                 @click="toggleCompleted(task)" :enabled="!task.editing"/>
                                </div>
                                <div class="w-full">
                                    <div @click="task.editing = !task.editing" class="cursor-pointer w-full">
                                        {{ task.label }}
                                    </div>
                                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400 underline">
                                        Completed on:{{
                                            format(
                                                task.completed_at,
                                                usePage().props.appLocale === 'en' ? 'MM/dd/yyyy' : 'dd/MM/yyyy'
                                            )
                                        }}
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <div :class="task.completed_at!==null?'invisible':''">
                                        <EditButton @click="editTask(task)"/>
                                    </div>
                                    <DeleteButton @click="deleteTask(task, index)"/>
                                </div>
                            </div>
                            <div :class="task.editing?'':'hidden'" class="m-2">
                                <div>
                                    <div class="text-secondary text-xs">Label :</div>
                                    <textarea v-model="task.label"
                                              class="text-primary-content rounded-md shadow-sm w-full"/>
                                </div>
                                <div>
                                    <div class="text-secondary text-xs">Description :</div>
                                    <textarea v-model="task.description"
                                              class="text-primary-content rounded-md shadow-sm w-full"/>
                                </div>
                                <div class="text-xs text-gray-400 underline">
                                    Scheduled on:{{
                                        format(
                                            task.scheduled_at,
                                            usePage().props.appLocale === 'en' ? 'MM/dd/yyyy' : 'dd/MM/yyyy'
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="min-h-6">
                    <SavedLabel/>
                </div>
                <DebuggingTasks :todayTasks="todayTasks"
                                :lateTasks="lateTasks"
                                :completedTodayTasks="completedTodayTasks"
                />
            </div>
        </div>
    </AppLayout>
</template>
