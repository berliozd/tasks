<script setup>
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import CompleteTaskModal from "@/Pages/Tasks/Partials/CompleteTaskModal.vue";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import InProgressIcon from "@/Components/InProgressIcon.vue";
import axios from "axios";
import ReScheduleModal from "@/Pages/Tasks/Partials/ReScheduleModal.vue";

const props = defineProps({task: Object});
const emits = defineEmits(['deleted', 'changed']);

const taskIsLate = (task) => {
    if (task.scheduled_at === undefined) return false;
    if (task.completed_at !== null) return false;
    return format(new Date(task.scheduled_at), 'yyyy-MM-dd') !== format(new Date(), 'yyyy-MM-dd');
}

const formatDateTime = (date) => {
    if (date === undefined) {
        return '';
    }
    return format(
        date,
        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm:ss' : 'dd/MM/yyyy HH:mm:ss'
    )
}
const updateProgression = (task) => {
    if (taskHasActiveProgression(task)) {
        // stop progression
        axios.post(route('task-progression.stop', task.id)).then(() => {
            emits('changed')
        })
    } else {
        // start progression
        axios.post(route('task-progression.start', task.id)).then(() => {
            emits('changed')
        })
    }
}

const taskHasActiveProgression = (task) => {
    if (task.completed_at !== null) return false
    if (task.progressions === undefined) return false
    for (let progression of task.progressions) {
        if (progression.end_at === null) return true
    }
}

const deleteTask = (task) => {
    axios.delete(route('tasks.delete', task.id)).then(() => {
        console.log('deleting task ' + task.id);
        emits('deleted')
    })
}

const rescheduleTomorrow = async (task) => {
    const newDate = new Date();
    newDate.setDate(newDate.getDate() + 1);
    await axios.patch(route('tasks.update', task.id), {scheduled_at: newDate})
    emits('changed')
}

</script>

<template>
    <div class="border border-gray-400 m-4"
         :class="task.completed_at?'bg-gray-300':'bg-gray-100'">
        <div class="p-2" :class="taskIsLate(task)?'border-t-2 border-red-400':''">
            <div class="flex justify-between gap-2">
                <div class="flex">
                    <CompleteTaskModal :task="task" @changed="emits('changed')"/>
                </div>
                <div class="w-full">
                    <div @click="task.editing = !task.editing" class="cursor-pointer w-full">
                        {{ task.label }}
                    </div>
                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400 underline">
                        Completed on:{{ formatDateTime(task.completed_at) }}
                    </div>
                </div>
                <ReScheduleModal @reschedule="rescheduleTomorrow(task)"/>
                <InProgressIcon :in-progress="taskHasActiveProgression(task)" :enabled="task.completed_at === null"
                                @click="updateProgression(task)"/>
                <DeleteModal @deleted="deleteTask(task)" label="Are you sure you want to delete this task?"/>
            </div>
            <div :class="task.editing?'':'hidden'" class="m-2">
                <div>
                    <div class="text-xs">Label :</div>
                    <textarea v-model="task.label"
                              class="rounded-md shadow-sm w-full"
                              :disabled="task.completed_at!==null"
                              maxlength="255"/>
                </div>
                <div>
                    <div class="text-xs">Description :</div>
                    <textarea v-model="task.description"
                              class="rounded-md shadow-sm w-full h-48"
                              :disabled="task.completed_at!==null"
                              maxlength="5000"/>
                </div>
                <div class="flex justify-between">
                    <div class="text-xs text-gray-400 underline">
                        Scheduled on:{{ formatDateTime(task.scheduled_at) }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
