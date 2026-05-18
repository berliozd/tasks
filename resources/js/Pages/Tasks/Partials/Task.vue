<script setup>
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import CompleteTaskModal from "@/Pages/Tasks/Partials/CompleteTaskModal.vue";
import {format} from "date-fns";
import {Link, usePage} from "@inertiajs/vue3";
import InProgressIcon from "@/Components/InProgressIcon.vue";
import FlagSwatches from "@/Components/FlagSwatches.vue";
import axios from "axios";
import ReScheduleModal from "@/Pages/Tasks/Partials/ReScheduleModal.vue";
import {ref} from "vue";

const props = defineProps({task: Object, allFlags: Array, allRecurrences: Array});
const emits = defineEmits(['deleted', 'changed']);

const historyLoading = ref(false);
const historyItems = ref([]);
const upcomingItem = ref(null);

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

const taskHasFlag = (task, flag) => {
    if (task.flags === undefined) {
        return false;
    }
    return task.flags.some(f => f.id === flag.id);
}

const addFlag = (task, flag) => {
    axios.post(route('tasks.add.flag', {taskId: task.id, flagId: flag.id})).then(() => {
        // Update locally to avoid reloading the whole list (which collapses the editor).
        task.flags = task.flags ?? [];
        if (!task.flags.some(f => f.id === flag.id)) {
            task.flags.push(flag);
        }
    })
}

const deleteFlag = (task, flag) => {
    axios.post(route('tasks.delete.flag', {taskId: task.id, flagId: flag.id})).then(() => {
        // Update locally to avoid reloading the whole list (which collapses the editor).
        task.flags = (task.flags ?? []).filter(f => f.id !== flag.id);
    })
}

const recurrenceLabel = (task) => {
    if (!props.allRecurrences || !task.recurrence_id) return '';
    return props.allRecurrences.find(r => r.id === task.recurrence_id)?.label ?? '';
}

const loadHistory = async () => {
    if (!props.task?.id) return;
    historyLoading.value = true;
    try {
        const response = await axios.get(route('tasks.history', props.task.id));
        historyItems.value = response.data?.history ?? [];
        upcomingItem.value = response.data?.upcoming ?? null;
    } finally {
        historyLoading.value = false;
    }
}

const toggleEditing = async () => {
    props.task.editing = !props.task.editing;
    if (props.task.editing) {
        await loadHistory();
    }
}
</script>

<template>
    <div class="px-4 py-3" :class="task.completed_at ? 'bg-gray-50' : 'bg-white'">
        <div class="rounded-lg ring-1 px-3 py-2 transition"
             :class="[
                taskIsLate(task) ? 'ring-red-200 border-l-4 border-red-400' : 'ring-gray-200 hover:ring-gray-300',
                task.completed_at ? 'bg-gray-50' : 'bg-white'
             ]">
            <div class="flex justify-between gap-2">
                <div class="flex">
                    <CompleteTaskModal :task="task" @changed="emits('changed')"/>
                </div>
                <div class="w-full">
                    <div @click="toggleEditing" class="cursor-pointer w-full">
                        <span :class="task.completed_at ? 'text-gray-500 line-through' : 'text-gray-900'">
                            {{ task.label }}
                        </span>
                    </div>
                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400 underline">
                        Completed on:{{ formatDateTime(task.completed_at) }}
                    </div>
                    <div v-if="task.recurrence_id" class="text-xs text-gray-500">
                        Repeats: {{ recurrenceLabel(task) }}
                    </div>
                </div>
                <div class="flex gap-2">
                    <FlagSwatches :flags="task.flags" size-class="w-5 h-5" gap-class="gap-2"/>
                </div>
                <ReScheduleModal @reschedule="rescheduleTomorrow(task)"/>
                <InProgressIcon :in-progress="taskHasActiveProgression(task)" :enabled="task.completed_at === null"
                                @click="updateProgression(task)"/>
                <DeleteModal @deleted="deleteTask(task)" label="Are you sure you want to delete this task?"/>
            </div>
            <div :class="task.editing?'':'hidden'" class="mt-3">
                <div>
                    <div class="text-xs">Label :</div>
                    <textarea v-model="task.label"
                              class="rounded-md shadow-sm w-full border-gray-300 focus:border-gray-400 focus:ring-gray-400"
                              :disabled="task.completed_at!==null"
                              maxlength="255"/>
                </div>
                <div>
                    <div class="text-xs">Description :</div>
                    <textarea v-model="task.description"
                              class="rounded-md shadow-sm w-full h-48 border-gray-300 focus:border-gray-400 focus:ring-gray-400"
                              :disabled="task.completed_at!==null"
                              maxlength="5000"/>
                </div>
                <div class="flex gap-2 my-2">
                    <div class="flex gap-2 items-center" v-if="allFlags.length > 0">
                        <div v-for="flag in allFlags" class="flex"
                             :class="task.flags?.some(f => f.id === flag.id)?'border-2 border-dashed border-gray-700':''"
                             @click="taskHasFlag(task, flag)?deleteFlag(task, flag):addFlag(task, flag)">
                            <div class="w-6 h-6 border border-gray-700 tooltip tooltip-bottom cursor-pointer"
                                 :style="{ 'background-color': flag.color  }" :data-tip="flag.name "/>
                        </div>
                        <Link :href="route('flags')"
                              class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update your flags
                        </Link>
                    </div>
                    <div v-else>
                        <Link :href="route('flags')"
                              class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create your flags
                        </Link>
                    </div>
                </div>
                <div class="my-2">
                    <div class="text-xs">Recurrence :</div>
                    <select v-model="task.recurrence_id"
                            class="rounded-md shadow-sm w-full border-gray-300 focus:border-gray-400 focus:ring-gray-400"
                            :disabled="task.completed_at!==null">
                        <option :value="null">No recurrence</option>
                        <option v-for="recurrence in allRecurrences" :key="recurrence.id" :value="recurrence.id">
                            {{ recurrence.label }}
                        </option>
                    </select>
                </div>
                <div v-if="upcomingItem || task.parent_task_id" class="my-3 border-t border-gray-300 pt-2">
                    <div v-if="upcomingItem" class="mb-3">
                        <div class="text-xs text-gray-600">Upcoming occurrence :</div>
                        <div class="mt-1 text-xs text-gray-700 flex justify-between gap-3">
                            <div class="truncate">{{ upcomingItem.label }}</div>
                            <div class="shrink-0 text-gray-500">
                                {{ upcomingItem.scheduled_at ? formatDateTime(upcomingItem.scheduled_at) : '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-600">Previous occurrences :</div>
                    <div v-if="historyLoading" class="text-xs text-gray-400">Loading...</div>
                    <div v-else-if="!historyItems.length" class="text-xs text-gray-400">None</div>
                    <div v-else class="mt-1 space-y-1">
                        <div v-for="h in historyItems" :key="h.id" class="text-xs text-gray-700 flex justify-between gap-3">
                            <div class="truncate">{{ h.label }}</div>
                            <div class="shrink-0 text-gray-500">
                                {{ h.completed_at ? formatDateTime(h.completed_at) : '' }}
                            </div>
                        </div>
                    </div>
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
