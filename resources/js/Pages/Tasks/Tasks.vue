<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SaveButton from "@/Components/SaveButton.vue";
import axios from 'axios';
import {computed, reactive, ref, watch} from "vue";
import debounce from "lodash/debounce";
import {format} from "date-fns";
import {Link, usePage} from "@inertiajs/vue3";
import {useStore} from "@/Composables/store.js";
import SavedLabel from "@/Components/SavedLabel.vue";
import DebuggingTasks from "@/Pages/Tasks/Partials/DebuggingTasks.vue";
import Task from "@/Pages/Tasks/Partials/Task.vue";
import Flags from "@/Pages/Tasks/Partials/Flags.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const newTaskLabel = ref('');
const newTaskDescription = ref('');
const newTaskRecurrenceId = ref(null);
const newTaskFlagIds = ref([]);
const showAddTaskModal = ref(false);

const openAddTaskModal = () => {
    newTaskLabel.value = '';
    newTaskDescription.value = '';
    newTaskRecurrenceId.value = null;
    newTaskFlagIds.value = [];
    showAddTaskModal.value = true;
}

const closeAddTaskModal = () => {
    showAddTaskModal.value = false;
}

const toggleNewTaskFlag = (flagId) => {
    newTaskFlagIds.value = newTaskFlagIds.value.includes(flagId)
        ? newTaskFlagIds.value.filter(id => id !== flagId)
        : [...newTaskFlagIds.value, flagId];
}
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
        description: newTaskDescription.value,
        'completed_at': null,
        recurrence_id: newTaskRecurrenceId.value,
        flags: (allFlags.value ?? []).filter(f => (newTaskFlagIds.value ?? []).includes(f.id)),
    });
    axios.post(route('tasks.store'), {
        label: newTaskLabel.value,
        description: newTaskDescription.value,
        recurrence_id: newTaskRecurrenceId.value,
        flag_ids: newTaskFlagIds.value,
    })
        .then((response) => {
                reactiveTasks.value[reactiveTasks.value.length - 1] = response.data;
            }
        ).then(() => {
            newTaskLabel.value = '';
            newTaskDescription.value = '';
            newTaskRecurrenceId.value = null;
            newTaskFlagIds.value = [];
            watchActive = true;
            storedReactiveTasks = JSON.parse(JSON.stringify(reactiveTasks.value));
            calculateProgress()
            showAddTaskModal.value = false;
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

const undoneFilteredTasks = computed(() => filteredTasks.value.filter(task => task.completed_at === null));

const exportText = computed(() => {
    return undoneFilteredTasks.value.map(task => {
        let block = `[ ] ${task.label}`;
        if (task.description) block += `\n${task.description}`;
        return block;
    }).join('\n\n');
});

const exportTasks = async () => {
    if (!undoneFilteredTasks.value.length) return;
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
            <div class="flex items-center gap-4">
                <h2 class="font-semibold text-xl leading-tight text-slate-900">Tasks</h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('future-tasks')" class="text-sm text-gray-500 hover:text-gray-700">
                        Future
                    </Link>
                    <Link :href="route('completed-tasks')" class="text-sm text-gray-500 hover:text-gray-700">
                        Completed
                    </Link>
                </div>
                <button type="button" @click="openAddTaskModal" title="Add a task"
                        class="ml-auto shrink-0 inline-flex items-center justify-center size-12 rounded-full bg-brand-accent text-white text-3xl leading-none hover:bg-brand-accent-dark active:scale-95 transition">
                    +
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div v-if="!usePage().props.auth.user.daily_report_enabled"
                 class="mb-4 rounded-xl bg-brand-accent/10 ring-1 ring-brand-accent/20 px-4 py-3 flex items-center gap-3 text-sm text-brand-accent-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="shrink-0">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <span class="flex-1">
                    You can get a daily email recap of your day — what you completed and what's scheduled
                    for tomorrow.
                    <Link :href="route('profile.show')" class="font-medium underline hover:no-underline">
                        Set it up
                    </Link>
                </span>
            </div>

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
            <div class="surface-card my-6 px-4"
                 v-if="!isNaN(progress) && progress > 0">
                <progress class="my-4 progress progress-primary w-full" :value="progress" max="100"/>
            </div>

            <Flags :all-flags="pageFlags" :known-flag-ids="(allFlags ?? []).map(f => f.id)" @filter="updateSelectedFlags"/>

            <div class="flex items-center justify-between gap-2 px-1 mb-2">
                <div class="text-xs font-medium text-gray-500">{{ filteredTasks.length }} task(s)</div>
                <button type="button" @click="exportTasks" :disabled="!undoneFilteredTasks.length"
                        class="btn btn-ghost btn-xs gap-1 normal-case disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="lucide lucide-copy">
                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                    </svg>
                    Export undone tasks as text
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
                        No tasks here. Use the + button to add one.
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

        <Modal :show="showAddTaskModal" @close="closeAddTaskModal">
            <div class="p-6 flex flex-col gap-4">
                <h3 class="text-lg font-medium text-gray-900">Add a task</h3>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Label</label>
                    <input type="text" v-model="newTaskLabel" placeholder="What needs doing?" autofocus
                           @keydown.enter="addTask"
                           class="h-10 px-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition">
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Description (optional)</label>
                    <textarea v-model="newTaskDescription" rows="3"
                              class="px-2 py-2 rounded-lg w-full border-gray-300 focus:border-brand-accent focus:ring-brand-accent transition text-sm"/>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Recurrence</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="newTaskRecurrenceId = null"
                                class="rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition"
                                :class="newTaskRecurrenceId === null
                                    ? 'bg-brand-navy text-white ring-brand-navy'
                                    : 'bg-white text-gray-700 ring-gray-200 hover:ring-gray-300 hover:bg-gray-50'">
                            No recurrence
                        </button>
                        <button v-for="recurrence in allRecurrences" :key="recurrence.id"
                                type="button" @click="newTaskRecurrenceId = recurrence.id"
                                class="rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition"
                                :class="newTaskRecurrenceId === recurrence.id
                                    ? 'bg-brand-navy text-white ring-brand-navy'
                                    : 'bg-white text-gray-700 ring-gray-200 hover:ring-gray-300 hover:bg-gray-50'">
                            {{ recurrence.label }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Flags</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="flag in allFlags" :key="flag.id"
                                type="button" @click="toggleNewTaskFlag(flag.id)"
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium ring-1 transition"
                                :class="newTaskFlagIds.includes(flag.id)
                                    ? 'bg-brand-navy text-white ring-brand-navy'
                                    : 'bg-white text-gray-700 ring-gray-200 hover:ring-gray-300 hover:bg-gray-50'">
                            <span class="inline-block w-2.5 h-2.5 rounded-full ring-1 ring-black/10"
                                  :style="{ backgroundColor: flag.color }"/>
                            <span class="truncate max-w-48">{{ flag.name }}</span>
                        </button>
                        <span v-if="!allFlags.length" class="text-xs text-gray-400">No flags yet.</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="closeAddTaskModal">Cancel</SecondaryButton>
                    <SaveButton @click="addTask"/>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
