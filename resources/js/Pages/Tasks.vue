<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EditButton from "@/Components/EditButton.vue";
import CheckButton from "@/Components/CheckButton.vue";
import axios from 'axios';
import {watch} from "vue";
import debounce from "lodash/debounce";
import DeleteButton from "@/Components/DeleteButton.vue";


const props = defineProps({tasks: Array})
const toggleDescription = (task) => {
    task.deployed = !task.deployed;
}
const editTask = (task) => {
    task.editing = !task.editing;
}
const toggleCompleted = (task, index) => {
    if (task.completed_at === null) {
        task.completed_at = new Date();
    } else {
        task.completed_at = null;
    }
}

const updateTask = (task) => {
    axios.patch(route('tasks.update', task.id), task)
        .then(response => {
            // console.log(response);
        })
}

const debouncedSave = debounce(updateTask, 300);

let storedTasks = JSON.parse(JSON.stringify(props.tasks));
watch(props.tasks, () => {
    props.tasks.forEach(task => {
        let storedTask = cleanTask(JSON.parse(JSON.stringify(storedTasks.find(storedTask => storedTask.id === task.id))));
        let taskCopy = cleanTask(JSON.parse(JSON.stringify(task)));
        if (JSON.stringify(storedTask) !== JSON.stringify(taskCopy)) {
            debouncedSave(task)
        }
    });
    storedTasks = JSON.parse(JSON.stringify(props.tasks));
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
            // console.log(response);
        })
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
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div v-for="(task, index) in tasks" class="border m-4 p-2"
                         :class="task.completed_at?'bg-gray-200':''" :key="index">
                        <div class="flex justify-between gap-2">
                            <div class="flex">
                                <div>
                                    <CheckButton :checked="task.completed_at!==null"
                                                 @click="toggleCompleted(task, index)"/>
                                </div>
                            </div>
                            <div class="w-full">
                                <div @click="toggleDescription(task)" class="cursor-pointer w-full">
                                    {{ task.label }}
                                </div>
                                <div :class="task.deployed || task.editing?'':'hidden'" class="text-xs text-gray-500">
                                    <div :class="task.editing?'hidden':''">{{ task.description }}</div>
                                </div>
                                <div v-if="task.completed_at !== null" class="text-xs text-gray-500 underline">
                                    Completed on:{{ task.completed_at }}
                                </div>
                            </div>
                            <div class="flex">
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
                                <div class="text-secondary text-xs">Label : </div>
                                <textarea v-model="task.label"
                                          class="text-primary-content rounded-md shadow-sm w-full"/>
                            </div>
                            <div :class="task.editing?'':'hidden'">
                                <div class="text-secondary text-xs">Description : </div>
                                <textarea v-model="task.description"
                                          class="text-primary-content rounded-md shadow-sm w-full"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
