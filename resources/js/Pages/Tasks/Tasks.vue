<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {computed, reactive, ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {usePage} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";
import Flags from "@/Pages/Tasks/Partials/Flags.vue";
import FlagMultiSelect from "@/Components/FlagMultiSelect.vue";

const newTaskLabel = ref('');
const newTaskRecurrenceId = ref(null);
const newTaskFlagIds = ref([]);
const props = defineProps({todayTasks: Array, lateTasks: Array, completedTodayTasks: Array});
const lastSaved = ref(new Date());
const reactiveTasks = reactive({});
const belowList = ref(null);
const progress = ref(null);
let storedReactiveTasks = null;
let watchActive = false;

const selectedFlagIds = ref([]);

const scrollTo = (view) => {
    view.value?.scrollIntoView({behavior: 'smooth'})
}

const updateTask = (task) => {
    // Only send editable fields; avoid server-managed fields (e.g. updated_at) to prevent save loops.
    axios.patch(route('tasks.update', task.id), {
        label: task.label,
        description: task.description,
        completed_at: task.completed_at,
        scheduled_at: task.scheduled_at,
        recurrence_id: task.recurrence_id,
    }).then(
        (response) => {
            // Keep local state in sync so "updated at" refreshes without reloading.
            if (response?.data) {
                task.updated_at = response.data.updated_at;
                task.scheduled_at = response.data.scheduled_at;
                task.completed_at = response.data.completed_at;
                task.recurrence_id = response.data.recurrence_id;
                task.label = response.data.label;
                task.description = response.data.description;
            }
            useStore().setSaved('Saved!');
            lastSaved.value = new Date();
        }
    )
}
const debouncedSave = debounce(updateTask, 1500);

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

const cleanTask = (storedTask) => {
    storedTask = JSON.parse(JSON.stringify(storedTask));
    // Compare only fields we actually save from this page. Exclude server-managed timestamps to avoid loops.
    const {
        id,
        label,
        description,
        completed_at,
        scheduled_at,
        recurrence_id,
    } = storedTask;
    return {id, label, description, completed_at, scheduled_at, recurrence_id};
}

const addTask = () => {
    if (newTaskLabel.value === '') return;
    watchActive = false;
    reactiveTasks.value.push({
        label: newTaskLabel.value,
        'completed_at': null,
        recurrence_id: newTaskRecurrenceId.value,
        flags: (allFlags.value ?? []).filter(f => (newTaskFlagIds.value ?? []).includes(f.id)),
    });
    axios.post(route('tasks.store'), {
        label: newTaskLabel.value,
        recurrence_id: newTaskRecurrenceId.value,
        flag_ids: newTaskFlagIds.value,
    })
        .then((response) => {
                reactiveTasks.value[reactiveTasks.value.length - 1] = response.data;
            }
        ).then(() => {
            newTaskLabel.value = '';
            newTaskRecurrenceId.value = null;
            newTaskFlagIds.value = [];
            watchActive = true;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
            calculateProgress()
            scrollTo(belowList)
        }
    )
}

const draggingTaskId = ref(null);

const onDragStartTask = (task) => {
    draggingTaskId.value = task.id;
}

const onDropTask = (targetTask) => {
    const fromId = draggingTaskId.value;
    draggingTaskId.value = null;
    if (fromId === null || fromId === targetTask.id) return;

    const list = reactiveTasks.value;
    const fromIndex = list.findIndex(t => t.id === fromId);
    const toIndex = list.findIndex(t => t.id === targetTask.id);
    if (fromIndex === -1 || toIndex === -1) return;

    const [moved] = list.splice(fromIndex, 1);
    list.splice(toIndex, 0, moved);

    axios.post(route('tasks.reorder'), {ids: list.map(t => t.id).filter(Boolean)});
}

const setActiveTask = (task) => {
    (reactiveTasks.value ?? []).forEach(t => {
        t.editing = t.id === task.id ? !t.editing : false;
    });
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

const pageFlags = computed(() => {
    const flagsById = new Set();
    const tasks = reactiveTasks.value ?? [];
    tasks.forEach(task => {
        (task.flags ?? []).forEach(flag => flagsById.add(flag.id));
    });
    return (allFlags.value ?? []).filter(flag => flagsById.has(flag.id));
});

const allRecurrences = ref([]);
const getAllRecurrences = () => {
    axios.get(route('recurrences.index'))
        .then(response => {
            allRecurrences.value = response.data;
        });
}
getAllRecurrences();

const filteredTasks = computed(() => {
    const tasks = reactiveTasks.value ?? [];
    if (!selectedFlagIds.value.length) return tasks;
    return tasks.filter(task => {
        if (!task.flags || !task.flags.length) return false;
        // Match ANY selected flag.
        return task.flags.some(flag => selectedFlagIds.value.includes(flag.id));
    });
});

const updateSelectedFlags = (e) => {
    selectedFlagIds.value = e.value;
};

const formatExportDate = (date) => {
    if (!date) return '';
    return format(
        date,
        usePage().props.appLocale === 'en' ? 'MM/dd/yyyy HH:mm' : 'dd/MM/yyyy HH:mm'
    )
}

const exportText = computed(() => {
    return filteredTasks.value.map(task => {
        let line = `[${task.completed_at ? 'x' : ' '}] ${task.label}`;
        const flagNames = (task.flags ?? []).map(f => f.name).filter(Boolean);
        if (flagNames.length) line += ` (${flagNames.join(', ')})`;
        if (task.completed_at) {
            line += ` - completed ${formatExportDate(task.completed_at)}`;
        } else if (task.scheduled_at) {
            line += ` - scheduled ${formatExportDate(task.scheduled_at)}`;
        }
        return line;
    }).join('\n');
});

const exportTasks = async () => {
    if (!filteredTasks.value.length) return;
    const text = exportText.value;
    try {
        await navigator.clipboard.writeText(text);
        useStore().setSaved('Copied to clipboard!');
    } catch (e) {
        // Clipboard API unavailable (e.g. insecure context) — fall back to a file download.
        const blob = new Blob([text], {type: 'text/plain'});
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'tasks.txt';
        link.click();
        URL.revokeObjectURL(url);
    }
}

</script>

<template>
    <AppLayout title="Tasks">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight text-slate-900">Tasks</h2>
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
            <div class="surface-card mb-6 overflow-visible">
                <div class="p-4 flex flex-wrap justify-between items-center gap-2">
                    <input type="text" v-model="newTaskLabel" placeholder="New task label"
                           class="w-full md:flex-1 rounded-lg border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition"
                           @keydown.enter="addTask">
                    <select v-model="newTaskRecurrenceId"
                            class="h-10 rounded-lg border-gray-300 text-sm focus:border-brand-accent focus:ring-brand-accent transition">
                        <option :value="null">No recurrence</option>
                        <option v-for="recurrence in allRecurrences" :key="recurrence.id" :value="recurrence.id">
                            {{ recurrence.label }}
                        </option>
                    </select>
                    <FlagMultiSelect v-model="newTaskFlagIds" :all-flags="allFlags"/>
                    <SaveButton @click="addTask"/>
                </div>
            </div>
            <div class="surface-card my-6 px-4"
                 v-if="!isNaN(progress) && progress > 0">
                <progress class="my-4 progress progress-primary w-full" :value="progress" max="100"/>
            </div>

            <Flags :all-flags="pageFlags" @filter="updateSelectedFlags"/>

            <div class="flex items-center justify-between gap-2 px-1 mb-2">
                <div class="text-xs font-medium text-gray-500">{{ filteredTasks.length }} task(s)</div>
                <button type="button" @click="exportTasks" :disabled="!filteredTasks.length"
                        class="btn btn-ghost btn-xs gap-1 normal-case disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-copy">
                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                    </svg>
                    Export as text
                </button>
            </div>

            <div class="surface-card mb-2 overflow-hidden">
                <div class="flex flex-col gap-2 p-2">
                    <div v-for="task in filteredTasks" :key="task.id" class="flex items-start gap-1"
                         @dragover.prevent @drop="onDropTask(task)">
                        <div draggable="true" @dragstart="onDragStartTask(task)" title="Drag to reorder"
                             class="shrink-0 pt-3 cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="lucide lucide-grip-vertical">
                                <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
                                <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <Task :task="task" @deleted="refreshTasks()" @changed="refreshTasks()"
                                  @toggle-editing="setActiveTask" :all-flags="allFlags"
                                  :all-recurrences="allRecurrences"/>
                        </div>
                    </div>
                    <div v-if="!filteredTasks.length" class="px-4 py-10 text-center text-sm text-gray-400">
                        No tasks here. Add one above to get started.
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
    </AppLayout>
</template>
