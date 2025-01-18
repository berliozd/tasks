<script setup>
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import CompleteTaskModal from "@/Pages/Tasks/Partials/CompleteTaskModal.vue";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import InProgressIcon from "@/Components/InProgressIcon.vue";

const props = defineProps({task: Object});
const emits = defineEmits(['deleted']);

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
const toggleStartProgressAt = () => {
    if (props.task.start_progress_at === null) {
        props.task.start_progress_at = new Date();
    } else {
        props.task.start_progress_at = null;
    }
}
</script>

<template>
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
            <InProgressIcon :in-progress="task.start_progress_at!==null" :enabled="task.completed_at===null"
                            @click="toggleStartProgressAt()"/>
            <DeleteModal :task="task" @deleted="emits('deleted')"/>
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
            <div class="text-xs text-gray-400 underline">
                Scheduled on:{{ formatDateTime(task.scheduled_at) }}
            </div>
        </div>
    </div>
</template>
