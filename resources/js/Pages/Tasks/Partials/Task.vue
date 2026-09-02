<script setup>
import DeleteModal from "@/Pages/Tasks/Partials/DeleteModal.vue";
import CompleteTaskModal from "@/Pages/Tasks/Partials/CompleteTaskModal.vue";
import {format} from "date-fns";
import {Link, usePage} from "@inertiajs/vue3";
import InProgressIcon from "@/Components/InProgressIcon.vue";
import FlagSwatches from "@/Components/FlagSwatches.vue";
import FlagMultiSelect from "@/Components/FlagMultiSelect.vue";
import axios from "axios";
import ReScheduleModal from "@/Pages/Tasks/Partials/ReScheduleModal.vue";
import {ref, watch} from "vue";

const props = defineProps({task: Object, allFlags: Array, allRecurrences: Array});
const emits = defineEmits(['deleted', 'changed', 'toggle-editing']);

const historyLoading = ref(false);
const historyItems = ref([]);
const upcomingItem = ref(null);
const editFlagIds = ref([]);
let suppressFlagDiff = false;

const newLinkUrl = ref('');
const newLinkLabel = ref('');
const addingLink = ref(false);
const linkError = ref('');
const deletingLinkIds = ref(new Set());

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
    axios.post(route('tasks.add.flag', {taskId: task.id, flagId: flag.id})).then((response) => {
        // Update locally to avoid reloading the whole list (which collapses the editor).
        if (response?.data) {
            task.flags = response.data.flags ?? task.flags ?? [];
            task.updated_at = response.data.updated_at ?? task.updated_at;
        } else {
            task.flags = task.flags ?? [];
            if (!task.flags.some(f => f.id === flag.id)) {
                task.flags.push(flag);
            }
        }
    });
}

const deleteFlag = (task, flag) => {
    axios.post(route('tasks.delete.flag', {taskId: task.id, flagId: flag.id})).then((response) => {
        // Update locally to avoid reloading the whole list (which collapses the editor).
        if (response?.data) {
            task.flags = response.data.flags ?? (task.flags ?? []).filter(f => f.id !== flag.id);
            task.updated_at = response.data.updated_at ?? task.updated_at;
        } else {
            task.flags = (task.flags ?? []).filter(f => f.id !== flag.id);
        }
    });
}

const addLink = (task) => {
    if (!newLinkUrl.value.trim()) return;
    addingLink.value = true;
    linkError.value = '';
    axios.post(route('tasks.links.add', task.id), {url: newLinkUrl.value, label: newLinkLabel.value})
        .then((response) => {
            task.links = response.data.links ?? task.links ?? [];
            newLinkUrl.value = '';
            newLinkLabel.value = '';
        })
        .catch((error) => {
            linkError.value = error.response?.data?.message ?? 'Could not add link';
        })
        .finally(() => addingLink.value = false);
}

