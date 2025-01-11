<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EditButton from "@/Components/EditButton.vue";
import CheckButton from "@/Components/CheckButton.vue";
import axios from 'axios';
import {ref, watch} from "vue";
import debounce from "lodash/debounce";
import DeleteButton from "@/Components/DeleteButton.vue";
import SaveButton from "@/Components/SaveButton.vue";

const newTaskLabel = ref('');

const props = defineProps({tasks: Array})
const toggleDescription = (task) => {
    task.deployed = !task.deployed;
}
const editTask = (task) => {
    task.editing = !task.editing;
}
const toggleCompleted = (task) => {
    if (task.completed_at === null) {
        task.completed_at = new Date();
    } else {
        task.completed_at = null;
    }
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), task)
}

const debouncedSave = debounce(updateTask, 300);

let storedTasks = JSON.parse(JSON.stringify(props.tasks));

function saveAllTasks() {
    props.tasks.forEach(task => {
        if (task.id === null || !task.id) {
            return;
        }
        let storedTask = storedTasks.find(storedTask => storedTask.id === task.id);
        storedTask = cleanTask(JSON.parse(JSON.stringify(storedTask)));
        let taskCopy = cleanTask(JSON.parse(JSON.stringify(task)));
        if (JSON.stringify(storedTask) !== JSON.stringify(taskCopy)) {
            debouncedSave(task)
        }
    });
    storedTasks = JSON.parse(JSON.stringify(props.tasks));
}

let watchActive = true;
watch(props.tasks, () => {
    if (watchActive) {
        saveAllTasks();
    }
})

const removeProperty = (obj, key) => {
    const {[key]: _, ...newObj} = obj;
    return newObj;
}
const cleanTask = (storedTask) => {
    storedTask = removeProperty(storedTask, 'editing');
    return removeProperty(storedTask, 'deployed');
}

const deleteTask = (task, index) => {
    axios.delete(route('tasks.delete', task.id))
        .then(response => {
            props.tasks.splice(index, 1);
        })
}

const addTask = () => {
    if (newTaskLabel.value === '') {
        return;
    }
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
</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">
                Tasks
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-100 overflow-hidden shadow-xl sm:rounded-lg ">

                    <div class="">

                        <div class="border-2 m-4 p-2 flex justify-between align-center items-center gap-2">
                            <input type="text" v-model="newTaskLabel" placeholder="New task label"
                                   class="w-full rounded">
                            <SaveButton @click="addTask"/>
                        </div>
                        <div v-for="(task, index) in tasks" class="border border-gray-400 m-4 p-2"
                             :class="task.completed_at?'bg-gray-200':''" :key="index">
                            <div class="flex justify-between gap-2">
                                <div class="flex">
                                    <div>
                                        <CheckButton :checked="task.completed_at !== null"
                                                     @click="toggleCompleted(task)" :enabled="!task.editing"/>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <div @click="toggleDescription(task)" class="cursor-pointer w-full">
                                        {{ task.label }}
                                    </div>
                                    <div :class="task.deployed || task.editing?'':'hidden'"
                                         class="text-xs text-gray-400">
                                        <div :class="task.editing?'hidden':''">{{ task.description }}</div>
                                    </div>
                                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400 underline">
                                        Completed on:{{ task.completed_at }}
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <div :class="task.completed_at!==null?'invisible':''">
                                        <EditButton @click="editTask(task)"/>
                                    </div>
                                    <div>
                                        <DeleteButton @click="deleteTask(task, index)"/>
                                    </div>
                                </div>
                            </div>
                            <div class="m-2">
                                <div :class="task.editing?'':'hidden'">
                                    <div class="text-secondary text-xs">Label :</div>
                                    <textarea v-model="task.label"
                                              class="text-primary-content rounded-md shadow-sm w-full"/>
                                </div>
                                <div :class="task.editing?'':'hidden'">
                                    <div class="text-secondary text-xs">Description :</div>
                                    <textarea v-model="task.description"
                                              class="text-primary-content rounded-md shadow-sm w-full"/>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