const deleteLink = (task, link) => {
    deletingLinkIds.value = new Set(deletingLinkIds.value).add(link.id);
    axios.delete(route('tasks.links.delete', {taskId: task.id, linkId: link.id}))
        .then((response) => {
            task.links = response.data.links ?? (task.links ?? []).filter(l => l.id !== link.id);
        })
        .finally(() => {
            const next = new Set(deletingLinkIds.value);
            next.delete(link.id);
            deletingLinkIds.value = next;
        });
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

const toggleEditing = () => {
    emits('toggle-editing', props.task);
}

watch(() => props.task.editing, async (isEditing) => {
    if (!isEditing) return;
    suppressFlagDiff = true;
    editFlagIds.value = (props.task.flags ?? []).map(f => f.id);
    newLinkUrl.value = '';
    newLinkLabel.value = '';
    linkError.value = '';
    await loadHistory();
})

watch(editFlagIds, (next, prev) => {
    if (suppressFlagDiff) {
        suppressFlagDiff = false;
        return;
    }
    if (!props.task?.editing) return;
    const nextSet = new Set(next ?? []);
    const prevSet = new Set(prev ?? []);

    // Added
    for (const id of nextSet) {
        if (!prevSet.has(id)) {
            const flag = (props.allFlags ?? []).find(f => f.id === id);
            if (flag) addFlag(props.task, flag);
        }
    }
    // Removed
    for (const id of prevSet) {
        if (!nextSet.has(id)) {
            const flag = (props.allFlags ?? []).find(f => f.id === id) || {id};
            deleteFlag(props.task, flag);
        }
    }
}, {deep: true});
</script>

<template>
    <div class="rounded-lg ring-1 shadow-soft px-3 py-2.5 transition hover:shadow-card-hover"
         :class="[
            taskIsLate(task) ? 'ring-red-200 border-l-4 border-red-400 bg-red-50/40' : 'ring-slate-900/[0.08] hover:ring-slate-900/[0.16]',
            task.completed_at ? 'bg-gray-50/70' : 'bg-white'
         ]">
            <div class="flex items-start gap-2 sm:grid sm:grid-cols-[auto_1fr_10rem_6.5rem] sm:items-center">
                <div class="flex items-center pt-0.5 sm:pt-0">
                    <CompleteTaskModal :task="task" @changed="emits('changed')"/>
                </div>
                <div class="min-w-0 w-full">
                    <div @click="toggleEditing" class="cursor-pointer w-full">
                        <span :class="task.completed_at ? 'text-gray-400 line-through' : 'text-gray-900'">
                            {{ task.label }}
                        </span>
                    </div>
                    <div v-if="task.completed_at !== null" class="text-xs text-gray-400">
                        Completed on {{ formatDateTime(task.completed_at) }}
                    </div>
                    <div v-if="task.recurrence_id" class="text-xs text-brand-accent-dark">
                        Repeats: {{ recurrenceLabel(task) }}
                    </div>
                </div>
                <div class="ml-auto flex items-center gap-2 sm:ml-0 sm:justify-end sm:min-h-10 sm:w-40">
                    <FlagSwatches :flags="task.flags" size-class="w-4 h-4" gap-class="gap-1.5"/>
                </div>
                <div class="flex items-center justify-end gap-1 sm:min-h-10 sm:w-[6.5rem]">
                    <div class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                        <ReScheduleModal v-if="task.completed_at === null" @reschedule="rescheduleTomorrow(task)"/>
                    </div>
                    <div class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                        <InProgressIcon v-if="task.completed_at === null"
                                        :in-progress="taskHasActiveProgression(task)" :enabled="true"
                                        @click="updateProgression(task)"/>
                    </div>
                    <div class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                        <DeleteModal @deleted="deleteTask(task)" label="Are you sure you want to delete this task?"/>
                    </div>
                </div>
            </div>
            <div :class="task.editing?'':'hidden'" class="mt-3 pt-3 border-t border-gray-100">
                <div>
                    <div class="text-xs font-medium text-gray-500 mb-1">Label</div>
                    <textarea v-model="task.label"
                              class="rounded-lg shadow-sm w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                              :disabled="task.completed_at!==null"
                              maxlength="255"/>
                </div>
                <div class="mt-3">
                    <div class="text-xs font-medium text-gray-500 mb-1">Description</div>
                    <textarea v-model="task.description"
                              class="rounded-lg shadow-sm w-full h-48 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                              :disabled="task.completed_at!==null"
                              maxlength="5000"/>
                </div>
                <div class="my-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl bg-brand-surface ring-1 ring-slate-900/[0.05] p-3">
                        <div class="text-xs font-medium text-gray-500 mb-2">Flags</div>
                        <template v-if="allFlags.length > 0">
                            <div class="flex items-center justify-between gap-2">
                                <FlagMultiSelect v-model="editFlagIds" :all-flags="allFlags" :disabled="task.completed_at !== null"/>
                                <Link :href="route('flags')"
                                      class="text-sm text-brand-accent-dark hover:text-brand-accent underline">
                                    Update your flags
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <Link :href="route('flags')"
                                  class="text-sm text-brand-accent-dark hover:text-brand-accent underline">
                                Create flags
                            </Link>
                        </template>
                    </div>

                    <div class="rounded-xl bg-brand-surface ring-1 ring-slate-900/[0.05] p-3">
                        <div class="text-xs font-medium text-gray-500 mb-2">Recurrence</div>
                        <select v-model="task.recurrence_id"
                                class="h-10 rounded-lg shadow-sm w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition bg-white"
                                :disabled="task.completed_at!==null">
                            <option :value="null">No recurrence</option>
                            <option v-for="recurrence in allRecurrences" :key="recurrence.id" :value="recurrence.id">
                                {{ recurrence.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="my-3 rounded-xl bg-brand-surface ring-1 ring-slate-900/[0.05] p-3">
                    <div class="text-xs font-medium text-gray-500 mb-2">Links</div>
                    <div v-if="(task.links ?? []).length" class="flex flex-col gap-1 mb-2">
                        <div v-for="link in task.links" :key="link.id" class="flex items-center gap-2">
                            <a :href="link.url" target="_blank" rel="noopener"
                               class="min-w-0 flex-1 truncate text-sm text-brand-accent-dark hover:text-brand-accent hover:underline">
                                {{ link.label || link.url }}
                            </a>
                            <button v-if="task.completed_at === null" type="button" @click="deleteLink(task, link)"
                                    :disabled="deletingLinkIds.has(link.id)"
                                    class="shrink-0 w-6 h-6 flex items-center justify-center rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                    title="Remove link">
                                ×
                            </button>
                        </div>
                    </div>
                    <div v-if="task.completed_at === null" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" v-model="newLinkUrl" placeholder="URL" @keydown.enter="addLink(task)"
                               class="h-9 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                        <input type="text" v-model="newLinkLabel" placeholder="Label (optional)" @keydown.enter="addLink(task)"
                               class="h-9 px-2 rounded-lg w-full sm:flex-1 border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm">
                        <button type="button" @click="addLink(task)" :disabled="addingLink || !newLinkUrl.trim()"
                                class="shrink-0 inline-flex items-center px-3 py-2 bg-brand-navy border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-soft hover:bg-brand-navy-light disabled:opacity-50 transition">
                            {{ addingLink ? 'Adding…' : 'Add' }}
                        </button>
                    </div>
                    <div v-if="linkError" class="mt-1 text-xs text-red-600">{{ linkError }}</div>
                </div>
                <div v-if="upcomingItem || task.parent_task_id" class="my-3 border-t border-gray-100 pt-3">
                    <div v-if="upcomingItem" class="mb-3">
                        <div class="text-xs font-medium text-gray-500">Upcoming occurrence</div>
                        <div class="mt-1 text-xs text-gray-700 flex justify-between gap-3">
                            <div class="truncate">{{ upcomingItem.label }}</div>
                            <div class="shrink-0 text-gray-500">
                                {{ upcomingItem.scheduled_at ? formatDateTime(upcomingItem.scheduled_at) : '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-xs font-medium text-gray-500">Previous occurrences</div>
                    <div v-if="historyLoading" class="text-xs text-gray-400">Loading...</div>
                    <div v-else-if="!historyItems.length" class="text-xs text-gray-400">None</div>
                    <div v-else class="mt-1 space-y-1">
                        <div v-for="h in historyItems" :key="h.id"
                             class="text-xs text-gray-700 flex justify-between gap-3">
                            <div class="truncate">{{ h.label }}</div>
                            <div class="shrink-0 text-gray-500">
                                {{ h.completed_at ? formatDateTime(h.completed_at) : '' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-6">
                    <div class="flex flex-col gap-1 min-w-0">
                        <div class="text-xs text-gray-400">
                            Scheduled on {{ formatDateTime(task.scheduled_at) }}
                        </div>
                    </div>
                    <div class="shrink-0 text-xs text-gray-400 text-right whitespace-nowrap">
                        Updated on {{ formatDateTime(task.updated_at) }}
                    </div>
                </div>
            </div>
    </div>
</template>
